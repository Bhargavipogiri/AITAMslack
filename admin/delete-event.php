
<?php

require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: events.php?error=invalid_request");
    exit;
}

// Validate CSRF token
$submittedToken = $_POST["csrf_token"] ?? "";
$sessionToken = $_SESSION["csrf_token"] ?? "";

if (
    empty($submittedToken) ||
    empty($sessionToken) ||
    !hash_equals($sessionToken, $submittedToken)
) {
    header("Location: events.php?error=invalid_request");
    exit;
}

// Validate event ID
$eventId = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);

if (!$eventId || $eventId < 1) {
    header("Location: events.php?error=invalid_event");
    exit;
}

try {

    // Find event and its image
    $stmt = $pdo->prepare("
        SELECT event_image
        FROM events
        WHERE event_id = ?
    ");

    $stmt->execute([$eventId]);

    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        header("Location: events.php?error=not_found");
        exit;
    }

    // Delete event from database
    $stmt = $pdo->prepare("
        DELETE FROM events
        WHERE event_id = ?
    ");

    $stmt->execute([$eventId]);

    // Delete associated image safely
    $imagePath = $event["event_image"] ?? "";

    if (
        $imagePath !== "" &&
        strpos($imagePath, "assets/uploads/events/") === 0
    ) {

        $filename = basename($imagePath);

        $uploadFolder = realpath(
            __DIR__ . "/../assets/uploads/events"
        );

        if ($uploadFolder !== false) {

            $fullImagePath = realpath(
                $uploadFolder . DIRECTORY_SEPARATOR . $filename
            );

            if (
                $fullImagePath !== false &&
                strpos(
                    $fullImagePath,
                    $uploadFolder . DIRECTORY_SEPARATOR
                ) === 0 &&
                is_file($fullImagePath)
            ) {
                unlink($fullImagePath);
            }
        }
    }

    header("Location: events.php?success=deleted");
    exit;

} catch (PDOException $e) {

    error_log("Event deletion error: " . $e->getMessage());

    header("Location: events.php?error=delete_failed");
    exit;
}
