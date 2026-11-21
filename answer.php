
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'leader-db.php';

// Escape output
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$databaseError = '';

// Submit answer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $questionId = filter_var(
        $_POST['question_id'] ?? '',
        FILTER_VALIDATE_INT
    );

    $answerText = trim($_POST['answer_text'] ?? '');

    if (!$questionId || $answerText === '') {
        $redirectId = $questionId ? $questionId : '';
        header("Location: answer.php"
            . ($redirectId ? "?question_id=" . $redirectId . "&error=invalid" : "?error=invalid"));
        exit();
    }

    try {

        // Check whether the question exists
        $check = $conn->prepare(
            "SELECT id FROM questions WHERE id = :id"
        );

        $check->execute(['id' => $questionId]);

        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            header("Location: answer.php?question_id=" . $questionId . "&error=notfound");
            exit();
        }

        $userId = 0;
        if (!empty($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT)) {
            $userId = (int)$_SESSION['user_id'];
        }

        $sql = "INSERT INTO answers
                (question_id, user_id, answer_text)
                VALUES (:question_id, :user_id, :answer_text)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            'question_id' => $questionId,
            'user_id' => $userId,
            'answer_text' => $answerText
        ]);

        // Update question status
        $update = $conn->prepare(
            "UPDATE questions
             SET status = 'Answered'
             WHERE id = :id"
        );

        $update->execute(['id' => $questionId]);

        // Redirect to the same question after submission
        header("Location: answer.php?question_id=" . $questionId . "&success=1");
        exit();

    } catch (PDOException $ex) {

        header("Location: answer.php?question_id=" . $questionId . "&error=database");
        exit();
    }
}

// Fetch questions
$questions = [];
$answerQuery = null;

try {

    // Display selected question if question_id is provided
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
                WHERE id = :id
                ORDER BY created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $selectedQuestionId]);

    } else {

        // Otherwise display all questions
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

    // Fetch answers belonging to each question
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
            overflow-wrap: anywhere;
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
            background: var(--gradient-brand);
            color: var(--text-on-brand);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-fast);
        }

        .answer-form button:hover {
            background: var(--gradient-accent);
            color: var(--color-navy-950);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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

        .error,
        .database-error {
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

<?php include 'nav.php'; ?>

<main class="answer-container">

    <header class="page-header">

        <h1>Questions & Answers</h1>

        <p>
            View questions and share your knowledge with others.
        </p>

    </header>

    <!-- Success message -->
    <?php if (isset($_GET['success'])): ?>

        <div class="message success">
            Your answer was submitted successfully!
        </div>

    <?php endif; ?>

    <!-- Error message -->
    <?php if (isset($_GET['error'])): ?>

        <div class="message error">

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

    <!-- Database error -->
    <?php if ($databaseError !== ''): ?>

        <div class="message database-error" role="alert">
            <?= e($databaseError) ?>
        </div>

    <?php endif; ?>


    <!-- Display questions -->
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


                <!-- Display answers -->
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

                                    <?php
                                    $answerDate = strtotime(
                                        (string)($answer['created_at'] ?? '')
                                    );
                                    ?>

                                    |
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


                <!-- Answer submission form -->
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


    <a href="question-history.php" class="back-link">
        View Questions
    </a>

</main>

<?php include 'footer.php'; ?>

</body>
</html>
