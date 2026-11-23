
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        die("Invalid request.");
    }

    $reportId = filter_input(INPUT_POST, "report_id", FILTER_VALIDATE_INT);
    $status = $_POST["report_status"] ?? "";
    $adminNote = trim($_POST["admin_note"] ?? "");

    if ($reportId && in_array($status, $allowedStatuses, true)) {
        $sql = "UPDATE reported_answers
                SET report_status = :status,
                    admin_note = :admin_note,
                    reviewed_at = NOW()
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":status" => $status,
            ":admin_note" => $adminNote,
            ":id" => $reportId
        ]);

        $_SESSION["report_message"] = "Report updated successfully.";
    } else {
        $_SESSION["report_message"] = "Invalid report details.";
    }

    header("Location: reported-answers.php");
    exit;
}

$message = $_SESSION["report_message"] ?? "";
unset($_SESSION["report_message"]);

$search = trim($_GET["search"] ?? "");
$statusFilter = $_GET["status"] ?? "";

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "";
}

/* Summary counts */

$summary = [];

foreach ($allowedStatuses as $status) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM reported_answers WHERE report_status = ?"
    );
    $stmt->execute([$status]);
    $summary[$status] = (int)$stmt->fetchColumn();
}

$stmt = $pdo->query("SELECT COUNT(*) FROM reported_answers");
$totalReports = (int)$stmt->fetchColumn();

/* Fetch reports */

$sql = "SELECT ra.*,
               reporter.name AS reporter_name,
               reporter.email AS reporter_email
        FROM reported_answers ra
        LEFT JOIN users reporter ON reporter.id = ra.reported_by
        WHERE 1=1";

$params = [];

if ($search !== "") {
    $sql .= " AND (
        CAST(ra.id AS CHAR) LIKE :search1
        OR CAST(ra.answer_id AS CHAR) LIKE :search2
        OR CAST(ra.reported_by AS CHAR) LIKE :search3
        OR ra.reason LIKE :search4
        OR ra.report_description LIKE :search5
        OR reporter.name LIKE :search6
        OR reporter.email LIKE :search7
    )";

    $searchValue = "%" . $search . "%";

    $params = [
        ":search1" => $searchValue,
        ":search2" => $searchValue,
        ":search3" => $searchValue,
        ":search4" => $searchValue,
        ":search5" => $searchValue,
        ":search6" => $searchValue,
        ":search7" => $searchValue
    ];
}

if ($statusFilter !== "") {
    $sql .= " AND ra.report_status = :status";
    $params[":status"] = $statusFilter;
}

$sql .= " ORDER BY ra.reported_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reported Answers | AITAM-SLACK</title>

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/reported-answers.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "includes/topbar.php"; ?>

    <main class="page-content">

        <div class="page-header">
            <div>
                <h2>Reported Answers</h2>
                <p>Review and manage answers reported by users.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->

        <div class="report-stats">

            <div class="report-stat-card">
                <span>Total Reports</span>
                <h3><?= $totalReports ?></h3>
            </div>

            <div class="report-stat-card pending">
                <span>Pending</span>
                <h3><?= $summary["pending"] ?></h3>
            </div>

            <div class="report-stat-card reviewed">
                <span>Reviewed</span>
                <h3><?= $summary["reviewed"] ?></h3>
            </div>

            <div class="report-stat-card dismissed">
                <span>Dismissed</span>
                <h3><?= $summary["dismissed"] ?></h3>
            </div>

            <div class="report-stat-card resolved">
                <span>Resolved</span>
                <h3><?= $summary["resolved"] ?></h3>
            </div>

        </div>

        <!-- Search and Filter -->

        <div class="report-toolbar">

            <form method="GET" class="report-filter-form">

                <input
                    type="text"
                    name="search"
                    placeholder="Search report ID, answer ID, user ID or reason..."
                    value="<?= e($search) ?>"
                >

                <select name="status">
                    <option value="">All Statuses</option>

                    <?php foreach ($allowedStatuses as $status): ?>
                        <option
                            value="<?= e($status) ?>"
                            <?= $statusFilter === $status ? "selected" : "" ?>
                        >
                            <?= ucfirst($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="filter-btn">
                    <i class="bi bi-search"></i> Search
                </button>

                <a href="reported-answers.php" class="reset-btn">
                    Reset
                </a>

            </form>

        </div>

        <!-- Reports Table -->

        <div class="report-table-container">

            <div class="table-heading">
                <h3>Reported Answers List</h3>
                <span><?= count($reports) ?> reports found</span>
            </div>

            <div class="table-responsive">

                <table class="report-table">

                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Answer ID</th>
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
                                <td>#<?= e($report["id"]) ?></td>

                                <td>#<?= e($report["answer_id"]) ?></td>

                                <td>#<?= e($report["reported_by"]) ?></td>

                                <td><?= e($report["reason"]) ?></td>

                                <td>
                                    <?= e(date(
                                        "d M Y, h:i A",
                                        strtotime($report["reported_at"])
                                    )) ?>
                                </td>

                                <td>
                                    <span class="status-badge <?= e($report["report_status"]) ?>">
                                        <?= e(ucfirst($report["report_status"])) ?>
                                    </span>
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="view-report-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reportDetailsModal"
                                        data-id="<?= e($report["id"]) ?>"
                                        data-answer="<?= e($report["answer_id"]) ?>"
                                        data-reporter="<?= e($report["reported_by"]) ?>"
                                        data-reason="<?= e($report["reason"]) ?>"
                                        data-description="<?= e($report["report_description"]) ?>"
                                        data-status="<?= e($report["report_status"]) ?>"
                                        data-note="<?= e($report["admin_note"]) ?>"
                                        data-date="<?= e($report["reported_at"]) ?>"
                                        data-reviewed="<?= e($report["reviewed_at"]) ?>"
                                    >
                                        View Details
                                    </button>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="empty-message">
                                No reported answers found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>
        </div>

    </main>
</div>

<!-- Report Details Modal -->

<div class="modal fade" id="reportDetailsModal" tabindex="-1"
     aria-labelledby="reportDetailsTitle" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="reportDetailsTitle">
                    Report Details
                </h5>

                <button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="detail-row">
                    <strong>Report ID:</strong>
                    <span id="detailReportId"></span>
                </div>

                <div class="detail-row">
                    <strong>Answer ID:</strong>
                    <span id="detailAnswerId"></span>
                </div>

                <div class="detail-row">
                    <strong>Reported By:</strong>
                    <span id="detailReporter"></span>
                </div>

                <div class="detail-row">
                    <strong>Reason:</strong>
                    <span id="detailReason"></span>
                </div>

                <div class="detail-section">
                    <strong>Report Description</strong>
                    <p id="detailDescription"></p>
                </div>

                <div class="detail-row">
                    <strong>Reported Date:</strong>
                    <span id="detailDate"></span>
                </div>

                <div class="detail-row">
                    <strong>Reviewed Date:</strong>
                    <span id="detailReviewed"></span>
                </div>

                <hr>

                <form method="POST">

                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION["csrf_token"]) ?>">

                    <input type="hidden" name="report_id"
                           id="modalReportId">

                    <div class="mb-3">
                        <label for="reportStatus" class="form-label">
                            Update Report Status
                        </label>

                        <select name="report_status" id="reportStatus"
                                class="form-select" required>

                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="dismissed">Dismissed</option>
                            <option value="resolved">Resolved</option>

                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="adminNote" class="form-label">
                            Admin Note
                        </label>

                        <textarea
                            name="admin_note"
                            id="adminNote"
                            class="form-control"
                            rows="3"
                            placeholder="Enter admin remarks..."
                        ></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Update Report
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script src="../assets/js/admin.js"></script>
<script src="../assets/js/reported-answers.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
