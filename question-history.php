
<?php
require_once 'leader-db.php';

// Fetch question history without requiring a login.
$questions = [];
$databaseError = '';

try {
    $sql = "SELECT id, user_id, question_title,
                   question_description, category,
                   status, created_at
            FROM questions
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $databaseError = 'Unable to load question history. Please try again later.';
}

// Escape output safely
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<?php include 'nav.php'; ?>    

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

<main class="history-container">

    <header class="history-header">
        <h1>Question History</h1>
        <p>View all your previously submitted questions.</p>
    </header>

    <div class="history-count">
        Total Questions: <?= count($questions) ?>
    </div>

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
                            User: <?= e($question['user_id']) ?>
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

    <a href="dashboard.php" class="back-link">
        Back to Dashboard
    </a>

</main>
<?php include 'footer.php'; ?>

</body>
</html>
