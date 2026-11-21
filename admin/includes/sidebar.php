
<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$mainMenu = [
    ['title' => 'Dashboard', 'icon' => 'bi-grid', 'link' => 'dashboard.php'],
    ['title' => 'User Management', 'icon' => 'bi-people', 'link' => 'users.php'],
    ['title' => 'Question Management', 'icon' => 'bi-chat', 'link' => 'questions.php'],
    ['title' => 'Answer Management', 'icon' => 'bi-chat-left-text', 'link' => 'answers.php'],
    ['title' => 'Reported Questions', 'icon' => 'bi-flag', 'link' => 'reported-questions.php'],
    ['title' => 'Reported Answers', 'icon' => 'bi-flag-fill', 'link' => 'reported-answers.php'],
    ['title' => 'Announcements Management', 'icon' => 'bi-megaphone', 'link' => 'announcements.php']
];

$accountMenu = [
    ['title' => 'Settings', 'icon' => 'bi-gear', 'link' => 'settings.php'],
    ['title' => 'My Profile', 'icon' => 'bi-person-circle', 'link' => 'profile.php']
];
?>

<aside class="sidebar" id="sidebar">

    <!-- Branding -->
    <div class="sidebar-brand">

        <div class="brand-icon">
            <img src="/AITAMslack/assets/images/college-logo.jpg"
                alt="AITAM College Logo">
        </div>

        <div class="brand-text">
            <h5>AITAM-SLACK</h5>
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
                    <a href="<?= htmlspecialchars($item['link']) ?>"
                       class="menu-link <?= $currentPage === $item['link'] ? 'active' : '' ?>">

                        <i class="bi <?= htmlspecialchars($item['icon']) ?>"></i>

                        <span class="menu-text">
                            <?= htmlspecialchars($item['title']) ?>
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
                    <a href="<?= htmlspecialchars($item['link']) ?>"
                       class="menu-link <?= $currentPage === $item['link'] ? 'active' : '' ?>">

                        <i class="bi <?= htmlspecialchars($item['icon']) ?>"></i>

                        <span class="menu-text">
                            <?= htmlspecialchars($item['title']) ?>
                        </span>

                    </a>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

    <!-- Logout -->
    <div class="sidebar-logout">

        <a href="logout.php" class="logout-link">

            <i class="bi bi-box-arrow-right"></i>

            <span class="menu-text">Logout</span>

        </a>

    </div>

</aside>
