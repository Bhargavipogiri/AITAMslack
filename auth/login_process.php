<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? null;
$sessionToken = $_SESSION['csrf_token'] ?? null;
if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    $_SESSION['login_error'] = 'Invalid security token. Please refresh and try again.';
    header('Location: ../login.php');
    exit;
}

$emailInput = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

if (!is_string($emailInput) || !is_string($password)) {
    $_SESSION['login_error'] = 'Please enter both a valid email address and password.';
    header('Location: ../login.php');
    exit;
}

$email = strtolower(trim($emailInput));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['login_error'] = 'Please enter both a valid email address and password.';
    header('Location: ../login.php');
    exit;
}

try {
    $adminStmt = $pdo->prepare(
        'SELECT id, name, email, password, status
         FROM admin_users
         WHERE email = :email
         LIMIT 1'
    );
    $adminStmt->execute([':email' => $email]);
    $admin = $adminStmt->fetch();

    if ($admin) {
        if (!password_verify($password, $admin['password'])) {
            $_SESSION['login_error'] = 'Invalid email or password.';
            header('Location: ../login.php');
            exit;
        }

        if ($admin['status'] !== 'active') {
            $_SESSION['login_error'] = 'Your administrator account is blocked.';
            header('Location: ../login.php');
            exit;
        }

        session_regenerate_id(true);
        unset(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            $_SESSION['email'],
            $_SESSION['department'],
            $_SESSION['role']
        );
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_status'] = $admin['status'];
        header('Location: ../admin/dashboard.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, email, department, role, password_hash, status
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_error'] = 'Invalid email or password.';
        header('Location: ../login.php');
        exit;
    }

    if ($user['status'] !== 'active') {
        $_SESSION['login_error'] = 'Your account has been suspended. Contact the administrator.';
        header('Location: ../login.php');
        exit;
    }

    if ($user['role'] === 'admin') {
        $_SESSION['login_error'] = 'This administrator account is not configured for the administration panel.';
        header('Location: ../login.php');
        exit;
    }

    session_regenerate_id(true);
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_email'],
        $_SESSION['admin_status']
    );
    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['department'] = $user['department'];
    $_SESSION['role']       = $user['role'];

    header('Location: ../index.php');
    exit;

} catch (PDOException $e) {
    error_log('Login Error: ' . $e->getMessage());
    $_SESSION['login_error'] = 'Unable to sign in right now. Please try again later.';
    header('Location: ../login.php');
    exit;
}