<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$counts = [
    'users' => null,
    'active_users' => null,
    'blocked_users' => null,
    'questions' => null,
    'answers' => null,
    'reported_questions' => null,
    'reported_answers' => null,
];

try {
    $counts['users'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $counts['active_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $counts['blocked_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'")->fetchColumn();
    $counts['questions'] = (int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
    $counts['reported_questions'] = (int)$pdo->query('SELECT COUNT(*) FROM reported_questions')->fetchColumn();
    $counts['reported_answers'] = (int)$pdo->query('SELECT COUNT(*) FROM reported_answers')->fetchColumn();
} catch (PDOException $exception) {
    // Preserve independent metrics when one table or query is unavailable.
}

$recentUsers = [];
$recentQuestions = [];
$recentAnnouncements = [];

try {
    $recentUsers = $pdo->query(
        'SELECT id, name, email, status, created_at
         FROM users
         ORDER BY created_at DESC
         LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC);

    $recentQuestions = $pdo->query(
        'SELECT q.id, q.question_title, q.status, q.created_at, u.name AS author_name
         FROM questions q
         LEFT JOIN users u ON u.id = q.user_id
         ORDER BY q.created_at DESC
         LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC);

    $recentAnnouncements = $pdo->query(
        'SELECT id, title, description, status, created_at
         FROM announcements
         WHERE status = \'published\'
         ORDER BY created_at DESC
         LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    // Empty-state sections remain honest if a section cannot be queried.
}

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AITAM-SLACK | Admin Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <!-- Root CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <!-- Page Content -->
        <main class="page-content">

            <div class="dashboard-header">
                <div>
                    <h3>Dashboard Overview</h3>
                    <p>Monitor and manage your College Connect community.</p>
                </div>
            </div>

            <div class="dashboard-cards">

                <a href="users.php" class="dashboard-card">
                    <div class="card-icon users-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Total Users</p>
                        <h3><?= $counts['users'] === null ? 'Unavailable' : (int)$counts['users'] ?></h3>
                    </div>
                </a>

                <a href="users.php?status=active" class="dashboard-card">
                    <div class="card-icon users-icon">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Total Active Users</p>
                        <h3><?= $counts['active_users'] === null ? 'Unavailable' : (int)$counts['active_users'] ?></h3>
                    </div>
                </a>

                <a href="users.php?status=blocked" class="dashboard-card">
                    <div class="card-icon blocked-icon">
                        <i class="bi bi-person-x-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Blocked / Suspended Users</p>
                        <h3><?= $counts['blocked_users'] === null ? 'Unavailable' : (int)$counts['blocked_users'] ?></h3>
                    </div>
                </a>

                <a href="questions.php" class="dashboard-card">
                    <div class="card-icon questions-icon">
                        <i class="bi bi-chat-square-text-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Total Questions</p>
                        <h3><?= $counts['questions'] === null ? 'Unavailable' : (int)$counts['questions'] ?></h3>
                    </div>
                </a>

                <a href="answers.php" class="dashboard-card">
                    <div class="card-icon answers-icon">
                        <i class="bi bi-chat-left-text-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Total Answers</p>
                        <h3>Unavailable</h3>
                    </div>
                </a>

                <a href="reported-questions.php" class="dashboard-card">
                    <div class="card-icon reported-icon">
                        <i class="bi bi-flag-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Reported Questions</p>
                        <h3><?= $counts['reported_questions'] === null ? 'Unavailable' : (int)$counts['reported_questions'] ?></h3>
                    </div>
                </a>

                <a href="reported-answers.php" class="dashboard-card">
                    <div class="card-icon reported-answers-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Reported Answers</p>
                        <h3><?= $counts['reported_answers'] === null ? 'Unavailable' : (int)$counts['reported_answers'] ?></h3>
                    </div>
                </a>

            </div>

            <section class="dashboard-announcements">
                <div class="announcements-header">
                    <div>
                        <h4>Recent Announcements</h4>
                        <p>Latest published updates from the database.</p>
                    </div>
                    <a href="announcements.php" class="view-all-btn">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="announcements-list">
                    <?php if ($recentAnnouncements === []): ?>
                        <div class="announcement-empty">
                            <i class="bi bi-megaphone"></i>
                            <h5>No Published Announcements</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentAnnouncements as $announcement): ?>
                            <div class="announcement-item">
                                <div class="announcement-icon"><i class="bi bi-megaphone-fill"></i></div>
                                <div class="announcement-details">
                                    <h5><?= e($announcement['title']) ?></h5>
                                    <p><?= e($announcement['description']) ?></p>
                                    <div class="announcement-meta">
                                        <span><i class="bi bi-calendar3"></i>
                                            <?= e(date('d F Y', strtotime($announcement['created_at']))) ?>
                                        </span>
                                        <span class="announcement-status published">Published</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <div class="row g-3 mt-1">
                <div class="col-lg-6">
                    <section class="dashboard-announcements">
                        <div class="announcements-header"><div><h4>Recent Users</h4></div></div>
                        <div class="announcements-list">
                            <?php if ($recentUsers === []): ?>
                                <div class="announcement-empty"><h5>No Users Yet</h5></div>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $user): ?>
                                    <div class="announcement-item">
                                        <div class="announcement-details">
                                            <h5><?= e($user['name'] ?: 'Unnamed User') ?></h5>
                                            <p><?= e($user['email']) ?> - <?= e(ucfirst($user['status'])) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                <div class="col-lg-6">
                    <section class="dashboard-announcements">
                        <div class="announcements-header"><div><h4>Recent Questions</h4></div></div>
                        <div class="announcements-list">
                            <?php if ($recentQuestions === []): ?>
                                <div class="announcement-empty"><h5>No Questions Yet</h5></div>
                            <?php else: ?>
                                <?php foreach ($recentQuestions as $question): ?>
                                    <div class="announcement-item">
                                        <div class="announcement-details">
                                            <h5><?= e($question['question_title']) ?></h5>
                                            <p><?= e($question['author_name'] ?: 'Unknown author') ?> - <?= e($question['status']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                <div class="col-lg-6">
                    <section class="dashboard-announcements">
                        <div class="announcements-header"><div><h4>Recent Answers</h4></div></div>
                        <div class="announcements-list">
                            <div class="announcement-empty">
                                <i class="bi bi-chat-left-text"></i>
                                <h5>Answers Unavailable</h5>
                                <p>The existing database does not contain an <code>answers</code> table.</p>
                                <a href="answers.php">Open Answer Management</a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>



        </main>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="../assets/js/admin.js"></script>

</body>
</html>
