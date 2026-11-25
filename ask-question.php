
<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ai_helpers.php';
require_once __DIR__ . '/includes/ai_validation.php';

$user = require_login();
$userId = (int)($user['user_id'] ?? $user['id'] ?? 0);

$pageTitle = 'Ask a question';
$errors = [];

$old = [
    'title' => '',
    'body' => '',
    'category_id' => ''
];

$textLength = static function (string $value): int {
    return function_exists('mb_strlen')
        ? mb_strlen($value)
        : strlen($value);
};

/* =====================================
   LOAD PREDEFINED CATEGORIES
===================================== */

$categories = [
    'Admissions',
    'Academics',
    'Placements',
    'Campus Life',
];

/* =====================================
   PROCESS QUESTION SUBMISSION
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $titleInput = $_POST['title'] ?? '';
    $bodyInput = $_POST['body'] ?? '';
    $categoryInput = $_POST['category_id'] ?? '';
    $old['title'] = is_string($titleInput) ? trim($titleInput) : '';
    $old['body'] = is_string($bodyInput) ? trim($bodyInput) : '';
    $old['category_id'] = is_string($categoryInput)
        ? trim($categoryInput)
        : '';

    $selectedCategory = '';

    // Validate title
    if (
        $textLength($old['title']) < 15 ||
        $textLength($old['title']) > 220
    ) {
        $errors[] = 'Your title must be between 15 and 220 characters.';
    }

    // Validate description
    if (
        $textLength($old['body']) < 30 ||
        $textLength($old['body']) > 10000
    ) {
        $errors[] = 'Your question details must be between 30 and 10,000 characters.';
    }

    // Validate category
    if (
        $old['category_id'] === '' ||
        !in_array($old['category_id'], $categories, true)
    ) {

        $errors[] = 'Please select a valid category.';

    } else {

        $selectedCategory = $old['category_id'];

    }

    if (!$errors) {
        $connection = db();

        try {
            if (!consumeModerationSubmissionToken(
                'question',
                0,
                $_POST['submission_token'] ?? null
            )) {
                throw new InvalidArgumentException(
                    'This question form has already been submitted or expired.'
                );
            }

            $validation = validateCollegeContent(
                $connection,
                $userId,
                'question',
                $selectedCategory,
                $old['body'],
                $old['title']
            );

            $needsGroqCheck = $validation['result'] === 'RELEVANT_AND_SUPPORTED' ||
                (
                    $validation['result'] === 'REVIEW_REQUIRED' &&
                    ($validation['api_status'] ?? 'ok') !== 'ok'
                );
            $moderation = $needsGroqCheck &&
                groqRateLimitAllowed($userId, 'question')
                ? moderateQuestion(
                    $old['title'],
                    $old['body'],
                    [],
                    $selectedCategory
                )
                : groqModerationFailure(
                    'question',
                    $needsGroqCheck ? 'rate_limited' : 'not_required'
                );

            if (collegeQuestionCanUseRelevanceFallback(
                $validation,
                $moderation,
                $selectedCategory
            )) {
                $validation = collegeValidationResult(
                    'question',
                    'RELEVANT_AND_SUPPORTED',
                    $selectedCategory,
                    'groq_relevance_fallback',
                    $moderation['model'] ?? null
                );
            }

            $groqCategoryMismatch = !empty($moderation['ok']) &&
                !collegeQuestionModerationMatchesCategory(
                    $moderation,
                    $selectedCategory
                );

            if (
                collegeQuestionValidationBlocksSubmission($validation) ||
                $groqCategoryMismatch
            ) {
                if ($groqCategoryMismatch) {
                    $errors[] = 'This question does not match the selected category. Please choose the correct category or revise the question.';
                } else {
                    $errors[] = match ($validation['result']) {
                        'IRRELEVANT' => 'This question is not relevant to the selected category. Please choose the correct category or ask about Admissions, Academics, Placements, or Campus Life.',
                        'NEEDS_CLARIFICATION' => 'Please clarify how your question relates to the selected college category and submit it again.',
                        'POTENTIALLY_INCORRECT', 'UNSUPPORTED' => 'This question could not be validated as relevant to the selected category. Please review it and try again.',
                        'REVIEW_REQUIRED' => 'Topic validation could not be completed. Your question was not submitted; please try again shortly.',
                        default => 'Topic validation could not be completed. Your question was not submitted; please try again shortly.',
                    };
                }
            } else {
                $connection->beginTransaction();

                $insert = $connection->prepare(
                    'INSERT INTO questions
                    (
                        user_id,
                        question_title,
                        question_description,
                        category,
                        status,
                        publication_status,
                        created_at
                    )
                    VALUES (?, ?, ?, ?, ?, ?, NOW())'
                );

                $publicationStatus = moderationPublicationStatus($moderation);

                $insert->execute([
                    $userId,
                    $old['title'],
                    $old['body'],
                    $selectedCategory,
                    'Pending',
                    $publicationStatus
                ]);

                $questionId = (int)$connection->lastInsertId();

                if ($insert->rowCount() !== 1 || $questionId <= 0) {
                    throw new RuntimeException(
                        'Question insert did not complete.'
                    );
                }

                recordModerationEvent(
                    $connection,
                    'question',
                    $questionId,
                    'AI',
                    $publicationStatus,
                    $moderation
                );
                recordCollegeValidationResult(
                    $connection,
                    'question',
                    $questionId,
                    $validation
                );

                $connection->commit();

                set_flash(
                    'success',
                    $publicationStatus === 'APPROVED'
                        ? 'Your question has been published successfully!'
                        : 'Your question has been forwarded to the administrator for review.'
                );

                redirect('question-history.php');
            }

        } catch (Throwable $exception) {

            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $privateText = array_values(array_filter(
                [$old['title'], $old['body']],
                static fn($value) => $value !== ''
            ));
            $safeError = str_replace(
                $privateText,
                '[content redacted]',
                $exception->getMessage()
            );
            $safeError = preg_replace('/[\r\n\t]+/', ' ', $safeError) ?? '';

            error_log(
                'Question submission failed: ' . substr($safeError, 0, 1000)
            );

            $errors[] = $exception instanceof InvalidArgumentException
                ? 'This form was already submitted or has expired. Refresh the page and try again.'
                : 'The question could not be saved. Please try again later.';
        }
    }
}

/* =====================================
   GET FLASH MESSAGES
===================================== */

$successMessage = $_SESSION['flash']['success'] ?? '';
$errorMessage = $_SESSION['flash']['error'] ?? '';

unset($_SESSION['flash']['success']);
unset($_SESSION['flash']['error']);

require __DIR__ . '/includes/header.php';

?>

<link rel="stylesheet" href="assets/css/ask-question.css">

<main class="cc-ask-page">

    <header class="cc-ask-hero">

        <div>
            <span class="cc-eyebrow">COLLEGE CONNECT COMMUNITY</span>

            <h1>Ask a question</h1>

            <p>
                Share your doubt with students, alumni and faculty.
                Add enough detail so the community can help.
            </p>
        </div>

        <div class="cc-hero-mark" aria-hidden="true">?</div>

    </header>

    <div class="cc-ask-layout">

        <section class="cc-ask-main">

            <?php if ($successMessage !== ''): ?>

                <div class="cc-alert <?= str_starts_with($successMessage, 'Your question has been published') ? 'cc-published-notice' : 'cc-review-notice' ?>" role="status">
                    <?= e($successMessage) ?>
                </div>

            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>

                <div class="cc-alert" role="alert">
                    <?= e($errorMessage) ?>
                </div>

            <?php endif; ?>

            <?php if ($errors): ?>

                <div class="cc-alert" role="alert">

                    <strong>We couldn't submit your question.</strong>

                    <ul>

                        <?php foreach ($errors as $error): ?>

                            <li><?= e($error) ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>

            <form
                id="askQuestionForm"
                class="cc-ask-card"
                method="post"
                action="ask-question.php"
                novalidate
            >

                <?= csrf_field() ?>
                <input
                    type="hidden"
                    name="submission_token"
                    value="<?= e(moderationSubmissionToken('question')) ?>"
                >

                <div class="cc-card-heading">

                    <div>

                        <span class="cc-step-label">QUESTION DETAILS</span>

                        <h2>What would you like to know?</h2>

                        <p>
                            Use a clear title and explain the problem
                            in your own words.
                        </p>

                    </div>

                </div>

                <!-- QUESTION TITLE -->

                <div class="cc-field">

                    <label for="questionTitle">
                        Question title <b>*</b>
                    </label>

                    <input
                        id="questionTitle"
                        name="title"
                        type="text"
                        minlength="10"
                        maxlength="220"
                        value="<?= e($old['title']) ?>"
                        placeholder="e.g. How can I prepare for the campus placement coding round?"
                        required
                    >

                    <div class="cc-meta">

                        <span>Make it clear and specific.</span>

                        <span id="titleCounter">0/220</span>

                    </div>

                </div>

                <!-- QUESTION CATEGORY -->

                <div class="cc-field">

                    <label for="questionCategory">
                        Category <b>*</b>
                    </label>

                    <select
                        id="questionCategory"
                        name="category_id"
                        required
                    >

                        <option value="">
                            Choose the most relevant category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= e($category) ?>"
                                <?= $category === $old['category_id'] ? 'selected' : '' ?>
                            >
                                <?= e($category) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- QUESTION DESCRIPTION -->

                <div class="cc-field">

                    <div class="cc-label-row">

                        <label for="questionBody">
                            Question details <b>*</b>
                        </label>

                        <small>Be respectful · Add context</small>

                    </div>

                    <div class="cc-editor">

                        <div class="cc-editor-tips">

                            <span>Describe the problem</span>

                            <span>Add what you tried</span>

                            <span>Include relevant details</span>

                        </div>

                        <textarea
                            id="questionBody"
                            name="body"
                            rows="10"
                            minlength="15"
                            maxlength="10000"
                            placeholder="Explain your question in detail...

What are you trying to understand?
What have you tried so far?
Which subject, technology, department, or process is involved?"
                            required
                        ><?= e($old['body']) ?></textarea>

                        <div class="cc-meta cc-editor-meta">

                            <span>
                                Do not include passwords, OTPs,
                                or private personal information.
                            </span>

                            <span id="bodyCounter">0/10,000</span>

                        </div>

                    </div>

                </div>

                <!-- FORM ACTIONS -->

                <div class="cc-form-actions">

                    <a href="question-history.php">Cancel</a>

                    <button
                        type="submit"
                        id="submitQuestionButton"
                    >

                        <span>Submit question</span>

                        <span>↗</span>

                    </button>

                </div>

                <p class="cc-submit-note">
                    Safe and relevant questions are published automatically.
                    Uncertain or flagged questions are sent to an administrator for review.
                </p>

            </form>

        </section>

        <!-- SIDEBAR -->

        <aside class="cc-ask-sidebar">

            <article class="cc-side-card">

                <div class="cc-side-icon">✦</div>

                <h3>How to ask a great question</h3>

                <ol>

                    <li>

                        <b>01</b>

                        <div>

                            <strong>Use a specific title</strong>

                            <small>Summarize your exact doubt.</small>

                        </div>

                    </li>

                    <li>

                        <b>02</b>

                        <div>

                            <strong>Provide context</strong>

                            <small>Include the subject or situation.</small>

                        </div>

                    </li>

                    <li>

                        <b>03</b>

                        <div>

                            <strong>Show your effort</strong>

                            <small>Mention what you already tried.</small>

                        </div>

                    </li>

                    <li>

                        <b>04</b>

                        <div>

                            <strong>Keep it respectful</strong>

                            <small>Help everyone contribute.</small>

                        </div>

                    </li>

                </ol>

            </article>

            <article class="cc-side-card cc-help-card">

                <strong>Need help?</strong>

                <p>
                    Browse existing discussions.
                    Someone may already have answered your question.
                </p>

                <a href="question-history.php">
                    Explore questions →
                </a>

            </article>

        </aside>

    </div>

</main>

<script src="assets/js/ask-question.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
