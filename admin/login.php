<?php

require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Email and password are required.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'SELECT id, name, email, password, status
                     FROM admin_users
                     WHERE email = :email
                     LIMIT 1'
                );
                $stmt->execute(['email' => $email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$admin || !password_verify($password, $admin['password'])) {
                    $error = 'Invalid email or password.';
                } elseif ($admin['status'] !== 'active') {
                    $error = 'This administrator account is blocked.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int)$admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_status'] = $admin['status'];
                    header('Location: dashboard.php');
                    exit;
                }
            } catch (PDOException $exception) {
                $error = 'Unable to sign in at this time.';
            }
        }
    }
}

$csrfToken = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITAM-SLACK | Administrator Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <main class="card shadow-sm border-0" style="width:min(420px, calc(100% - 32px));">
        <div class="card-body p-4">
            <h4 class="mb-1">AITAM-SLACK</h4>
            <p class="text-muted mb-4">Administrator sign in</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php elseif (($_GET['error'] ?? '') === 'blocked'): ?>
                <div class="alert alert-danger" role="alert">Your administrator account is blocked.</div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" autocomplete="username" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
        </div>
    </main>
</body>
</html>
