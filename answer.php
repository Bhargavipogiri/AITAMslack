
<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ai_helpers.php';
require_once __DIR__ . '/includes/ai_validation.php';
require_once __DIR__ . '/lesder-db.php';

$databaseError = '';
<<<<<<< HEAD

=======
$answerValidationNotice = $_SESSION['flash']['answer_validation'] ?? null;
unset($_SESSION['flash']['answer_validation']);
>>>>>>> 64e62b774cc80e4e2d0be33e0f90b0e23cf096ff
$currentUserId = filter_var(
    $_SESSION['user_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$canAnswer = $currentUserId !== false && $currentUserId !== null;

/* =====================================
   SUBMIT ANSWER
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $questionIdInput = $_POST['question_id'] ?? '';
    $questionId = is_string($questionIdInput) || is_int($questionIdInput)
        ? filter_var($questionIdInput, FILTER_VALIDATE_INT)
        : false;

    $answerInput = $_POST['answer_text'] ?? '';
    $answerText = is_string($answerInput) ? trim($answerInput) : '';

    if (!$canAnswer) {
        header('Location: login.php');
        exit();
    }

    if ($questionId === false || $questionId < 1 || $answerText === '') {
        header("Location: answer.php?error=invalid");
        exit();
    }

    try {

        /*
         * Check that the question is approved
         * and retrieve its owner's user ID.
         */

        $check = $conn->prepare(
            "SELECT
                id,
                user_id,
                question_title,
                question_description,
                category
             FROM questions
             WHERE id = :id
               AND publication_status = 'APPROVED'
             LIMIT 1"
        );

        $check->execute(['id' => $questionId]);

        $question = $check->fetch(PDO::FETCH_ASSOC);

        if (!$question) {
            header("Location: answer.php?question_id=" . $questionId . "&error=notfound");
            exit();
        }

        /*
         * PREVENT USERS FROM ANSWERING
         * THEIR OWN QUESTIONS.
         */

        if ((int)$question['user_id'] === (int)$currentUserId) {
            header("Location: answer.php?error=ownquestion");
            exit();
        }

        /*
         * Validate submission token.
         */

        if (!consumeModerationSubmissionToken(
            'answer',
            $questionId,
            $_POST['submission_token'] ?? null
        )) {
            header("Location: answer.php?question_id=" . $questionId . "&error=invalid");
            exit();
        }

        /*
         * Validate answer length.
         */

        $answerLength = function_exists('mb_strlen')
            ? mb_strlen($answerText, 'UTF-8')
            : strlen($answerText);

        if ($answerLength > 10000) {
            header("Location: answer.php?question_id=" . $questionId . "&error=invalid");
            exit();
        }

        /*
         * Check for duplicate answers.
         */

        $duplicateCheck = findPotentialDuplicateAnswers(
            $conn,
            (int)$questionId,
            $answerText
        );

        $possibleDuplicates = !empty($duplicateCheck['ok'])
            && is_array($duplicateCheck['matches'] ?? null)
            ? $duplicateCheck['matches']
            : [];

<<<<<<< HEAD
        /*
         * AI MODERATION
         */
=======
        $validation = validateCollegeContent(
            $conn,
            (int)$currentUserId,
            'answer',
            (string)($question['category'] ?? ''),
            $answerText,
            (string)$question['question_title'],
            (string)$question['question_description']
        );
>>>>>>> 64e62b774cc80e4e2d0be33e0f90b0e23cf096ff

        $moderation = groqRateLimitAllowed((int)$currentUserId, 'answer')
            ? moderateAnswer(
                (string)$question['question_title'],
                (string)$question['question_description'],
                $answerText,
                $possibleDuplicates,
                (string)($question['category'] ?? '')
            )
            : groqModerationFailure('answer', 'rate_limited');

<<<<<<< HEAD
        $publicationStatus = moderationPublicationStatus($moderation);
=======
        if (collegeAnswerCanUseRelevanceFallback($validation, $moderation)) {
            $validation = collegeValidationResult(
                'answer',
                'RELEVANT',
                (string)($question['category'] ?? 'Unknown'),
                'groq_relevance_fallback',
                $moderation['model'] ?? null
            );
        }

        if (collegeAnswerIsIrrelevant($validation, $moderation)) {
            $rejectedValidation = collegeValidationResult(
                'answer',
                'IRRELEVANT',
                (string)($question['category'] ?? 'Unknown')
            );
            $_SESSION['flash']['answer_validation'] = [
                'result' => $rejectedValidation['result'],
                'reason' => $rejectedValidation['reason'],
                'category' => $rejectedValidation['category'],
            ];
            header(
                "Location: answer.php?question_id=" .
                $questionId .
                '&success=irrelevant'
            );
            exit();
        }

        $conn->beginTransaction();

        $publicationStatus = collegeAnswerCanPublish($validation, $moderation)
            ? 'APPROVED'
            : 'PENDING';
>>>>>>> 64e62b774cc80e4e2d0be33e0f90b0e23cf096ff

        /*
         * IMPORTANT:
         * answers table uses:
         * Published, Pending, Hidden
         *
         * moderation_events uses:
         * APPROVED, PENDING, REJECTED
         */

        $answerStatus = $publicationStatus === 'APPROVED'
            ? 'Published'
            : 'Pending';

        $conn->beginTransaction();

        /*
         * INSERT ANSWER
         *
         * Use the actual answers.status column.
         * Do not use answers.publication_status.
         */

        $sql = "INSERT INTO answers
                (
                    question_id,
                    user_id,
                    answer_text,
                    status
                )
                VALUES
                (
                    :question_id,
                    :user_id,
                    :answer_text,
                    :status
                )";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            'question_id' => $questionId,
            'user_id' => $currentUserId,
            'answer_text' => $answerText,
            'status' => $answerStatus
        ]);

        $answerId = (int)$conn->lastInsertId();

        if ($answerId < 1) {
            throw new RuntimeException('Answer insert did not complete.');
        }

        /*
         * RECORD AI MODERATION HISTORY
         *
         * Keep the uppercase publication status
         * for moderation_events.
         */

        recordModerationEvent(
            $conn,
            'answer',
            $answerId,
            'AI',
            $publicationStatus,
            $moderation
        );
        recordCollegeValidationResult(
            $conn,
            'answer',
            $answerId,
            $validation
        );

        /*
         * Update question status only
         * when the answer is published.
         */

        if ($publicationStatus === 'APPROVED') {

            $updateQuestion = $conn->prepare(
                "UPDATE questions
                 SET status = 'Answered'
                 WHERE id = :id
                   AND publication_status = 'APPROVED'
                   AND status = 'Pending'"
            );

            $updateQuestion->execute([
                'id' => $questionId
            ]);
        }

        $conn->commit();
        $_SESSION['flash']['answer_validation'] = [
            'result' => $validation['result'],
            'reason' => $validation['reason'],
            'category' => $validation['category'],
        ];

        header(
            "Location: answer.php?question_id=" .
            $questionId .
            "&success=" .
            ($publicationStatus === 'APPROVED' ? 'approved' : 'pending')
        );

        exit();

    } catch (Throwable $ex) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        error_log(
            'Answer submission failed: ' .
            get_class($ex) . ' (' . $ex->getCode() . ')'
        );

        header("Location: answer.php?question_id=" . $questionId . "&error=database");
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

    /*
     * If a specific question is selected,
     * fetch it only if it is approved
     * and does not belong to the logged-in user.
     */

    if ($selectedQuestionId !== false && $selectedQuestionId !== null) {

        if ($canAnswer) {

            $sql = "SELECT
                        id,
                        user_id,
                        question_title,
                        question_description,
                        category,
                        created_at
                    FROM questions
                    WHERE id = :id
                      AND publication_status = 'APPROVED'
                      AND user_id <> :user_id";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                'id' => $selectedQuestionId,
                'user_id' => $currentUserId
            ]);

        } else {

            $sql = "SELECT
                        id,
                        user_id,
                        question_title,
                        question_description,
                        category,
                        created_at
                    FROM questions
                    WHERE id = :id
                      AND publication_status = 'APPROVED'";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                'id' => $selectedQuestionId
            ]);
        }

    } else {

        /*
         * Fetch all approved questions
         * except the logged-in user's questions.
         */

        if ($canAnswer) {

            $sql = "SELECT
                        id,
                        user_id,
                        question_title,
                        question_description,
                        category,
                        created_at
                    FROM questions
                    WHERE publication_status = 'APPROVED'
                      AND user_id <> :user_id
                    ORDER BY created_at DESC";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                'user_id' => $currentUserId
            ]);

        } else {

            $sql = "SELECT
                        id,
                        user_id,
                        question_title,
                        question_description,
                        category,
                        created_at
                    FROM questions
                    WHERE publication_status = 'APPROVED'
                    ORDER BY created_at DESC";

            $stmt = $conn->prepare($sql);
            $stmt->execute();
        }
    }

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * FETCH ONLY PUBLISHED ANSWERS.
     *
     * The answers table uses status,
     * not publication_status.
     */

    $answerQuery = $conn->prepare(
        "SELECT
            a.id,
            a.user_id,
            a.answer_text,
            a.created_at,
            a.publication_status,
            validation.result_code AS validation_result,
            validation.reason AS validation_reason,
            validation.category AS validation_category,
            COALESCE(NULLIF(s.name, ''), NULLIF(u.full_name, '')) AS author_name,
            COALESCE(NULLIF(s.department, ''), NULLIF(u.department, '')) AS author_department
         FROM answers a
         LEFT JOIN students s ON s.id = a.user_id
         LEFT JOIN users u ON u.user_id = a.user_id
         LEFT JOIN qa_validation_results validation
            ON validation.id = (
                SELECT MAX(qvr.id)
                FROM qa_validation_results qvr
                WHERE qvr.content_type = 'answer'
                  AND qvr.content_id = a.id
            )
         WHERE a.question_id = :question_id
<<<<<<< HEAD
           AND a.status = 'Published'
=======
           AND (
                a.publication_status = 'APPROVED'
                OR (
                    a.user_id = :viewer_id
                    AND a.publication_status IN ('PENDING', 'REJECTED')
                )
           )
>>>>>>> 64e62b774cc80e4e2d0be33e0f90b0e23cf096ff
         ORDER BY a.created_at ASC, a.id ASC"
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

.message.review {
    background: rgba(217, 153, 32, 0.13);
    color: var(--answer-text);
    border: 1px solid #d69e2e;
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

        <div class="message <?= $_GET['success'] === 'approved' ? 'success' : 'review' ?>" role="status">
<<<<<<< HEAD

            <?php if ($_GET['success'] === 'approved'): ?>

                Your answer has been published successfully!

            <?php else: ?>

                Your answer could not be automatically approved because it may be incorrect, irrelevant, or inappropriate. It has been forwarded to the administrator for review.

            <?php endif; ?>

=======
        <?php if ($_GET['success'] === 'approved'): ?>
            Your answer has been published successfully!
        <?php elseif ($_GET['success'] === 'irrelevant'): ?>
            Your answer does not address this question and was not submitted.
        <?php else: ?>
            Your answer could not be confirmed as safe and relevant, so it was not published. The original submission is retained.
        <?php endif; ?>
>>>>>>> 64e62b774cc80e4e2d0be33e0f90b0e23cf096ff
        </div>

    <?php endif; ?>

    <?php if (is_array($answerValidationNotice)): ?>
        <div class="message <?= ($answerValidationNotice['result'] ?? '') === 'RELEVANT' ? 'success' : 'review' ?>" role="status">
            <strong>
                Validation: <?= e($answerValidationNotice['result'] ?? 'REVIEW_REQUIRED') ?>
                · <?= e($answerValidationNotice['category'] ?? 'Unknown') ?>
            </strong>
            <?= e($answerValidationNotice['reason'] ?? 'Moderator review is required.') ?>
        </div>
    <?php endif; ?>


    <!-- ERROR MESSAGE -->
    <?php if (isset($_GET['error'])): ?>

        <div class="message error" role="alert">

            <?php

            if ($_GET['error'] === 'database') {

                echo 'Unable to save your answer. Please try again.';

            } elseif ($_GET['error'] === 'notfound') {

                echo 'The selected question was not found or is not available for answering.';

            } elseif ($_GET['error'] === 'ownquestion') {

                echo 'You cannot answer a question that you posted yourself.';

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

            <article
                class="question-card"
                id="question-<?= e($question['id']) ?>"
            >

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
                            'question_id' => $question['id'],
                            'viewer_id' => $canAnswer ? (int)$currentUserId : 0,
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

                                <?php if ($answer['publication_status'] !== 'APPROVED'): ?>
                                    <div class="answer-meta">
                                        Your answer is <?= ($answer['publication_status'] ?? '') === 'PENDING'
                                            ? 'pending administrator review.'
                                            : 'not published.' ?>
                                    </div>
                                <?php endif; ?>

                                <p>
                                    <?= nl2br(e($answer['answer_text'])) ?>
                                </p>

                                <?php if (!empty($answer['validation_result'])): ?>
                                    <div class="answer-meta" role="status">
                                        Validation: <?= e($answer['validation_result']) ?>
                                        · <?= e($answer['validation_category']) ?>
                                        — <?= e($answer['validation_reason']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="answer-meta">

                                    <?php if (!empty($answer['author_name'])): ?>

                                        <?= e($answer['author_name']) ?>

                                        <?php if (!empty($answer['author_department'])): ?>
                                            · <?= e($answer['author_department']) ?>
                                        <?php endif; ?>

                                    <?php else: ?>

                                        <?= (int)$answer['user_id'] > 0
                                            ? 'Student #' . e($answer['user_id'])
                                            : 'Answered publicly' ?>

                                    <?php endif; ?>

                                    |

                                    <?php
                                    $answerDate = strtotime(
                                        (string)($answer['created_at'] ?? '')
                                    );
                                    ?>

                                    <?= e($answerDate ? date('d M Y, h:i A', $answerDate) : 'Unknown') ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p class="answer-meta">
                            No answers yet
                        </p>

                    <?php endif; ?>

                </div>


                <!-- SUBMIT ANSWER FORM -->
                <?php if ($canAnswer): ?>

                    <form method="POST"
                          action="answer.php"
                          class="answer-form">

                        <input
                            type="hidden"
                            name="question_id"
                            value="<?= e($question['id']) ?>"
                        >

                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="submission_token"
                            value="<?= e(moderationSubmissionToken('answer', (int)$question['id'])) ?>"
                        >

                        <label for="answer-<?= e($question['id']) ?>">
                            Write Your Answer (up to 10,000 characters)
                        </label>

                        <textarea
                            id="answer-<?= e($question['id']) ?>"
                            name="answer_text"
                            maxlength="10000"
                            placeholder="Type your answer here..."
                            required
                        ></textarea>

                        <button type="submit">
                            Submit Answer
                        </button>

                    </form>

                <?php else: ?>

                    <p class="answer-meta">
                        <a href="login.php">Sign in</a> to submit an answer.
                    </p>

                <?php endif; ?>

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
++5