
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

// Handle user status updates
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Invalid request.");
    }

    $userId = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if ($userId && $action === "status") {

        $stmt = $pdo->prepare("
            UPDATE users
            SET status = CASE
                WHEN status = 'active' THEN 'blocked'
                ELSE 'active'
            END
            WHERE user_id = ?
        ");

        $stmt->execute([$userId]);
    }

    header("Location: users.php");
    exit;
}

// Search and filters
$search = trim($_GET["search"] ?? "");
$role = $_GET["role"] ?? "";
$status = $_GET["status"] ?? "";

$page = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$perPage = 20;

// Get available roles
$roles = $pdo->query("
    SELECT DISTINCT role
    FROM users
    WHERE role IS NOT NULL AND role != ''
    ORDER BY role
")->fetchAll(PDO::FETCH_COLUMN);

// Build search query
$countSql = "SELECT COUNT(*) FROM users WHERE 1=1";
$sql = "SELECT * FROM users WHERE 1=1";

$params = [];

if ($search !== "") {

    $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $countSql .= " AND (full_name LIKE ? OR email LIKE ?)";

    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role !== "" && in_array($role, $roles, true)) {

    $sql .= " AND role = ?";
    $countSql .= " AND role = ?";

    $params[] = $role;
}

if (in_array($status, ["active", "blocked"], true)) {

    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";

    $params[] = $status;
}

// Get filtered user count
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);

$totalFilteredUsers = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalFilteredUsers / $perPage));
$page = min($page, $totalPages);

// Get users
$offset = ($page - 1) * $perPage;

$sql .= " ORDER BY user_id DESC";
$sql .= " LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dashboard statistics
$totalUsers = $pdo->query("
    SELECT COUNT(*) FROM users
")->fetchColumn();

$activeUsers = $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE status = 'active'
")->fetchColumn();

$blockedUsers = $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE status = 'blocked'
")->fetchColumn();

$totalPoints = $pdo->query("
    SELECT COALESCE(SUM(points), 0)
    FROM users
")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>User Management | AITAM Slack</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <!-- Project CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/users.css">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "includes/topbar.php"; ?>

    <main class="page-content users-page">

        <!-- Page Heading -->
        <div class="page-heading">
            <h2>User Management</h2>
            <p>Manage all registered users</p>
        </div>

        <!-- Summary Cards -->
        <div class="user-stats">

            <div class="stat-card">
                <i class="bi bi-people"></i>

                <div>
                    <p>Total Users</p>
                    <h3><?= e($totalUsers) ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <i class="bi bi-person-check"></i>

                <div>
                    <p>Active Users</p>
                    <h3><?= e($activeUsers) ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <i class="bi bi-person-x"></i>

                <div>
                    <p>Blocked Users</p>
                    <h3><?= e($blockedUsers) ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <i class="bi bi-star"></i>

                <div>
                    <p>Total Points</p>
                    <h3><?= e($totalPoints) ?></h3>
                </div>
            </div>

        </div>

        <!-- Registered Users -->
        <section class="users-panel">

            <h4>Registered Users</h4>

            <!-- Search and Filters -->
            <form method="GET" class="filter-bar">

                <input
                    type="search"
                    name="search"
                    placeholder="Search users..."
                    value="<?= e($search) ?>"
                >

                <select name="role">

                    <option value="">All Roles</option>

                    <?php foreach ($roles as $availableRole): ?>

                        <option
                            value="<?= e($availableRole) ?>"
                            <?= $role === $availableRole ? "selected" : "" ?>
                        >
                            <?= e(ucfirst($availableRole)) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <select name="status">

                    <option value="">All Status</option>

                    <option value="active"
                        <?= $status === "active" ? "selected" : "" ?>>
                        Active
                    </option>

                    <option value="blocked"
                        <?= $status === "blocked" ? "selected" : "" ?>>
                        Blocked
                    </option>

                </select>

                <button type="submit">Search</button>

                <a href="users.php">Reset</a>

            </form>

            <!-- Users Table -->
            <div class="table-responsive">

                <table class="table users-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($users) > 0): ?>

                        <?php foreach ($users as $user): ?>

                            <tr>

                                <td>
                                    <?= e($user["user_id"]) ?>
                                </td>

                                <td>
                                    <?= e($user["full_name"]) ?>
                                </td>

                                <td>
                                    <?= e($user["email"]) ?>
                                </td>

                                <td>
                                    <?= e(ucfirst($user["role"])) ?>
                                </td>

                                <td>
                                    <?= e($user["department"]) ?>
                                </td>

                                <td>
                                    <?= e($user["points"]) ?>
                                </td>

                                <td>
                                    <span class="status <?= $user["status"] === "active" ? "active" : "blocked" ?>">
                                        <?= e(ucfirst($user["status"])) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= !empty($user["created_at"])
                                        ? e(date("d M Y", strtotime($user["created_at"])))
                                        : "N/A" ?>
                                </td>

                                <td>

                                    <div class="action-buttons">

                                        <!-- View User -->
                                        <button
                                            type="button"
                                            class="view-btn"
                                            data-name="<?= e($user["full_name"]) ?>"
                                            data-email="<?= e($user["email"]) ?>"
                                            data-role="<?= e($user["role"]) ?>"
                                            data-department="<?= e($user["department"]) ?>"
                                            data-phone="Not available"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <!-- Block / Unblock User -->
                                        <form method="POST" class="status-form">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION["csrf_token"]) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= e($user["user_id"]) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="status"
                                            >

                                            <button
                                                type="submit"
                                                class="block-btn"
                                                title="<?= $user["status"] === "active" ? "Block User" : "Unblock User" ?>"
                                            >
                                                <i class="bi bi-person-slash"></i>
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="text-center">
                                No users found.
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
                                "role" => $role,
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

<!-- View User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">User Details</h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <p>
                    <strong>Name:</strong>
                    <span id="modalName"></span>
                </p>

                <p>
                    <strong>Email:</strong>
                    <span id="modalEmail"></span>
                </p>

                <p>
                    <strong>Role:</strong>
                    <span id="modalRole"></span>
                </p>

                <p>
                    <strong>Department:</strong>
                    <span id="modalDepartment"></span>
                </p>

                <p>
                    <strong>Phone:</strong>
                    <span id="modalPhone">Not available</span>
                </p>

            </div>

        </div>

    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Project JavaScript -->
<script src="../assets/js/admin.js"></script>
<script src="../assets/js/users.js"></script>

</body>
</html>
