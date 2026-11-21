
<?php
session_start();
require_once "includes/db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Update user status or verification
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Invalid request.");
    }

    $id = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if ($id) {
        if ($action === "verify") {
            $stmt = $pdo->prepare(
                "UPDATE users SET is_verified =
                CASE WHEN is_verified = 1 THEN 0 ELSE 1 END
                WHERE id = ?"
            );
            $stmt->execute([$id]);

        } elseif ($action === "status") {
            $stmt = $pdo->prepare(
                "UPDATE users SET status =
                CASE WHEN status = 'active'
                THEN 'blocked' ELSE 'active' END
                WHERE id = ?"
            );
            $stmt->execute([$id]);
        }
    }

    header("Location: users.php");
    exit;
}

// Search and filters
$search = trim($_GET["search"] ?? "");
$role = $_GET["role"] ?? "";
$status = $_GET["status"] ?? "";

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search !== "") {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (in_array($role, ["student", "faculty", "admin"], true)) {
    $sql .= " AND role = ?";
    $params[] = $role;
}

if (in_array($status, ["active", "blocked"], true)) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dashboard counts
$totalUsers = $pdo->query(
    "SELECT COUNT(*) FROM users"
)->fetchColumn();

$activeUsers = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE status='active'"
)->fetchColumn();

$blockedUsers = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE status='blocked'"
)->fetchColumn();

$verifiedUsers = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE is_verified=1"
)->fetchColumn();
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
                <i class="bi bi-patch-check"></i>
                <div>
                    <p>Verified Users</p>
                    <h3><?= e($verifiedUsers) ?></h3>
                </div>
            </div>

        </div>

        <!-- Registered Users -->
        <section class="users-panel">

            <h4>Registered Users</h4>

            <form method="GET" class="filter-bar">

                <input type="search"
                       name="search"
                       placeholder="Search users..."
                       value="<?= e($search) ?>">

                <select name="role">
                    <option value="">All Roles</option>
                    <option value="student"
                        <?= $role === "student" ? "selected" : "" ?>>
                        Student
                    </option>
                    <option value="faculty"
                        <?= $role === "faculty" ? "selected" : "" ?>>
                        Faculty
                    </option>
                    <option value="admin"
                        <?= $role === "admin" ? "selected" : "" ?>>
                        Admin
                    </option>
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

            <div class="table-responsive">

                <table class="table users-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Verification</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($users) > 0): ?>

                        <?php foreach ($users as $user): ?>

                        <tr>
                            <td><?= e($user["id"]) ?></td>
                            <td><?= e($user["name"]) ?></td>
                            <td><?= e($user["email"]) ?></td>
                            <td><?= e(ucfirst($user["role"])) ?></td>
                            <td><?= e($user["department"]) ?></td>

                            <td>
                                <span class="status <?= $user["status"] === "active" ? "active" : "blocked" ?>">
                                    <?= e(ucfirst($user["status"])) ?>
                                </span>
                            </td>

                            <td>
                                <?= $user["is_verified"] ? "Verified" : "Pending" ?>
                            </td>

                            <td>
                                <div class="action-buttons">

                                    <button type="button"
                                            class="view-btn"
                                            data-name="<?= e($user["name"]) ?>"
                                            data-email="<?= e($user["email"]) ?>"
                                            data-role="<?= e($user["role"]) ?>"
                                            data-department="<?= e($user["department"]) ?>"
                                            data-phone="<?= e($user["phone"]) ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <form method="POST">
                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION["csrf_token"]) ?>">
                                        <input type="hidden"
                                               name="user_id"
                                               value="<?= e($user["id"]) ?>">
                                        <input type="hidden"
                                               name="action"
                                               value="verify">

                                        <button type="submit"
                                                class="verify-btn"
                                                title="Toggle verification">
                                            <i class="bi bi-patch-check"></i>
                                        </button>
                                    </form>

                                    <form method="POST" class="status-form">
                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION["csrf_token"]) ?>">
                                        <input type="hidden"
                                               name="user_id"
                                               value="<?= e($user["id"]) ?>">
                                        <input type="hidden"
                                               name="action"
                                               value="status">

                                        <button type="submit"
                                                class="block-btn">
                                            <i class="bi bi-person-slash"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center">
                                No users found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>
</div>

<!-- View User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p><strong>Name:</strong> <span id="modalName"></span></p>
                <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                <p><strong>Role:</strong> <span id="modalRole"></span></p>
                <p><strong>Department:</strong> <span id="modalDepartment"></span></p>
                <p><strong>Phone:</strong> <span id="modalPhone"></span></p>
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
