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

$message = "";
$messageType = "success";

$platformKeys = [
    "platform_name",
    "platform_description",
    "support_email",
    "allow_registration"
];

$notificationKeys = [
    "new_user_registration",
    "reported_questions",
    "reported_answers",
    "new_questions_answers"
];

$notificationLabels = [
    "new_user_registration" => "New User Registrations",
    "reported_questions" => "Reported Questions",
    "reported_answers" => "Reported Answers",
    "new_questions_answers" => "New Questions and Answers"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid request. Please try again.";
        $messageType = "danger";
    } else {

        $action = $_POST["action"] ?? "";

        try {

            if ($action === "save_platform") {

                $platformName = trim($_POST["platform_name"] ?? "");
                $platformDescription = trim($_POST["platform_description"] ?? "");
                $supportEmail = trim($_POST["support_email"] ?? "");
                $allowRegistration = isset($_POST["allow_registration"]) ? "1" : "0";

                if ($platformName === "") {
                    throw new Exception("Platform name is required.");
                }

                if ($supportEmail !== "" && !filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Please enter a valid support email.");
                }

                $settings = [
                    "platform_name" => $platformName,
                    "platform_description" => $platformDescription,
                    "support_email" => $supportEmail,
                    "allow_registration" => $allowRegistration
                ];

                $sql = "
                    INSERT INTO platform_settings (setting_key, setting_value)
                    VALUES (:setting_key, :setting_value)
                    ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value)
                ";

                $stmt = $pdo->prepare($sql);
                $pdo->beginTransaction();
                try {
                    foreach ($settings as $key => $value) {
                        $stmt->execute([
                            ":setting_key" => $key,
                            ":setting_value" => $value
                        ]);
                    }
                    $pdo->commit();
                } catch (Throwable $transactionError) {
                    $pdo->rollBack();
                    throw $transactionError;
                }

                $message = "Platform settings updated successfully.";
            }

            elseif ($action === "save_notifications") {

                $selectedNotifications = $_POST["notifications"] ?? [];

                if (!is_array($selectedNotifications)) {
                    $selectedNotifications = [];
                }

                $sql = "
                    INSERT INTO notification_preferences
                    (notification_key, is_enabled)
                    VALUES (:notification_key, :is_enabled)
                    ON DUPLICATE KEY UPDATE
                    is_enabled = VALUES(is_enabled)
                ";

                $stmt = $pdo->prepare($sql);
                $pdo->beginTransaction();
                try {
                    foreach ($notificationKeys as $key) {
                        $enabled = isset($selectedNotifications[$key]) ? 1 : 0;
                        $stmt->execute([
                            ":notification_key" => $key,
                            ":is_enabled" => $enabled
                        ]);
                    }
                    $pdo->commit();
                } catch (Throwable $transactionError) {
                    $pdo->rollBack();
                    throw $transactionError;
                }

                $message = "Notification preferences updated successfully.";
            }

        } catch (Exception $ex) {
            $message = $ex->getMessage();
            $messageType = "danger";
        } catch (PDOException $ex) {
            $message = "Database error. Please try again.";
            $messageType = "danger";
        }
    }
}

/* Fetch platform settings */

$platformSettings = [];

$stmt = $pdo->query("
    SELECT setting_key, setting_value
    FROM platform_settings
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $platformSettings[$row["setting_key"]] = $row["setting_value"];
}

/* Fetch notification preferences */

$notificationSettings = [];

$stmt = $pdo->query("
    SELECT notification_key, is_enabled
    FROM notification_preferences
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $notificationSettings[$row["notification_key"]] = (int)$row["is_enabled"];
}

/* Default values */

$platformName = $platformSettings["platform_name"] ?? "";
$platformDescription = $platformSettings["platform_description"] ?? "";
$supportEmail = $platformSettings["support_email"] ?? "";
$allowRegistration = $platformSettings["allow_registration"] ?? "1";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Settings | AITAM-SLACK Admin</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>

<body>

    <?php include "includes/sidebar.php"; ?>

    <div class="main-content">

        <?php include "includes/topbar.php"; ?>

        <main class="page-content">

            <div class="settings-header">
                <div>
                    <h2>Settings</h2>
                    <p>Manage your platform configuration and notification preferences.</p>
                </div>
            </div>

            <?php if ($message !== ""): ?>
                <div class="alert alert-<?php echo e($messageType); ?> alert-dismissible fade show" role="alert">
                    <?php echo e($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Platform Settings -->

            <section class="settings-card">

                <div class="settings-card-header">
                    <div class="settings-icon">
                        <i class="bi bi-sliders"></i>
                    </div>

                    <div>
                        <h4>Platform Settings</h4>
                        <p>Manage the basic information and configuration of your platform.</p>
                    </div>
                </div>

                <form method="POST" action="settings.php">

                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                    <input type="hidden" name="action" value="save_platform">

                    <div class="settings-form-grid">

                        <div class="settings-field">
                            <label for="platform_name">Platform Name</label>
                            <input
                                type="text"
                                id="platform_name"
                                name="platform_name"
                                class="form-control"
                                value="<?php echo e($platformName); ?>"
                                maxlength="100"
                                required
                            >
                        </div>

                        <div class="settings-field">
                            <label for="support_email">Support Email</label>
                            <input
                                type="email"
                                id="support_email"
                                name="support_email"
                                class="form-control"
                                value="<?php echo e($supportEmail); ?>"
                                maxlength="255"
                                placeholder="Enter support email"
                            >
                        </div>

                        <div class="settings-field full-width">
                            <label for="platform_description">Platform Description</label>
                            <textarea
                                id="platform_description"
                                name="platform_description"
                                class="form-control"
                                rows="3"
                                placeholder="Enter platform description"
                            ><?php echo e($platformDescription); ?></textarea>
                        </div>

                    </div>

                    <div class="settings-toggle-row">
                        <div>
                            <h6>Allow New User Registrations</h6>
                            <p>Allow new users to register on the platform.</p>
                        </div>

                        <label class="settings-switch">
                            <input
                                type="checkbox"
                                name="allow_registration"
                                value="1"
                                <?php echo $allowRegistration === "1" ? "checked" : ""; ?>
                            >
                            <span class="settings-slider"></span>
                        </label>
                    </div>

                    <div class="settings-actions">
                        <button type="submit" class="btn settings-save-btn">
                            <i class="bi bi-check2-circle"></i>
                            Save Platform Settings
                        </button>
                    </div>

                </form>

            </section>

            <!-- Notification Preferences -->

            <section class="settings-card">

                <div class="settings-card-header">
                    <div class="settings-icon">
                        <i class="bi bi-bell"></i>
                    </div>

                    <div>
                        <h4>Notification Preferences</h4>
                        <p>Choose which platform activities should generate notifications.</p>
                    </div>
                </div>

                <form method="POST" action="settings.php">

                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                    <input type="hidden" name="action" value="save_notifications">

                    <div class="notification-list">

                        <?php foreach ($notificationLabels as $key => $label): ?>

                            <div class="settings-toggle-row">

                                <div>
                                    <h6><?php echo e($label); ?></h6>
                                    <p>Enable or disable notifications for this activity.</p>
                                </div>

                                <label class="settings-switch">
                                    <input
                                        type="checkbox"
                                        name="notifications[<?php echo e($key); ?>]"
                                        value="1"
                                        <?php echo ($notificationSettings[$key] ?? 1) === 1 ? "checked" : ""; ?>
                                    >
                                    <span class="settings-slider"></span>
                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <div class="settings-actions">
                        <button type="submit" class="btn settings-save-btn">
                            <i class="bi bi-check2-circle"></i>
                            Save Notification Preferences
                        </button>
                    </div>

                </form>

            </section>

            <!-- Account Security -->

            <section class="settings-card">

                <div class="settings-card-header">
                    <div class="settings-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>

                    <div>
                        <h4>Account Security</h4>
                        <p>Manage administrator account security.</p>
                    </div>
                </div>

                <div class="security-coming-soon">
                    <div class="security-info">
                        <i class="bi bi-lock"></i>
                        <div>
                            <h6>Change Password</h6>
                            <p>This feature will be available after admin login functionality is implemented.</p>
                        </div>
                    </div>

                    <span class="coming-soon-badge">Coming Soon</span>
                </div>

            </section>

        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/settings.js"></script>

</body>
</html>