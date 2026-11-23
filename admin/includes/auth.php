<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function admin_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function admin_csrf_verify(?string $token): bool
{
    return $token !== null
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: /AITAMslack/admin/login.php');
        exit;
    }

    $status = $_SESSION['admin_status'] ?? '';
    if ($status !== 'active') {
        session_unset();
        session_destroy();
        header('Location: /AITAMslack/admin/login.php?error=blocked');
        exit;
    }
}

