<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

/* =========================================
   SESSION AND CSRF
========================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================================
   HELPER
========================================= */

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================================
   POST ACTIONS
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die('Invalid request. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';

    try {

        /* CREATE */

        if ($action === 'create') {

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = $_POST['status'] ?? 'draft';

            if ($title === '' || $description === '') {
                $_SESSION['announcement_message'] = 'Please fill in all required fields.';
            } elseif (!in_array($status, ['published', 'draft'], true)) {
                $_SESSION['announcement_message'] = 'Invalid announcement status.';
            } else {

                $createdBy = (int)($_SESSION['admin_id'] ?? 0);

                $stmt = $pdo->prepare("
                    INSERT INTO announcements
                    (title, description, created_by, status)
                    VALUES
                    (:title, :description, :created_by, :status)
                ");

                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':created_by' => $createdBy,
                    ':status' => $status
                ]);

                $_SESSION['announcement_message'] = 'Announcement created successfully.';
            }
        }

        /* UPDATE */

        elseif ($action === 'update') {

            $id = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = $_POST['status'] ?? 'draft';

            if (!$id || $title === '' || $description === '') {
                $_SESSION['announcement_message'] = 'Please provide valid announcement details.';
            } elseif (!in_array($status, ['published', 'draft'], true)) {
                $_SESSION['announcement_message'] = 'Invalid announcement status.';
            } else {

                $stmt = $pdo->prepare("
                    UPDATE announcements
                    SET title = :title,
                        description = :description,
                        status = :status
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':status' => $status,
                    ':id' => $id
                ]);

                $_SESSION['announcement_message'] = 'Announcement updated successfully.';
            }
        }

        /* DELETE */

        elseif ($action === 'delete') {

            $id = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
                $stmt->execute([':id' => $id]);

                $_SESSION['announcement_message'] = 'Announcement deleted successfully.';
            } else {
                $_SESSION['announcement_message'] = 'Invalid announcement ID.';
            }
        }

        /* PUBLISH / UNPUBLISH */

        elseif ($action === 'toggle_status') {

            $id = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
            $status = $_POST['status'] ?? '';

            if ($id && in_array($status, ['published', 'draft'], true)) {

                $stmt = $pdo->prepare("
                    UPDATE announcements
                    SET status = :status
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':status' => $status,
                    ':id' => $id
                ]);

                $_SESSION['announcement_message'] = 'Announcement status updated successfully.';
            } else {
                $_SESSION['announcement_message'] = 'Invalid announcement details.';
            }
        }

        else {
            $_SESSION['announcement_message'] = 'Invalid action.';
        }

    } catch (PDOException $exception) {

        error_log('Announcement action error: ' . $exception->getMessage());

        $_SESSION['announcement_message'] = 'A database error occurred. Please check your database table.';
    }

    header('Location: announcements.php');
    exit;
}

/* =========================================
   MESSAGE
========================================= */

$message = $_SESSION['announcement_message'] ?? '';
unset($_SESSION['announcement_message']);

/* =========================================
   SEARCH AND FILTER
========================================= */

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

if (!in_array($statusFilter, ['published', 'draft'], true)) {
    $statusFilter = '';
}

/* =========================================
   DEFAULT VALUES
========================================= */

$totalAnnouncements = 0;
$publishedAnnouncements = 0;
$draftAnnouncements = 0;
$announcements = [];
$loadError = false;

/* =========================================
   FETCH ANNOUNCEMENTS
========================================= */

try {

    $totalAnnouncements = (int)$pdo->query("
        SELECT COUNT(*) FROM announcements
    ")->fetchColumn();

    $publishedAnnouncements = (int)$pdo->query("
        SELECT COUNT(*) FROM announcements
        WHERE status = 'published'
    ")->fetchColumn();

    $draftAnnouncements = (int)$pdo->query("
        SELECT COUNT(*) FROM announcements
        WHERE status = 'draft'
    ")->fetchColumn();

    $sql = "
        SELECT id, title, description, created_by, status, created_at
        FROM announcements
        WHERE 1 = 1
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " AND (title LIKE :title OR description LIKE :description)";
        $params[':title'] = '%' . $search . '%';
        $params[':description'] = '%' . $search . '%';
    }

    if ($statusFilter !== '') {
        $sql .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $exception) {

    error_log('Announcement fetch error: ' . $exception->getMessage());

    $loadError = true;
    $message = 'Unable to load announcements. Please verify the announcements table and its columns.';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Announcement Management | AITAM-SLACK</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Global CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <!-- Announcement CSS: load after admin.css -->
    <link rel="stylesheet" href="../assets/css/announcements.css">
</head>

<body>

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- TOPBAR -->
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <main class="page-content">

            <!-- PAGE HEADER -->
            <div class="announcement-header">

                <div>
                    <h2>Announcement Management</h2>
                    <p>Create and manage college announcements.</p>
                </div>

                <button type="button"
                        class="add-announcement-btn"
                        id="addAnnouncementBtn"
                        data-bs-toggle="modal"
                        data-bs-target="#announcementModal">

                    <i class="bi bi-plus-lg"></i>
                    Create Announcement

                </button>

            </div>

            <!-- MESSAGE -->
            <?php if ($message !== ''): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">

                    <?= e($message) ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- SUMMARY CARDS -->
            <section class="announcement-stats">

                <div class="announcement-stat-card">
                    <span>Total Announcements</span>
                    <h3><?= $totalAnnouncements ?></h3>
                </div>

                <div class="announcement-stat-card published">
                    <span>Published</span>
                    <h3><?= $publishedAnnouncements ?></h3>
                </div>

                <div class="announcement-stat-card draft">
                    <span>Drafts</span>
                    <h3><?= $draftAnnouncements ?></h3>
                </div>

            </section>

            <!-- SEARCH AND FILTER -->
            <section class="announcement-toolbar">

                <form method="GET"
                      action="announcements.php"
                      class="announcement-filter-form">

                    <input type="text"
                           name="search"
                           placeholder="Search announcements..."
                           value="<?= e($search) ?>">

                    <select name="status">
                        <option value="">All Statuses</option>

                        <option value="published"
                            <?= $statusFilter === 'published' ? 'selected' : '' ?>>
                            Published
                        </option>

                        <option value="draft"
                            <?= $statusFilter === 'draft' ? 'selected' : '' ?>>
                            Draft
                        </option>
                    </select>

                    <button type="submit" class="filter-btn">
                        <i class="bi bi-search"></i>
                        Search
                    </button>

                    <a href="announcements.php" class="reset-btn">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset
                    </a>

                </form>

            </section>

            <!-- ANNOUNCEMENTS TABLE -->
            <section class="announcement-table-container">

                <div class="table-heading">
                    <h3>Announcements List</h3>

                    <span>
                        <?= count($announcements) ?> announcements found
                    </span>
                </div>

                <div class="table-responsive">

                    <table class="announcement-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!$loadError && !empty($announcements)): ?>

                            <?php foreach ($announcements as $announcement): ?>

                                <tr>

                                    <td>#<?= e($announcement['id']) ?></td>

                                    <td class="announcement-title">
                                        <?= e($announcement['title']) ?>
                                    </td>

                                    <td class="announcement-description"
                                        title="<?= e($announcement['description']) ?>">
                                        <?= e(
                                            strlen($announcement['description']) > 65
                                                ? substr($announcement['description'], 0, 65) . '...'
                                                : $announcement['description']
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="announcement-status <?= e($announcement['status']) ?>">
                                            <?= e(ucfirst($announcement['status'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                        $date = $announcement['created_at'] ?? '';
                                        echo $date ? e(date('d M Y', strtotime($date))) : 'N/A';
                                        ?>
                                    </td>

                                    <td>
                                        <div class="announcement-actions">

                                            <!-- EDIT -->
                                            <button type="button"
                                                    class="action-btn edit-btn edit-announcement"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#announcementModal"
                                                    data-id="<?= e($announcement['id']) ?>"
                                                    data-title="<?= e($announcement['title']) ?>"
                                                    data-description="<?= e($announcement['description']) ?>"
                                                    data-status="<?= e($announcement['status']) ?>"
                                                    title="Edit Announcement">

                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- PUBLISH / UNPUBLISH -->
                                            <form method="POST" class="inline-form">

                                                <input type="hidden" name="csrf_token"
                                                       value="<?= e($_SESSION['csrf_token']) ?>">

                                                <input type="hidden" name="action"
                                                       value="toggle_status">

                                                <input type="hidden" name="announcement_id"
                                                       value="<?= e($announcement['id']) ?>">

                                                <input type="hidden" name="status"
                                                       value="<?= $announcement['status'] === 'published' ? 'draft' : 'published' ?>">

                                                <button type="submit"
                                                        class="action-btn status-btn"
                                                        title="<?= $announcement['status'] === 'published' ? 'Unpublish' : 'Publish' ?>">

                                                    <i class="bi <?= $announcement['status'] === 'published' ? 'bi-eye-slash' : 'bi-send' ?>"></i>
                                                </button>

                                            </form>

                                            <!-- DELETE -->
                                            <form method="POST"
                                                  class="inline-form delete-announcement-form">

                                                <input type="hidden" name="csrf_token"
                                                       value="<?= e($_SESSION['csrf_token']) ?>">

                                                <input type="hidden" name="action"
                                                       value="delete">

                                                <input type="hidden" name="announcement_id"
                                                       value="<?= e($announcement['id']) ?>">

                                                <button type="submit"
                                                        class="action-btn delete-btn"
                                                        title="Delete Announcement">

                                                    <i class="bi bi-trash"></i>
                                                </button>

                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="empty-message">
                                    <?= $loadError ? 'Announcements could not be loaded.' : 'No announcements found.' ?>
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>
    </div>

    <!-- CREATE / EDIT MODAL -->
    <div class="modal fade"
         id="announcementModal"
         tabindex="-1"
         aria-labelledby="announcementModalTitle"
         aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content">

                <form method="POST" id="announcementForm">

                    <div class="modal-header">

                        <h5 class="modal-title" id="announcementModalTitle">
                            Create Announcement
                        </h5>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"></button>

                    </div>

                    <div class="modal-body">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= e($_SESSION['csrf_token']) ?>">

                        <input type="hidden"
                               name="action"
                               id="formAction"
                               value="create">

                        <input type="hidden"
                               name="announcement_id"
                               id="announcementId">

                        <div class="mb-3">

                            <label for="announcementTitle" class="form-label">
                                Announcement Title
                            </label>

                            <input type="text"
                                   name="title"
                                   id="announcementTitle"
                                   class="form-control"
                                   maxlength="255"
                                   placeholder="Enter announcement title"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label for="announcementDescription" class="form-label">
                                Description
                            </label>

                            <textarea name="description"
                                      id="announcementDescription"
                                      class="form-control"
                                      rows="6"
                                      placeholder="Enter announcement details..."
                                      required></textarea>

                        </div>

                        <div class="mb-3">

                            <label for="announcementStatus" class="form-label">
                                Publication Status
                            </label>

                            <select name="status"
                                    id="announcementStatus"
                                    class="form-select"
                                    required>

                                <option value="draft">Save as Draft</option>
                                <option value="published">Publish</option>

                            </select>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit"
                                class="btn btn-primary"
                                id="saveAnnouncementBtn">
                            Save Announcement
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const modal = document.getElementById('announcementModal');
        const formAction = document.getElementById('formAction');
        const announcementId = document.getElementById('announcementId');
        const titleInput = document.getElementById('announcementTitle');
        const descriptionInput = document.getElementById('announcementDescription');
        const statusInput = document.getElementById('announcementStatus');
        const modalTitle = document.getElementById('announcementModalTitle');
        const saveButton = document.getElementById('saveAnnouncementBtn');

        document.getElementById('addAnnouncementBtn').addEventListener('click', function () {

            formAction.value = 'create';
            announcementId.value = '';
            titleInput.value = '';
            descriptionInput.value = '';
            statusInput.value = 'draft';

            modalTitle.textContent = 'Create Announcement';
            saveButton.textContent = 'Save Announcement';
        });

        document.querySelectorAll('.edit-announcement').forEach(function (button) {

            button.addEventListener('click', function () {

                formAction.value = 'update';
                announcementId.value = this.dataset.id || '';
                titleInput.value = this.dataset.title || '';
                descriptionInput.value = this.dataset.description || '';
                statusInput.value = this.dataset.status || 'draft';

                modalTitle.textContent = 'Edit Announcement';
                saveButton.textContent = 'Update Announcement';
            });

        });

        document.querySelectorAll('.delete-announcement-form').forEach(function (form) {

            form.addEventListener('submit', function (event) {

                if (!confirm('Are you sure you want to delete this announcement?')) {
                    event.preventDefault();
                }

            });

        });

        modal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('announcementForm').reset();
        });

    });
    </script>

</body>
</html>