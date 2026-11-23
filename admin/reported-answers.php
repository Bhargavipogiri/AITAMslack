
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

$allowedStatuses = ["pending", "reviewed", "dismissed", "resolved"];

/*
|--------------------------------------------------------------------------
| Handle report status update
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !isset($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        die("Invalid request.");
    }

    $reportId = filter_input(INPUT_POST, "report_id", FILTER_VALIDATE_INT);
    $newStatus = $_POST["report_status"] ?? "";
    $adminNote = trim($_POST["admin_note"] ?? "");

    if ($reportId && in_array($newStatus, $allowedStatuses, true)) {

        $sql = "UPDATE reported_answers
                SET report_status = :status,
                    admin_note = :admin_note,
                    reviewed_at = NOW()
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":status" => $newStatus,
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

/*
|--------------------------------------------------------------------------
| Success message
|--------------------------------------------------------------------------
*/

$message = $_SESSION["report_message"] ?? "";
unset($_SESSION["report_message"]);

/*
|--------------------------------------------------------------------------
| Search and status filter
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$statusFilter = $_GET["status"] ?? "";

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "";
}

/*
|--------------------------------------------------------------------------
| Summary counts
|--------------------------------------------------------------------------
*/

$summary = [];

foreach ($allowedStatuses as $reportStatus) {

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM reported_answers
         WHERE report_status = ?"
    );

    $stmt->execute([$reportStatus]);

    $summary[$reportStatus] = (int)$stmt->fetchColumn();
}

$totalReports = (int)$pdo->query(
    "SELECT COUNT(*) FROM reported_answers"
)->fetchColumn();

/*
|--------------------------------------------------------------------------
| Fetch reported answers
|--------------------------------------------------------------------------
|
| reported_answers -> answers -> questions
| reported_answers -> users (reporter)
| answers -> users (answer author)
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        ra.id AS report_id,
        ra.answer_id,
        ra.reported_by,
        ra.reason,
        ra.report_description,
        ra.report_status,
        ra.admin_note,
        ra.reported_at,
        ra.reviewed_at,

        a.answer_text,
        a.question_id,

        q.question_title,

        reporter.full_name AS reporter_name,
        reporter.email AS reporter_email,

        author.full_name AS author_name,
        author.email AS author_email

    FROM reported_answers ra

    LEFT JOIN answers a
        ON ra.answer_id = a.id

    LEFT JOIN questions q
        ON a.question_id = q.id

    LEFT JOIN users reporter
        ON ra.reported_by = reporter.user_id

    LEFT JOIN users author
        ON a.user_id = author.user_id

    WHERE 1=1
";

$params = [];

if ($search !== "") {

    $sql .= "
        AND (
            CAST(ra.id AS CHAR) LIKE :search1
            OR CAST(ra.answer_id AS CHAR) LIKE :search2
            OR CAST(ra.reported_by AS CHAR) LIKE :search3
            OR ra.reason LIKE :search4
            OR ra.report_description LIKE :search5
            OR reporter.full_name LIKE :search6
            OR reporter.email LIKE :search7
            OR author.full_name LIKE :search8
            OR q.question_title LIKE :search9
            OR a.answer_text LIKE :search10
        )
    ";

    $searchValue = "%" . $search . "%";

    $params = [
        ":search1" => $searchValue,
        ":search2" => $searchValue,
        ":search3" => $searchValue,
        ":search4" => $searchValue,
        ":search5" => $searchValue,
        ":search6" => $searchValue,
        ":search7" => $searchValue,
        ":search8" => $searchValue,
        ":search9" => $searchValue,
        ":search10" => $searchValue
    ];
}

if ($statusFilter !== "") {

    $sql .= " AND ra.report_status = :status";

    $params[":status"] = $statusFilter;
}

$sql .= " ORDER BY ra.reported_at DESC, ra.id DESC";

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

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Project CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/reported-answers.css">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "includes/topbar.php"; ?>

    <main class="page-content">

        <!-- PAGE HEADING -->

        <div class="page-header">

            <div>
                <h2>Reported Answers</h2>

                <p>
                    Review and manage answers reported by users.
                </p>
            </div>

        </div>


        <!-- SUCCESS MESSAGE -->

        <?php if ($message): ?>

            <div class="alert alert-info">
                <?= e($message) ?>
            </div>

        <?php endif; ?>


        <!-- SUMMARY CARDS -->

        <div class="report-stats">

            <div class="report-stat-card">

                <span>Total Reports</span>

                <h3><?= e($totalReports) ?></h3>

            </div>


            <div class="report-stat-card pending">

                <span>Pending</span>

                <h3><?= e($summary["pending"]) ?></h3>

            </div>


            <div class="report-stat-card reviewed">

                <span>Reviewed</span>

                <h3><?= e($summary["reviewed"]) ?></h3>

            </div>


            <div class="report-stat-card dismissed">

                <span>Dismissed</span>

                <h3><?= e($summary["dismissed"]) ?></h3>

            </div>


            <div class="report-stat-card resolved">

                <span>Resolved</span>

                <h3><?= e($summary["resolved"]) ?></h3>

            </div>

        </div>


        <!-- SEARCH AND FILTER -->

        <div class="report-toolbar">

            <form method="GET" class="report-filter-form">

                <input
                    type="text"
                    name="search"
                    placeholder="Search answer, question, reporter or reason..."
                    value="<?= e($search) ?>"
                >


                <select name="status">

                    <option value="">All Statuses</option>

                    <?php foreach ($allowedStatuses as $s): ?>

                        <option
                            value="<?= e($s) ?>"
                            <?= $statusFilter === $s ? "selected" : "" ?>
                        >
                            <?= e(ucfirst($s)) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <button type="submit" class="filter-btn">

                    <i class="bi bi-search"></i>
                    Search

                </button>


                <a href="reported-answers.php" class="reset-btn">
                    Reset
                </a>

            </form>

        </div>


        <!-- REPORTS TABLE -->

        <div class="report-table-container">

            <div class="table-heading">

                <h3>Reported Answers List</h3>

                <span>
                    <?= e(count($reports)) ?> reports found
                </span>

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

                                <!-- REPORT ID -->

                                <td>
                                    #<?= e($report["report_id"]) ?>
                                </td>


                                <!-- ANSWER ID -->

                                <td>
                                    #<?= e($report["answer_id"]) ?>
                                </td>


                                <!-- REPORTER DETAILS -->

                                <td>

                                    <div class="reporter-cell">

                                        <strong>
                                            <?= e($report["reporter_name"] ?: "Unknown User") ?>
                                        </strong>

                                        <small>
                                            <?= e($report["reporter_email"] ?: "Email unavailable") ?>
                                        </small>

                                    </div>

                                </td>


                                <!-- REASON -->

                                <td>
                                    <?= e($report["reason"]) ?>
                                </td>


                                <!-- REPORTED DATE -->

                                <td>

                                    <?= e(
                                        date(
                                            "d M Y, h:i A",
                                            strtotime($report["reported_at"])
                                        )
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span class="status-badge <?= e($report["report_status"]) ?>">

                                        <?= e(ucfirst($report["report_status"])) ?>

                                    </span>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <button
                                        type="button"
                                        class="view-report-btn"

                                        data-bs-toggle="modal"
                                        data-bs-target="#reportDetailsModal"

                                        data-id="<?= e($report["report_id"]) ?>"
                                        data-answer="<?= e($report["answer_id"]) ?>"
                                        data-question="<?= e($report["question_title"]) ?>"
                                        data-answer-text="<?= e($report["answer_text"]) ?>"

                                        data-reporter="<?= e($report["reporter_name"]) ?>"
                                        data-reporter-email="<?= e($report["reporter_email"]) ?>"

                                        data-author="<?= e($report["author_name"]) ?>"
                                        data-author-email="<?= e($report["author_email"]) ?>"

                                        data-reason="<?= e($report["reason"]) ?>"
                                        data-description="<?= e($report["report_description"]) ?>"
                                        data-status="<?= e($report["report_status"]) ?>"
                                        data-note="<?= e($report["admin_note"]) ?>"
                                        data-date="<?= e($report["reported_at"]) ?>"
                                        data-reviewed="<?= e($report["reviewed_at"]) ?>"
                                    >

                                        <i class="bi bi-eye"></i>
                                        View Details

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="empty-message">

                                <i class="bi bi-inbox"></i>

                                <p>No reported answers found.</p>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>


<!-- REPORT DETAILS MODAL -->

<div
    class="modal fade"
    id="reportDetailsModal"
    tabindex="-1"
    aria-labelledby="reportDetailsTitle"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">


            <!-- MODAL HEADER -->

            <div class="modal-header">

                <h5 class="modal-title" id="reportDetailsTitle">
                    Reported Answer Details
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <!-- MODAL BODY -->

            <div class="modal-body">


                <h6>Answer Details</h6>

                <div class="detail-row">

                    <strong>Answer ID:</strong>

                    <span id="detailAnswerId"></span>

                </div>


                <div class="detail-section">

                    <strong>Original Question:</strong>

                    <p id="detailQuestion"></p>

                </div>


                <div class="detail-section">

                    <strong>Answer Content:</strong>

                    <p id="detailAnswerText"></p>

                </div>


                <div class="detail-row">

                    <strong>Answer Posted By:</strong>

                    <span id="detailAuthor"></span>

                </div>


                <div class="detail-row">

                    <strong>Author Email:</strong>

                    <span id="detailAuthorEmail"></span>

                </div>


                <hr>


                <h6>Report Details</h6>


                <div class="detail-row">

                    <strong>Report ID:</strong>

                    <span id="detailReportId"></span>

                </div>


                <div class="detail-row">

                    <strong>Reported By:</strong>

                    <span id="detailReporter"></span>

                </div>


                <div class="detail-row">

                    <strong>Reporter Email:</strong>

                    <span id="detailReporterEmail"></span>

                </div>


                <div class="detail-row">

                    <strong>Reason:</strong>

                    <span id="detailReason"></span>

                </div>


                <div class="detail-section">

                    <strong>Report Description:</strong>

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


                <!-- UPDATE REPORT STATUS -->

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION["csrf_token"]) ?>"
                    >

                    <input
                        type="hidden"
                        name="report_id"
                        id="modalReportId"
                    >


                    <div class="mb-3">

                        <label
                            for="reportStatus"
                            class="form-label"
                        >
                            Update Report Status
                        </label>


                        <select
                            name="report_status"
                            id="reportStatus"
                            class="form-select"
                            required
                        >

                            <option value="pending">Pending</option>

                            <option value="reviewed">Reviewed</option>

                            <option value="dismissed">Dismissed</option>

                            <option value="resolved">Resolved</option>

                        </select>

                    </div>


                    <div class="mb-3">

                        <label
                            for="adminNote"
                            class="form-label"
                        >
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

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Close
                        </button>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Update Report
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<!-- BOOTSTRAP JAVASCRIPT -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/admin.js"></script>


<!-- MODAL JAVASCRIPT -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("reportDetailsModal");

    if (!modal) {
        return;
    }

    modal.addEventListener("show.bs.modal", function (event) {

        const button = event.relatedTarget;

        if (!button) {
            return;
        }

        function setValue(id, value) {

            const element = document.getElementById(id);

            if (element) {
                element.textContent = value || "Not available";
            }

        }


        setValue("detailReportId", button.dataset.id);

        setValue("detailAnswerId", button.dataset.answer);

        setValue("detailQuestion", button.dataset.question);

        setValue("detailAnswerText", button.dataset.answerText);

        setValue("detailAuthor", button.dataset.author);

        setValue("detailAuthorEmail", button.dataset.authorEmail);

        setValue("detailReporter", button.dataset.reporter);

        setValue("detailReporterEmail", button.dataset.reporterEmail);

        setValue("detailReason", button.dataset.reason);

        setValue("detailDescription", button.dataset.description);

        setValue("detailDate", button.dataset.date);

        setValue("detailReviewed", button.dataset.reviewed);


        document.getElementById("modalReportId").value =
            button.dataset.id || "";

        document.getElementById("reportStatus").value =
            button.dataset.status || "pending";

        document.getElementById("adminNote").value =
            button.dataset.note || "";

    });

});

</script>

</body>
</html>
