
<?php

require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

/* -------------------------------------------------
   HELPER FUNCTION
------------------------------------------------- */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string)($value ?? ''),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}

/* -------------------------------------------------
   SEARCH AND FILTER
------------------------------------------------- */

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

/* -------------------------------------------------
   FLASH MESSAGE
------------------------------------------------- */

$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

/* -------------------------------------------------
   FETCH CATEGORIES
------------------------------------------------- */

$categoryStmt = $pdo->query("
    SELECT DISTINCT category
    FROM questions
    WHERE category IS NOT NULL
      AND category != ''
    ORDER BY category ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array($category, $categories, true)) {
    $category = '';
}

/* -------------------------------------------------
   ANSWER STATISTICS
------------------------------------------------- */

$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_answers,
        COUNT(DISTINCT question_id) AS answered_questions,
        COUNT(DISTINCT user_id) AS total_contributors,
        COALESCE(
            SUM(
                CASE
                    WHEN DATE(created_at) = CURDATE()
                    THEN 1
                    ELSE 0
                END
            ), 0
        ) AS answers_today
    FROM answers
");

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalAnswers = (int)($stats['total_answers'] ?? 0);
$answeredQuestions = (int)($stats['answered_questions'] ?? 0);
$totalContributors = (int)($stats['total_contributors'] ?? 0);
$answersToday = (int)($stats['answers_today'] ?? 0);

/* -------------------------------------------------
   FETCH ANSWERS
------------------------------------------------- */

$sql = "
    SELECT
        a.id,
        a.answer_text,
        a.question_id,
        a.user_id,
        a.created_at,

        u.full_name,
        u.email,

        q.question_title,
        q.category

    FROM answers a

    LEFT JOIN questions q
        ON q.id = a.question_id

    LEFT JOIN users u
        ON u.user_id = a.user_id

    WHERE 1 = 1
";

$params = [];

/* SEARCH */

if ($search !== '') {

    $sql .= "
        AND (
            a.answer_text LIKE :search1
            OR q.question_title LIKE :search2
            OR u.full_name LIKE :search3
            OR u.email LIKE :search4
            OR CAST(a.user_id AS CHAR) LIKE :search5
            OR CAST(a.id AS CHAR) LIKE :search6
        )
    ";

    $keyword = '%' . $search . '%';

    $params[':search1'] = $keyword;
    $params[':search2'] = $keyword;
    $params[':search3'] = $keyword;
    $params[':search4'] = $keyword;
    $params[':search5'] = $keyword;
    $params[':search6'] = $keyword;
}

/* CATEGORY FILTER */

if ($category !== '') {

    $sql .= " AND q.category = :category";

    $params[':category'] = $category;
}

/* ORDER */

$sql .= " ORDER BY a.created_at DESC";

/* EXECUTE */

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AITAM Slack | Answer Management</title>

    <!-- Bootstrap CSS -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Existing Project CSS -->

    <link rel="stylesheet" href="../assets/css/root.css">

    <link rel="stylesheet" href="../assets/css/admin.css">

    <link rel="stylesheet" href="../assets/css/answers.css">

</head>

<body>

<!-- SIDEBAR: KEEP EXISTING SIDEBAR -->

<?php include 'includes/sidebar.php'; ?>


<!-- MAIN CONTENT: SAME STRUCTURE AS questions.php -->

<div class="main-content">

    <!-- TOP NAVBAR: KEEP EXISTING TOPBAR -->

    <?php include 'includes/topbar.php'; ?>


    <!-- PAGE CONTENT -->

    <main class="page-content answers-page">


        <!-- PAGE HEADING -->

        <div class="page-heading">

            <h2>Answer Management</h2>

            <p>
                Manage and monitor all answers submitted by users.
            </p>

        </div>


        <!-- FLASH MESSAGE -->

        <?php if ($message !== ''): ?>

            <div class="alert alert-info alert-dismissible fade show">

                <?= e($message) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>

            </div>

        <?php endif; ?>


        <!-- STATISTICS -->

        <div class="answer-stats">


            <!-- TOTAL ANSWERS -->

            <div class="answer-card">

                <div class="answer-icon total-icon">

                    <i class="bi bi-chat-left-text"></i>

                </div>

                <div class="answer-details">

                    <p>Total Answers</p>

                    <h3><?= e($totalAnswers) ?></h3>

                </div>

            </div>


            <!-- ANSWERED QUESTIONS -->

            <div class="answer-card">

                <div class="answer-icon published-icon">

                    <i class="bi bi-check-circle"></i>

                </div>

                <div class="answer-details">

                    <p>Questions with Answers</p>

                    <h3><?= e($answeredQuestions) ?></h3>

                </div>

            </div>


            <!-- CONTRIBUTORS -->

            <div class="answer-card">

                <div class="answer-icon pending-icon">

                    <i class="bi bi-people"></i>

                </div>

                <div class="answer-details">

                    <p>Answer Contributors</p>

                    <h3><?= e($totalContributors) ?></h3>

                </div>

            </div>


            <!-- ANSWERS TODAY -->

            <div class="answer-card">

                <div class="answer-icon hidden-icon">

                    <i class="bi bi-calendar-check"></i>

                </div>

                <div class="answer-details">

                    <p>Answers Today</p>

                    <h3><?= e($answersToday) ?></h3>

                </div>

            </div>


        </div>


        <!-- ANSWERS TABLE PANEL -->

        <div class="answers-panel">


            <!-- PANEL HEADING -->

            <div class="panel-heading">

                <h4>All Answers</h4>

                <p>
                    View and monitor answers submitted by users.
                </p>

            </div>


            <!-- SEARCH AND FILTER -->

            <form method="GET" class="answer-filters">


                <!-- SEARCH BOX -->

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search answers, questions or users..."
                        value="<?= e($search) ?>"
                    >

                </div>


                <!-- CATEGORY -->

                <select name="category">

                    <option value="">All Categories</option>

                    <?php foreach ($categories as $cat): ?>

                        <option
                            value="<?= e($cat) ?>"
                            <?= $category === $cat ? 'selected' : '' ?>
                        >

                            <?= e($cat) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <!-- FILTER BUTTON -->

                <button type="submit" class="filter-btn">

                    <i class="bi bi-funnel"></i>

                    Filter

                </button>


                <!-- RESET BUTTON -->

                <a href="answers.php" class="reset-btn">

                    Reset

                </a>


            </form>


            <!-- ANSWERS TABLE -->

            <div class="table-responsive">

                <table class="table answers-table">


                    <!-- TABLE HEADER -->

                    <thead>

                        <tr>

                            <th>Answer ID</th>

                            <th>Answer Content</th>

                            <th>Question</th>

                            <th>User ID</th>

                            <th>Status</th>

                            <th>Submitted Date</th>

                            <th>Manage</th>

                        </tr>

                    </thead>


                    <!-- TABLE BODY -->

                    <tbody>


                        <?php if (!empty($answers)): ?>


                            <?php foreach ($answers as $answer): ?>


                                <tr>


                                    <!-- ANSWER ID -->

                                    <td>

                                        #<?= e($answer['id']) ?>

                                    </td>


                                    <!-- ANSWER CONTENT -->

                                    <td class="answer-content">

                                        <?= e($answer['answer_text']) ?>

                                    </td>


                                    <!-- QUESTION TITLE -->

                                    <td>

                                        <?= e(
                                            $answer['question_title']
                                            ?? 'Question unavailable'
                                        ) ?>

                                    </td>


                                    <!-- USER INFORMATION -->

                                    <td>

                                        <strong>
                                            <?= e($answer['user_id']) ?>
                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            <?= e(
                                                $answer['full_name']
                                                ?? 'User unavailable'
                                            ) ?>

                                        </small>

                                    </td>


                                    <!-- SUBMISSION INFORMATION -->

                                    <td>

                                        <span class="status-badge submitted">

                                            Submitted

                                        </span>

                                    </td>


                                    <!-- SUBMITTED DATE -->

                                    <td>

                                        <?php if (!empty($answer['created_at'])): ?>

                                            <?= e(
                                                date(
                                                    'd M Y, h:i A',
                                                    strtotime($answer['created_at'])
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            Not available

                                        <?php endif; ?>

                                    </td>


                                    <!-- MANAGE -->

                                    <td>

                                        <span class="text-muted">—</span>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td colspan="7" class="no-data text-center">

                                    <i class="bi bi-inbox"></i>

                                    <p>No answers found.</p>

                                </td>

                            </tr>


                        <?php endif; ?>


                    </tbody>

                </table>

            </div>


        </div>


    </main>

</div>


<!-- JAVASCRIPT -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/admin.js"></script>

<script src="../assets/js/answers.js"></script>


</body>

</html>
