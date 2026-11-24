
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/includes/db.php';

$profileMessage = '';
$profileMessageType = 'success';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$adminId = (int)$_SESSION['admin_id'];

$stmt = $pdo->prepare(
    'SELECT id, name, email, status, created_at, profile_image
     FROM admin_users
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute(['id' => $adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

/* INITIALIZE ADMIN SESSION DETAILS */

$_SESSION['admin_name'] = $admin['name'];
$_SESSION['admin_email'] = $admin['email'];
$_SESSION['admin_profile_image'] = $admin['profile_image'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {

        $profileMessage = 'Invalid security token. Please try again.';
        $profileMessageType = 'danger';

    } else {

        $action = $_POST['action'] ?? '';

        try {

            /* PROFILE IMAGE UPLOAD */

            if ($action === 'upload_profile_image') {

                if (
                    !isset($_FILES['profile_image']) ||
                    $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK
                ) {
                    throw new InvalidArgumentException('Please select a valid image.');
                }

                $file = $_FILES['profile_image'];

                if ($file['size'] > 2 * 1024 * 1024) {
                    throw new InvalidArgumentException('Image size must not exceed 2 MB.');
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedTypes[$mime])) {
                    throw new InvalidArgumentException(
                        'Only JPG, PNG, and WEBP images are allowed.'
                    );
                }

                $uploadDirectory = __DIR__ . '/uploads/profile_images';

                if (
                    !is_dir($uploadDirectory) &&
                    !mkdir($uploadDirectory, 0755, true)
                ) {
                    throw new RuntimeException(
                        'Unable to create the image upload folder.'
                    );
                }

                $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];

                $destination = $uploadDirectory . '/' . $fileName;
                $relativePath = 'uploads/profile_images/' . $fileName;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new RuntimeException(
                        'Unable to upload the profile image.'
                    );
                }

                $updateImage = $pdo->prepare(
                    'UPDATE admin_users
                     SET profile_image = :image
                     WHERE id = :id'
                );

                $updateImage->execute([
                    'image' => $relativePath,
                    'id' => $adminId
                ]);

                /* UPDATE SESSION IMAGE */

                $_SESSION['admin_profile_image'] = $relativePath;

                $admin['profile_image'] = $relativePath;

                $profileMessage = 'Profile picture updated successfully.';

            }

            /* UPDATE PROFILE DETAILS */

            elseif ($action === 'update_profile') {

                $name = trim((string)($_POST['name'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));

                if ($name === '') {
                    throw new InvalidArgumentException('Full name is required.');
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException(
                        'Please enter a valid email address.'
                    );
                }

                $update = $pdo->prepare(
                    'UPDATE admin_users
                     SET name = :name, email = :email
                     WHERE id = :id'
                );

                $update->execute([
                    'name' => $name,
                    'email' => $email,
                    'id' => $adminId
                ]);

                /* UPDATE SESSION DETAILS */

                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;

                $admin['name'] = $name;
                $admin['email'] = $email;

                $profileMessage = 'Profile details updated successfully.';

            }

            /* CHANGE PASSWORD */

            elseif ($action === 'change_password') {

                $currentPassword = (string)($_POST['current_password'] ?? '');
                $newPassword = (string)($_POST['new_password'] ?? '');
                $confirmPassword = (string)($_POST['confirm_password'] ?? '');

                if ($newPassword !== $confirmPassword) {
                    throw new InvalidArgumentException(
                        'New password and confirmation do not match.'
                    );
                }

                if (strlen($newPassword) < 8) {
                    throw new InvalidArgumentException(
                        'New password must be at least 8 characters.'
                    );
                }

                $passwordStmt = $pdo->prepare(
                    'SELECT password
                     FROM admin_users
                     WHERE id = :id'
                );

                $passwordStmt->execute(['id' => $adminId]);
                $passwordHash = $passwordStmt->fetchColumn();

                if (
                    !$passwordHash ||
                    !password_verify($currentPassword, $passwordHash)
                ) {
                    throw new InvalidArgumentException(
                        'Current password is incorrect.'
                    );
                }

                $updatePassword = $pdo->prepare(
                    'UPDATE admin_users
                     SET password = :password
                     WHERE id = :id'
                );

                $updatePassword->execute([
                    'password' => password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    ),
                    'id' => $adminId
                ]);

                $profileMessage = 'Password changed successfully.';

            }

        } catch (InvalidArgumentException $exception) {

            $profileMessage = $exception->getMessage();
            $profileMessageType = 'danger';

        } catch (PDOException $exception) {

            if ($exception->getCode() === '23000') {
                $profileMessage = 'This email address is already registered with another account.';
            } else {
                $profileMessage = 'Unable to save account changes. Please try again.';
            }

            $profileMessageType = 'danger';

        } catch (Throwable $exception) {

            $profileMessage = 'An error occurred while processing your request.';
            $profileMessageType = 'danger';
        }
    }

    /* REFRESH ADMIN DETAILS FROM DATABASE */

    $stmt->execute(['id' => $adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {

        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_profile_image'] = $admin['profile_image'] ?? '';

    }
}

/* PROFILE IMAGE PATH */

$profileImage = !empty($admin['profile_image'])
    ? $admin['profile_image']
    : 'https://placehold.co/140x140/167D8D/FFFFFF?text=Admin';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AITAM Slack | My Profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/my_profile.css">

</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <?php include 'includes/topbar.php'; ?>

        <main class="page-content profile-page">

            <div class="page-heading">
                <h2>My Profile</h2>
                <p>View and manage your personal account information.</p>
            </div>

            <?php if ($profileMessage !== ''): ?>

                <div class="alert alert-<?= e($profileMessageType) ?> alert-dismissible fade show"
                     role="alert">

                    <?= e($profileMessage) ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"></button>

                </div>

            <?php endif; ?>

            <div class="profile-layout">

                <!-- PROFILE OVERVIEW -->

                <section class="profile-overview">

                    <div class="profile-cover"></div>

                    <div class="profile-overview-content">

                        <div class="profile-image-container">

                            <img
                                src="<?= e($profileImage) ?>"
                                id="profilePreview"
                                alt="Admin Profile Picture"
                            >

                            <form
                                id="imageUploadForm"
                                method="POST"
                                action="my_profile.php"
                                enctype="multipart/form-data"
                            >

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= e(admin_csrf_token()) ?>">

                                <input type="hidden"
                                       name="action"
                                       value="upload_profile_image">

                                <label for="profilePicture"
                                       class="camera-icon"
                                       title="Change profile picture">

                                    <i class="bi bi-camera-fill"></i>

                                </label>

                                <input
                                    type="file"
                                    id="profilePicture"
                                    name="profile_image"
                                    accept="image/png,image/jpeg,image/webp"
                                    hidden
                                >

                            </form>

                        </div>

                        <div class="profile-details">

                            <h3><?= e($admin['name']) ?></h3>

                            <p class="profile-role">
                                <i class="bi bi-shield-check"></i>
                                Administrator
                            </p>

                            <p class="profile-email">
                                <?= e($admin['email']) ?>
                            </p>

                            <span class="profile-active">
                                <i class="bi bi-circle-fill"></i>
                                <?= e(ucfirst($admin['status'])) ?> Account
                            </span>

                        </div>

                    </div>

                    <div class="profile-overview-footer">

                        <div>
                            <i class="bi bi-calendar-check"></i>
                            <span>Member Since</span>

                            <strong>
                                <?= e(date('d F Y', strtotime($admin['created_at']))) ?>
                            </strong>
                        </div>

                    </div>

                </section>

                <!-- PERSONAL INFORMATION -->

                <section class="profile-card">

                    <div class="section-heading">
                        <div>
                            <h4>Personal Information</h4>
                            <p>Manage your personal details.</p>
                        </div>
                    </div>

                    <form id="profileForm" method="POST" action="my_profile.php">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= e(admin_csrf_token()) ?>">

                        <input type="hidden"
                               name="action"
                               value="update_profile">

                        <div class="profile-form-grid">

                            <div class="form-group">

                                <label for="fullName">Full Name</label>

                                <input type="text"
                                       id="fullName"
                                       name="name"
                                       value="<?= e($admin['name']) ?>"
                                       maxlength="100"
                                       required>

                            </div>

                            <div class="form-group">

                                <label for="email">Email Address</label>

                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="<?= e($admin['email']) ?>"
                                       maxlength="255"
                                       required>

                            </div>

                        </div>

                        <div class="profile-actions show" id="profileActions">

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

                <!-- ACCOUNT INFORMATION -->

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
                                <span>Account Status</span>
                                <strong><?= e(ucfirst($admin['status'])) ?></strong>
                            </div>
                        </div>

                        <div class="account-info-item">
                            <i class="bi bi-calendar3"></i>
                            <div>
                                <span>Registration Date</span>
                                <strong>
                                    <?= e(date('d F Y', strtotime($admin['created_at']))) ?>
                                </strong>
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

                <!-- CHANGE PASSWORD -->

                <section class="profile-card password-card">

                    <div class="section-heading">

                        <div>
                            <h4>Change Password</h4>
                            <p>Update your account password.</p>
                        </div>

                        <i class="bi bi-lock password-heading-icon"></i>

                    </div>

                    <form id="passwordForm" method="POST" action="my_profile.php">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= e(admin_csrf_token()) ?>">

                        <input type="hidden"
                               name="action"
                               value="change_password">

                        <div class="password-grid">

                            <div class="form-group">

                                <label for="currentPassword">Current Password</label>

                                <div class="password-input">

                                    <input type="password"
                                           id="currentPassword"
                                           name="current_password"
                                           placeholder="Enter current password"
                                           required>

                                    <button type="button"
                                            class="password-toggle"
                                            data-target="currentPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                </div>

                            </div>

                            <div class="form-group">

                                <label for="newPassword">New Password</label>

                                <div class="password-input">

                                    <input type="password"
                                           id="newPassword"
                                           name="new_password"
                                           placeholder="Enter new password"
                                           minlength="8"
                                           required>

                                    <button type="button"
                                            class="password-toggle"
                                            data-target="newPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                </div>

                            </div>

                            <div class="form-group">

                                <label for="confirmPassword">Confirm New Password</label>

                                <div class="password-input">

                                    <input type="password"
                                           id="confirmPassword"
                                           name="confirm_password"
                                           placeholder="Confirm new password"
                                           minlength="8"
                                           required>

                                    <button type="button"
                                            class="password-toggle"
                                            data-target="confirmPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                </div>

                            </div>

                        </div>

                        <button type="submit" class="change-password-btn">
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

        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>

    <script>

        /* PROFILE IMAGE UPLOAD */

        document.getElementById('profilePicture').addEventListener('change', function () {

            if (this.files.length > 0) {
                document.getElementById('imageUploadForm').submit();
            }

        });

        /* CANCEL PROFILE EDIT */

        const originalName = document.getElementById('fullName').value;
        const originalEmail = document.getElementById('email').value;

        document.getElementById('cancelProfileBtn').addEventListener('click', function () {

            document.getElementById('fullName').value = originalName;
            document.getElementById('email').value = originalEmail;

        });

        /* PASSWORD VISIBILITY */

        document.querySelectorAll('.password-toggle').forEach(function (button) {

            button.addEventListener('click', function () {

                const input = document.getElementById(this.dataset.target);
                const icon = this.querySelector('i');

                if (input.type === 'password') {

                    input.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');

                } else {

                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');

                }

            });

        });

    </script>

</body>
</html>
