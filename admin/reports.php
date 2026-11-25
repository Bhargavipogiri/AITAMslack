
<?php

require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

/* =========================================
   HELPER FUNCTION
========================================= */

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars(
            (string)($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$allowedStatuses = [
    'Pending',
    'Under Review',
    'Resolved',
    'Dismissed'
];

$allowedPriorities = [
    'normal',
    'high',
    'urgent'
];

$success = '';
$error = '';

/* =========================================
   UPDATE REPORT
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        die('Invalid request. Please refresh the page.');
    }

    $reportId = filter_var(
        $_POST['report_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $newStatus = trim($_POST['status'] ?? '');
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    if (!$reportId || $reportId <= 0) {

        $error = 'Invalid report ID.';

    } elseif (!in_array($newStatus, $allowedStatuses, true)) {

        $error = 'Invalid report status.';

    } elseif (mb_strlen($adminNotes) > 5000) {

        $error = 'Admin notes cannot exceed 5000 characters.';

    } else {

        try {

            $adminId = filter_var(
                $_SESSION['user_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $stmt = $pdo->prepare("
                UPDATE user_reports
                SET
                    status = :status,
                    admin_notes = :admin_notes,
                    reviewed_by = :reviewed_by
                WHERE report_id = :report_id
            ");

            $stmt->execute([
                ':status' => $newStatus,
                ':admin_notes' => $adminNotes !== '' ? $adminNotes : null,
                ':reviewed_by' => $adminId ?: null,
                ':report_id' => $reportId
            ]);

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: reports.php?updated=1');
            exit;

        } catch (PDOException $ex) {

            error_log('Report update error: ' . $ex->getMessage());

            $error = 'Unable to update the report. Please try again.';

        }
    }
}

/* =========================================
   SUCCESS MESSAGE
========================================= */

if (isset($_GET['updated'])) {
    $success = 'Report updated successfully.';
}

/* =========================================
   SEARCH AND FILTER
========================================= */

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

if (!in_array($priority, $allowedPriorities, true)) {
    $priority = '';
}

$page = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT
);

$page = ($page && $page > 0) ? $page : 1;

$perPage = 10;

/* =========================================
   STATISTICS
========================================= */

$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_reports,
        SUM(status = 'Pending') AS pending_reports,
        SUM(status = 'Under Review') AS review_reports,
        SUM(status = 'Resolved') AS resolved_reports,
        SUM(status = 'Dismissed') AS dismissed_reports
    FROM user_reports
");

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalReports = (int)($stats['total_reports'] ?? 0);
$pendingReports = (int)($stats['pending_reports'] ?? 0);
$reviewReports = (int)($stats['review_reports'] ?? 0);
$resolvedReports = (int)($stats['resolved_reports'] ?? 0);
$dismissedReports = (int)($stats['dismissed_reports'] ?? 0);

/* =========================================
   BUILD SEARCH QUERY
========================================= */

$where = [];
$params = [];

if ($search !== '') {

    $where[] = "(
        ur.subject LIKE ?
        OR ur.description LIKE ?
        OR ur.report_type LIKE ?
        OR u.full_name LIKE ?
        OR u.email LIKE ?
    )";

    $keyword = '%' . $search . '%';

    $params = array_merge(
        $params,
        [$keyword, $keyword, $keyword, $keyword, $keyword]
    );
}

if ($status !== '') {
    $where[] = "ur.status = ?";
    $params[] = $status;
}

if ($priority !== '') {
    $where[] = "ur.priority = ?";
    $params[] = $priority;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

/* =========================================
   COUNT FILTERED REPORTS
========================================= */

$countSql = "
    SELECT COUNT(*)
    FROM user_reports ur
    INNER JOIN users u
        ON u.user_id = ur.user_id
    $whereSql
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);

$totalFilteredReports = (int)$countStmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalFilteredReports / $perPage)
);

$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

/* =========================================
   FETCH REPORTS
========================================= */

$reportsSql = "
    SELECT
        ur.report_id,
        ur.user_id,
        ur.report_type,
        ur.subject,
        ur.description,
        ur.priority,
        ur.status,
        ur.admin_notes,
        ur.reviewed_by,
        ur.created_at,
        ur.updated_at,

        u.full_name,
        u.email,
        u.department

    FROM user_reports ur

    INNER JOIN users u
        ON u.user_id = ur.user_id

    $whereSql

    ORDER BY
        CASE ur.status
            WHEN 'Pending' THEN 1
            WHEN 'Under Review' THEN 2
            WHEN 'Resolved' THEN 3
            WHEN 'Dismissed' THEN 4
            ELSE 5
        END,
        ur.created_at DESC

    LIMIT $perPage OFFSET $offset
";

$reportsStmt = $pdo->prepare($reportsSql);
$reportsStmt->execute($params);

$reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports Management | AITAM Slack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>

        .reports-page {
            padding-bottom: 35px;
        }

        /* STATISTICS */

        .report-stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .report-stat-card {
            background: var(--bg-surface, #fff);
            border: 1px solid var(--border-color, #e5eaf1);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .report-stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            background: #edf3fc;
            color: #315f9e;
            flex-shrink: 0;
        }

        .report-stat-card p {
            margin: 0 0 5px;
            font-size: 13px;
            color: var(--text-secondary, #77849a);
        }

        .report-stat-card h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 750;
            color: var(--text-primary, #172b4d);
        }

        /* REPORTS PANEL */

        .reports-panel {
            background: var(--bg-surface, #fff);
            border: 1px solid var(--border-color, #e5eaf1);
            border-radius: 12px;
            padding: 23px;
        }

        .reports-panel h4 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        /* FILTERS */

        .report-filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .report-filter-bar input,
        .report-filter-bar select {
            min-height: 42px;
            padding: 8px 12px;
            border: 1px solid var(--border-color, #dce3ee);
            border-radius: 7px;
            background: var(--bg-surface, #fff);
            color: var(--text-primary, #172b4d);
        }

        .report-filter-bar input {
            flex: 1;
            min-width: 190px;
        }

        .report-filter-bar button,
        .report-filter-bar a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 17px;
            border-radius: 7px;
            border: none;
            text-decoration: none;
            font-size: 14px;
        }

        .report-search-btn {
            background: #244f87;
            color: #fff;
        }

        .report-reset-btn {
            background: #edf1f6;
            color: #334155;
        }

        /* TABLE */

        .reports-table {
            min-width: 1050px;
            vertical-align: middle;
        }

        .reports-table th {
            white-space: nowrap;
            font-size: 13px;
            color: var(--text-secondary, #64748b);
            background: #f5f7fa;
            padding: 14px 12px;
        }

        .reports-table td {
            padding: 14px 12px;
            font-size: 13px;
        }

        .report-subject {
            font-weight: 650;
            max-width: 210px;
            overflow-wrap: anywhere;
        }

        .report-type {
            text-transform: capitalize;
        }

        /* STATUS BADGES */

        .report-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .report-pending {
            background: #fff3cd;
            color: #856404;
        }

        .report-review {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .report-resolved {
            background: #dcfce7;
            color: #166534;
        }

        .report-dismissed {
            background: #fee2e2;
            color: #991b1b;
        }

        /* PRIORITY */

        .priority-normal {
            color: #64748b;
        }

        .priority-high {
            color: #d97706;
            font-weight: 700;
        }

        .priority-urgent {
            color: #dc2626;
            font-weight: 750;
        }

        /* VIEW BUTTON */

        .report-view-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 7px;
            background: #eaf1fb;
            color: #24538c;
        }

        .report-view-btn:hover {
            background: #d7e6fa;
        }

        /* PAGINATION */

        .report-pagination {
            display: flex;
            justify-content: flex-end;
            gap: 7px;
            margin-top: 22px;
            flex-wrap: wrap;
        }

        .report-pagination a {
            padding: 7px 12px;
            border: 1px solid #dce3ee;
            border-radius: 6px;
            text-decoration: none;
            color: #172b4d;
        }

        .report-pagination a.active {
            background: #244f87;
            color: #fff;
            border-color: #244f87;
        }

        /* MODAL */

        #reportModal {
            z-index: 1060;
        }

        #reportModal .modal-dialog {
            max-width: 850px;
            margin: 1rem auto;
        }

        #reportModal .modal-content {
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }

        #reportModal .modal-header {
            padding: 18px 22px;
            border-bottom: 1px solid #e5eaf1;
            background: #fff;
        }

        #reportModal .modal-title {
            font-size: 21px;
            font-weight: 650;
        }

        #reportModal .modal-body {
            padding: 22px;
            max-height: calc(100vh - 180px);
            overflow-y: auto;
        }

        .report-detail-label {
            font-size: 12px;
            color: #77849a;
            margin-bottom: 4px;
        }

        .report-detail-value {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 17px;
            overflow-wrap: anywhere;
        }

        .report-description-box {
            background: #f5f7fa;
            padding: 14px;
            border-radius: 8px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 18px;
        }

        .report-modal .form-label {
            font-size: 13px;
            font-weight: 650;
        }

        .report-modal textarea,
        .report-modal select {
            font-size: 14px;
        }

        /* SAVE AND CANCEL BUTTONS */

        .report-modal-actions {
            display: flex !important;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            padding: 18px 0 5px;
            margin-top: 20px;
            border-top: 1px solid #dee2e6;
            width: 100%;
        }

        .report-modal-actions button {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 9px 18px;
            border-radius: 7px;
            font-size: 14px;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .report-cancel-btn {
            background: #f1f3f5 !important;
            color: #334155 !important;
            border: 1px solid #dce3ee !important;
        }

        .report-save-btn {
            background: #24538c !important;
            color: #fff !important;
            border: 1px solid #24538c !important;
        }

        @media (max-width: 1200px) {
            .report-stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .report-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .reports-panel {
                padding: 15px;
            }

            #reportModal .modal-dialog {
                margin: 10px;
            }

            #reportModal .modal-body {
                padding: 16px;
            }
        }

        @media (max-width: 420px) {
            .report-stats {
                grid-template-columns: 1fr;
            }

            .report-modal-actions {
                flex-direction: column-reverse;
            }

            .report-modal-actions button {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "includes/topbar.php"; ?>

    <main class="page-content reports-page">

        <div class="page-heading">

            <h2>Reports Management</h2>

            <p>Review and manage reports submitted by users.</p>

        </div>

        <!-- ALERTS -->

        <?php if ($success !== ''): ?>

            <div class="alert alert-success alert-dismissible fade show">

                <?= e($success) ?>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"></button>

            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="alert alert-danger alert-dismissible fade show">

                <?= e($error) ?>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"></button>

            </div>

        <?php endif; ?>

        <!-- STATISTICS -->

        <div class="report-stats">

            <div class="report-stat-card">

                <div class="report-stat-icon">
                    <i class="bi bi-flag"></i>
                </div>

                <div>
                    <p>Total Reports</p>
                    <h3><?= e($totalReports) ?></h3>
                </div>

            </div>

            <div class="report-stat-card">

                <div class="report-stat-icon">
                    <i class="bi bi-clock-history"></i>
                </div>

                <div>
                    <p>Pending</p>
                    <h3><?= e($pendingReports) ?></h3>
                </div>

            </div>

            <div class="report-stat-card">

                <div class="report-stat-icon">
                    <i class="bi bi-search"></i>
                </div>

                <div>
                    <p>Under Review</p>
                    <h3><?= e($reviewReports) ?></h3>
                </div>

            </div>

            <div class="report-stat-card">

                <div class="report-stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>

                <div>
                    <p>Resolved</p>
                    <h3><?= e($resolvedReports) ?></h3>
                </div>

            </div>

            <div class="report-stat-card">

                <div class="report-stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>

                <div>
                    <p>Dismissed</p>
                    <h3><?= e($dismissedReports) ?></h3>
                </div>

            </div>

        </div>

        <!-- REPORTS TABLE -->

        <section class="reports-panel">

            <h4>Submitted Reports</h4>

            <!-- FILTERS -->

            <form method="GET" class="report-filter-bar">

                <input
                    type="search"
                    name="search"
                    placeholder="Search by subject, user or email..."
                    value="<?= e($search) ?>"
                >

                <select name="status">

                    <option value="">All Status</option>

                    <?php foreach ($allowedStatuses as $availableStatus): ?>

                        <option
                            value="<?= e($availableStatus) ?>"
                            <?= $status === $availableStatus ? 'selected' : '' ?>
                        >
                            <?= e($availableStatus) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <select name="priority">

                    <option value="">All Priorities</option>

                    <?php foreach ($allowedPriorities as $availablePriority): ?>

                        <option
                            value="<?= e($availablePriority) ?>"
                            <?= $priority === $availablePriority ? 'selected' : '' ?>
                        >
                            <?= e(ucfirst($availablePriority)) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button type="submit" class="report-search-btn">
                    <i class="bi bi-search me-1"></i> Search
                </button>

                <a href="reports.php" class="report-reset-btn">
                    Reset
                </a>

            </form>

            <!-- TABLE -->

            <div class="table-responsive">

                <table class="table table-hover reports-table">

                    <thead>

                        <tr>
                            <th>Report ID</th>
                            <th>Reporter</th>
                            <th>Category</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (!empty($reports)): ?>

                        <?php foreach ($reports as $report): ?>

                            <?php

                            $statusClass = match ($report['status']) {
                                'Pending' => 'report-pending',
                                'Under Review' => 'report-review',
                                'Resolved' => 'report-resolved',
                                'Dismissed' => 'report-dismissed',
                                default => ''
                            };

                            ?>

                            <tr>

                                <td>
                                    #<?= e($report['report_id']) ?>
                                </td>

                                <td>

                                    <strong>
                                        <?= e($report['full_name']) ?>
                                    </strong>

                                    <br>

                                    <small class="text-secondary">
                                        <?= e($report['email']) ?>
                                    </small>

                                </td>

                                <td class="report-type">
                                    <?= e(str_replace('_', ' ', $report['report_type'])) ?>
                                </td>

                                <td>

                                    <div class="report-subject">
                                        <?= e($report['subject']) ?>
                                    </div>

                                </td>

                                <td>

                                    <span class="priority-<?= e($report['priority']) ?>">
                                        <?= e(ucfirst($report['priority'])) ?>
                                    </span>

                                </td>

                                <td>

                                    <?= e(date(
                                        'd M Y, h:i A',
                                        strtotime($report['created_at'])
                                    )) ?>

                                </td>

                                <td>

                                    <span class="report-badge <?= e($statusClass) ?>">
                                        <?= e($report['status']) ?>
                                    </span>

                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="report-view-btn"
                                        title="View and manage report"

                                        data-bs-toggle="modal"
                                        data-bs-target="#reportModal"

                                        data-id="<?= e($report['report_id']) ?>"
                                        data-name="<?= e($report['full_name']) ?>"
                                        data-email="<?= e($report['email']) ?>"
                                        data-department="<?= e($report['department']) ?>"
                                        data-type="<?= e(str_replace('_', ' ', $report['report_type'])) ?>"
                                        data-subject="<?= e($report['subject']) ?>"
                                        data-description="<?= e($report['description']) ?>"
                                        data-priority="<?= e($report['priority']) ?>"
                                        data-status="<?= e($report['status']) ?>"
                                        data-notes="<?= e($report['admin_notes']) ?>"
                                        data-date="<?= e(date(
                                            'd M Y, h:i A',
                                            strtotime($report['created_at'])
                                        )) ?>"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" class="text-center py-5">

                                <i class="bi bi-inbox fs-2 text-secondary"></i>

                                <p class="mt-2 mb-0">
                                    No reports found.
                                </p>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- PAGINATION -->

            <?php if ($totalPages > 1): ?>

                <div class="report-pagination">

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                        <a
                            href="?<?= e(http_build_query([
                                'search' => $search,
                                'status' => $status,
                                'priority' => $priority,
                                'page' => $i
                            ])) ?>"
                            class="<?= $page === $i ? 'active' : '' ?>"
                        >
                            <?= e($i) ?>
                        </a>

                    <?php endfor; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<!-- =====================================
     REPORT DETAILS MODAL
===================================== -->

<div class="modal fade report-modal"
     id="reportModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-flag me-2"></i>

                    Report Details

                    <span id="modalReportId"></span>

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>

            </div>

            <form method="POST" action="reports.php">

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['csrf_token']) ?>"
                    >

                    <input
                        type="hidden"
                        name="report_id"
                        id="modalReportInput"
                    >

                    <!-- REPORTER DETAILS -->

                    <div class="row">

                        <div class="col-md-6">

                            <div class="report-detail-label">
                                Reported By
                            </div>

                            <div class="report-detail-value" id="modalReporter"></div>

                        </div>

                        <div class="col-md-6">

                            <div class="report-detail-label">
                                Email
                            </div>

                            <div class="report-detail-value" id="modalEmail"></div>

                        </div>

                        <div class="col-md-6">

                            <div class="report-detail-label">
                                Department
                            </div>

                            <div class="report-detail-value" id="modalDepartment"></div>

                        </div>

                        <div class="col-md-6">

                            <div class="report-detail-label">
                                Category
                            </div>

                            <div class="report-detail-value" id="modalType"></div>

                        </div>

                        <div class="col-md-6">

                            <div class="report-detail-label">
                                Priority
                            </div>

                            <div class="report-detail-value" id="modalPriority"></div>

                        </div>

                        <div class="col-md-6">

                            <div class="report-detail-label">
                                Submitted Date
                            </div>

                            <div class="report-detail-value" id="modalDate"></div>

                        </div>

                    </div>

                    <div class="report-detail-label">
                        Subject
                    </div>

                    <div class="report-detail-value" id="modalSubject"></div>

                    <div class="report-detail-label">
                        Description
                    </div>

                    <div class="report-description-box" id="modalDescription"></div>

                    <hr>

                    <!-- UPDATE STATUS -->

                    <div class="mb-3">

                        <label for="modalStatus" class="form-label">
                            Update Report Status
                        </label>

                        <select
                            name="status"
                            id="modalStatus"
                            class="form-select"
                            required
                        >

                            <?php foreach ($allowedStatuses as $availableStatus): ?>

                                <option value="<?= e($availableStatus) ?>">
                                    <?= e($availableStatus) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- ADMIN NOTES -->

                    <div class="mb-3">

                        <label for="modalNotes" class="form-label">
                            Admin Notes
                        </label>

                        <textarea
                            name="admin_notes"
                            id="modalNotes"
                            class="form-control"
                            rows="4"
                            maxlength="5000"
                            placeholder="Add your review notes or action details..."
                        ></textarea>

                        <small class="text-secondary">
                            Maximum 5000 characters.
                        </small>

                    </div>

                    <!-- SAVE AND CANCEL BUTTONS
                         PLACED INSIDE MODAL BODY -->

                    <div class="report-modal-actions">

                        <button
                            type="button"
                            class="btn report-cancel-btn"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="btn report-save-btn"
                        >

                            <i class="bi bi-save me-1"></i>

                            Save Changes

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- BOOTSTRAP JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/admin.js"></script>

<script>

/* =========================================
   POPULATE REPORT DETAILS
========================================= */

const reportModal = document.getElementById('reportModal');

reportModal.addEventListener('show.bs.modal', function (event) {

    const button = event.relatedTarget;

    const data = button.dataset;

    document.getElementById('modalReportId').textContent =
        '#' + data.id;

    document.getElementById('modalReportInput').value =
        data.id;

    document.getElementById('modalReporter').textContent =
        data.name;

    document.getElementById('modalEmail').textContent =
        data.email;

    document.getElementById('modalDepartment').textContent =
        data.department || 'Not available';

    document.getElementById('modalType').textContent =
        data.type;

    document.getElementById('modalPriority').textContent =
        data.priority;

    document.getElementById('modalDate').textContent =
        data.date;

    document.getElementById('modalSubject').textContent =
        data.subject;

    document.getElementById('modalDescription').textContent =
        data.description;

    document.getElementById('modalStatus').value =
        data.status;

    document.getElementById('modalNotes').value =
        data.notes || '';

});

</script>

</body>
</html>
