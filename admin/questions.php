
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Update question status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid request. Please refresh the page.");
    }

    $questionId = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';

    $allowedStatuses = ['Pending', 'Answered', 'Closed'];

    if ($questionId && in_array($newStatus, $allowedStatuses, true)) {

        $updateSql = "
            UPDATE questions
            SET status = :status
            WHERE id = :id
        ";

        $updateStmt = $pdo->prepare($updateSql);

        $updateStmt->execute([
            ':status' => $newStatus,
            ':id' => $questionId
        ]);
    }

    header("Location: questions.php");
    exit;
}

// Search and filter values
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$status = trim($_GET['status'] ?? '');

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? $page : 1;

$perPage = 20;

$allowedStatuses = ['Pending', 'Answered', 'Closed'];

// Statistics
$totalQuestions = $pdo->query("
    SELECT COUNT(*) FROM questions
")->fetchColumn();

$pendingQuestions = $pdo->query("
    SELECT COUNT(*) FROM questions
    WHERE status = 'Pending'
")->fetchColumn();

$answeredQuestions = $pdo->query("
    SELECT COUNT(*) FROM questions
    WHERE status = 'Answered'
")->fetchColumn();

$closedQuestions = $pdo->query("
    SELECT COUNT(*) FROM questions
    WHERE status = 'Closed'
")->fetchColumn();

// Categories
$categoryStmt = $pdo->query("
    SELECT DISTINCT category
    FROM questions
    WHERE category IS NOT NULL AND category != ''
    ORDER BY category
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch questions
$sql = "
    SELECT
        q.id,
        q.user_id,
        q.question_title,
        q.question_description,
        q.category,
        q.status,
        q.created_at,
        u.full_name AS author_name,
        u.email AS author_email

    FROM questions q

    LEFT JOIN users u
        ON u.user_id = q.user_id

    WHERE 1=1
";

$params = [];

// Search
if ($search !== '') {

    $sql .= "
        AND (
            q.question_title LIKE :search1
            OR q.question_description LIKE :search2
        )
    ";

    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
}

// Category filter
if ($category !== '') {

    $sql .= " AND q.category = :category";

    $params[':category'] = $category;
}

// Status filter
if (in_array($status, $allowedStatuses, true)) {

    $sql .= " AND q.status = :status";

    $params[':status'] = $status;
}

// Count filtered questions
$countSql = "
    SELECT COUNT(*)
    FROM questions q
    WHERE 1=1
";

$countParams = [];

if ($search !== '') {

    $countSql .= "
        AND (
            q.question_title LIKE :search1
            OR q.question_description LIKE :search2
        )
    ";

    $countParams[':search1'] = "%$search%";
    $countParams[':search2'] = "%$search%";
}

if ($category !== '') {

    $countSql .= " AND q.category = :category";

    $countParams[':category'] = $category;
}

if (in_array($status, $allowedStatuses, true)) {

    $countSql .= " AND q.status = :status";

    $countParams[':status'] = $status;
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);

$totalFilteredQuestions = (int)$countStmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalFilteredQuestions / $perPage)
);

$page = min($page, $totalPages);

// Fetch paginated questions
$offset = ($page - 1) * $perPage;

$sql .= " ORDER BY q.created_at DESC";
$sql .= " LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>AITAM Slack | Question Management</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Project CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/questions.css">

</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <?php include 'includes/topbar.php'; ?>

    <main class="page-content questions-page">

        <!-- Page Heading -->
        <div class="page-heading">

            <h2>Question Management</h2>

            <p>Manage and monitor all questions submitted by users.</p>

        </div>

        <!-- Statistics -->
        <div class="question-stats">

            <div class="question-card">

                <i class="bi bi-question-circle"></i>

                <p>Total Questions</p>

                <h3><?= e($totalQuestions) ?></h3>

            </div>

            <div class="question-card">

                <i class="bi bi-hourglass-split"></i>

                <p>Pending Questions</p>

                <h3><?= e($pendingQuestions) ?></h3>

            </div>

            <div class="question-card">

                <i class="bi bi-check-circle"></i>

                <p>Answered Questions</p>

                <h3><?= e($answeredQuestions) ?></h3>

            </div>

            <div class="question-card">

                <i class="bi bi-x-circle"></i>

                <p>Closed Questions</p>

                <h3><?= e($closedQuestions) ?></h3>

            </div>

        </div>

        <!-- Questions Panel -->
        <div class="questions-panel">

            <div class="panel-heading">

                <h4>All Questions</h4>

                <p>View, search and update submitted questions.</p>

            </div>

            <!-- Search and Filters -->
            <form method="GET" class="question-filters">

                <input
                    type="text"
                    name="search"
                    placeholder="Search questions..."
                    value="<?= e($search) ?>"
                >

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

                <button type="submit">
                    <i class="bi bi-search"></i> Search
                </button>

                <a href="questions.php">Reset</a>

            </form>

            <!-- Questions Table -->
            <div class="table-responsive">

                <table class="table questions-table">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Question</th>
                            <th>User ID</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Update Status</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (!empty($questions)): ?>

                        <?php foreach ($questions as $question): ?>

                            <tr>

                                <td>
                                    <?= e($question['id']) ?>
                                </td>

                                <td>

                                    <strong>
                                        <?= e($question['question_title']) ?>
                                    </strong>

                                    <div class="small text-muted">
                                        <?= e($question['question_description']) ?>
                                    </div>

                                    <?php if (!empty($question['author_name'])): ?>
                                        <div class="small text-muted">
                                            By: <?= e($question['author_name']) ?>
                                        </div>
                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= e($question['user_id']) ?>
                                </td>

                                <td>
                                    <?= e($question['category'] ?? 'General') ?>
                                </td>

                                <td>

                                    <span class="question-status">
                                        <?= e($question['status']) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= e($question['created_at']) ?>
                                </td>

                                <td>

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="question_id"
                                            value="<?= e($question['id']) ?>"
                                        >

                                        <select
                                            name="status"
                                            class="form-select form-select-sm mb-1"
                                        >

                                            <?php foreach ($allowedStatuses as $item): ?>

                                                <option
                                                    value="<?= e($item) ?>"
                                                    <?= $question['status'] === $item ? 'selected' : '' ?>
                                                >
                                                    <?= e($item) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                        <button
                                            type="submit"
                                            name="update_status"
                                            class="btn btn-sm btn-primary"
                                        >
                                            Update
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="text-center">
                                No questions found.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>

                <div class="pagination-container">

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                        <a
                            href="?<?= e(http_build_query([
                                'search' => $search,
                                'category' => $category,
                                'status' => $status,
                                'page' => $i
                            ])) ?>"
                            class="<?= $page === $i ? 'active' : '' ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Project JavaScript -->
<script src="../assets/js/admin.js"></script>

</body>
</html>
