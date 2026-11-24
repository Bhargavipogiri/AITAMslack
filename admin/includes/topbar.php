
<?php

$pageTitles = [
    'dashboard.php' => 'ADMIN-PANEL',
    'users.php' => 'User Management',
    'questions.php' => 'Question Management',
    'answers.php' => 'Answer Management',
    'reported-questions.php' => 'Reported Questions',
    'reported-answers.php' => 'Reported Answers',
    'announcements.php' => 'Announcements Management',
    'settings.php' => 'Settings',
    'my_profile.php' => 'My Profile',
    'events.php' => 'Event Management'
];

$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitles[$currentPage] ?? 'Admin Panel';

$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$adminImage = $_SESSION['admin_profile_image'] ?? '';

?>

<header class="topbar">

    <div class="topbar-left">

        <button class="sidebar-toggle"
                id="sidebarToggle"
                type="button"
                aria-label="Toggle sidebar"
                aria-controls="sidebar"
                aria-expanded="true">

            <i class="bi bi-list"></i>

        </button>

        <div class="page-heading">

            <h4>ADITYA INSTITUTE OF TECHNOLOGY AND MANAGMENT</h4>

            <p class="topbar-description">
                Connecting the College Community...
            </p>

        </div>

    </div>

    <div class="topbar-right">

        <div class="dropdown">

            <button class="topbar-icon notification-btn"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Notifications">

                <i class="bi bi-bell"></i>
                <span class="notification-dot"></span>

            </button>

            <div class="dropdown-menu dropdown-menu-end notification-menu">

                <div class="notification-header">
                    <strong>Notifications</strong>
                    <span>Recent updates</span>
                </div>

                <div class="notification-empty">
                    <i class="bi bi-bell-slash"></i>
                    <p>No new notifications</p>
                </div>

            </div>

        </div>

        <div class="dropdown">

            <button class="admin-profile-btn"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">

                <div class="admin-avatar">

                    <?php if (!empty($adminImage)): ?>

                        <img
                            src="<?= htmlspecialchars($adminImage, ENT_QUOTES, 'UTF-8') ?>"
                            alt="Admin Profile"
                        >

                    <?php else: ?>

                        <i class="bi bi-person"></i>

                    <?php endif; ?>

                </div>

                <div class="admin-info">

                    <strong>
                        <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>
                    </strong>

                    <span>Administrator</span>

                </div>

                <i class="bi bi-chevron-down dropdown-arrow"></i>

            </button>

            <ul class="dropdown-menu dropdown-menu-end profile-menu">

                <li>
                    <a class="dropdown-item" href="my_profile.php">
                        <i class="bi bi-person-circle"></i>
                        My Profile
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="settings.php">
                        <i class="bi bi-gear"></i>
                        Settings
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item logout-dropdown"
                       href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </li>

            </ul>

        </div>

    </div>

</header>
