
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Escape output
function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Handle publish and hide actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        empty($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")
    ) {
        die("Invalid request.");
    }

    $questionId = filter_input(INPUT_POST, "question_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if ($questionId && in_array($action, ["publish", "hide"], true)) {

        $newStatus = ($action === "publish") ? "Published" : "Hidden";

        $stmt = $pdo->prepare("
            UPDATE questions
            SET status = ?
            WHERE id = ?
        ");

        $stmt->execute([$newStatus, $questionId]);
    }

    header("Location: questions.php");
    exit;
}

// Search and filters
$search = trim($_GET["search"] ?? "");
$status = $_GET["status"] ?? "";

$page = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$perPage = 20;

// Available statuses
$statuses = ["Pending", "Published", "Hidden"];

// Build search query
$countSql = "SELECT COUNT(*) FROM questions WHERE 1=1";
$sql = "SELECT * FROM questions WHERE 1=1";

$params = [];

if ($search !== "") {

    $searchCondition = "
        AND (
            question_title LIKE ?
            OR question_description LIKE ?
            OR category LIKE ?
            OR CAST(user_id AS CHAR) LIKE ?
        )
    ";

    $sql .= $searchCondition;
    $countSql .= $searchCondition;

    $keyword = "%" . $search . "%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}

// Status filter
if ($status !== "" && in_array($status, $statuses, true)) {

    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";

    $params[] = $status;
}

// Get filtered question count
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);

$totalFilteredQuestions = (int)$countStmt->fetchColumn();

// Pagination
$totalPages = max(1, (int)ceil($totalFilteredQuestions / $perPage));
$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

// Get questions
$sql .= " ORDER BY id DESC";
$sql .= " LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dashboard statistics
$totalQuestions = (int)$pdo->query("
    SELECT COUNT(*) FROM questions
")->fetchColumn();

$pendingQuestions = (int)$pdo->query("
    SELECT COUNT(*) FROM questions
    WHERE status = 'Pending'
")->fetchColumn();

$publishedQuestions = (int)$pdo->query("
    SELECT COUNT(*) FROM questions
    WHERE status = 'Published'
")->fetchColumn();

$hiddenQuestions = (int)$pdo->query("
    SELECT COUNT(*) FROM questions
    WHERE status = 'Hidden'
")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Question Management | AITAM Slack</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <!-- Project CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/questions.css?v=1">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "includes/topbar.php"; ?>

    <main class="page-content questions-page">

        <!-- Page Heading -->
        <div class="page-heading">

            <h2>Question Management</h2>

            <p>View and manage questions submitted by users</p>

        </div>

        <!-- Summary Cards -->
        <div class="question-stats">

            <div class="question-card">

                <i class="bi bi-chat-square-text"></i>

                <div>
                    <p>Total Questions</p>
                    <h3><?= e($totalQuestions) ?></h3>
                </div>

            </div>

            <div class="question-card">

                <i class="bi bi-clock-history"></i>

                <div>
                    <p>Pending Questions</p>
                    <h3><?= e($pendingQuestions) ?></h3>
                </div>

            </div>

            <div class="question-card">

                <i class="bi bi-check-circle"></i>

                <div>
                    <p>Published Questions</p>
                    <h3><?= e($publishedQuestions) ?></h3>
                </div>

            </div>

            <div class="question-card">

                <i class="bi bi-eye-slash"></i>

                <div>
                    <p>Hidden Questions</p>
                    <h3><?= e($hiddenQuestions) ?></h3>
                </div>

            </div>

        </div>

        <!-- Questions Panel -->
        <section class="questions-panel">

            <div class="panel-heading">

                <h4>Submitted Questions</h4>

                <p>Manage questions submitted by registered users</p>

            </div>

            <!-- Search and Filters -->
            <form method="GET" class="question-filters">

                <input
                    type="search"
                    name="search"
                    placeholder="Search questions..."
                    value="<?= e($search) ?>"
                >

                <select name="status">

                    <option value="">All Status</option>

                    <?php foreach ($statuses as $availableStatus): ?>

                        <option
                            value="<?= e($availableStatus) ?>"
                            <?= $status === $availableStatus ? "selected" : "" ?>
                        >
                            <?= e($availableStatus) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button type="submit">
                    Search
                </button>

                <a href="questions.php">
                    Reset
                </a>

            </form>

            <!-- Questions Table -->
            <div class="table-responsive">

                <table class="table questions-table">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Question Title</th>
                            <th>Category</th>
                            <th>User ID</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($questions) > 0): ?>

                        <?php foreach ($questions as $question): ?>

                            <tr>

                                <td>
                                    <?= e($question["id"]) ?>
                                </td>

                                <td>

                                    <div class="question-title">
                                        <?= e($question["question_title"]) ?>
                                    </div>

                                    <details class="question-details">

                                        <summary>
                                            View Description
                                        </summary>

                                        <p>
                                            <?= nl2br(e($question["question_description"])) ?>
                                        </p>

                                    </details>

                                </td>

                                <td>

                                    <span class="category-label">
                                        <?= e($question["category"]) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= e($question["user_id"]) ?>
                                </td>

                                <td>

                                    <?php
                                    $currentStatus = strtolower($question["status"] ?? "");
                                    ?>

                                    <span class="question-status <?= e($currentStatus) ?>">
                                        <?= e($question["status"]) ?>
                                    </span>

                                </td>

                                <td>

                                    <?= !empty($question["created_at"])
                                        ? e(date("d M Y", strtotime($question["created_at"])))
                                        : "N/A" ?>

                                </td>

                                <td>

                                    <div class="question-actions">

                                        <?php if ($currentStatus !== "published"): ?>

                                            <!-- Publish Question -->
                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION["csrf_token"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="question_id"
                                                    value="<?= e($question["id"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="publish"
                                                >

                                                <button
                                                    type="submit"
                                                    class="publish-btn"
                                                    title="Publish Question"
                                                    onclick="return confirm('Are you sure you want to publish this question?')"
                                                >
                                                    <i class="bi bi-check-circle"></i>
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                        <?php if ($currentStatus !== "hidden"): ?>

                                            <!-- Hide Question -->
                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION["csrf_token"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="question_id"
                                                    value="<?= e($question["id"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="hide"
                                                >

                                                <button
                                                    type="submit"
                                                    class="hide-btn"
                                                    title="Hide Question"
                                                    onclick="return confirm('Are you sure you want to hide this question?')"
                                                >
                                                    <i class="bi bi-eye-slash"></i>
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

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
                                "search" => $search,
                                "status" => $status,
                                "page" => $i
                            ])) ?>"
                            class="<?= $page === $i ? "active" : "" ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Project JavaScript -->
<script src="../assets/js/admin.js"></script>

</body>
</html>
