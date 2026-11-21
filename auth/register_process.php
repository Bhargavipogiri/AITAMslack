<?php
// auth/register_process.php
session_start();
require_once '../config/db.php';

// 1. Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

// 2. Verify CSRF Token
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['register_error'] = 'Invalid security token. Please refresh the page and try again.';
    header('Location: ../register.php');
    exit;
}

// 3. Collect & sanitize inputs
$full_name        = trim($_POST['full_name'] ?? '');
$email            = strtolower(trim($_POST['email'] ?? ''));
$department       = trim($_POST['department'] ?? '');
$role             = trim($_POST['role'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$allowed_departments = ['CSE', 'CSM', 'CSD', 'IT', 'ECE', 'EEE', 'MECH', 'CIVIL', 'OTHER'];
$allowed_roles       = ['fresher', 'student', 'faculty', 'alumni'];

// 4. Server-side validation
if (strlen($full_name) < 3 || strlen($full_name) > 100) {
    $_SESSION['register_error'] = 'Full name must be between 3 and 100 characters.';
    header('Location: ../register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = 'Please enter a valid email address.';
    header('Location: ../register.php');
    exit;
}

if (!in_array($department, $allowed_departments, true)) {
    $_SESSION['register_error'] = 'Please select a valid department.';
    header('Location: ../register.php');
    exit;
}

if (!in_array($role, $allowed_roles, true)) {
    $_SESSION['register_error'] = 'Please select a valid role.';
    header('Location: ../register.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['register_error'] = 'Password must be at least 8 characters long.';
    header('Location: ../register.php');
    exit;
}

if ($password !== $confirm_password) {
    $_SESSION['register_error'] = 'Passwords do not match.';
    header('Location: ../register.php');
    exit;
}

try {
    // 5. Check if email already exists
    $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
    $checkStmt->execute([':email' => $email]);

    if ($checkStmt->fetch()) {
        $_SESSION['register_error'] = 'An account with this email already exists. Please sign in.';
        header('Location: ../register.php');
        exit;
    }

    // 6. Hash password & insert user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare('
        INSERT INTO users (full_name, email, department, role, password_hash)
        VALUES (:full_name, :email, :department, :role, :password_hash)
    ');

    $insertStmt->execute([
        ':full_name'     => $full_name,
        ':email'         => $email,
        ':department'    => $department,
        ':role'          => $role,
        ':password_hash' => $password_hash,
    ]);

    // 7. Redirect to login page with success message
    $_SESSION['register_success'] = 'Account created successfully! Please sign in.';
    header('Location: ../login.php');
    exit;

} catch (PDOException $e) {
    error_log("Registration Error: " . $e->getMessage());
    $_SESSION['register_error'] = 'Something went wrong while creating your account. Please try again.';
    header('Location: ../register.php');
    exit;
}