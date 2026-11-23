
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once 'includes/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$allowedStatuses = ['Published', 'Pending', 'Hidden'];

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

/* UPDATE ANSWER STATUS */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    $token = $_POST['csrf_token'] ?? '';
    $answerId = filter_var($_POST['answer_id'] ?? null, FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['flash_message'] = 'Invalid request. Please try again.';
    } elseif (!$answerId || !in_array($newStatus, $allowedStatuses, true)) {
        $_SESSION['flash_message'] = 'Invalid answer details.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE answers
            SET status = :status
            WHERE id = :id
        ");

        $stmt->execute([
            ':status' => $newStatus,
            ':id' => $answerId
        ]);

        $_SESSION['flash_message'] = 'Answer status updated successfully.';
    }

    $redirectParams = [];

    if ($search !== '') {
        $redirectParams['search'] = $search;
    }

    if ($status !== '') {
        $redirectParams['status'] = $status;
    }

    $redirectUrl = 'answers.php';

    if (!empty($redirectParams)) {
        $redirectUrl .= '?' . http_build_query($redirectParams);
    }

    header("Location: " . $redirectUrl);
    exit;
}

/* FETCH ANSWER STATISTICS */

$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_answers,
        SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) AS published_answers,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_answers,
        SUM(CASE WHEN status = 'Hidden' THEN 1 ELSE 0 END) AS hidden_answers
    FROM answers
");

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalAnswers = (int)$stats['total_answers'];
$publishedAnswers = (int)$stats['published_answers'];
$pendingAnswers = (int)$stats['pending_answers'];
$hiddenAnswers = (int)$stats['hidden_answers'];

/* FETCH ANSWERS */

$sql = "
    SELECT
        a.id,
        a.answer_text,
        a.question_id,
        a.user_id,
        a.status,
        a.created_at,
        q.question_title
    FROM answers a
    LEFT JOIN questions q
        ON a.question_id = q.id
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            a.answer_text LIKE :search1
            OR q.question_title LIKE :search2
            OR CAST(a.user_id AS CHAR) LIKE :search3
            OR CAST(a.id AS CHAR) LIKE :search4
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[':search1'] = $searchValue;
    $params[':search2'] = $searchValue;
    $params[':search3'] = $searchValue;
    $params[':search4'] = $searchValue;
}

if ($status !== '') {
    $sql .= " AND a.status = :status";
    $params[':status'] = $status;
}

$sql .= " ORDER BY a.created_at DESC";

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

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/answers.css">

</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <?php include 'includes/topbar.php'; ?>

        <main class="page-content answers-page">

            <div class="page-heading">

                <h2>Answer Management</h2>

                <p>
                    Monitor, review, and manage answers submitted by users.
                </p>

            </div>

            <?php if ($message !== ''): ?>

                <div class="alert alert-info alert-dismissible fade show" role="alert">

                    <?= e($message) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- STATISTICS CARDS -->

            <div class="answer-stats">

                <div class="answer-card">

                    <div class="answer-icon total-icon">
                        <i class="bi bi-chat-left-text"></i>
                    </div>

                    <div class="answer-details">
                        <p>Total Answers</p>
                        <h3><?= $totalAnswers ?></h3>
                    </div>

                </div>


                <div class="answer-card">

                    <div class="answer-icon published-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <div class="answer-details">
                        <p>Published Answers</p>
                        <h3><?= $publishedAnswers ?></h3>
                    </div>

                </div>


                <div class="answer-card">

                    <div class="answer-icon pending-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>

                    <div class="answer-details">
                        <p>Pending Answers</p>
                        <h3><?= $pendingAnswers ?></h3>
                    </div>

                </div>


                <div class="answer-card">

                    <div class="answer-icon hidden-icon">
                        <i class="bi bi-eye-slash"></i>
                    </div>

                    <div class="answer-details">
                        <p>Hidden Answers</p>
                        <h3><?= $hiddenAnswers ?></h3>
                    </div>

                </div>

            </div>


            <!-- ANSWERS TABLE PANEL -->

            <div class="answers-panel">

                <div class="panel-heading">

                    <div>

                        <h4>All Answers</h4>

                        <p>
                            Monitor and manage user-submitted answers.
                        </p>

                    </div>

                </div>


                <!-- SEARCH AND FILTER -->

                <form method="GET" class="answer-filters">

                    <div class="search-box">

                        <i class="bi bi-search"></i>

                        <input
                            type="text"
                            name="search"
                            placeholder="Search answers, questions, or user ID..."
                            value="<?= e($search) ?>"
                        >

                    </div>


                    <select name="status">

                        <option value="">All Statuses</option>

                        <?php foreach ($allowedStatuses as $item): ?>

                            <option
                                value="<?= e($item) ?>"
                                <?= $status === $item ? 'selected' : '' ?>
                            >
                                <?= e($item) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>


                    <button type="submit" class="filter-btn">

                        <i class="bi bi-funnel"></i>
                        Filter

                    </button>


                    <a href="answers.php" class="reset-btn">
                        Reset
                    </a>

                </form>


                <!-- ANSWERS TABLE -->

                <div class="table-responsive">

                    <table class="table answers-table">

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


                        <tbody>

                            <?php if (!empty($answers)): ?>

                                <?php foreach ($answers as $answer): ?>

                                    <tr>

                                        <td>
                                            #<?= e($answer['id']) ?>
                                        </td>


                                        <td class="answer-content">
                                            <?= e($answer['answer_text']) ?>
                                        </td>


                                        <td>
                                            <?= e($answer['question_title'] ?? 'Question unavailable') ?>
                                        </td>


                                        <td>
                                            <?= e($answer['user_id']) ?>
                                        </td>


                                        <td>

                                            <span class="status-badge <?= strtolower(e($answer['status'])) ?>">
                                                <?= e($answer['status']) ?>
                                            </span>

                                        </td>


                                        <td>
                                            <?= e($answer['created_at']) ?>
                                        </td>


                                        <td>

                                            <form method="POST" class="status-form">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="answer_id"
                                                    value="<?= e($answer['id']) ?>"
                                                >

                                                <select
                                                    name="status"
                                                    class="form-select form-select-sm"
                                                >

                                                    <?php foreach ($allowedStatuses as $item): ?>

                                                        <option
                                                            value="<?= e($item) ?>"
                                                            <?= $answer['status'] === $item ? 'selected' : '' ?>
                                                        >
                                                            <?= e($item) ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>


                                                <button
                                                    type="submit"
                                                    name="update_status"
                                                    class="update-btn"
                                                >
                                                    Update
                                                </button>

                                            </form>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="7" class="no-data">

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


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/answers.js"></script>

</body>

</html>
