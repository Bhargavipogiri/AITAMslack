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
    $respond(401, ['success' => false, 'message' => 'Please sign in to upload a profile picture.']);
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

if (($_POST['action'] ?? '') === 'remove') {
    require_once __DIR__ . '/../config/db.php';
    $uploadDirectory = __DIR__ . '/../uploads/avatars';

    try {
        $pdo->beginTransaction();
        $userStmt = $pdo->prepare(
            'SELECT profile_image
             FROM users
             WHERE user_id = :user_id
             LIMIT 1
             FOR UPDATE'
        );
        $userStmt->execute([':user_id' => $userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $pdo->rollBack();
            $respond(404, ['success' => false, 'message' => 'Your profile could not be found.']);
        }

        $previousImage = (string)($user['profile_image'] ?? '');
        if (preg_match('~\Auploads/avatars/([A-Za-z0-9._-]+)\z~D', $previousImage, $matches)) {
            $avatarDirectoryRealPath = realpath($uploadDirectory);
            $previousPath = realpath($uploadDirectory . DIRECTORY_SEPARATOR . $matches[1]);
            if (
                $avatarDirectoryRealPath !== false
                && $previousPath !== false
                && dirname($previousPath) === $avatarDirectoryRealPath
                && is_file($previousPath)
                && !@unlink($previousPath)
            ) {
                throw new RuntimeException('Previous profile picture could not be removed.');
            }
        }

        $updateStmt = $pdo->prepare(
            'UPDATE users
             SET profile_image = NULL
             WHERE user_id = :user_id'
        );
        $updateStmt->execute([':user_id' => $userId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Avatar removal error: ' . $exception->getMessage());
        $respond(500, ['success' => false, 'message' => 'Unable to remove your profile picture.']);
    }

    $_SESSION['profile_image'] = '';
    $respond(200, ['success' => true]);
}

if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
    $respond(400, ['success' => false, 'message' => 'Choose an image to upload.']);
}

$file = $_FILES['avatar'];
$uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
if ($uploadError !== UPLOAD_ERR_OK) {
    $status = in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 413 : 400;
    $message = $status === 413
        ? 'Image must be no larger than 3 MB.'
        : 'The image upload did not complete. Please try again.';
    $respond($status, ['success' => false, 'message' => $message]);
}

$temporaryPath = $file['tmp_name'] ?? '';
if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
    $respond(400, ['success' => false, 'message' => 'The uploaded file is invalid.']);
}

$fileSize = filesize($temporaryPath);
if ($fileSize === false || $fileSize < 1) {
    $respond(400, ['success' => false, 'message' => 'The uploaded image is empty or invalid.']);
}
if ($fileSize > 3 * 1024 * 1024) {
    $respond(413, ['success' => false, 'message' => 'Image must be no larger than 3 MB.']);
}

if (!function_exists('finfo_open') || !function_exists('finfo_file')) {
    error_log('Avatar upload error: PHP Fileinfo is unavailable.');
    $respond(500, ['success' => false, 'message' => 'Image validation is unavailable.']);
}

$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $fileInfo === false ? false : finfo_file($fileInfo, $temporaryPath);
if ($fileInfo !== false) {
    finfo_close($fileInfo);
}
$extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
if (!is_string($mimeType) || !isset($extensions[$mimeType]) || @getimagesize($temporaryPath) === false) {
    $respond(415, ['success' => false, 'message' => 'Choose a valid JPEG, PNG, or WebP image.']);
}

require_once __DIR__ . '/../config/db.php';

$uploadDirectory = __DIR__ . '/../uploads/avatars';
if (
    !is_dir($uploadDirectory)
    && !mkdir($uploadDirectory, 0750, true)
    && !is_dir($uploadDirectory)
) {
    error_log('Avatar upload error: could not create uploads/avatars directory.');
    $respond(500, ['success' => false, 'message' => 'Profile picture storage is unavailable.']);
}
if (!is_writable($uploadDirectory)) {
    error_log('Avatar upload error: uploads/avatars is not writable.');
    $respond(500, ['success' => false, 'message' => 'Profile picture storage is unavailable.']);
}

try {
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
} catch (Throwable $exception) {
    error_log('Avatar upload filename error: ' . $exception->getMessage());
    $respond(500, ['success' => false, 'message' => 'Unable to prepare the profile picture.']);
}

$targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($temporaryPath, $targetPath)) {
    error_log('Avatar upload error: uploaded file could not be moved into storage.');
    $respond(500, ['success' => false, 'message' => 'Unable to save the profile picture.']);
}

$relativePath = 'uploads/avatars/' . $filename;

try {
    $pdo->beginTransaction();
    $userStmt = $pdo->prepare(
        'SELECT profile_image
         FROM users
         WHERE user_id = :user_id
         LIMIT 1
         FOR UPDATE'
    );
    $userStmt->execute([':user_id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $pdo->rollBack();
        if (is_file($targetPath) && !@unlink($targetPath)) {
            error_log('Avatar upload cleanup error: new image could not be removed.');
        }
        $respond(404, ['success' => false, 'message' => 'Your profile could not be found.']);
    }

    $updateStmt = $pdo->prepare(
        'UPDATE users
         SET profile_image = :profile_image
         WHERE user_id = :user_id'
    );
    $updateStmt->execute([
        ':profile_image' => $relativePath,
        ':user_id' => $userId,
    ]);
    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (is_file($targetPath) && !@unlink($targetPath)) {
        error_log('Avatar upload cleanup error: new image could not be removed.');
    }
    error_log('Avatar upload database error: ' . $exception->getMessage());
    $respond(500, ['success' => false, 'message' => 'Unable to update your profile picture right now.']);
}

$_SESSION['profile_image'] = $relativePath;

$previousImage = (string)($user['profile_image'] ?? '');
if (preg_match('~\Auploads/avatars/([A-Za-z0-9._-]+)\z~D', $previousImage, $matches)) {
    $avatarDirectoryRealPath = realpath($uploadDirectory);
    $previousPath = realpath($uploadDirectory . DIRECTORY_SEPARATOR . $matches[1]);
    if (
        $avatarDirectoryRealPath !== false
        && $previousPath !== false
        && dirname($previousPath) === $avatarDirectoryRealPath
        && is_file($previousPath)
        && $previousPath !== realpath($targetPath)
        && !@unlink($previousPath)
    ) {
        error_log('Avatar cleanup error: previous profile picture could not be removed.');
    }
}

$respond(200, [
    'success' => true,
    'message' => 'Profile picture updated successfully.',
    'profile_image' => $relativePath,
]);
