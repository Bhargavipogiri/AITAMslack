
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once "includes/db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

$allowedStatuses = ["pending", "reviewed", "dismissed", "resolved"];

// Handle admin actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Invalid request.");
    }

    $reportId = filter_input(INPUT_POST, "report_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";
    $adminNote = trim($_POST["admin_note"] ?? "");

    $actionStatuses = [
        "review" => "reviewed",
        "dismiss" => "dismissed",
        "resolve" => "resolved"
    ];

    if ($reportId && isset($actionStatuses[$action])) {

        $newStatus = $actionStatuses[$action];

        $stmt = $pdo->prepare(
            "UPDATE reported_questions
             SET report_status = ?,
                 admin_note = ?,
                 reviewed_at = NOW()
             WHERE id = ?"
        );

        $stmt->execute([
            $newStatus,
            $adminNote,
            $reportId
        ]);
    }

    header("Location: reported-questions.php");
    exit;
}

// Search and filters
$search = trim($_GET["search"] ?? "");
$status = $_GET["status"] ?? "";

$sql = "
    SELECT
        rq.id AS report_id,
        rq.question_id,
        rq.reported_by,
        rq.reason,
        rq.report_description,
        rq.report_status,
        rq.admin_note,
        rq.reported_at,
        rq.reviewed_at,

        q.question_title,
        q.question_description,
        q.category,
        q.status AS question_status,
        q.created_at AS question_created_at,
        q.user_id AS question_author_id,

        reporter.name AS reporter_name,
        reporter.email AS reporter_email,

        author.name AS author_name,
        author.email AS author_email

    FROM reported_questions rq

    INNER JOIN questions q
        ON rq.question_id = q.id

    INNER JOIN users reporter
        ON rq.reported_by = reporter.id

    LEFT JOIN users author
        ON q.user_id = author.id

    WHERE 1=1
";

$params = [];

if ($search !== "") {
    $sql .= "
        AND (
            q.question_title LIKE ?
            OR reporter.name LIKE ?
            OR reporter.email LIKE ?
            OR rq.reason LIKE ?
            OR rq.report_description LIKE ?
        )
    ";

    $keyword = "%$search%";

    $params = array_merge($params, [
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword
    ]);
}

if (in_array($status, $allowedStatuses, true)) {
    $sql .= " AND rq.report_status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY rq.reported_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary counts
$totalReports = $pdo->query(
    "SELECT COUNT(*) FROM reported_questions"
)->fetchColumn();

$pendingReports = $pdo->query(
    "SELECT COUNT(*) FROM reported_questions
     WHERE report_status = 'pending'"
)->fetchColumn();

$reviewedReports = $pdo->query(
    "SELECT COUNT(*) FROM reported_questions
     WHERE report_status = 'reviewed'"
)->fetchColumn();

$dismissedReports = $pdo->query(
    "SELECT COUNT(*) FROM reported_questions
     WHERE report_status = 'dismissed'"
)->fetchColumn();

$resolvedReports = $pdo->query(
    "SELECT COUNT(*) FROM reported_questions
     WHERE report_status = 'resolved'"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reported Questions | AITAM Slack</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <!-- Project CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/reported-questions.css">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "includes/topbar.php"; ?>

    <main class="page-content reported-page">

        <!-- Page Heading -->
        <div class="page-heading reported-heading">
            <div>
                <h2>Reported Questions</h2>
                <p>Review questions reported by users and take appropriate action.</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="report-stats">

            <div class="report-stat-card">
                <div class="report-stat-icon total-icon">
                    <i class="bi bi-flag"></i>
                </div>
                <div>
                    <p>Total Reports</p>
                    <h3><?= e($totalReports) ?></h3>
                </div>
            </div>

            <div class="report-stat-card">
                <div class="report-stat-icon pending-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <p>Pending</p>
                    <h3><?= e($pendingReports) ?></h3>
                </div>
            </div>

            <div class="report-stat-card">
                <div class="report-stat-icon reviewed-icon">
                    <i class="bi bi-eye"></i>
                </div>
                <div>
                    <p>Reviewed</p>
                    <h3><?= e($reviewedReports) ?></h3>
                </div>
            </div>

            <div class="report-stat-card">
                <div class="report-stat-icon dismissed-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div>
                    <p>Dismissed</p>
                    <h3><?= e($dismissedReports) ?></h3>
                </div>
            </div>

            <div class="report-stat-card">
                <div class="report-stat-icon resolved-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <p>Resolved</p>
                    <h3><?= e($resolvedReports) ?></h3>
                </div>
            </div>

        </div>

        <!-- Reports Table -->
        <section class="reports-panel">

            <div class="reports-panel-header">
                <div>
                    <h4>Question Reports</h4>
                    <p>View and manage reports submitted by users.</p>
                </div>
            </div>

            <!-- Search and Filters -->
            <form method="GET" class="report-filter-bar">

                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="search"
                           name="search"
                           placeholder="Search questions or reporters..."
                           value="<?= e($search) ?>">
                </div>

                <select name="status">
                    <option value="">All Statuses</option>

                    <option value="pending"
                        <?= $status === "pending" ? "selected" : "" ?>>
                        Pending
                    </option>

                    <option value="reviewed"
                        <?= $status === "reviewed" ? "selected" : "" ?>>
                        Reviewed
                    </option>

                    <option value="dismissed"
                        <?= $status === "dismissed" ? "selected" : "" ?>>
                        Dismissed
                    </option>

                    <option value="resolved"
                        <?= $status === "resolved" ? "selected" : "" ?>>
                        Resolved
                    </option>
                </select>

                <button type="submit" class="filter-btn">
                    <i class="bi bi-funnel"></i>
                    Filter
                </button>

                <a href="reported-questions.php" class="reset-btn">
                    Reset
                </a>

            </form>

            <!-- Reports Table -->
            <div class="table-responsive">

                <table class="table reports-table">

                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Question</th>
                            <th>Reported By</th>
                            <th>Reason</th>
                            <th>Reported Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($reports) > 0): ?>

                        <?php foreach ($reports as $report): ?>

                        <tr>

                            <td>#<?= e($report["report_id"]) ?></td>

                            <td>
                                <div class="question-cell">
                                    <strong>
                                        <?= e($report["question_title"]) ?>
                                    </strong>
                                    <small>
                                        Question ID: #<?= e($report["question_id"]) ?>
                                    </small>
                                </div>
                            </td>

                            <td>
                                <div class="reporter-cell">
                                    <strong>
                                        <?= e($report["reporter_name"]) ?>
                                    </strong>
                                    <small>
                                        <?= e($report["reporter_email"]) ?>
                                    </small>
                                </div>
                            </td>

                            <td>
                                <?= e($report["reason"]) ?>
                            </td>

                            <td>
                                <?= e(date(
                                    "d M Y",
                                    strtotime($report["reported_at"])
                                )) ?>
                            </td>

                            <td>
                                <span class="report-status <?= e($report["report_status"]) ?>">
                                    <?= e(ucfirst($report["report_status"])) ?>
                                </span>
                            </td>

                            <td>
                                <div class="report-actions">

                                    <!-- View Report -->
                                    <button type="button"
                                            class="action-btn view-btn"
                                            title="View Report"
                                            data-bs-toggle="modal"
                                            data-bs-target="#reportModal"

                                            data-report-id="<?= e($report["report_id"]) ?>"
                                            data-question-id="<?= e($report["question_id"]) ?>"
                                            data-question-title="<?= e($report["question_title"]) ?>"
                                            data-question-description="<?= e($report["question_description"]) ?>"
                                            data-category="<?= e($report["category"]) ?>"
                                            data-author="<?= e($report["author_name"]) ?>"
                                            data-author-email="<?= e($report["author_email"]) ?>"
                                            data-reporter="<?= e($report["reporter_name"]) ?>"
                                            data-reporter-email="<?= e($report["reporter_email"]) ?>"
                                            data-reason="<?= e($report["reason"]) ?>"
                                            data-description="<?= e($report["report_description"]) ?>"
                                            data-status="<?= e($report["report_status"]) ?>"
                                            data-admin-note="<?= e($report["admin_note"]) ?>"
                                            data-reported-at="<?= e($report["reported_at"]) ?>">

                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Review Report -->
                                    <?php if ($report["report_status"] === "pending"): ?>

                                        <form method="POST">
                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e($_SESSION["csrf_token"]) ?>">

                                            <input type="hidden"
                                                   name="report_id"
                                                   value="<?= e($report["report_id"]) ?>">

                                            <input type="hidden"
                                                   name="action"
                                                   value="review">

                                            <button type="submit"
                                                    class="action-btn review-btn"
                                                    title="Mark as Reviewed">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        </form>

                                    <?php endif; ?>

                                    <!-- Dismiss Report -->
                                    <?php if (!in_array($report["report_status"], ["dismissed", "resolved"], true)): ?>

                                        <form method="POST"
                                              onsubmit="return confirm('Are you sure you want to dismiss this report?');">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e($_SESSION["csrf_token"]) ?>">

                                            <input type="hidden"
                                                   name="report_id"
                                                   value="<?= e($report["report_id"]) ?>">

                                            <input type="hidden"
                                                   name="action"
                                                   value="dismiss">

                                            <button type="submit"
                                                    class="action-btn dismiss-btn"
                                                    title="Dismiss Report">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>

                                    <?php endif; ?>

                                    <!-- Resolve Report -->
                                    <?php if (!in_array($report["report_status"], ["dismissed", "resolved"], true)): ?>

                                        <form method="POST"
                                              onsubmit="return confirm('Mark this report as resolved?');">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e($_SESSION["csrf_token"]) ?>">

                                            <input type="hidden"
                                                   name="report_id"
                                                   value="<?= e($report["report_id"]) ?>">

                                            <input type="hidden"
                                                   name="action"
                                                   value="resolve">

                                            <button type="submit"
                                                    class="action-btn resolve-btn"
                                                    title="Mark as Resolved">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>

                                    <?php endif; ?>

                                </div>
                            </td>

                        </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>No reported questions found.</p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>
</div>

<!-- Report Details Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Reported Question Details</h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">

                <div class="detail-section">
                    <h6>Question Details</h6>

                    <p><strong>Question ID:</strong>
                        <span id="modalQuestionId"></span>
                    </p>

                    <p><strong>Title:</strong>
                        <span id="modalQuestionTitle"></span>
                    </p>

                    <p><strong>Description:</strong></p>
                    <p class="detail-description" id="modalQuestionDescription"></p>

                    <p><strong>Category:</strong>
                        <span id="modalCategory"></span>
                    </p>

                    <p><strong>Posted By:</strong>
                        <span id="modalAuthor"></span>
                    </p>

                    <p><strong>Author Email:</strong>
                        <span id="modalAuthorEmail"></span>
                    </p>
                </div>

                <hr>

                <div class="detail-section">
                    <h6>Report Details</h6>

                    <p><strong>Report ID:</strong>
                        <span id="modalReportId"></span>
                    </p>

                    <p><strong>Reported By:</strong>
                        <span id="modalReporter"></span>
                    </p>

                    <p><strong>Reporter Email:</strong>
                        <span id="modalReporterEmail"></span>
                    </p>

                    <p><strong>Reason:</strong>
                        <span id="modalReason"></span>
                    </p>

                    <p><strong>Additional Description:</strong></p>
                    <p class="detail-description" id="modalReportDescription"></p>

                    <p><strong>Reported At:</strong>
                        <span id="modalReportedAt"></span>
                    </p>

                    <p><strong>Report Status:</strong>
                        <span id="modalStatus"></span>
                    </p>

                    <p><strong>Admin Note:</strong></p>
                    <p class="detail-description" id="modalAdminNote"></p>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>

    </div>

</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Project JavaScript -->
<script src="../assets/js/admin.js"></script>
<script src="../assets/js/reported-questions.js"></script>

</body>
</html>
