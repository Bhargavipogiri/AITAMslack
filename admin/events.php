
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Escape output
function e($value)
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Filters
$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");
$status = trim($_GET["status"] ?? "");

// Build query
$sql = "SELECT * FROM events WHERE 1=1";
$params = [];

if ($search !== "") {
    $sql .= " AND (event_title LIKE :search OR venue LIKE :search)";
    $params[":search"] = "%" . $search . "%";
}

if ($category !== "") {
    $sql .= " AND category = :category";
    $params[":category"] = $category;
}

if ($status === "upcoming") {
    $sql .= " AND start_datetime >= NOW()";
} elseif ($status === "completed") {
    $sql .= " AND end_datetime < NOW()";
} elseif ($status === "ongoing") {
    $sql .= " AND start_datetime <= NOW() AND end_datetime >= NOW()";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$totalEvents = (int)$pdo->query(
    "SELECT COUNT(*) FROM events"
)->fetchColumn();

$upcomingEvents = (int)$pdo->query(
    "SELECT COUNT(*) FROM events WHERE start_datetime >= NOW()"
)->fetchColumn();

$ongoingEvents = (int)$pdo->query(
    "SELECT COUNT(*) FROM events
     WHERE start_datetime <= NOW() AND end_datetime >= NOW()"
)->fetchColumn();

$completedEvents = (int)$pdo->query(
    "SELECT COUNT(*) FROM events WHERE end_datetime < NOW()"
)->fetchColumn();

// Categories
$categoryStmt = $pdo->query(
    "SELECT DISTINCT category FROM events ORDER BY category ASC"
);
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Messages
$successMessage = "";
$errorMessage = "";

if (isset($_GET["success"]) && $_GET["success"] === "deleted") {
    $successMessage = "Event deleted successfully.";
}

if (isset($_GET["error"])) {
    switch ($_GET["error"]) {
        case "invalid_request":
            $errorMessage = "Invalid request. Please try again.";
            break;

        case "invalid_event":
            $errorMessage = "Invalid event selected.";
            break;

        case "not_found":
            $errorMessage = "The selected event was not found.";
            break;

        case "delete_failed":
            $errorMessage = "Unable to delete the event. Please try again.";
            break;

        default:
            $errorMessage = "Something went wrong.";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AITAM-SLACK | Event Management</title>

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

    <!-- Root CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">

    <!-- Event Management Page Styles -->
    <style>
        .events-page .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .events-page .page-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary, #172b4d);
            margin-bottom: 6px;
        }

        .events-page .page-header p {
            color: var(--text-muted, #6c757d);
            margin-bottom: 0;
            font-size: 14px;
        }

        .events-page .stat-card {
            display: flex;
            align-items: center;
            gap: 16px;
            height: 100%;
            min-height: 110px;
            padding: 20px;
            background: var(--bg-surface, #ffffff);
            border: 1px solid var(--border-default, #e5e7eb);
            border-radius: 12px;
            box-shadow: var(--shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.04));
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .events-page .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .events-page .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 12px;
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            font-size: 23px;
        }

        .events-page .stat-card:nth-child(2) .stat-icon {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .events-page .stat-card h3 {
            font-size: 25px;
            font-weight: 700;
            color: var(--text-primary, #172b4d);
            margin: 0 0 5px;
        }

        .events-page .stat-card p {
            font-size: 13px;
            color: var(--text-muted, #6c757d);
            margin: 0;
        }

        .events-page .card {
            background: var(--bg-surface, #ffffff);
            border: 1px solid var(--border-default, #e5e7eb);
            border-radius: 12px;
            box-shadow: var(--shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.04));
        }

        .events-page .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-default, #e5e7eb);
            padding: 16px 20px;
        }

        .events-page .card-header h5 {
            color: var(--text-primary, #172b4d);
            font-weight: 700;
        }

        .events-page .card-body {
            padding: 20px;
        }

        .events-page .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary, #172b4d);
        }

        .events-page .form-control,
        .events-page .form-select {
            min-height: 42px;
            border-color: var(--border-default, #dee2e6);
            border-radius: 7px;
            font-size: 14px;
        }

        .events-page .form-control:focus,
        .events-page .form-select:focus {
            border-color: var(--color-primary, #0d6efd);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
        }

        .events-page .table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .events-page .table thead th {
            background: var(--bg-app, #f8f9fa);
            color: var(--text-primary, #172b4d);
            font-size: 12px;
            font-weight: 700;
            padding: 14px 12px;
            white-space: nowrap;
            border-bottom: 1px solid var(--border-default, #dee2e6);
        }

        .events-page .table tbody td {
            color: var(--text-primary, #343a40);
            font-size: 13px;
            padding: 14px 12px;
        }

        .events-page .table tbody tr:hover {
            background: rgba(13, 110, 253, 0.025);
        }

        .events-page .event-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1px solid var(--border-default, #dee2e6);
            border-radius: 7px;
            background: var(--bg-surface, #ffffff);
            color: #0d6efd;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .events-page .event-action:hover {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #ffffff;
        }

        .events-page .event-delete-btn {
            color: #dc3545;
        }

        .events-page .event-delete-btn:hover {
            background: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
        }

        #deleteEventModal .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
        }

        #deleteEventModal .modal-header {
            border-bottom: 1px solid #eeeeee;
            padding: 18px 22px;
        }

        #deleteEventModal .modal-title {
            font-weight: 700;
        }

        #deleteEventModal .modal-body {
            padding: 25px;
        }

        #deleteEventModal .modal-footer {
            border-top: 1px solid #eeeeee;
            padding: 15px 22px;
        }

        @media (max-width: 768px) {
            .events-page .page-header {
                align-items: flex-start;
            }

            .events-page .page-header .btn {
                width: 100%;
            }

            .events-page .card-body {
                padding: 14px;
            }

            .events-page .stat-card {
                min-height: 95px;
                padding: 15px;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <!-- Page Content -->
        <main class="page-content events-page">

            <div class="page-header">
                <div>
                    <h2>Event Management</h2>
                    <p>Manage college events and activities.</p>
                </div>

                <a href="add-event.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i>
                    Add New Event
                </a>
            </div>

            <!-- Messages -->
            <?php if ($successMessage !== ""): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?= e($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== ""): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= e($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="row g-3 mb-4">

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div>
                            <h3><?= $totalEvents ?></h3>
                            <p>Total Events</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div>
                            <h3><?= $upcomingEvents ?></h3>
                            <p>Upcoming Events</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <h3><?= $ongoingEvents ?></h3>
                            <p>Ongoing Events</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-x"></i>
                        </div>
                        <div>
                            <h3><?= $completedEvents ?></h3>
                            <p>Completed Events</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">

                    <form method="GET" action="events.php">

                        <div class="row g-3 align-items-end">

                            <div class="col-md-5">
                                <label class="form-label">Search Events</label>
                                <input
                                    type="text"
                                    name="search"
                                    class="form-control"
                                    placeholder="Search by title or venue..."
                                    value="<?= e($search) ?>"
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>

                                    <?php foreach ($categories as $cat): ?>
                                        <option
                                            value="<?= e($cat) ?>"
                                            <?= $category === $cat ? "selected" : "" ?>
                                        >
                                            <?= e($cat) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="upcoming" <?= $status === "upcoming" ? "selected" : "" ?>>Upcoming</option>
                                    <option value="ongoing" <?= $status === "ongoing" ? "selected" : "" ?>>Ongoing</option>
                                    <option value="completed" <?= $status === "completed" ? "selected" : "" ?>>Completed</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i>
                                    Search
                                </button>
                            </div>

                        </div>

                    </form>

                </div>
            </div>

            <!-- Events Table -->
            <div class="card">

                <div class="card-header">
                    <h5 class="mb-0">All Events</h5>
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Event Details</th>
                                    <th>Category</th>
                                    <th>Venue</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (count($events) > 0): ?>

                                    <?php foreach ($events as $event): ?>

                                        <tr>

                                            <td>
                                                <?php if (!empty($event["event_image"])): ?>
                                                    <img
                                                        src="../<?= e($event["event_image"]) ?>"
                                                        alt="Event Image"
                                                        width="65"
                                                        height="50"
                                                        style="object-fit:cover;border-radius:6px;"
                                                    >
                                                <?php else: ?>
                                                    <span class="text-muted">No Image</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <strong><?= e($event["event_title"]) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?= e(substr($event["event_description"], 0, 60)) ?>
                                                </small>
                                            </td>

                                            <td><?= e($event["category"]) ?></td>

                                            <td><?= e($event["venue"]) ?></td>

                                            <td>
                                                <?= e(date("d M Y, h:i A", strtotime($event["start_datetime"]))) ?>
                                            </td>

                                            <td>
                                                <?= e(date("d M Y, h:i A", strtotime($event["end_datetime"]))) ?>
                                            </td>

                                            <td>
                                                <div class="d-flex gap-2">

                                                    <a
                                                        href="edit-event.php?id=<?= (int)$event["event_id"] ?>"
                                                        class="event-action"
                                                        title="Edit Event"
                                                    >
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="event-action event-delete-btn"
                                                        title="Delete Event"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteEventModal"
                                                        data-event-id="<?= e($event["event_id"]) ?>"
                                                        data-event-title="<?= e($event["event_title"]) ?>"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                    </button>

                                                </div>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="bi bi-calendar-x fs-2 text-muted"></i>
                                            <p class="mt-2 mb-0">No events found.</p>
                                        </td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>
            </div>

        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteEventModal" tabindex="-1"
         aria-labelledby="deleteEventModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <form action="delete-event.php" method="POST" id="deleteEventForm">

                    <div class="modal-header">

                        <h5 class="modal-title" id="deleteEventModalLabel">
                            Confirm Event Deletion
                        </h5>

                        <button type="button" class="btn-close"
                                data-bs-dismiss="modal" aria-label="Close">
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="text-center mb-3">
                            <i class="bi bi-exclamation-triangle-fill text-danger"
                               style="font-size: 45px;"></i>
                        </div>

                        <p class="text-center">
                            Are you sure you want to delete this event?
                        </p>

                        <h6 class="text-center fw-bold" id="deleteEventTitle"></h6>

                        <p class="text-center text-muted small">
                            This action cannot be undone.
                        </p>

                        <input type="hidden" name="event_id" id="deleteEventId">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION["csrf_token"]) ?>"
                        >

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                            <i class="bi bi-trash"></i>
                            Delete Event
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Admin JavaScript -->
    <script src="../assets/js/admin.js"></script>

    <!-- Delete Modal JavaScript -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const deleteModal = document.getElementById("deleteEventModal");
            const deleteForm = document.getElementById("deleteEventForm");
            const eventIdInput = document.getElementById("deleteEventId");
            const eventTitle = document.getElementById("deleteEventTitle");
            const deleteButton = document.getElementById("confirmDeleteBtn");

            if (!deleteModal || !deleteForm) {
                console.error("Delete modal or form not found.");
                return;
            }

            deleteModal.addEventListener("show.bs.modal", function (event) {

                const button = event.relatedTarget;

                if (!button) {
                    return;
                }

                eventIdInput.value = button.getAttribute("data-event-id");
                eventTitle.textContent = button.getAttribute("data-event-title");

            });

            deleteForm.addEventListener("submit", function (event) {

                if (!eventIdInput.value) {
                    event.preventDefault();
                    alert("Please select a valid event.");
                    return;
                }

                deleteButton.disabled = true;
                deleteButton.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span> Deleting...';

            });

            deleteModal.addEventListener("hidden.bs.modal", function () {
                deleteButton.disabled = false;
                deleteButton.innerHTML = '<i class="bi bi-trash"></i> Delete Event';
                eventIdInput.value = "";
                eventTitle.textContent = "";
            });

        });
    </script>

</body>
</html>
