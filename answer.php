
<?php
session_start();
require_once 'db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Escape output
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Submit answer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $questionId = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
    $answerText = trim($_POST['answer_text'] ?? '');

    if (!$questionId || $answerText === '') {
        header("Location: answer.php?error=invalid");
        exit();
    }

    // Check whether the question exists
    $check = $pdo->prepare(
        "SELECT id FROM questions WHERE id = :id"
    );
    $check->execute(['id' => $questionId]);

    if (!$check->fetch()) {
        header("Location: answer.php?error=notfound");
        exit();
    }

    // Save answer
    $sql = "INSERT INTO answers
            (question_id, user_id, answer_text)
            VALUES (:question_id, :user_id, :answer_text)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'question_id' => $questionId,
        'user_id' => $userId,
        'answer_text' => $answerText
    ]);

    // Update question status
    $update = $pdo->prepare(
        "UPDATE questions SET status = 'Answered' WHERE id = :id"
    );
    $update->execute(['id' => $questionId]);

    header("Location: answer.php?success=1");
    exit();
}

// Fetch questions
$sql = "SELECT
            q.id,
            q.question_title,
            q.question_description,
            q.category,
            q.created_at
        FROM questions q
        ORDER BY q.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch answers for each question
$answerQuery = $pdo->prepare(
    "SELECT id, user_id, answer_text, created_at
     FROM answers
     WHERE question_id = :question_id
     ORDER BY created_at ASC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Answers | AITAM Slack</title>

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

        .answer-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            color: var(--color-navy-950);
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--text-secondary);
        }

        .question-card {
            border: 1px solid var(--color-cyan-100);
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 22px;
            background: var(--color-white);
        }

        .question-card h3 {
            color: var(--color-navy-950);
            margin-top: 0;
        }

        .question-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .category {
            display: inline-block;
            background: var(--color-cyan-100);
            color: var(--color-navy-950);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
        }

        .answers-section {
            margin-top: 20px;
        }

        .answers-section h4 {
            color: var(--color-navy-950);
            margin-bottom: 12px;
        }

        .answer-item {
            background: var(--surface-secondary);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
        }

        .answer-item p {
            line-height: 1.6;
            margin: 0 0 8px;
        }

        .answer-meta {
            color: var(--text-secondary);
            font-size: 13px;
        }

        .answer-form {
            margin-top: 20px;
        }

        .answer-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .answer-form textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid var(--color-cyan-100);
            border-radius: 8px;
            font: inherit;
            resize: vertical;
        }

        .answer-form button {
            margin-top: 12px;
            padding: 11px 22px;
            border: none;
            border-radius: 8px;
            background: var(--color-blue-600);
            color: var(--text-on-brand);
            font: inherit;
            cursor: pointer;
        }

        .answer-form button:hover {
            opacity: 0.9;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            border: 1px dashed var(--color-blue-500);
            border-radius: 12px;
            color: var(--text-secondary);
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 18px;
            border-radius: 8px;
            background: var(--color-blue-600);
            color: var(--text-on-brand);
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .answer-container {
                margin: 20px auto;
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<main class="answer-container">

    <header class="page-header">
        <h1>Questions & Answers</h1>
        <p>View questions and share your knowledge with others.</p>
    </header>

    <?php if (isset($_GET['success'])): ?>
        <div class="message success">
            Your answer was submitted successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="message error">
            Unable to submit your answer. Please check your input.
        </div>
    <?php endif; ?>

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

                <div class="answers-section">

                    <h4>Answers</h4>

                    <?php
                    $answerQuery->execute([
                        'question_id' => $question['id']
                    ]);

                    $answers = $answerQuery->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (count($answers) > 0): ?>

                        <?php foreach ($answers as $answer): ?>

                            <div class="answer-item">
                                <p>
                                    <?= nl2br(e($answer['answer_text'])) ?>
                                </p>

                                <div class="answer-meta">
                                    Answered by User #<?= e($answer['user_id']) ?>
                                    |
                                    <?= e(date(
                                        'd M Y, h:i A',
                                        strtotime($answer['created_at'])
                                    )) ?>
                                </div>
                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p class="answer-meta">
                            No answers yet. Be the first to answer!
                        </p>

                    <?php endif; ?>

                </div>

                <form method="POST" action="answer.php" class="answer-form">

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
            <p>There are no questions to answer right now.</p>
        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="back-link">
        Back to Dashboard
    </a>

</main>

</body>
</html>
