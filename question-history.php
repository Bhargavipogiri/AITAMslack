<?php
<<<<<<< HEAD
require_once __DIR__ . '/includes/bootstrap.php';
=======
session_start();

require_once 'lesder-db.php';
>>>>>>> 90f2f6348ddfe309d4540311a92ebdcd51102fdb

$questions = [];
$databaseError = '';
$successMessage = $_SESSION['flash']['success'] ?? '';
unset($_SESSION['flash']['success']);

try {
    $stmt = db()->query(
        'SELECT
            q.id,
            q.user_id,
            q.question_title,
            q.question_description,
            q.category,
            q.status,
            q.created_at,
            COALESCE(NULLIF(u.full_name, \'\'), CONCAT(\'User #\', q.user_id)) AS user_name
         FROM questions q
         LEFT JOIN users u ON u.user_id = q.user_id
         ORDER BY q.created_at DESC, q.id DESC'
    );
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('Question history load failed: ' . $exception->getMessage());
    $databaseError = 'Unable to load question history. Please try again later.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question History | AITAM Slack</title>

    <link rel="stylesheet" href="root.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-family-base);
            background: var(--color-white);
            color: var(--text-primary);
        }

        .history-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .history-header {
            margin-bottom: 25px;
        }

        .history-header h1 {
            margin: 0 0 8px;
            color: var(--color-navy-950);
        }

        .history-header p {
            color: var(--text-secondary);
        }

        .history-count {
            margin-bottom: 18px;
            font-weight: 600;
        }

        .question-list {
            display: grid;
            gap: 18px;
        }

        .question-card {
            padding: 22px;
            border: 1px solid var(--color-cyan-100);
            border-radius: 12px;
            background: var(--color-white);
        }

        .question-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .question-card h3 {
            margin: 0 0 10px;
            color: var(--color-navy-950);
        }

        .question-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 8px 0 15px;
        }

        .question-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .answered {
            background: #d4edda;
            color: #155724;
        }

        .closed {
            background: #e2e3e5;
            color: #383d41;
        }

        .empty-state {
            text-align: center;
            padding: 45px 20px;
            border: 1px dashed var(--color-blue-500);
            border-radius: 12px;
            color: var(--text-secondary);
        }

        .database-error {
            margin-bottom: 18px;
            padding: 14px 18px;
            border: 1px solid #dc3545;
            border-radius: 8px;
            background: #f8d7da;
            color: #842029;
        }

        .success-message {
            margin-bottom: 18px;
            padding: 14px 18px;
            border: 1px solid #198754;
            border-radius: 8px;
            background: #d1e7dd;
            color: #0f5132;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 18px;
            border-radius: 8px;
            background: var(--color-blue-600);
            color: var(--text-on-brand);
            text-decoration: none;
        }

        .back-link:hover {
            opacity: 0.9;
        }

        .question-actions {
    margin-top: 18px;
}

.answer-btn {
    display: inline-block;
    padding: 10px 20px;
    background: var(--gradient-brand);
    color: var(--text-on-brand);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: var(--transition-fast);
}

.answer-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

        @media (max-width: 600px) {
            .history-container {
                margin: 20px auto;
                padding: 15px;
            }

            .question-top {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
<?php include 'nav.php'; ?>

<main class="history-container">

    <header class="history-header">
        <h1>Question History</h1>
        <p>View all your previously submitted questions.</p>
    </header>

    <div class="history-count">
        Total Questions: <?= count($questions) ?>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="success-message" role="status">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($databaseError !== ''): ?>
        <div class="database-error" role="alert">
            <?= e($databaseError) ?>
        </div>
    <?php endif; ?>

    <section class="question-list">

        <?php if (count($questions) > 0): ?>

            <?php foreach ($questions as $question): ?>

                <?php
                    $statusClass = strtolower((string)($question['status'] ?? ''));
                    $createdAt = strtotime((string)($question['created_at'] ?? ''));
                ?>

                <article class="question-card">

                    <div class="question-top">
                        <h3>
                            <?= e($question['question_title']) ?>
                        </h3>

                        <span class="status <?= e($statusClass) ?>">
                            <?= e($question['status']) ?>
                        </span>
                    </div>

                    <p class="question-description">
                        <?= nl2br(e($question['question_description'])) ?>
                    </p>

                    <div class="question-meta">
                        <span>
                            Question ID: #<?= e($question['id']) ?>
                        </span>

                        <span>
                            User: <?= e($question['user_name']) ?>
                        </span>

                        <span>
                            Category: <?= e($question['category']) ?>
                        </span>

                        <span>
                            Date:
                            <?= e($createdAt
                                ? date('d M Y, h:i A', $createdAt)
                                : 'Unknown') ?>
                        </span>
                    </div>
                    <div class="question-actions">
    <a href="answer.php?question_id=<?= e($question['id']) ?>"
       class="answer-btn">
        Answer
    </a>
</div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-state">
                <h3>No Questions Found</h3>
                <p>You have not submitted any questions yet.</p>
            </div>

        <?php endif; ?>

    </section>

    <a href="index.php" class="back-link">
        Back to Dashboard
    </a>

</main>
<?php include 'footer.php'; ?>

</body>
</html>
