<?php
// auth/login_process.php
session_start();
require_once '../config/db.php';

// 1. Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// 2. Verify CSRF Token
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['login_error'] = 'Invalid security token. Please refresh and try again.';
    header('Location: ../login.php');
    exit;
}

$email    = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter both a valid email address and password.';
    header('Location: ../login.php');
    exit;
}

try {
    // 3. Fetch user by email
    $stmt = $pdo->prepare('
        SELECT user_id, full_name, email, department, role, password_hash, status
        FROM users
        WHERE email = :email
        LIMIT 1
    ');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // 4. Verify user exists & password matches
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_error'] = 'Invalid email or password.';
        header('Location: ../login.php');
        exit;
    }

    // 5. Check if admin blocked the user
    if ($user['status'] !== 'active') {
        $_SESSION['login_error'] = 'Your account has been suspended. Contact the administrator.';
        header('Location: ../login.php');
        exit;
    }

    // 6. Regenerate session ID & store user session data
    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['department'] = $user['department'];
    $_SESSION['role']       = $user['role'];

    // 7. Role-based redirect (Admin goes to Admin Panel, others go to Home Page)
    if ($user['role'] === 'admin') {
        header('Location: ../admin.php');
    } else {
        header('Location: ../index.php');
    }
    exit;

} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    $_SESSION['login_error'] = 'Unable to sign in right now. Please try again later.';
    header('Location: ../login.php');
    exit;
}