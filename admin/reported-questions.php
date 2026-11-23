
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$allowedStatuses = ['pending', 'reviewed', 'dismissed', 'resolved'];

/*
|--------------------------------------------------------------------------
| Handle admin actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die('Invalid request.');
    }

    $reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
    $reportType = $_POST['report_type'] ?? '';
    $action = $_POST['action'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');

    $actionStatuses = [
        'review'  => 'reviewed',
        'dismiss' => 'dismissed',
        'resolve' => 'resolved'
    ];

    // Only allow these two table names.
    $reportTables = [
        'question' => 'reported_questions',
        'answer'   => 'reported_answers'
    ];

    if (
        $reportId &&
        isset($reportTables[$reportType]) &&
        isset($actionStatuses[$action])
    ) {
        $table = $reportTables[$reportType];
        $newStatus = $actionStatuses[$action];

        $sql = "UPDATE `$table`
                SET report_status = ?,
                    admin_note = ?,
                    reviewed_at = NOW()
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $newStatus,
            $adminNote,
            $reportId
        ]);
    }

    header('Location: reported-questions.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Search and filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

/*
|--------------------------------------------------------------------------
| Fetch question reports and answer reports
|--------------------------------------------------------------------------
|
| Question reports:
| reported_questions -> questions -> users
|
| Answer reports:
| reported_answers -> answers -> questions -> users
|
| Both queries return the same columns using UNION ALL.
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM (

        /* QUESTION REPORTS */

        SELECT
            rq.id AS report_id,
            'question' AS report_type,
            rq.question_id AS content_id,
            rq.reported_by,
            rq.reason,
            rq.report_description,
            rq.report_status,
            rq.admin_note,
            rq.reported_at,
            rq.reviewed_at,

            q.question_title AS content_title,
            q.question_description AS content_text,
            q.category,
            q.question_title AS related_question,

            author.full_name AS author_name,
            author.email AS author_email,

            reporter.full_name AS reporter_name,
            reporter.email AS reporter_email

        FROM reported_questions rq

        INNER JOIN questions q
            ON rq.question_id = q.id

        LEFT JOIN users reporter
            ON rq.reported_by = reporter.user_id

        LEFT JOIN users author
            ON q.user_id = author.user_id


        UNION ALL


        /* ANSWER REPORTS */

        SELECT
            ra.id AS report_id,
            'answer' AS report_type,
            a.id AS content_id,
            ra.reported_by,
            ra.reason,
            ra.report_description,
            ra.report_status,
            ra.admin_note,
            ra.reported_at,
            ra.reviewed_at,

            CONCAT('Answer to: ', q.question_title) AS content_title,
            a.answer_text AS content_text,
            q.category,
            q.question_title AS related_question,

            author.full_name AS author_name,
            author.email AS author_email,

            reporter.full_name AS reporter_name,
            reporter.email AS reporter_email

        FROM reported_answers ra

        INNER JOIN answers a
            ON ra.answer_id = a.id

        INNER JOIN questions q
            ON a.question_id = q.id

        LEFT JOIN users reporter
            ON ra.reported_by = reporter.user_id

        LEFT JOIN users author
            ON a.user_id = author.user_id

    ) AS reports

    WHERE 1=1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            content_title LIKE ?
            OR content_text LIKE ?
            OR reporter_name LIKE ?
            OR reporter_email LIKE ?
            OR author_name LIKE ?
            OR reason LIKE ?
            OR report_description LIKE ?
        )
    ";

    $keyword = '%' . $search . '%';

    $params = array_fill(0, 7, $keyword);
}

if (in_array($status, $allowedStatuses, true)) {
    $sql .= " AND report_status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY reported_at DESC, report_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Summary statistics
|--------------------------------------------------------------------------
*/

$statsSql = "
    SELECT
        COUNT(*) AS total_reports,

        COALESCE(SUM(report_status = 'pending'), 0)
            AS pending_reports,

        COALESCE(SUM(report_status = 'reviewed'), 0)
            AS reviewed_reports,

        COALESCE(SUM(report_status = 'dismissed'), 0)
            AS dismissed_reports,

        COALESCE(SUM(report_status = 'resolved'), 0)
            AS resolved_reports

    FROM (
        SELECT report_status FROM reported_questions

        UNION ALL

        SELECT report_status FROM reported_answers
    ) AS all_reports
";

$stats = $pdo->query($statsSql)->fetch(PDO::FETCH_ASSOC);

$totalReports = $stats['total_reports'] ?? 0;
$pendingReports = $stats['pending_reports'] ?? 0;
$reviewedReports = $stats['reviewed_reports'] ?? 0;
$dismissedReports = $stats['dismissed_reports'] ?? 0;
$resolvedReports = $stats['resolved_reports'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reported Questions & Answers | AITAM Slack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/reported-questions.css">

</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <?php include 'includes/topbar.php'; ?>

    <main class="page-content reported-page">

        <!-- PAGE HEADING -->

        <div class="page-heading reported-heading">

            <div>
                <h2>Reported Questions & Answers</h2>

                <p>
                    Review questions and answers reported by users
                    and take appropriate action.
                </p>
            </div>

        </div>


        <!-- SUMMARY CARDS -->

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


        <!-- REPORTS PANEL -->

        <section class="reports-panel">

            <div class="reports-panel-header">

                <div>
                    <h4>Reported Content</h4>

                    <p>
                        View and manage question and answer reports.
                    </p>
                </div>

            </div>


            <!-- SEARCH AND FILTER -->

            <form method="GET" class="report-filter-bar">

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="search"
                        name="search"
                        placeholder="Search questions, answers or reporters..."
                        value="<?= e($search) ?>"
                    >

                </div>


                <select name="status">

                    <option value="">All Statuses</option>

                    <?php foreach ($allowedStatuses as $s): ?>

                        <option
                            value="<?= e($s) ?>"
                            <?= $status === $s ? 'selected' : '' ?>
                        >
                            <?= e(ucfirst($s)) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <button type="submit" class="filter-btn">

                    <i class="bi bi-funnel"></i>
                    Filter

                </button>


                <a href="reported-questions.php" class="reset-btn">
                    Reset
                </a>

            </form>


            <!-- REPORTS TABLE -->

            <div class="table-responsive">

                <table class="table reports-table">

                    <thead>

                        <tr>
                            <th>Report ID</th>
                            <th>Type</th>
                            <th>Reported Content</th>
                            <th>Reported By</th>
                            <th>Reason</th>
                            <th>Reported Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!empty($reports)): ?>

                        <?php foreach ($reports as $report): ?>

                            <tr>

                                <!-- REPORT ID -->

                                <td>
                                    #<?= e($report['report_id']) ?>
                                </td>


                                <!-- REPORT TYPE -->

                                <td>

                                    <?php if ($report['report_type'] === 'question'): ?>

                                        <span class="badge text-bg-primary">
                                            <i class="bi bi-question-circle"></i>
                                            Question
                                        </span>

                                    <?php else: ?>

                                        <span class="badge text-bg-info">
                                            <i class="bi bi-chat-left-text"></i>
                                            Answer
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- REPORTED CONTENT -->

                                <td>

                                    <div class="question-cell">

                                        <strong>
                                            <?= e($report['content_title']) ?>
                                        </strong>

                                        <small>
                                            Content ID: #<?= e($report['content_id']) ?>
                                        </small>

                                        <?php if ($report['report_type'] === 'answer'): ?>

                                            <small>
                                                Question: <?= e($report['related_question']) ?>
                                            </small>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- REPORTER -->

                                <td>

                                    <div class="reporter-cell">

                                        <strong>
                                            <?= e($report['reporter_name'] ?: 'Unknown User') ?>
                                        </strong>

                                        <small>
                                            <?= e($report['reporter_email'] ?: 'Email unavailable') ?>
                                        </small>

                                    </div>

                                </td>


                                <!-- REASON -->

                                <td>
                                    <?= e($report['reason']) ?>
                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= e(
                                        date(
                                            'd M Y',
                                            strtotime($report['reported_at'])
                                        )
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span class="report-status <?= e($report['report_status']) ?>">

                                        <?= e(ucfirst($report['report_status'])) ?>

                                    </span>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="report-actions">


                                        <!-- VIEW BUTTON -->

                                        <button
                                            type="button"
                                            class="action-btn view-btn"
                                            title="View Report"
                                            data-bs-toggle="modal"
                                            data-bs-target="#reportModal"

                                            data-report-id="<?= e($report['report_id']) ?>"
                                            data-report-type="<?= e($report['report_type']) ?>"
                                            data-content-id="<?= e($report['content_id']) ?>"
                                            data-content-title="<?= e($report['content_title']) ?>"
                                            data-content-text="<?= e($report['content_text']) ?>"
                                            data-category="<?= e($report['category']) ?>"
                                            data-related-question="<?= e($report['related_question']) ?>"
                                            data-author="<?= e($report['author_name']) ?>"
                                            data-author-email="<?= e($report['author_email']) ?>"
                                            data-reporter="<?= e($report['reporter_name']) ?>"
                                            data-reporter-email="<?= e($report['reporter_email']) ?>"
                                            data-reason="<?= e($report['reason']) ?>"
                                            data-description="<?= e($report['report_description']) ?>"
                                            data-status="<?= e($report['report_status']) ?>"
                                            data-admin-note="<?= e($report['admin_note']) ?>"
                                            data-reported-at="<?= e($report['reported_at']) ?>"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </button>


                                        <!-- REVIEW BUTTON -->

                                        <?php if ($report['report_status'] === 'pending'): ?>

                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="report_id"
                                                    value="<?= e($report['report_id']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="report_type"
                                                    value="<?= e($report['report_type']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="review"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action-btn review-btn"
                                                    title="Mark as Reviewed"
                                                >
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>

                                            </form>

                                        <?php endif; ?>


                                        <!-- DISMISS BUTTON -->

                                        <?php if (!in_array($report['report_status'], ['dismissed', 'resolved'], true)): ?>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to dismiss this report?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="report_id"
                                                    value="<?= e($report['report_id']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="report_type"
                                                    value="<?= e($report['report_type']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="dismiss"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action-btn dismiss-btn"
                                                    title="Dismiss Report"
                                                >
                                                    <i class="bi bi-x-lg"></i>
                                                </button>

                                            </form>

                                        <?php endif; ?>


                                        <!-- RESOLVE BUTTON -->

                                        <?php if (!in_array($report['report_status'], ['dismissed', 'resolved'], true)): ?>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Mark this report as resolved?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="report_id"
                                                    value="<?= e($report['report_id']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="report_type"
                                                    value="<?= e($report['report_type']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="resolve"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action-btn resolve-btn"
                                                    title="Mark as Resolved"
                                                >
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

                            <td colspan="8" class="empty-state">

                                <i class="bi bi-inbox"></i>

                                <p>No reported questions or answers found.</p>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>


<!-- REPORT DETAILS MODAL -->

<div class="modal fade" id="reportModal" tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="modalHeading">
                    Report Details
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">


                <!-- CONTENT DETAILS -->

                <div class="detail-section">

                    <h6 id="modalContentHeading">
                        Reported Content
                    </h6>

                    <p>
                        <strong>Report Type:</strong>
                        <span id="modalReportType"></span>
                    </p>

                    <p>
                        <strong>Content ID:</strong>
                        <span id="modalContentId"></span>
                    </p>

                    <p>
                        <strong>Title:</strong>
                        <span id="modalContentTitle"></span>
                    </p>

                    <p>
                        <strong>Content:</strong>
                    </p>

                    <p
                        class="detail-description"
                        id="modalContentText"
                    ></p>

                    <p>
                        <strong>Category:</strong>
                        <span id="modalCategory"></span>
                    </p>

                    <p id="relatedQuestionRow">
                        <strong>Related Question:</strong>
                        <span id="modalRelatedQuestion"></span>
                    </p>

                    <p>
                        <strong>Posted By:</strong>
                        <span id="modalAuthor"></span>
                    </p>

                    <p>
                        <strong>Author Email:</strong>
                        <span id="modalAuthorEmail"></span>
                    </p>

                </div>


                <hr>


                <!-- REPORT DETAILS -->

                <div class="detail-section">

                    <h6>Report Details</h6>

                    <p>
                        <strong>Report ID:</strong>
                        <span id="modalReportId"></span>
                    </p>

                    <p>
                        <strong>Reported By:</strong>
                        <span id="modalReporter"></span>
                    </p>

                    <p>
                        <strong>Reporter Email:</strong>
                        <span id="modalReporterEmail"></span>
                    </p>

                    <p>
                        <strong>Reason:</strong>
                        <span id="modalReason"></span>
                    </p>

                    <p>
                        <strong>Additional Description:</strong>
                    </p>

                    <p
                        class="detail-description"
                        id="modalReportDescription"
                    ></p>

                    <p>
                        <strong>Reported At:</strong>
                        <span id="modalReportedAt"></span>
                    </p>

                    <p>
                        <strong>Report Status:</strong>
                        <span id="modalStatus"></span>
                    </p>

                    <p>
                        <strong>Admin Note:</strong>
                    </p>

                    <p
                        class="detail-description"
                        id="modalAdminNote"
                    ></p>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>

            </div>

        </div>

    </div>

</div>


<!-- BOOTSTRAP JAVASCRIPT -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/admin.js"></script>


<!-- REPORT DETAILS MODAL JAVASCRIPT -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const reportModal = document.getElementById('reportModal');

    if (!reportModal) {
        return;
    }

    reportModal.addEventListener('show.bs.modal', function (event) {

        const button = event.relatedTarget;

        if (!button) {
            return;
        }

        function setText(id, value) {
            const element = document.getElementById(id);

            if (element) {
                element.textContent = value || 'Not available';
            }
        }

        const type = button.dataset.reportType;

        setText('modalReportId', button.dataset.reportId);
        setText('modalReportType', type);
        setText('modalContentId', button.dataset.contentId);
        setText('modalContentTitle', button.dataset.contentTitle);
        setText('modalContentText', button.dataset.contentText);
        setText('modalCategory', button.dataset.category);
        setText('modalRelatedQuestion', button.dataset.relatedQuestion);
        setText('modalAuthor', button.dataset.author);
        setText('modalAuthorEmail', button.dataset.authorEmail);

        setText('modalReporter', button.dataset.reporter);
        setText('modalReporterEmail', button.dataset.reporterEmail);
        setText('modalReason', button.dataset.reason);
        setText('modalReportDescription', button.dataset.description);
        setText('modalStatus', button.dataset.status);
        setText('modalAdminNote', button.dataset.adminNote);
        setText('modalReportedAt', button.dataset.reportedAt);

        const heading = document.getElementById('modalHeading');
        const contentHeading = document.getElementById('modalContentHeading');
        const relatedQuestionRow = document.getElementById('relatedQuestionRow');

        if (type === 'answer') {

            heading.textContent = 'Reported Answer Details';
            contentHeading.textContent = 'Answer Details';
            relatedQuestionRow.style.display = '';

        } else {

            heading.textContent = 'Reported Question Details';
            contentHeading.textContent = 'Question Details';
            relatedQuestionRow.style.display = 'none';

        }

    });

});

</script>

</body>
</html>
