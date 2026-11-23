
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        die("Invalid request.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "create" || $action === "update") {

        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $status = $_POST["status"] ?? "draft";

        if ($title === "" || $description === "") {

            $_SESSION["announcement_message"] = "Please fill in all required fields.";

        } elseif (!in_array($status, ["published", "draft"], true)) {

            $_SESSION["announcement_message"] = "Invalid announcement status.";

        } else {

            if ($action === "create") {

                $createdBy = (int)($_SESSION["admin_id"] ?? 0);

                $stmt = $pdo->prepare(
                    "INSERT INTO announcements
                    (title, description, created_by, status)
                    VALUES (:title, :description, :created_by, :status)"
                );

                $stmt->execute([
                    ":title" => $title,
                    ":description" => $description,
                    ":created_by" => $createdBy,
                    ":status" => $status
                ]);

                $_SESSION["announcement_message"] = "Announcement created successfully.";

            } else {

                $id = filter_input(INPUT_POST, "announcement_id", FILTER_VALIDATE_INT);

                if ($id) {

                    $stmt = $pdo->prepare(
                        "UPDATE announcements
                         SET title = :title,
                             description = :description,
                             status = :status
                         WHERE id = :id"
                    );

                    $stmt->execute([
                        ":title" => $title,
                        ":description" => $description,
                        ":status" => $status,
                        ":id" => $id
                    ]);

                    $_SESSION["announcement_message"] = "Announcement updated successfully.";
                }
            }
        }

    } elseif ($action === "delete") {

        $id = filter_input(INPUT_POST, "announcement_id", FILTER_VALIDATE_INT);

        if ($id) {

            $stmt = $pdo->prepare(
                "DELETE FROM announcements WHERE id = :id"
            );

            $stmt->execute([":id" => $id]);

            $_SESSION["announcement_message"] = "Announcement deleted successfully.";
        }

    } elseif ($action === "toggle_status") {

        $id = filter_input(INPUT_POST, "announcement_id", FILTER_VALIDATE_INT);
        $status = $_POST["status"] ?? "";

        if ($id && in_array($status, ["published", "draft"], true)) {

            $stmt = $pdo->prepare(
                "UPDATE announcements SET status = :status WHERE id = :id"
            );

            $stmt->execute([
                ":status" => $status,
                ":id" => $id
            ]);

            $_SESSION["announcement_message"] = "Announcement status updated.";
        }
    }

    header("Location: announcements.php");
    exit;
}

$message = $_SESSION["announcement_message"] ?? "";
unset($_SESSION["announcement_message"]);

$search = trim($_GET["search"] ?? "");
$statusFilter = $_GET["status"] ?? "";

if (!in_array($statusFilter, ["published", "draft"], true)) {
    $statusFilter = "";
}

/* Summary counts */

$totalAnnouncements = (int)$pdo->query(
    "SELECT COUNT(*) FROM announcements"
)->fetchColumn();

$publishedAnnouncements = (int)$pdo->query(
    "SELECT COUNT(*) FROM announcements WHERE status = 'published'"
)->fetchColumn();

$draftAnnouncements = (int)$pdo->query(
    "SELECT COUNT(*) FROM announcements WHERE status = 'draft'"
)->fetchColumn();

/* Fetch announcements */

$sql = "SELECT a.*, au.name AS creator_name, au.email AS creator_email
        FROM announcements a
        LEFT JOIN admin_users au ON au.id = a.created_by
        WHERE 1=1";
$params = [];

if ($search !== "") {

    $sql .= " AND (a.title LIKE :title OR a.description LIKE :description)";

    $params[":title"] = "%" . $search . "%";
    $params[":description"] = "%" . $search . "%";
}

if ($statusFilter !== "") {

    $sql .= " AND a.status = :status";
    $params[":status"] = $statusFilter;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Announcement Management | AITAM-SLACK</title>

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/announcements.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "includes/topbar.php"; ?>

    <main class="page-content">

        <!-- Page Header -->

        <div class="announcement-header">

            <div>
                <h2>Announcement Management</h2>
                <p>Create and manage college announcements.</p>
            </div>

            <button type="button"
                    class="add-announcement-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#announcementModal"
                    id="addAnnouncementBtn">

                <i class="bi bi-plus-lg"></i>
                Create Announcement

            </button>

        </div>

        <!-- Success Message -->

        <?php if ($message !== ""): ?>

            <div class="alert alert-info">
                <?= e($message) ?>
            </div>

        <?php endif; ?>

        <!-- Summary Cards -->

        <div class="announcement-stats">

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

        </div>

        <!-- Search and Filter -->

        <div class="announcement-toolbar">

            <form method="GET" class="announcement-filter-form">

                <input
                    type="text"
                    name="search"
                    placeholder="Search announcements..."
                    value="<?= e($search) ?>"
                >

                <select name="status">

                    <option value="">All Statuses</option>

                    <option value="published"
                        <?= $statusFilter === "published" ? "selected" : "" ?>>
                        Published
                    </option>

                    <option value="draft"
                        <?= $statusFilter === "draft" ? "selected" : "" ?>>
                        Draft
                    </option>

                </select>

                <button type="submit" class="filter-btn">
                    <i class="bi bi-search"></i>
                    Search
                </button>

                <a href="announcements.php" class="reset-btn">
                    Reset
                </a>

            </form>

        </div>

        <!-- Announcements Table -->

        <div class="announcement-table-container">

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

                    <?php if ($announcements): ?>

                        <?php foreach ($announcements as $announcement): ?>

                            <tr>

                                <td>
                                    #<?= e($announcement["id"]) ?>
                                </td>

                                <td class="announcement-title">
                                    <?= e($announcement["title"]) ?>
                                </td>

                                <td class="announcement-description">
                                    <?= e(mb_strimwidth(
                                        $announcement["description"],
                                        0,
                                        65,
                                        "..."
                                    )) ?>
                                </td>

                                <td>

                                    <span class="announcement-status <?= e($announcement["status"]) ?>">
                                        <?= e(ucfirst($announcement["status"])) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= e(date(
                                        "d M Y",
                                        strtotime($announcement["created_at"])
                                    )) ?>
                                </td>

                                <td class="announcement-actions">

                                    <!-- Edit -->

                                    <button
                                        type="button"
                                        class="action-btn edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#announcementModal"
                                        data-id="<?= e($announcement["id"]) ?>"
                                        data-title="<?= e($announcement["title"]) ?>"
                                        data-description="<?= e($announcement["description"]) ?>"
                                        data-status="<?= e($announcement["status"]) ?>"
                                        title="Edit">

                                        <i class="bi bi-pencil-square"></i>

                                    </button>

                                    <!-- Publish / Unpublish -->

                                    <form method="POST" class="inline-form">

                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden" name="action"
                                               value="toggle_status">

                                        <input type="hidden" name="announcement_id"
                                               value="<?= e($announcement["id"]) ?>">

                                        <input type="hidden" name="status"
                                               value="<?= $announcement["status"] === "published" ? "draft" : "published" ?>">

                                        <button type="submit"
                                                class="action-btn status-btn"
                                                title="<?= $announcement["status"] === "published" ? "Unpublish" : "Publish" ?>">

                                            <i class="bi <?= $announcement["status"] === "published" ? "bi-eye-slash" : "bi-send" ?>"></i>

                                        </button>

                                    </form>

                                    <!-- Delete -->

                                    <form method="POST"
                                          class="inline-form"
                                          onsubmit="return confirm('Are you sure you want to delete this announcement?');">

                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden" name="action"
                                               value="delete">

                                        <input type="hidden" name="announcement_id"
                                               value="<?= e($announcement["id"]) ?>">

                                        <button type="submit"
                                                class="action-btn delete-btn"
                                                title="Delete">

                                            <i class="bi bi-trash"></i>

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-message">
                                No announcements found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

<!-- Create / Edit Announcement Modal -->

<div class="modal fade" id="announcementModal" tabindex="-1"
     aria-labelledby="announcementModalTitle" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">

                    <h5 class="modal-title" id="announcementModalTitle">
                        Create Announcement
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION["csrf_token"]) ?>">

                    <input type="hidden" name="action"
                           id="formAction" value="create">

                    <input type="hidden" name="announcement_id"
                           id="announcementId">

                    <div class="mb-3">

                        <label for="announcementTitle" class="form-label">
                            Announcement Title
                        </label>

                        <input
                            type="text"
                            name="title"
                            id="announcementTitle"
                            class="form-control"
                            maxlength="255"
                            placeholder="Enter announcement title"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label for="announcementDescription" class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            id="announcementDescription"
                            class="form-control"
                            rows="6"
                            placeholder="Enter announcement details..."
                            required
                        ></textarea>

                    </div>

                    <div class="mb-3">

                        <label for="announcementStatus" class="form-label">
                            Publication Status
                        </label>

                        <select
                            name="status"
                            id="announcementStatus"
                            class="form-select"
                            required
                        >

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/admin.js"></script>
<script src="../assets/js/announcements.js"></script>

</body>
</html>
