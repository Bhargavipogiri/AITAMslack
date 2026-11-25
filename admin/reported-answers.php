
<?php

require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/ai_helpers.php';

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
   CSRF TOKEN
------------------------------------------------- */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$allowedStatuses = [
    'pending',
    'reviewed',
    'dismissed',
    'resolved'
];

/* -------------------------------------------------
   HANDLE REPORT STATUS UPDATE
------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    $reportId = filter_var(
        $_POST['report_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $newStatus = is_string($_POST['report_status'] ?? null)
        ? $_POST['report_status']
        : '';

    $adminNote = is_string($_POST['admin_note'] ?? null)
        ? trim($_POST['admin_note'])
        : '';

    try {

        if (
            !isset($_SESSION['csrf_token']) ||
            !is_string($token) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            throw new RuntimeException('Invalid request. Please refresh the page.');
        }

        if (
            $reportId === false ||
            !in_array($newStatus, $allowedStatuses, true)
        ) {
            throw new RuntimeException('Invalid report details.');
        }

        $pdo->beginTransaction();

        /* -----------------------------------------
           FETCH REPORT
        ----------------------------------------- */

        $reportStmt = $pdo->prepare(
            "SELECT id, answer_id, report_status
             FROM reported_answers
             WHERE id = :id
             FOR UPDATE"
        );

        $reportStmt->execute([
            ':id' => $reportId
        ]);

        $report = $reportStmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            throw new RuntimeException('Report not found.');
        }

        $answerId = (int)$report['answer_id'];

        /* -----------------------------------------
           RESOLVE REPORT = HIDE REPORTED ANSWER
        ----------------------------------------- */

        if ($newStatus === 'resolved') {

            $answerStmt = $pdo->prepare(
                "SELECT id, question_id, status
                 FROM answers
                 WHERE id = :id
                 FOR UPDATE"
            );

            $answerStmt->execute([
                ':id' => $answerId
            ]);

            $answer = $answerStmt->fetch(PDO::FETCH_ASSOC);

            if ($answer) {

                $questionId = (int)$answer['question_id'];

                /*
                 * Hide the answer only if it is not
                 * already hidden.
                 */

                if ($answer['status'] !== 'Hidden') {

                    $hideStmt = $pdo->prepare(
                        "UPDATE answers
                         SET status = 'Hidden'
                         WHERE id = :id"
                    );

                    $hideStmt->execute([
                        ':id' => $answerId
                    ]);

                    /* ---------------------------------
                       UPDATE RELATED QUESTION
                    --------------------------------- */

                    /*
                     * If no published answers remain,
                     * return the question to Pending.
                     *
                     * Do not reopen Closed questions.
                     */

                    $questionStmt = $pdo->prepare(
                        "UPDATE questions
                         SET status = 'Pending'
                         WHERE id = :question_id
                           AND status = 'Answered'
                           AND NOT EXISTS (
                               SELECT 1
                               FROM answers
                               WHERE question_id = :question_id
                                 AND status = 'Published'
                           )"
                    );

                    $questionStmt->execute([
                        ':question_id' => $questionId
                    ]);

                    /* ---------------------------------
                       RECORD ADMIN MODERATION EVENT
                    --------------------------------- */

                    $adminId = filter_var(
                        $_SESSION['admin_id'] ?? null,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1]]
                    );

                    recordModerationEvent(
                        $pdo,
                        'answer',
                        $answerId,
                        'ADMIN',
                        'REJECTED',
                        [],
                        $adminId !== false ? (int)$adminId : null,
                        'REJECTED'
                    );
                }
            }
        }

        /* -----------------------------------------
           UPDATE REPORT STATUS
        ----------------------------------------- */

        $updateStmt = $pdo->prepare(
            "UPDATE reported_answers
             SET report_status = :status,
                 admin_note = :admin_note,
                 reviewed_at = NOW()
             WHERE id = :id"
        );

        $updateStmt->execute([
            ':status' => $newStatus,
            ':admin_note' => $adminNote,
            ':id' => $reportId
        ]);

        $pdo->commit();

        switch ($newStatus) {

            case 'resolved':
                $_SESSION['report_message'] =
                    'Report resolved successfully. The reported answer has been hidden if it was not already hidden.';
                break;

            case 'dismissed':
                $_SESSION['report_message'] =
                    'Report dismissed successfully. The answer status was not changed.';
                break;

            case 'reviewed':
                $_SESSION['report_message'] =
                    'Report marked as reviewed successfully.';
                break;

            default:
                $_SESSION['report_message'] =
                    'Report status updated successfully.';
                break;
        }

    } catch (Throwable $exception) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Reported answer update failed: ' .
            $exception->getMessage()
        );

        $_SESSION['report_message'] =
            $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Unable to update the report. Please try again.';
    }

    /* -----------------------------------------
       REDIRECT WITH EXISTING FILTERS
    ----------------------------------------- */

    $redirectParams = [];

    if (!empty($_POST['return_search'])) {
        $redirectParams['search'] = trim($_POST['return_search']);
    }

    if (
        isset($_POST['return_status']) &&
        in_array($_POST['return_status'], $allowedStatuses, true)
    ) {
        $redirectParams['status'] = $_POST['return_status'];
    }

    $redirectUrl = 'reported-answers.php';

    if (!empty($redirectParams)) {
        $redirectUrl .= '?' . http_build_query($redirectParams);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

/* -------------------------------------------------
   SUCCESS MESSAGE
------------------------------------------------- */

$message = $_SESSION['report_message'] ?? '';

unset($_SESSION['report_message']);

/* -------------------------------------------------
   SEARCH AND STATUS FILTER
------------------------------------------------- */

$search = is_string($_GET['search'] ?? null)
    ? trim($_GET['search'])
    : '';

$statusFilter = is_string($_GET['status'] ?? null)
    ? $_GET['status']
    : '';

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

/* -------------------------------------------------
   SUMMARY COUNTS
------------------------------------------------- */

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
    "SELECT COUNT(*)
     FROM reported_answers"
)->fetchColumn();

/* -------------------------------------------------
   FETCH REPORTED ANSWERS
------------------------------------------------- */

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
        a.status AS answer_status,

        q.question_title,

        reporter.full_name AS reporter_name,
        reporter.email AS reporter_email,

        author.full_name AS author_name,
        author.email AS author_email,

        ai.ai_decision,
        ai.publication_status AS ai_publication_status,
        ai.ai_reason,
        ai.ai_confidence,
        ai.model AS ai_model,
        ai.api_status

    FROM reported_answers ra

    LEFT JOIN answers a
        ON ra.answer_id = a.id

    LEFT JOIN questions q
        ON a.question_id = q.id

    LEFT JOIN users reporter
        ON ra.reported_by = reporter.user_id

    LEFT JOIN users author
        ON a.user_id = author.user_id

    LEFT JOIN moderation_events ai
        ON ai.id = (
            SELECT MAX(me.id)
            FROM moderation_events me
            WHERE me.content_type = 'answer'
              AND me.content_id = ra.answer_id
              AND me.event_type = 'AI'
        )

    WHERE 1 = 1
";

$params = [];

/* SEARCH FILTER */

if ($search !== '') {

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

    $searchValue = '%' . $search . '%';

    $params = [
        ':search1' => $searchValue,
        ':search2' => $searchValue,
        ':search3' => $searchValue,
        ':search4' => $searchValue,
        ':search5' => $searchValue,
        ':search6' => $searchValue,
        ':search7' => $searchValue,
        ':search8' => $searchValue,
        ':search9' => $searchValue,
        ':search10' => $searchValue
    ];
}

/* STATUS FILTER */

if ($statusFilter !== '') {

    $sql .= " AND ra.report_status = :status";

    $params[':status'] = $statusFilter;
}

$sql .= "
    ORDER BY ra.reported_at DESC, ra.id DESC
";

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

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

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

        <!-- SUMMARY CARDS -->

        <div class="report-stats">

            <div class="report-stat-card">

                <span>Total Reports</span>

                <h3><?= e($totalReports) ?></h3>

            </div>

            <div class="report-stat-card pending">

                <span>Pending</span>

                <h3><?= e($summary['pending']) ?></h3>

            </div>

            <div class="report-stat-card reviewed">

                <span>Reviewed</span>

                <h3><?= e($summary['reviewed']) ?></h3>

            </div>

            <div class="report-stat-card dismissed">

                <span>Dismissed</span>

                <h3><?= e($summary['dismissed']) ?></h3>

            </div>

            <div class="report-stat-card resolved">

                <span>Resolved</span>

                <h3><?= e($summary['resolved']) ?></h3>

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
                            <?= $statusFilter === $s ? 'selected' : '' ?>
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

                    <?php if (!empty($reports)): ?>

                        <?php foreach ($reports as $report): ?>

                            <tr>

                                <td>
                                    #<?= e($report['report_id']) ?>
                                </td>

                                <td>

                                    #<?= e($report['answer_id']) ?>

                                    <?php if (!empty($report['answer_status'])): ?>

                                        <div class="small text-muted">
                                            <?= e($report['answer_status']) ?>
                                        </div>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="reporter-cell">

                                        <strong>
                                            <?= e(
                                                $report['reporter_name']
                                                ?: 'Unknown User'
                                            ) ?>
                                        </strong>

                                        <small>
                                            <?= e(
                                                $report['reporter_email']
                                                ?: 'Email unavailable'
                                            ) ?>
                                        </small>

                                    </div>

                                </td>

                                <td>
                                    <?= e($report['reason']) ?>
                                </td>

                                <td>

                                    <?php if (!empty($report['reported_at'])): ?>

                                        <?= e(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime($report['reported_at'])
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        Not available

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <span class="status-badge <?= e($report['report_status']) ?>">

                                        <?= e(ucfirst($report['report_status'])) ?>

                                    </span>

                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="view-report-btn"

                                        data-bs-toggle="modal"
                                        data-bs-target="#reportDetailsModal"

                                        data-id="<?= e($report['report_id']) ?>"
                                        data-answer="<?= e($report['answer_id']) ?>"
                                        data-answer-status="<?= e($report['answer_status']) ?>"

                                        data-question="<?= e($report['question_title']) ?>"
                                        data-answer-text="<?= e($report['answer_text']) ?>"

                                        data-reporter="<?= e($report['reporter_name']) ?>"
                                        data-reporter-email="<?= e($report['reporter_email']) ?>"

                                        data-author="<?= e($report['author_name']) ?>"
                                        data-author-email="<?= e($report['author_email']) ?>"

                                        data-reason="<?= e($report['reason']) ?>"
                                        data-description="<?= e($report['report_description']) ?>"

                                        data-status="<?= e($report['report_status']) ?>"
                                        data-note="<?= e($report['admin_note']) ?>"

                                        data-date="<?= e($report['reported_at']) ?>"
                                        data-reviewed="<?= e($report['reviewed_at']) ?>"

                                        data-ai-decision="<?= e($report['ai_decision']) ?>"
                                        data-ai-publication="<?= e($report['ai_publication_status']) ?>"
                                        data-ai-reason="<?= e($report['ai_reason']) ?>"
                                        data-ai-confidence="<?= e($report['ai_confidence']) ?>"
                                        data-ai-model="<?= e($report['ai_model']) ?>"
                                        data-api-status="<?= e($report['api_status']) ?>"
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
                    aria-label="Close"
                ></button>

            </div>

            <!-- MODAL BODY -->

            <div class="modal-body">

                <h6>Answer Details</h6>

                <div class="detail-row">

                    <strong>Answer ID:</strong>

                    <span id="detailAnswerId"></span>

                </div>

                <div class="detail-row">

                    <strong>Answer Status:</strong>

                    <span id="detailAnswerStatus"></span>

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

                <h6>AI Moderation Details</h6>

                <div class="detail-row">

                    <strong>AI Decision:</strong>

                    <span id="detailAIDecision"></span>

                </div>

                <div class="detail-row">

                    <strong>AI Publication Status:</strong>

                    <span id="detailAIPublication"></span>

                </div>

                <div class="detail-row">

                    <strong>AI API Status:</strong>

                    <span id="detailAPIStatus"></span>

                </div>

                <div class="detail-row">

                    <strong>AI Confidence:</strong>

                    <span id="detailAIConfidence"></span>

                </div>

                <div class="detail-row">

                    <strong>AI Model:</strong>

                    <span id="detailAIModel"></span>

                </div>

                <div class="detail-section">

                    <strong>AI Reason:</strong>

                    <p id="detailAIReason"></p>

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
                        value="<?= e($_SESSION['csrf_token']) ?>"
                    >

                    <input
                        type="hidden"
                        name="report_id"
                        id="modalReportId"
                    >

                    <input
                        type="hidden"
                        name="return_search"
                        value="<?= e($search) ?>"
                    >

                    <input
                        type="hidden"
                        name="return_status"
                        value="<?= e($statusFilter) ?>"
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

                            <option value="resolved">Resolved - Hide Answer</option>

                        </select>

                        <small class="text-muted">
                            Selecting Resolved will hide the reported answer.
                        </small>

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

document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('reportDetailsModal');

    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {

        const button = event.relatedTarget;

        if (!button) {
            return;
        }

        function setValue(id, value) {

            const element = document.getElementById(id);

            if (element) {
                element.textContent = value || 'Not available';
            }
        }

        setValue('detailReportId', button.dataset.id);
        setValue('detailAnswerId', button.dataset.answer);
        setValue('detailAnswerStatus', button.dataset.answerStatus);

        setValue('detailQuestion', button.dataset.question);
        setValue('detailAnswerText', button.dataset.answerText);

        setValue('detailAuthor', button.dataset.author);
        setValue('detailAuthorEmail', button.dataset.authorEmail);

        setValue('detailReporter', button.dataset.reporter);
        setValue('detailReporterEmail', button.dataset.reporterEmail);

        setValue('detailReason', button.dataset.reason);
        setValue('detailDescription', button.dataset.description);

        setValue('detailDate', button.dataset.date);
        setValue('detailReviewed', button.dataset.reviewed);

        setValue('detailAIDecision', button.dataset.aiDecision);
        setValue('detailAIPublication', button.dataset.aiPublication);
        setValue('detailAPIStatus', button.dataset.apiStatus);
        setValue('detailAIConfidence', button.dataset.aiConfidence);
        setValue('detailAIModel', button.dataset.aiModel);
        setValue('detailAIReason', button.dataset.aiReason);

        document.getElementById('modalReportId').value =
            button.dataset.id || '';

        document.getElementById('reportStatus').value =
            button.dataset.status || 'pending';

        document.getElementById('adminNote').value =
            button.dataset.note || '';

    });

});

</script>

</body>
</html>
