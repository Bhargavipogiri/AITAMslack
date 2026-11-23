
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$profileMessage = '';
$profileMessageType = 'success';

$stmt = $pdo->prepare(
    'SELECT id, name, email, status, created_at
     FROM admin_users
     WHERE id = :id
     LIMIT 1'
);
$stmt->execute(['id' => (int)$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $profileMessage = 'Invalid security token. Please try again.';
        $profileMessageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'update_profile') {
                $name = trim((string)($_POST['name'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));

                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('A name and valid email address are required.');
                }

                $update = $pdo->prepare(
                    'UPDATE admin_users SET name = :name, email = :email WHERE id = :id'
                );
                $update->execute([
                    'name' => $name,
                    'email' => $email,
                    'id' => (int)$admin['id']
                ]);

                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;
                $admin['name'] = $name;
                $admin['email'] = $email;
                $profileMessage = 'Profile updated successfully.';
            } elseif ($action === 'change_password') {
                $currentPassword = (string)($_POST['current_password'] ?? '');
                $newPassword = (string)($_POST['new_password'] ?? '');
                $confirmPassword = (string)($_POST['confirm_password'] ?? '');

                if ($newPassword !== $confirmPassword) {
                    throw new InvalidArgumentException('New password and confirmation do not match.');
                }
                if (strlen($newPassword) < 8) {
                    throw new InvalidArgumentException('New password must be at least 8 characters.');
                }

                $passwordStmt = $pdo->prepare('SELECT password FROM admin_users WHERE id = :id');
                $passwordStmt->execute(['id' => (int)$admin['id']]);
                $passwordHash = $passwordStmt->fetchColumn();

                if (!password_verify($currentPassword, $passwordHash)) {
                    throw new InvalidArgumentException('Current password is incorrect.');
                }

                $updatePassword = $pdo->prepare(
                    'UPDATE admin_users SET password = :password WHERE id = :id'
                );
                $updatePassword->execute([
                    'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'id' => (int)$admin['id']
                ]);
                $profileMessage = 'Password changed successfully.';
            }
        } catch (InvalidArgumentException $exception) {
            $profileMessage = $exception->getMessage();
            $profileMessageType = 'danger';
        } catch (PDOException $exception) {
            $profileMessage = 'Unable to save account changes.';
            $profileMessageType = 'danger';
        }
    }
}

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AITAM Slack | My Profile</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Project CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/my_profile.css">
</head>

<body>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <!-- Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <main class="page-content profile-page">

            <!-- Page Heading -->
            <div class="page-heading">
                <h2>My Profile</h2>
                <p>View and manage your personal account information.</p>
            </div>

            <!-- Profile Layout -->
            <div class="profile-layout">

                <!-- Profile Overview -->
                <section class="profile-overview">

                    <div class="profile-cover"></div>

                    <div class="profile-overview-content">

                        <div class="profile-image-container">

                            <img
                                src="https://placehold.co/140x140/167D8D/FFFFFF?text=Admin"
                                id="profilePreview"
                                alt="Admin Profile Picture"
                            >

                            <label for="profilePicture" class="camera-icon"
                                   title="Change profile picture">
                                <i class="bi bi-camera-fill"></i>
                            </label>

                            <input type="file"
                                   id="profilePicture"
                                   accept="image/png,image/jpeg,image/webp"
                                   hidden>

                        </div>

                        <h3 id="profileName"><?= e($admin['name']) ?></h3>

                        <p class="profile-role">
                            <i class="bi bi-shield-check"></i>
                            Administrator
                        </p>

                        <p class="profile-email" id="profileEmail"><?= e($admin['email']) ?></p>

                        <span class="profile-active">
                            <i class="bi bi-circle-fill"></i>
                            <?= e(ucfirst($admin['status'])) ?> Account
                        </span>

                    </div>

                    <div class="profile-overview-footer">
                        <div>
                            <i class="bi bi-calendar-check"></i>
                            <span>Member Since</span>
                            <strong><?= e(date('d F Y', strtotime($admin['created_at']))) ?></strong>
                        </div>
                    </div>

                </section>

                <!-- Personal Information -->
                <section class="profile-card">

                    <div class="section-heading">

                        <div>
                            <h4>Personal Information</h4>
                            <p>Manage your personal details.</p>
                        </div>

                    </div>

                    <?php if ($profileMessage !== ''): ?>
                        <div class="alert alert-<?= e($profileMessageType) ?>" role="alert">
                            <?= e($profileMessage) ?>
                        </div>
                    <?php endif; ?>

                    <form id="profileForm" method="post" action="my_profile.php">
                        <input type="hidden" name="csrf_token" value="<?= e(admin_csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="profile-form-grid">

                            <div class="form-group">
                                <label for="fullName">Full Name</label>
                                <input type="text"
                                       id="fullName"
                                       name="name"
                                       value="<?= e($admin['name']) ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="<?= e($admin['email']) ?>"
                                       required>
                            </div>


                        </div>

                        <div class="profile-actions" id="profileActions">

                            <button type="submit" class="save-btn">
                                <i class="bi bi-check-lg"></i>
                                Save Changes
                            </button>

                            <button type="button"
                                    class="cancel-btn"
                                    id="cancelProfileBtn">
                                Cancel
                            </button>

                        </div>

                    </form>

                </section>

                <!-- Account Information -->
                <section class="profile-card">

                    <div class="section-heading">
                        <div>
                            <h4>Account Information</h4>
                            <p>Details about your administrator account.</p>
                        </div>
                    </div>

                    <div class="account-info-grid">

                        <div class="account-info-item">
                            <i class="bi bi-person-badge"></i>
                            <div>
                                <span>Account Type</span>
                                <strong>Administrator</strong>
                            </div>
                        </div>

                        <div class="account-info-item">
                            <i class="bi bi-patch-check"></i>
                            <div>
                                <span>Verification</span>
                                <strong><?= e(ucfirst($admin['status'])) ?></strong>
                            </div>
                        </div>

                        <div class="account-info-item">
                            <i class="bi bi-calendar3"></i>
                            <div>
                                <span>Registration Date</span>
                                <strong><?= e(date('d F Y', strtotime($admin['created_at']))) ?></strong>
                            </div>
                        </div>

                        <div class="account-info-item">
                            <i class="bi bi-shield-lock"></i>
                            <div>
                                <span>Account Security</span>
                                <strong>Password protected</strong>
                            </div>
                        </div>

                    </div>

                </section>

                <!-- Change Password -->
                <section class="profile-card password-card">

                    <div class="section-heading">
                        <div>
                            <h4>Change Password</h4>
                            <p>Update your account password.</p>
                        </div>

                        <i class="bi bi-lock password-heading-icon"></i>
                    </div>

                    <form id="passwordForm" method="post" action="my_profile.php">
                        <input type="hidden" name="csrf_token" value="<?= e(admin_csrf_token()) ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="password-grid">

                            <div class="form-group">
                                <label for="currentPassword">
                                    Current Password
                                </label>

                                <div class="password-input">
                                    <input type="password"
                                           id="currentPassword"
                                           name="current_password"
                                           placeholder="Enter current password"
                                           required>

                                    <button type="button"
                                            class="password-toggle"
                                            data-target="currentPassword"
                                            aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="newPassword">
                                    New Password
                                </label>

                                <div class="password-input">
                                    <input type="password"
                                           id="newPassword"
                                           name="new_password"
                                           placeholder="Enter new password"
                                           minlength="8"
                                           required>

                                    <button type="button"
                                            class="password-toggle"
                                            data-target="newPassword"
                                            aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">
                                    Confirm New Password
                                </label>

                                <div class="password-input">
                                    <input type="password"
                                           id="confirmPassword"
                                           name="confirm_password"
                                           placeholder="Confirm new password"
                                           minlength="8"
                                           required>

                                    <button type="button"
                                            class="password-toggle"
                                            data-target="confirmPassword"
                                            aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                        </div>

                        <button type="submit"
                                class="change-password-btn"
                                id="changePasswordBtn">
                            <i class="bi bi-shield-lock"></i>
                            Change Password
                        </button>

                    </form>

                    <p class="security-note">
                        <i class="bi bi-info-circle"></i>
                        Use a strong password of at least 8 characters.
                    </p>

                </section>

            </div>

            <!-- Message -->
            <div id="profileMessage" class="profile-message"
                 role="status" aria-live="polite"></div>

        </main>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Project JS -->
    <script src="../assets/js/admin.js"></script>

</body>
</html>
