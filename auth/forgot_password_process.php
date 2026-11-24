<?php
// auth/forgot_password_process.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.php');
    exit;
}

// Verify CSRF token
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['fp_error'] = 'Invalid security token. Please refresh and try again.';
    header('Location: ../forgot_password.php');
    exit;
}

$action = $_POST['action'] ?? '';

/**
 * Helper: Masks email like j**********@gmail.com
 */
function maskEmail(string $email): string {
    [$user, $domain] = explode('@', $email);
    $firstChar = mb_substr($user, 0, 1);
    $stars = str_repeat('*', max(6, mb_strlen($user) - 1));
    return $firstChar . $stars . '@' . $domain;
}

/* =========================================================
   STEP 1: SEND 6-DIGIT OTP TO REGISTERED EMAIL VIA GMAIL SMTP
   ========================================================= */
if ($action === 'send_otp') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['fp_error'] = 'Please enter a valid email address.';
        header('Location: ../forgot_password.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT user_id, full_name, email FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['fp_error'] = 'No account found with this registered email address.';
            header('Location: ../forgot_password.php');
            exit;
        }

        // Generate 6-digit OTP valid for 10 minutes
        $otp = (string) random_int(100000, 999999);

        $_SESSION['fp_step']         = 'verify_otp';
        $_SESSION['fp_user_id']      = $user['user_id'];
        $_SESSION['fp_email']        = $user['email'];
        $_SESSION['fp_masked_email'] = maskEmail($user['email']);
        $_SESSION['fp_otp_hash']     = password_hash($otp, PASSWORD_DEFAULT);
        $_SESSION['fp_otp_expires']  = time() + 600; // 10 minutes
        $_SESSION['fp_verified']     = false;

        // Send OTP via PHPMailer + Gmail SMTP
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_EMAIL;
            $mail->Password   = str_replace(' ', '', SMTP_APP_PASSWORD);
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom(SMTP_EMAIL, 'AITAM Slack');
            $mail->addAddress($user['email'], $user['full_name']);

            $mail->isHTML(true);
            $mail->Subject = 'AITAM Slack - Password Reset OTP';
            $mail->Body    = "
                <div style='font-family: Inter, Arial, sans-serif; max-width: 480px; margin: auto; padding: 24px; border: 1px solid #DCE6EF; border-radius: 12px; background: #FFFFFF;'>
                    <h2 style='color: #0F2854; margin: 0 0 12px;'>AITAM Slack Account Recovery</h2>
                    <p style='color: #334155; font-size: 14px; line-height: 1.6;'>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
                    <p style='color: #334155; font-size: 14px; line-height: 1.6;'>Use the 6-digit One-Time Password (OTP) below to reset your account password:</p>
                    <div style='font-size: 28px; font-weight: 700; letter-spacing: 8px; color: #1C4D8D; background: #EAF2F9; padding: 14px; text-align: center; border-radius: 8px; margin: 18px 0;'>
                        {$otp}
                    </div>
                    <p style='color: #64748B; font-size: 12px; margin-bottom: 0;'>This code expires in 10 minutes. If you did not request this, you can safely ignore this email.</p>
                </div>
            ";
            $mail->AltBody = "Hello {$user['full_name']}, your AITAM Slack password reset OTP is: {$otp} (valid for 10 minutes).";

            $mail->send();
            unset($_SESSION['fp_dev_otp']); // Real email sent; hide dev banner

        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            $_SESSION['fp_step']  = 'enter_email';
            $_SESSION['fp_error'] = $mail->ErrorInfo;
            header('Location: ../forgot_password.php');
            exit;
        }

        header('Location: ../forgot_password.php');
        exit;

    } catch (PDOException $e) {
        error_log("Forgot Password Error: " . $e->getMessage());
        $_SESSION['fp_error'] = 'Server error while processing your request.';
        header('Location: ../forgot_password.php');
        exit;
    }
}

/* =========================================================
   STEP 2: VERIFY 6-DIGIT OTP
   ========================================================= */
if ($action === 'verify_otp') {
    $otp_digits = $_POST['otp'] ?? [];
    $entered_otp = is_array($otp_digits) ? implode('', array_map('trim', $otp_digits)) : trim((string) $otp_digits);

    if (empty($_SESSION['fp_otp_hash']) || empty($_SESSION['fp_user_id'])) {
        $_SESSION['fp_step']  = 'enter_email';
        $_SESSION['fp_error'] = 'Session expired. Please enter your email again.';
        header('Location: ../forgot_password.php');
        exit;
    }

    if (time() > ($_SESSION['fp_otp_expires'] ?? 0)) {
        $_SESSION['fp_error'] = 'OTP has expired. Please request a new code.';
        header('Location: ../forgot_password.php');
        exit;
    }

    if (!preg_match('/^\d{6}$/', $entered_otp) || !password_verify($entered_otp, $_SESSION['fp_otp_hash'])) {
        $_SESSION['fp_error'] = 'Incorrect OTP. Please check the 6-digit code and try again.';
        header('Location: ../forgot_password.php');
        exit;
    }

    // OTP is valid -> unlock Step 3 (Change Password)
    $_SESSION['fp_verified'] = true;
    $_SESSION['fp_step']     = 'reset_password';
    unset($_SESSION['fp_dev_otp']);

    header('Location: ../forgot_password.php');
    exit;
}

/* =========================================================
   STEP 3: CHANGE PASSWORD & CONFIRM NEW PASSWORD
   ========================================================= */
if ($action === 'reset_password') {
    if (empty($_SESSION['fp_verified']) || empty($_SESSION['fp_user_id'])) {
        $_SESSION['fp_step']  = 'enter_email';
        $_SESSION['fp_error'] = 'Unauthorized password reset attempt. Please verify OTP first.';
        header('Location: ../forgot_password.php');
        exit;
    }

    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $_SESSION['fp_error'] = 'New password must be at least 8 characters long.';
        header('Location: ../forgot_password.php');
        exit;
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['fp_error'] = 'Passwords do not match.';
        header('Location: ../forgot_password.php');
        exit;
    }

    try {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $updateStmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :id');
        $updateStmt->execute([
            ':hash' => $new_hash,
            ':id'   => $_SESSION['fp_user_id']
        ]);

        // Clear all forgot password session variables
        unset(
            $_SESSION['fp_step'],
            $_SESSION['fp_user_id'],
            $_SESSION['fp_email'],
            $_SESSION['fp_masked_email'],
            $_SESSION['fp_otp_hash'],
            $_SESSION['fp_otp_expires'],
            $_SESSION['fp_verified'],
            $_SESSION['fp_dev_otp']
        );

        $_SESSION['register_success'] = 'Password changed successfully! Please sign in with your new password.';
        header('Location: ../login.php');
        exit;

    } catch (PDOException $e) {
        error_log("Password Reset Update Error: " . $e->getMessage());
        $_SESSION['fp_error'] = 'Unable to update password right now. Please try again.';
        header('Location: ../forgot_password.php');
        exit;
    }
}

header('Location: ../forgot_password.php');
exit;