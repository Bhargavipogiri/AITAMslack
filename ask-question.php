<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ai_helpers.php';
require_once __DIR__ . '/includes/openai_moderation.php';

$user = require_login();
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

// Load categories
$categoryQuery = db()->query(
    'SELECT id, name FROM categories ORDER BY name ASC'
);

$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $old['title'] = trim((string)($_POST['title'] ?? ''));
    $old['body'] = trim((string)($_POST['body'] ?? ''));
    $old['category_id'] = (string)($_POST['category_id'] ?? '');

    $categoryId = filter_var(
        $old['category_id'],
        FILTER_VALIDATE_INT
    );

    $selectedCategory = '';

    // Validate title
    if (
        $textLength($old['title']) < 15 ||
        $textLength($old['title']) > 220
    ) {
        $errors[] = 'Your title must be between 15 and 220 characters.';
    }

    // Validate question description
    if (
        $textLength($old['body']) < 30 ||
        $textLength($old['body']) > 10000
    ) {
        $errors[] = 'Your question details must be between 30 and 10,000 characters.';
    }

    // Validate category
    if ($categoryId === false || $categoryId < 1) {

        $errors[] = 'Please select a valid category.';

    } else {

        $check = db()->prepare(
            'SELECT name FROM categories WHERE id = ? LIMIT 1'
        );

        $check->execute([$categoryId]);

        $selectedCategory = $check->fetchColumn();

        if (!$selectedCategory) {
            $errors[] = 'The selected category is no longer available.';
        }
    }

    if (!$errors) {

        $moderation = moderateContent(
            $old['title'] . "\n\n" . $old['body']
        );

        if (empty($moderation['success']) || !empty($moderation['flagged'])) {
            $message = $moderation['message'] ?? 'The question did not pass moderation.';
            $errors[] = is_string($message)
                ? $message
                : 'The question did not pass moderation.';
        } else {
            $connection = db();

            try {
                $connection->beginTransaction();

                $insert = $connection->prepare(
                    'INSERT INTO questions
                    (
                        user_id,
                        question_title,
                        question_description,
                        category,
                        status,
                        created_at
                    )
                    VALUES (:user_id, :question_title, :question_description, :category, :status, NOW())'
                );

                $insert->execute([
                    ':user_id' => (int)$user['user_id'],
                    ':question_title' => $old['title'],
                    ':question_description' => $old['body'],
                    ':category' => $selectedCategory,
                    ':status' => 'Pending'
                ]);

                $questionId = (int)$connection->lastInsertId();

                if ($insert->rowCount() !== 1 || $questionId <= 0) {
                    $connection->rollBack();
                    $errors[] = 'The question could not be saved. Please try again.';
                } else {
                    $connection->commit();

                    set_flash(
                        'success',
                        'Your question has been submitted successfully!'
                    );

                    redirect('question-history.php');
                }
            } catch (PDOException $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }

                error_log('Question insert failed: ' . $exception->getMessage());
                $errors[] = 'The question could not be saved. Please try again.';
            }
        }
    }
}

// Get flash messages
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
                <div class="cc-alert" role="status">
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

                <div class="cc-card-heading">

                    <div>
                        <span class="cc-step-label">
                            QUESTION DETAILS
                        </span>

                        <h2>What would you like to know?</h2>

                        <p>
                            Use a clear title and explain the problem
                            in your own words.
                        </p>
                    </div>

                    <span class="cc-ai-badge">✦ AI checked</span>

                </div>

                <div class="cc-field">

                    <label for="questionTitle">
                        Question title <b>*</b>
                    </label>

                    <input
                        id="questionTitle"
                        name="title"
                        type="text"
                        minlength="15"
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
                                value="<?= (int)$category['id'] ?>"
                                <?= (string)$category['id'] === $old['category_id'] ? 'selected' : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <small>
                        Categories are loaded from your existing categories table.
                    </small>

                </div>

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
                            minlength="30"
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
                    Your question is checked before it is saved.
                    If the required checks fail or are unavailable,
                    it will not be saved.
                </p>

            </form>

        </section>

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

            <article class="cc-side-card cc-ai-card">

                <div class="cc-ai-heading">
                    <i></i>
                    <strong>AI moderation enabled</strong>
                </div>

                <p>
                    Questions are screened for safety and genuine
                    question quality before publishing.
                </p>

                <span>✓ Safety screening</span>
                <span>✓ Question quality screening</span>
                <span>✓ Server-side API processing</span>

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
