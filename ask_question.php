<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ai_helpers.php';
require_once __DIR__ . '/includes/openai_moderation.php';

$user = require_login();
$pageTitle = 'Ask a question';
$errors = [];
$old = ['title' => '', 'body' => '', 'category_id' => ''];

$textLength = static function (string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
};

$categoryQuery = db()->query('SELECT id, name FROM categories ORDER BY name ASC');
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['title'] = trim((string)($_POST['title'] ?? ''));
    $old['body'] = trim((string)($_POST['body'] ?? ''));
    $old['category_id'] = (string)($_POST['category_id'] ?? '');

    $categoryId = filter_var($old['category_id'], FILTER_VALIDATE_INT);

    if ($textLength($old['title']) < 15 || $textLength($old['title']) > 220) {
        $errors[] = 'Your title must be between 15 and 220 characters.';
    }
    if ($textLength($old['body']) < 30 || $textLength($old['body']) > 10000) {
        $errors[] = 'Your question details must be between 30 and 10,000 characters.';
    }
    if (!$categoryId || $categoryId < 1) {
        $errors[] = 'Please select a valid category.';
    } else {
        $check = db()->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
        $check->execute([$categoryId]);
        if (!$check->fetchColumn()) {
            $errors[] = 'The selected category is no longer available.';
        }
    }

    if (!$errors) {
        $diagnosticId = cc_phase16_diagnostic_id();
        cc_phase16_log_diagnostic($diagnosticId, 'question_submission', [
            'stage' => 'submission_start',
            'request_reached_openai' => false,
            'database_insert_succeeded' => false,
        ]);

        // Safety scan always runs; quality check reuses the existing Phase 16 helper.
        $risk = cc_phase16_risk_scan($old['title'], $old['body'], null, $diagnosticId);
        $quality = cc_phase16_question_quality_for_submission($old['title'], $old['body']);
        $decision = cc_phase16_question_decision($risk, $quality);
        $canPersist = cc_phase16_question_can_persist($risk, $quality, $decision);

        if (!$canPersist) {
            $checksUnavailable = empty($risk['api_ok']) || empty($quality['api_ok']);

            if (empty($risk['api_ok'])) {
                $reasonCode = 'safety_unavailable';
            } elseif (!empty($risk['flagged'])) {
                $reasonCode = 'safety_flagged';
            } elseif (empty($quality['api_ok'])) {
                $reasonCode = 'quality_unavailable';
            } elseif (($quality['decision'] ?? '') === 'block'
                && (float)($quality['confidence'] ?? 0) >= 0.85) {
                $reasonCode = 'quality_blocked';
            } else {
                $reasonCode = 'quality_uncertain';
            }

            cc_phase16_log_submission_decision(
                'question',
                (int)$user['id'],
                $checksUnavailable ? 'retry' : 'blocked',
                $reasonCode,
                !$checksUnavailable
            );

            cc_phase16_log_diagnostic($diagnosticId, 'question_submission', [
                'stage' => 'checks_rejected_or_unavailable',
                'moderation_flagged' => !empty($risk['flagged']),
                'relevance_decision_valid' => !empty($quality['api_ok']),
                'database_insert_succeeded' => false,
                'error_category' => $reasonCode,
            ]);

            if ($checksUnavailable) {
                set_flash('error', 'Required safety and question-quality checks could not be completed. Your question was not saved. Please try again later. Reference: ' . $diagnosticId);
            } elseif (!empty($risk['flagged'])) {
                set_flash('error', 'Your question was not saved because it did not pass the safety check. Reference: ' . $diagnosticId);
            } elseif ($reasonCode === 'quality_blocked') {
                set_flash('error', 'This submission appears to be spam or not a genuine question. Please revise it.');
            } else {
                set_flash('error', 'We could not confidently verify this question. Please add more context and try again.');
            }
            redirect('ask_question.php');
        }

        try {
            db()->beginTransaction();

            $insert = db()->prepare(
                'INSERT INTO questions (user_id, category_id, title, body, status, moderation_status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                (int)$user['id'],
                (int)$categoryId,
                $old['title'],
                $old['body'],
                $decision['status'],
                $decision['moderation_status'],
            ]);

            $questionId = (int)db()->lastInsertId();

            cc_phase16_record_moderation(
                'question',
                $questionId,
                $risk,
                $quality,
                $decision['status']
            );

            db()->commit();

            cc_phase16_log_diagnostic($diagnosticId, 'question_submission', [
                'stage' => 'database_commit',
                'moderation_flagged' => false,
                'relevance_decision_valid' => true,
                'database_insert_succeeded' => true,
            ]);

            set_flash('success', 'Your question has been published after automated safety and quality checks.');
            redirect('question.php?id=' . $questionId);
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            $privateText = array_values(array_filter([$old['title'], $old['body']], static fn($v) => $v !== ''));
            $safeError = cc_openai_sanitize_error(
                str_replace($privateText, '[content redacted]', $exception->getMessage())
            );

            cc_phase16_log_diagnostic($diagnosticId, 'question_submission', [
                'stage' => 'database_or_transaction_failure',
                'database_insert_succeeded' => false,
                'sqlstate' => $exception instanceof PDOException ? (string)$exception->getCode() : '',
                'error_category' => get_class($exception),
                'error_message' => $safeError,
            ]);

            $errors[] = 'The question could not be published. It was not saved. Reference: ' . $diagnosticId;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/ask-question.css">

<main class="cc-ask-page">
    <header class="cc-ask-hero">
        <div>
            <span class="cc-eyebrow">COLLEGE CONNECT COMMUNITY</span>
            <h1>Ask a question</h1>
            <p>Share your doubt with students, alumni and faculty. Add enough detail so the community can help.</p>
        </div>
        <div class="cc-hero-mark" aria-hidden="true">?</div>
    </header>

    <div class="cc-ask-layout">
        <section class="cc-ask-main">
            <?php if ($errors): ?>
                <div class="cc-alert" role="alert">
                    <strong>We couldn't submit your question.</strong>
                    <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form id="askQuestionForm" class="cc-ask-card" method="post" action="ask_question.php" novalidate>
                <?= csrf_field() ?>
                <div class="cc-card-heading">
                    <div>
                        <span class="cc-step-label">QUESTION DETAILS</span>
                        <h2>What would you like to know?</h2>
                        <p>Use a clear title and explain the problem in your own words.</p>
                    </div>
                    <span class="cc-ai-badge">✦ AI checked</span>
                </div>

                <div class="cc-field">
                    <label for="questionTitle">Question title <b>*</b></label>
                    <input id="questionTitle" name="title" type="text" minlength="15" maxlength="220"
                        value="<?= e($old['title']) ?>"
                        placeholder="e.g. How can I prepare for the campus placement coding round?" required>
                    <div class="cc-meta"><span>Make it clear and specific.</span><span id="titleCounter">0/220</span></div>
                </div>

                <div class="cc-field">
                    <label for="questionCategory">Category <b>*</b></label>
                    <select id="questionCategory" name="category_id" required>
                        <option value="">Choose the most relevant category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int)$category['id'] ?>"
                                <?= (string)$category['id'] === $old['category_id'] ? 'selected' : '' ?>>
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Categories are loaded from your existing categories table.</small>
                </div>

                <div class="cc-field">
                    <div class="cc-label-row">
                        <label for="questionBody">Question details <b>*</b></label>
                        <small>Be respectful · Add context</small>
                    </div>
                    <div class="cc-editor">
                        <div class="cc-editor-tips">
                            <span>Describe the problem</span><span>Add what you tried</span><span>Include relevant details</span>
                        </div>
                        <textarea id="questionBody" name="body" rows="10" minlength="30" maxlength="10000"
                            placeholder="Explain your question in detail...

What are you trying to understand?
What have you tried so far?
Which subject, technology, department, or process is involved?" required><?= e($old['body']) ?></textarea>
                        <div class="cc-meta cc-editor-meta">
                            <span>Do not include passwords, OTPs, or private personal information.</span>
                            <span id="bodyCounter">0/10,000</span>
                        </div>
                    </div>
                </div>

                <div class="cc-form-actions">
                    <a href="questions.php">Cancel</a>
                    <button type="submit" id="submitQuestionButton"><span>Submit question</span><span>↗</span></button>
                </div>
                <p class="cc-submit-note">Your question is checked before it is saved. If the required checks fail or are unavailable, it will not be saved.</p>
            </form>
        </section>

        <aside class="cc-ask-sidebar">
            <article class="cc-side-card">
                <div class="cc-side-icon">✦</div>
                <h3>How to ask a great question</h3>
                <ol>
                    <li><b>01</b><div><strong>Use a specific title</strong><small>Summarize your exact doubt.</small></div></li>
                    <li><b>02</b><div><strong>Provide context</strong><small>Include the subject or situation.</small></div></li>
                    <li><b>03</b><div><strong>Show your effort</strong><small>Mention what you already tried.</small></div></li>
                    <li><b>04</b><div><strong>Keep it respectful</strong><small>Help everyone contribute.</small></div></li>
                </ol>
            </article>

            <article class="cc-side-card cc-ai-card">
                <div class="cc-ai-heading"><i></i><strong>AI moderation enabled</strong></div>
                <p>Questions are screened for safety and genuine question quality before publishing.</p>
                <span>✓ Safety screening</span>
                <span>✓ Question quality screening</span>
                <span>✓ Server-side API processing</span>
            </article>

            <article class="cc-side-card cc-help-card">
                <strong>Need help?</strong>
                <p>Browse existing discussions. Someone may already have answered your question.</p>
                <a href="questions.php">Explore questions →</a>
            </article>
        </aside>
    </div>
</main>

<script src="assets/ask-question.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
