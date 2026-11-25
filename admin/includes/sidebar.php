
<?php

require_once __DIR__ . '/platform-settings.php';

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$mainMenu = [
    [
        'title' => 'Dashboard',
        'icon' => 'bi-grid',
        'link' => '/AITAMslack/admin/dashboard.php'
    ],
    [
        'title' => 'User Management',
        'icon' => 'bi-people',
        'link' => '/AITAMslack/admin/users.php'
    ],
    [
        'title' => 'Question Management',
        'icon' => 'bi-chat',
        'link' => '/AITAMslack/admin/questions.php'
    ],
    [
        'title' => 'Answer Management',
        'icon' => 'bi-chat-left-text',
        'link' => '/AITAMslack/admin/answers.php'
    ],
    [
<<<<<<< HEAD
    'title' => 'Reports Management',
    'icon' => 'bi-flag',
    'link' => '/AITAMslack/admin/reports.php'
=======
        'title' => 'Verified College Facts',
        'icon' => 'bi-journal-check',
        'link' => '/AITAMslack/admin/verified-facts.php'
    ],
    [
        'title' => 'Moderation History',
        'icon' => 'bi-shield-check',
        'link' => '/AITAMslack/admin/moderation-history.php'
>>>>>>> 64e62b774cc80e4e2d0be33e0f90b0e23cf096ff
    ],
    [
        'title' => 'Reported Questions',
        'icon' => 'bi-flag',
        'link' => '/AITAMslack/admin/reported-questions.php'
    ],
    [
        'title' => 'Reported Answers',
        'icon' => 'bi-flag-fill',
        'link' => '/AITAMslack/admin/reported-answers.php'
    ],
    [
        'title' => 'Announcements Management',
        'icon' => 'bi-megaphone',
        'link' => '/AITAMslack/admin/announcements.php'
    ],

    [
    'title' => 'Event Management',
    'icon' => 'bi-calendar-event',
    'link' => '/AITAMslack/admin/events.php'
    ]
];

$accountMenu = [
    [
        'title' => 'Settings',
        'icon' => 'bi-gear',
        'link' => '/AITAMslack/admin/settings.php'
    ],
    [
        'title' => 'My Profile',
        'icon' => 'bi-person-circle',
        'link' => '/AITAMslack/admin/my_profile.php'
    ]
];

?>

<aside class="sidebar" id="sidebar">

    <!-- Branding -->
    <div class="sidebar-brand">

        <div class="brand-icon">

            <img
                src="https://adityatekkali.edu.in/ahlaad2k26/aitamsinglelogo.png"
                alt="AITAM College Logo"
            >

        </div>

        <div class="brand-text">

            <h5>
                <?= htmlspecialchars(
                    $platformSettings['platform_name'] ?? 'AITAM Slack',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h5>

            <span>Administration Panel</span>

        </div>

    </div>

    <div class="sidebar-divider"></div>

    <!-- Main Menu -->
    <div class="menu-section">

        <p class="menu-heading">MAIN MENU</p>

        <ul class="sidebar-menu">

            <?php foreach ($mainMenu as $item): ?>

                <li>

                    <a
                        href="<?= htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') ?>"
                        class="menu-link <?= basename($item['link']) === $currentPage ? 'active' : '' ?>"
                    >

                        <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>

                        <span class="menu-text">
                            <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </a>

                </li>

            <?php endforeach; ?>

        </ul>

    </div>

    <div class="sidebar-divider"></div>

    <!-- Account Menu -->
    <div class="menu-section">

        <p class="menu-heading">ACCOUNT</p>

        <ul class="sidebar-menu">

            <?php foreach ($accountMenu as $item): ?>

                <li>

                    <a
                        href="<?= htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') ?>"
                        class="menu-link <?= basename($item['link']) === $currentPage ? 'active' : '' ?>"
                    >

                        <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>

                        <span class="menu-text">
                            <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </a>

                </li>

            <?php endforeach; ?>

        </ul>

    </div>

    <!-- Logout -->
    <div class="sidebar-logout">

        <a
            href="/AITAMslack/auth/logout.php"
            class="logout-link"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span class="menu-text">Logout</span>

        </a>

    </div>

</aside>
