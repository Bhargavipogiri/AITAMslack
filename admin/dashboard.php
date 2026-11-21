
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
                        <h3>0</h3>
                    </div>
                </a>

                <a href="users.php?status=blocked" class="dashboard-card">
                    <div class="card-icon blocked-icon">
                        <i class="bi bi-person-x-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Blocked / Suspended Users</p>
                        <h3>0</h3>
                    </div>
                </a>

                <a href="questions.php" class="dashboard-card">
                    <div class="card-icon questions-icon">
                        <i class="bi bi-chat-square-text-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Total Questions</p>
                        <h3>0</h3>
                    </div>
                </a>

                <a href="answers.php" class="dashboard-card">
                    <div class="card-icon answers-icon">
                        <i class="bi bi-chat-left-text-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Total Answers</p>
                        <h3>0</h3>
                    </div>
                </a>

                <a href="reported-questions.php" class="dashboard-card">
                    <div class="card-icon reported-icon">
                        <i class="bi bi-flag-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Reported Questions</p>
                        <h3>0</h3>
                    </div>
                </a>

                <a href="reported-answers.php" class="dashboard-card">
                    <div class="card-icon reported-answers-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="card-details">
                        <p>Reported Answers</p>
                        <h3>0</h3>
                    </div>
                </a>

            </div>


<!-- Announcements Section -->
<section class="dashboard-announcements">

    <div class="announcements-header">
        <div>
            <h4>Recent Announcements</h4>
            <p>Latest updates and announcements from the college.</p>
        </div>

        <a href="announcements.php" class="view-all-btn">
            View All
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="announcements-list">

        <!-- Announcement 1 -->
        <div class="announcement-item">

            <div class="announcement-icon">
                <i class="bi bi-megaphone-fill"></i>
            </div>

            <div class="announcement-details">
                <h5>Welcome to College Connect</h5>
                <p>
                    Stay updated with the latest college news,
                    events and important announcements.
                </p>

                <div class="announcement-meta">
                    <span>
                        <i class="bi bi-calendar3"></i>
                        21 November 2026
                    </span>

                    <span class="announcement-status published">
                        Published
                    </span>
                </div>
            </div>

        </div>

        <!-- Empty state example: remove this when announcements are available -->
        <!--
        <div class="announcement-empty">
            <i class="bi bi-megaphone"></i>
            <h5>No Announcements Yet</h5>
            <p>New college announcements will appear here.</p>
        </div>
        -->

    </div>

</section>



        </main>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="../assets/js/admin.js"></script>

</body>
</html>
