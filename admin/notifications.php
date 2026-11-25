
<?php

require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Generate CSRF token
if (empty($_SESSION['admin_notification_csrf'])) {
    $_SESSION['admin_notification_csrf'] = bin2hex(random_bytes(32));
}

try {

    // FETCH NOTIFICATIONS
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $sql = "
            SELECT
                n.notification_id,
                n.report_id,
                n.is_read,
                n.created_at AS notification_time,
                r.subject,
                r.report_type,
                r.priority,
                r.status,
                u.full_name AS reporter_name
            FROM admin_notifications n
            INNER JOIN user_reports r
                ON n.report_id = r.report_id
            INNER JOIN users u
                ON r.user_id = u.user_id
            ORDER BY n.created_at DESC
            LIMIT 30
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $pdo->query("
            SELECT COUNT(*)
            FROM admin_notifications
            WHERE is_read = 0
        ");

        $unreadCount = (int)$countStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'csrf_token' => $_SESSION['admin_notification_csrf']
        ]);

        exit;
    }

    // HANDLE POST ACTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $csrfToken = $_POST['csrf_token'] ?? '';

        if (
            !hash_equals(
                $_SESSION['admin_notification_csrf'],
                $csrfToken
            )
        ) {
            http_response_code(403);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid security token.'
            ]);

            exit;
        }

        $action = $_POST['action'] ?? '';

        $notificationId = filter_var(
            $_POST['notification_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$notificationId || $notificationId <= 0) {
            throw new Exception('Invalid notification ID.');
        }

        // MARK AS READ
        if ($action === 'mark_read') {

            $stmt = $pdo->prepare("
                UPDATE admin_notifications
                SET is_read = 1
                WHERE notification_id = :id
            ");

            $stmt->execute([
                ':id' => $notificationId
            ]);

        }

        // DELETE NOTIFICATION ONLY
        elseif ($action === 'delete') {

            $stmt = $pdo->prepare("
                DELETE FROM admin_notifications
                WHERE notification_id = :id
            ");

            $stmt->execute([
                ':id' => $notificationId
            ]);

        }

        else {
            throw new Exception('Invalid notification action.');
        }

        // Get updated unread count
        $countStmt = $pdo->query("
            SELECT COUNT(*)
            FROM admin_notifications
            WHERE is_read = 0
        ");

        echo json_encode([
            'success' => true,
            'message' => 'Notification updated successfully.',
            'unread_count' => (int)$countStmt->fetchColumn()
        ]);

        exit;
    }

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

} catch (Throwable $e) {

    error_log('Admin notification error: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to process notifications.'
    ]);
}
