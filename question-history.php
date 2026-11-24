
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* Database connection */
require_once 'lesder-db.php';

/* Escape output safely */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Fetch question history */
$questions = [];
$databaseError = '';

try {

    $sql = "SELECT
                id,
                user_id,
                question_title,
                question_description,
                category,
                status,
                created_at
            FROM questions
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {

    error_log("Question History Error: " . $ex->getMessage());

    $databaseError = 'Unable to load question history. Please try again later.';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Question History | AITAM Slack</title>

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
   HISTORY CONTAINER - LIGHT THEME
===================================== */

.history-container {

    --history-bg: var(--bg-app, #f5f7fb);
    --history-card: var(--bg-surface, #ffffff);
    --history-text: var(--text-primary, #172033);
    --history-muted: var(--text-secondary, #64748b);
    --history-border: var(--border-color, #dce3ed);
    --history-soft: var(--surface-secondary, #f1f5f9);
    --history-heading: var(--color-navy-950, #102b50);

    max-width: 1150px;
    width: 100%;

    margin: 40px auto;
    padding: 25px;

    background: var(--history-bg);
    color: var(--history-text);

    transition: background 0.3s ease, color 0.3s ease;
}

/* =====================================
   DARK THEME
===================================== */

html[data-theme="dark"] .history-container,
body[data-theme="dark"] .history-container {

    --history-bg: #0b1730;
    --history-card: #112544;
    --history-text: #f5f7ff;
    --history-muted: #c1cee0;
    --history-border: #29476b;
    --history-soft: #192f4e;
    --history-heading: #ffffff;
}

/* =====================================
   PAGE HEADER
===================================== */

.history-header {
    margin-bottom: 30px;
}

.history-header h1 {
    margin: 0 0 10px;

    color: var(--history-heading);

    font-size: 32px;
    font-weight: 800;
}

.history-header p {
    color: var(--history-muted);

    font-size: 16px;
    line-height: 1.7;

    margin: 0;
}

/* =====================================
   QUESTION COUNT
===================================== */

.history-count {

    display: inline-block;

    margin-bottom: 22px;
    padding: 10px 16px;

    background: var(--history-soft);
    border: 1px solid var(--history-border);

    border-radius: 9px;

    color: var(--history-text);

    font-size: 15px;
    font-weight: 700;
}

/* =====================================
   QUESTION LIST
===================================== */

.question-list {
    display: grid;
    gap: 20px;
}

/* =====================================
   QUESTION CARD
===================================== */

.question-card {

    padding: 25px;

    background: var(--history-card);

    border: 1px solid var(--history-border);
    border-radius: 13px;

    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);

    transition: background 0.3s ease,
                border 0.3s ease,
                transform 0.2s ease;
}

html[data-theme="dark"] .question-card,
body[data-theme="dark"] .question-card {
    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.18);
}

.question-card:hover {
    transform: translateY(-2px);
}

/* =====================================
   QUESTION TOP
===================================== */

.question-top {

    display: flex;
    justify-content: space-between;
    align-items: flex-start;

    gap: 18px;
}

.question-card h3 {

    margin: 0 0 12px;

    color: var(--history-heading);

    font-size: 20px;
    font-weight: 700;
    line-height: 1.5;

    overflow-wrap: anywhere;
}

/* =====================================
   QUESTION DESCRIPTION
===================================== */

.question-description {

    color: var(--history-muted);

    font-size: 15px;
    line-height: 1.8;

    margin: 10px 0 20px;

    overflow-wrap: anywhere;
}

/* =====================================
   QUESTION META
===================================== */

.question-meta {

    display: flex;
    flex-wrap: wrap;

    gap: 12px 20px;

    color: var(--history-muted);

    font-size: 13px;
    line-height: 1.6;
}

.question-meta span {
    overflow-wrap: anywhere;
}

/* =====================================
   STATUS LABELS
===================================== */

.status {

    display: inline-block;

    padding: 7px 13px;

    border-radius: 20px;

    font-size: 12px;
    font-weight: 700;

    white-space: nowrap;
}

/* Pending */

.pending {
    background: #fff3cd;
    color: #856404;
}

/* Answered */

.answered {
    background: #d4edda;
    color: #155724;
}

/* Closed */

.closed {
    background: #e2e3e5;
    color: #383d41;
}

/* Other statuses */

.status:not(.pending):not(.answered):not(.closed) {
    background: var(--history-soft);
    color: var(--history-text);
    border: 1px solid var(--history-border);
}

/* =====================================
   ANSWER BUTTON
===================================== */

.question-actions {
    margin-top: 22px;
    padding-top: 18px;

    border-top: 1px solid var(--history-border);
}

.answer-btn {

    display: inline-block;

    padding: 11px 22px;

    background: var(--gradient-brand);
    color: var(--text-on-brand);

    text-decoration: none;

    border-radius: 9px;

    font-size: 14px;
    font-weight: 700;

    transition: var(--transition-fast);
}

.answer-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.answer-btn:focus-visible {
    outline: 2px solid var(--color-blue-500);
    outline-offset: 3px;
}

/* =====================================
   EMPTY STATE
===================================== */

.empty-state {

    text-align: center;

    padding: 50px 20px;

    background: var(--history-card);

    border: 1px dashed var(--history-border);
    border-radius: 13px;
}

.empty-state h3 {
    color: var(--history-heading);
    font-size: 21px;
    margin: 0 0 12px;
}

.empty-state p {
    color: var(--history-muted);
    font-size: 15px;
    line-height: 1.7;
}

/* =====================================
   DATABASE ERROR
===================================== */

.database-error {

    margin-bottom: 22px;
    padding: 15px 18px;

    background: rgba(220, 38, 38, 0.12);

    border: 1px solid #dc3545;
    border-radius: 9px;

    color: var(--history-text);

    font-size: 14px;
    line-height: 1.6;
}

/* =====================================
   BACK LINK
===================================== */

.back-link {

    display: inline-block;

    margin-top: 25px;
    padding: 12px 20px;

    background: var(--color-blue-600);
    color: var(--text-on-brand);

    text-decoration: none;

    border-radius: 9px;

    font-size: 14px;
    font-weight: 700;

    transition: var(--transition-fast);
}

.back-link:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

/* =====================================
   TABLET RESPONSIVE
===================================== */

@media (max-width: 768px) {

    .history-container {
        margin: 25px auto;
        padding: 18px;
    }

    .history-header h1 {
        font-size: 28px;
    }

    .question-card {
        padding: 20px;
    }

}

/* =====================================
   MOBILE RESPONSIVE
===================================== */

@media (max-width: 600px) {

    .history-container {
        margin: 18px auto;
        padding: 14px;
    }

    .history-header h1 {
        font-size: 25px;
    }

    .history-header p {
        font-size: 14px;
    }

    .question-top {
        flex-direction: column;
        gap: 12px;
    }

    .question-card h3 {
        font-size: 18px;
    }

    .question-meta {
        flex-direction: column;
        gap: 8px;
    }

    .answer-btn {
        width: 100%;
        text-align: center;
    }

    .back-link {
        width: 100%;
        text-align: center;
    }

}

    </style>

</head>

<body>

<?php include 'nav.php'; ?>

<main class="history-container">

    <!-- PAGE HEADER -->
    <header class="history-header">

        <h1>Question History</h1>

        <p>
            View all previously submitted questions.
        </p>

    </header>


    <!-- TOTAL QUESTIONS -->
    <div class="history-count">

        Total Questions: <?= count($questions) ?>

    </div>


    <!-- DATABASE ERROR -->
    <?php if ($databaseError !== ''): ?>

        <div class="database-error" role="alert">
            <?= e($databaseError) ?>
        </div>

    <?php endif; ?>


    <!-- QUESTION LIST -->
    <section class="question-list">

        <?php if (count($questions) > 0): ?>

            <?php foreach ($questions as $question): ?>

                <?php

                    $statusClass = strtolower(
                        trim((string)($question['status'] ?? ''))
                    );

                    $createdAt = strtotime(
                        (string)($question['created_at'] ?? '')
                    );

                ?>

                <article class="question-card">

                    <!-- QUESTION TITLE AND STATUS -->
                    <div class="question-top">

                        <h3>
                            <?= e($question['question_title']) ?>
                        </h3>

                        <span class="status <?= e($statusClass) ?>">
                            <?= e($question['status']) ?>
                        </span>

                    </div>


                    <!-- QUESTION DESCRIPTION -->
                    <p class="question-description">

                        <?= nl2br(e($question['question_description'])) ?>

                    </p>


                    <!-- QUESTION DETAILS -->
                    <div class="question-meta">

                        <span>
                            Question ID: #<?= e($question['id']) ?>
                        </span>

                        <span>
                            User: <?= e($question['user_id']) ?>
                        </span>

                        <span>
                            Category: <?= e($question['category']) ?>
                        </span>

                        <span>
                            Date:

                            <?= e(
                                $createdAt
                                    ? date('d M Y, h:i A', $createdAt)
                                    : 'Unknown'
                            ) ?>

                        </span>

                    </div>


                    <!-- ANSWER BUTTON -->
                    <div class="question-actions">

                        <a
                            href="answer.php?question_id=<?= e($question['id']) ?>"
                            class="answer-btn"
                        >
                            Answer →
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <!-- EMPTY STATE -->
            <div class="empty-state">

                <h3>No Questions Found</h3>

                <p>
                    No questions have been submitted yet.
                </p>

            </div>

        <?php endif; ?>

    </section>


    <!-- BACK TO DASHBOARD -->
    <a href="index.php" class="back-link">
        ← Back to Dashboard
    </a>

</main>

<?php include 'footer.php'; ?>

</body>
</html>
