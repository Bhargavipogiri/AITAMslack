<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$respond = static function (int $status, array $payload): void {
    http_response_code($status);
    echo json_encode(
        $payload,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    $respond(405, ['success' => false, 'message' => 'Only POST requests are allowed.']);
}

$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1) {
    $respond(401, ['success' => false, 'message' => 'Please sign in to update your profile.']);
}

$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';
if (
    !is_string($submittedToken)
    || !is_string($sessionToken)
    || $sessionToken === ''
    || !hash_equals($sessionToken, $submittedToken)
) {
    $respond(403, ['success' => false, 'message' => 'Your session token is invalid. Refresh and try again.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $respond(400, ['success' => false, 'message' => 'Invalid profile data.']);
}

foreach (['name', 'email', 'department', 'role'] as $requiredField) {
    if (!isset($input[$requiredField]) || !is_string($input[$requiredField])) {
        $respond(422, ['success' => false, 'message' => 'Please complete all required profile fields.']);
    }
}

$fullName = trim($input['name']);
$email = strtolower(trim($input['email']));
$department = strtoupper(trim($input['department']));
$role = strtolower(trim($input['role']));
$textLength = static function (string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
};

if ($textLength($fullName) < 3 || $textLength($fullName) > 100) {
    $respond(422, ['success' => false, 'message' => 'Full name must be between 3 and 100 characters.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    $respond(422, ['success' => false, 'message' => 'Enter a valid email address.']);
}

$allowedDepartments = ['CSE', 'CSM', 'CSD', 'IT', 'ECE', 'EEE', 'MECH', 'CIVIL', 'OTHER'];
$allowedRoles = ['fresher', 'student', 'faculty', 'alumni'];
if (!in_array($department, $allowedDepartments, true)) {
    $respond(422, ['success' => false, 'message' => 'Select a valid department.']);
}
if (!in_array($role, $allowedRoles, true)) {
    $respond(422, ['success' => false, 'message' => 'Select a valid role.']);
}

$password = $input['password'] ?? '';
if (!is_string($password)) {
    $respond(422, ['success' => false, 'message' => 'The new password is invalid.']);
}
if ($password !== '' && strlen($password) < 8) {
    $respond(422, ['success' => false, 'message' => 'New password must be at least 8 characters.']);
}

require_once __DIR__ . '/../config/db.php';

try {
    $emailStmt = $pdo->prepare(
        'SELECT user_id
         FROM users
         WHERE email = :email AND user_id <> :user_id
         LIMIT 1'
    );
    $emailStmt->execute([
        ':email' => $email,
        ':user_id' => $userId,
    ]);

    if ($emailStmt->fetchColumn() !== false) {
        $respond(409, ['success' => false, 'message' => 'That email address is already in use.']);
    }

    if ($password !== '') {
        $updateStmt = $pdo->prepare(
            'UPDATE users
             SET full_name = :full_name,
                 email = :email,
                 department = :department,
                 role = :role,
                 password_hash = :password_hash
             WHERE user_id = :user_id'
        );
        $updateStmt->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':department' => $department,
            ':role' => $role,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':user_id' => $userId,
        ]);
    } else {
        $updateStmt = $pdo->prepare(
            'UPDATE users
             SET full_name = :full_name,
                 email = :email,
                 department = :department,
                 role = :role
             WHERE user_id = :user_id'
        );
        $updateStmt->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':department' => $department,
            ':role' => $role,
            ':user_id' => $userId,
        ]);
    }

    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;
    $_SESSION['department'] = $department;
    $_SESSION['role'] = $role;

    $respond(200, [
        'success' => true,
        'message' => 'Profile updated successfully.',
        'profile' => [
            'name' => $fullName,
            'email' => $email,
            'department' => $department,
            'role' => $role,
        ],
    ]);
} catch (PDOException $exception) {
    error_log('Profile update error: ' . $exception->getMessage());
    if ($exception->getCode() === '23000') {
        $respond(409, ['success' => false, 'message' => 'That email address is already in use.']);
    }
    $respond(500, ['success' => false, 'message' => 'Unable to update your profile right now.']);
}
