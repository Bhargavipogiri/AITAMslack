<?php
<<<<<<< HEAD

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
=======
session_start();
>>>>>>> fa50bb2b2a8341826462e66e39e8937cd9d3912f

/* Correct database filename */
require_once 'lesder-db.php';

/* Escape output */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$databaseError = '';

/* =====================================
   SUBMIT ANSWER
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $questionId = filter_var(
        $_POST['question_id'] ?? '',
        FILTER_VALIDATE_INT
    );

    $answerText = trim($_POST['answer_text'] ?? '');

    if (!$questionId || $answerText === '') {

        $redirectId = $questionId ? $questionId : '';

        header(
            "Location: answer.php" .
            ($redirectId
                ? "?question_id=" . $redirectId . "&error=invalid"
                : "?error=invalid")
        );

        exit();
    }

    try {

        /* Check whether question exists */
        $check = $conn->prepare(
            "SELECT id FROM questions WHERE id = :id"
        );

        $check->execute(['id' => $questionId]);

        if (!$check->fetch(PDO::FETCH_ASSOC)) {

            header(
                "Location: answer.php?question_id=" .
                $questionId . "&error=notfound"
            );

            exit();
        }

        /* Get logged-in user ID */
        $userId = 0;

        if (
            !empty($_SESSION['user_id']) &&
            filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT)
        ) {
            $userId = (int)$_SESSION['user_id'];
        }

        /* Insert answer */
        $sql = "INSERT INTO answers
                (question_id, user_id, answer_text)
                VALUES
                (:question_id, :user_id, :answer_text)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            'question_id' => $questionId,
            'user_id' => $userId,
            'answer_text' => $answerText
        ]);

        /* Update question status */
        $update = $conn->prepare(
            "UPDATE questions
             SET status = 'Answered'
             WHERE id = :id"
        );

        $update->execute(['id' => $questionId]);

        /* Redirect after successful submission */
        header(
            "Location: answer.php?question_id=" .
            $questionId . "&success=1"
        );

        exit();

    } catch (PDOException $ex) {

        error_log("Answer Submission Error: " . $ex->getMessage());

        header(
            "Location: answer.php?question_id=" .
            $questionId . "&error=database"
        );

        exit();
    }
}

/* =====================================
   FETCH QUESTIONS
===================================== */

$questions = [];
$answerQuery = null;

try {

    $selectedQuestionId = filter_input(
        INPUT_GET,
        'question_id',
        FILTER_VALIDATE_INT
    );

    if ($selectedQuestionId) {

        $sql = "SELECT
                    id,
                    question_title,
                    question_description,
                    category,
                    created_at
                FROM questions
                WHERE id = :id";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            'id' => $selectedQuestionId
        ]);

    } else {

        $sql = "SELECT
                    id,
                    question_title,
                    question_description,
                    category,
                    created_at
                FROM questions
                ORDER BY created_at DESC";

        $stmt = $conn->prepare($sql);

        $stmt->execute();
    }

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Fetch answers */
    $answerQuery = $conn->prepare(
        "SELECT
            id,
            user_id,
            answer_text,
            created_at
         FROM answers
         WHERE question_id = :question_id
         ORDER BY created_at ASC"
    );

} catch (PDOException $ex) {

    error_log("Question Fetch Error: " . $ex->getMessage());

    $databaseError = 'Unable to load questions and answers.';

    $answerQuery = null;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Answers | AITAM Slack</title>

    <!-- Global Root CSS -->
    <link rel="stylesheet" href="./assets/css/root.css">

    <style>

/* =====================================
   GLOBAL STYLES
===================================== */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: var(--font-family-base);
    background: var(--bg-app, var(--color-white));
    color: var(--text-primary);
    transition: background 0.3s ease, color 0.3s ease;
}

/* =====================================
   ANSWER PAGE - LIGHT THEME
===================================== */

.answer-container {

    --answer-bg: var(--bg-app, #f5f7fb);
    --answer-card: var(--bg-surface, #ffffff);
    --answer-text: var(--text-primary, #172033);
    --answer-muted: var(--text-secondary, #64748b);
    --answer-border: var(--border-color, #dce3ed);
    --answer-soft: var(--surface-secondary, #f1f5f9);
    --answer-heading: var(--color-navy-950, #102b50);
    --answer-input: var(--bg-surface, #ffffff);
    --answer-category-bg: var(--color-cyan-100, #cffafe);
    --answer-category-text: var(--color-navy-950, #102b50);

    width: 100%;
    max-width: 1100px;

    margin: 40px auto;
    padding: 25px;

    background: var(--answer-bg);
    color: var(--answer-text);

    transition: background 0.3s ease, color 0.3s ease;
}

/* =====================================
   DARK THEME
===================================== */

html[data-theme="dark"] .answer-container,
body[data-theme="dark"] .answer-container {

    --answer-bg: #0b1730;
    --answer-card: #112544;
    --answer-text: #f5f7ff;
    --answer-muted: #c1cee0;
    --answer-border: #29476b;
    --answer-soft: #192f4e;
    --answer-heading: #ffffff;
    --answer-input: #102442;
    --answer-category-bg: #20476a;
    --answer-category-text: #d9f5ff;
}

/* =====================================
   PAGE HEADER
===================================== */

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    color: var(--answer-heading);
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 10px;
}

.page-header p {
    color: var(--answer-muted);
    font-size: 16px;
    line-height: 1.7;
    margin: 0;
}

/* =====================================
   QUESTION CARD
===================================== */

.question-card {

    background: var(--answer-card);

    border: 1px solid var(--answer-border);
    border-radius: 14px;

    padding: 25px;
    margin-bottom: 25px;

    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);

    transition: background 0.3s ease, border 0.3s ease;
}

html[data-theme="dark"] .question-card,
body[data-theme="dark"] .question-card {
    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.18);
}

.question-card h3 {
    color: var(--answer-heading);
    font-size: 21px;
    font-weight: 700;
    line-height: 1.5;
    margin: 0 0 15px;
}

.question-description {
    color: var(--answer-muted);
    font-size: 15px;
    line-height: 1.8;
    margin-bottom: 18px;
    overflow-wrap: anywhere;
}

/* =====================================
   CATEGORY
===================================== */

.category {
    display: inline-block;

    background: var(--answer-category-bg);
    color: var(--answer-category-text);

    padding: 7px 14px;

    border-radius: 20px;

    font-size: 13px;
    font-weight: 700;
}

/* =====================================
   ANSWERS SECTION
===================================== */

.answers-section {
    margin-top: 25px;
}

.answers-section h4 {
    color: var(--answer-heading);
    font-size: 18px;
    margin: 0 0 15px;
}

/* Individual Answer */

.answer-item {

    background: var(--answer-soft);

    border: 1px solid var(--answer-border);
    border-radius: 10px;

    padding: 17px;
    margin-bottom: 13px;
}

.answer-item p {
    color: var(--answer-text);
    font-size: 15px;
    line-height: 1.8;

    margin: 0 0 12px;

    overflow-wrap: anywhere;
}

.answer-meta {
    color: var(--answer-muted);
    font-size: 13px;
    line-height: 1.6;
}

/* =====================================
   ANSWER FORM
===================================== */

.answer-form {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid var(--answer-border);
}

.answer-form label {
    display: block;

    color: var(--answer-text);

    font-size: 15px;
    font-weight: 700;

    margin-bottom: 12px;
}

.answer-form textarea {

    width: 100%;
    min-height: 120px;

    padding: 15px;

    background: var(--answer-input);
    color: var(--answer-text);

    border: 1px solid var(--answer-border);
    border-radius: 10px;

    font-family: var(--font-family-base);
    font-size: 15px;
    line-height: 1.6;

    resize: vertical;
    outline: none;

    transition: 0.2s ease;
}

.answer-form textarea::placeholder {
    color: var(--answer-muted);
    opacity: 0.8;
}

.answer-form textarea:focus {
    border-color: var(--color-blue-500, #3182ce);
    box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
}

/* =====================================
   SUBMIT BUTTON
===================================== */

.answer-form button {

    display: inline-block;

    margin-top: 15px;
    padding: 12px 25px;

    background: var(--gradient-brand);
    color: var(--text-on-brand);

    border: none;
    border-radius: 9px;

    font-family: var(--font-family-base);
    font-size: 15px;
    font-weight: 700;

    cursor: pointer;

    transition: var(--transition-fast);
}

.answer-form button:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.answer-form button:focus-visible {
    outline: 2px solid var(--color-blue-500, #3182ce);
    outline-offset: 3px;
}

/* =====================================
   SUCCESS AND ERROR MESSAGES
===================================== */

.message {

    padding: 15px 18px;

    border-radius: 9px;
    margin-bottom: 22px;

    font-size: 14px;
    line-height: 1.6;
}

.message.success {
    background: rgba(22, 163, 74, 0.12);
    color: var(--answer-text);
    border: 1px solid #16a34a;
}

.message.error,
.message.database-error {
    background: rgba(220, 38, 38, 0.12);
    color: var(--answer-text);
    border: 1px solid #dc2626;
}

/* =====================================
   EMPTY STATE
===================================== */

.empty-state {

    text-align: center;

    padding: 45px 20px;

    background: var(--answer-card);

    border: 1px dashed var(--answer-border);
    border-radius: 14px;
}

.empty-state h3 {
    color: var(--answer-heading);
    font-size: 21px;
    margin: 0 0 10px;
}

.empty-state p {
    color: var(--answer-muted);
    font-size: 15px;
    line-height: 1.7;
}

/* =====================================
   BACK LINK
===================================== */

.back-link {

    display: inline-block;

    margin-top: 15px;
    padding: 12px 20px;

    background: var(--color-blue-600);
    color: var(--text-on-brand);

    border-radius: 9px;

    text-decoration: none;
    font-size: 14px;
    font-weight: 700;

    transition: var(--transition-fast);
}

.back-link:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

/* =====================================
   RESPONSIVE DESIGN
===================================== */

@media (max-width: 768px) {

    .answer-container {
        margin: 25px auto;
        padding: 18px;
    }

    .page-header h1 {
        font-size: 27px;
    }

    .question-card {
        padding: 20px;
    }

}

@media (max-width: 480px) {

    .answer-container {
        margin: 15px auto;
        padding: 13px;
    }

    .page-header h1 {
        font-size: 24px;
    }

    .page-header p {
        font-size: 14px;
    }

    .question-card {
        padding: 16px;
    }

    .question-card h3 {
        font-size: 18px;
    }

    .answer-form button {
        width: 100%;
    }

}

    </style>

</head>

<body>

<?php include 'nav.php'; ?>

<main class="answer-container">

    <!-- PAGE HEADER -->
    <header class="page-header">

        <h1>Questions & Answers</h1>

        <p>
            View questions and share your knowledge with others.
        </p>

    </header>


    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['success'])): ?>

        <div class="message success" role="status">
            ✓ Your answer was submitted successfully!
        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->
    <?php if (isset($_GET['error'])): ?>

        <div class="message error" role="alert">

            <?php

            if ($_GET['error'] === 'database') {

                echo 'Unable to save your answer. Please try again.';

            } elseif ($_GET['error'] === 'notfound') {

                echo 'The selected question was not found.';

            } else {

                echo 'Unable to submit your answer. Please check your input.';

            }

            ?>

        </div>

    <?php endif; ?>


    <!-- DATABASE ERROR -->
    <?php if ($databaseError !== ''): ?>

        <div class="message database-error" role="alert">
            <?= e($databaseError) ?>
        </div>

    <?php endif; ?>


    <!-- DISPLAY QUESTIONS -->
    <?php if (count($questions) > 0): ?>

        <?php foreach ($questions as $question): ?>

            <article class="question-card">

                <h3>
                    <?= e($question['question_title']) ?>
                </h3>

                <p class="question-description">
                    <?= nl2br(e($question['question_description'])) ?>
                </p>

                <span class="category">
                    <?= e($question['category']) ?>
                </span>


                <!-- ANSWERS SECTION -->
                <div class="answers-section">

                    <h4>Answers</h4>

                    <?php

                    $answers = [];

                    if ($answerQuery !== null) {

                        $answerQuery->execute([
                            'question_id' => $question['id']
                        ]);

                        $answers = $answerQuery->fetchAll(
                            PDO::FETCH_ASSOC
                        );

                    }

                    ?>


                    <!-- EXISTING ANSWERS -->
                    <?php if (count($answers) > 0): ?>

                        <?php foreach ($answers as $answer): ?>

                            <div class="answer-item">

                                <p>
                                    <?= nl2br(e($answer['answer_text'])) ?>
                                </p>

                                <div class="answer-meta">

                                    <?= ((int)$answer['user_id'] === 0)
                                        ? 'Answered publicly'
                                        : 'Answered by User #' . e($answer['user_id']) ?>

                                    |

                                    <?php

                                    $answerDate = strtotime(
                                        (string)($answer['created_at'] ?? '')
                                    );

                                    ?>

                                    <?= e(
                                        $answerDate
                                            ? date('d M Y, h:i A', $answerDate)
                                            : 'Unknown'
                                    ) ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p class="answer-meta">
                            No answers yet. Be the first to answer!
                        </p>

                    <?php endif; ?>

                </div>


                <!-- SUBMIT ANSWER FORM -->
                <form method="POST"
                      action="answer.php"
                      class="answer-form">

                    <input
                        type="hidden"
                        name="question_id"
                        value="<?= e($question['id']) ?>"
                    >

                    <label for="answer-<?= e($question['id']) ?>">
                        Write Your Answer
                    </label>

                    <textarea
                        id="answer-<?= e($question['id']) ?>"
                        name="answer_text"
                        placeholder="Type your answer here..."
                        required
                    ></textarea>

                    <button type="submit">
                        Submit Answer
                    </button>

                </form>

            </article>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty-state">

            <h3>No Questions Available</h3>

            <p>
                There are no questions to answer right now.
            </p>

        </div>

    <?php endif; ?>


    <!-- BACK LINK -->
    <a href="question-history.php" class="back-link">
        View Questions
    </a>

</main>

<?php include 'footer.php'; ?>

</body>
</html>
