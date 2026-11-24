<?php
require_once __DIR__ . '/admin/includes/platform-settings.php';
$currentPage = basename(
    parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php'
);
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php');
$profileName = trim((string)($_SESSION['full_name'] ?? 'User'));
$profileRole = (string)($_SESSION['role'] ?? '');
if (strcasecmp(trim($profileRole), 'faculty') === 0) {
    $profileName = trim(preg_replace('/^Sri\s+/iu', '', $profileName) ?? $profileName);
}
$profileImage = $_SESSION['profile_image'] ?? '';
$profileImagePath = is_string($profileImage) ? trim($profileImage) : '';
$profileFilePath = $profileImagePath !== ''
    ? ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $profileImagePath), DIRECTORY_SEPARATOR)
    : '';
$hasProfileImage = $profileFilePath !== '' && is_file(__DIR__ . DIRECTORY_SEPARATOR . $profileFilePath);
$profileInitial = preg_match('/^./us', $profileName, $initialMatch) ? $initialMatch[0] : '?';
?>
<!-- College Connect Navbar -->

<nav class="cc-navbar">

    <div class="cc-nav-container">

        <!-- LOGO -->
        <a href="index.php" class="cc-brand">

            <div class="cc-logo">
                <img src="https://adityatekkali.edu.in/ahlaad2k26/aitamsinglelogo.png"
                    alt="AITAM College Logo"
                    width="44"
                    height="44">
            </div>

            <div class="cc-brand-text">

                <h2>
                    <?= htmlspecialchars($platformSettings['platform_name'], ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <span>
                    <?= htmlspecialchars($platformSettings['platform_description'], ENT_QUOTES, 'UTF-8') ?>
                </span>

            </div>

        </a>

        <!-- DESKTOP MENU -->
        <div class="cc-nav-menu" id="ccNavMenu">

          <a href="index.php" class="cc-nav-link<?= $currentPage === 'index.php' ? ' active' : '' ?>"<?= $currentPage === 'index.php' ? ' aria-current="page"' : '' ?>>Home</a>
           <a href="ask-question.php" class="cc-nav-link<?= $currentPage === 'ask-question.php' ? ' active' : '' ?>"<?= $currentPage === 'ask-question.php' ? ' aria-current="page"' : '' ?>>Ask Question</a>
            <a href="question-history.php" class="cc-nav-link<?= $currentPage === 'question-history.php' ? ' active' : '' ?>"<?= $currentPage === 'question-history.php' ? ' aria-current="page"' : '' ?>>Questions</a>
            <a href="answer.php" class="cc-nav-link<?= $currentPage === 'answer.php' ? ' active' : '' ?>"<?= $currentPage === 'answer.php' ? ' aria-current="page"' : '' ?>>Answer</a>
            <a href="leaderboard.php" class="cc-nav-link<?= $currentPage === 'leaderboard.php' ? ' active' : '' ?>"<?= $currentPage === 'leaderboard.php' ? ' aria-current="page"' : '' ?>>Leaderboard</a>
            <a href="report.php" class="cc-nav-link<?= $currentPage === 'report.php' ? ' active' : '' ?>"<?= $currentPage === 'report.php' ? ' aria-current="page"' : '' ?>>Report</a>
            <a href="index.php#events" class="cc-nav-link">Events</a>
            <a href="about.php" class="cc-nav-link<?= $currentPage === 'about.php' ? ' active' : '' ?>"<?= $currentPage === 'about.php' ? ' aria-current="page"' : '' ?>>About</a>

            <!-- MOBILE ACTIONS -->
            <div class="cc-mobile-actions">
                <?php if (!$is_logged_in): ?>
                    <a href="login.php" class="cc-login">Login</a>
                    <a href="register.php" class="cc-register">Join Now</a>
                <?php else: ?>
                    <div class="nav-profile-wrapper">
                        <button class="nav-profile-trigger" type="button" aria-expanded="false" aria-haspopup="true">
                            <?php if ($hasProfileImage): ?>
                                <img class="nav-avatar-img" src="<?= htmlspecialchars($profileImagePath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                            <?php else: ?>
                                <span class="nav-avatar-fallback" aria-hidden="true"><?= htmlspecialchars($profileInitial, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <span class="nav-user-meta">
                                <span class="nav-user-name"><?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="nav-user-role"><?= htmlspecialchars($profileRole, ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                            <span class="nav-chevron" aria-hidden="true">⌄</span>
                        </button>
                        <div class="nav-profile-menu" role="menu">
                            <a class="nav-dropdown-item" role="menuitem" href="profile.php">My Profile</a>
                            <a class="nav-dropdown-item" role="menuitem" href="questions_history.php">Questions History</a>
                            <?php if (($_SESSION['role'] ?? null) === 'admin'): ?>
                                <a class="nav-dropdown-item" role="menuitem" href="admin.php">Admin Panel</a>
                            <?php endif; ?>
                            <div class="nav-dropdown-divider" role="separator"></div>
                            <a class="nav-dropdown-item danger" role="menuitem" href="auth/logout.php">Logout</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- RIGHT ACTIONS -->
        <div class="cc-nav-actions">

            <button class="cc-theme-btn" id="ccThemeBtn"
                    type="button" aria-label="Toggle theme">
                <span id="ccThemeIcon">☾</span>
            </button>

            <?php if (!$is_logged_in): ?>
                <a href="login.php" class="cc-login">Login</a>
                <a href="register.php" class="cc-register">Join Now</a>
            <?php else: ?>
                <div class="nav-profile-wrapper">
                    <button class="nav-profile-trigger" type="button" aria-expanded="false" aria-haspopup="true">
                        <?php if ($hasProfileImage): ?>
                            <img class="nav-avatar-img" src="<?= htmlspecialchars($profileImagePath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                        <?php else: ?>
                            <span class="nav-avatar-fallback" aria-hidden="true"><?= htmlspecialchars($profileInitial, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <span class="nav-user-meta">
                            <span class="nav-user-name"><?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="nav-user-role"><?= htmlspecialchars($profileRole, ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span class="nav-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="nav-profile-menu" role="menu">
                        <a class="nav-dropdown-item" role="menuitem" href="profile.php">My Profile</a>
                        <a class="nav-dropdown-item" role="menuitem" href="questions-history.php">Questions History</a>
                        <?php if (($_SESSION['role'] ?? null) === 'admin'): ?>
                            <a class="nav-dropdown-item" role="menuitem" href="admin.php">Admin Panel</a>
                        <?php endif; ?>
                        <div class="nav-dropdown-divider" role="separator"></div>
                        <a class="nav-dropdown-item danger" role="menuitem" href="auth/logout.php">Logout</a>
                    </div>
                </div>
            <?php endif; ?>

            <button class="cc-menu-toggle" id="ccMenuToggle"
                    type="button" aria-label="Open menu"
                    aria-expanded="false" aria-controls="ccNavMenu">
                <span></span>
                <span></span>
                <span></span>
            </button>

        </div>

    </div>

</nav>

<style>
/* Import global design tokens */
@import url("assets/css/root.css");

/* NAVBAR */
.cc-navbar {
    width: 100%;
    position: sticky;
    top: 0;
    z-index: 1000;
    background: var(--bg-header);
    border-bottom: 1px solid var(--border-default);
    box-shadow: var(--shadow-sm);
    box-sizing: border-box;
    font-family: var(--font-family-base, Arial, sans-serif);
    font-size: 16px;
    line-height: 1.5;
}

.cc-navbar *,
.cc-navbar *::before,
.cc-navbar *::after {
    box-sizing: border-box;
    font-family: inherit;
}

.cc-nav-container {
    width: min(1400px, 94%);
    min-height: 78px;
    margin: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
}

/* BRAND */
.cc-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    flex-shrink: 0;
}

.cc-logo {
    width: 56px;
    height: 56px;
    flex: 0 0 56px;
    display: grid;
    place-items: center;
    box-sizing: border-box;
    padding: 5px;
    overflow: hidden;
    border: 0;
    border-radius: 50%;
    background: var(--color-white);
}

.cc-logo img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
}

.cc-brand-text h2 {
    margin: 0;
    color: var(--text-primary);
    font-size: 18px;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: normal;
    text-transform: none;
}

.cc-brand-text span {
    color: var(--color-secondary);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.5px;
    line-height: 1.2;
    text-transform: none;
}

/* MENU */
.cc-nav-menu {
    display: flex;
    align-items: center;
    gap: 5px;
}

.cc-nav-link {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 11px 12px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 13px;
    font-weight: 650;
    line-height: 1.2;
    letter-spacing: normal;
    text-transform: none;
    white-space: nowrap;
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
}

.cc-nav-link:hover,
.cc-nav-link.active {
    color: var(--color-primary);
    background: var(--bg-surface-muted);
}

.cc-nav-link.active::after {
    content: "";
    position: absolute;
    bottom: 3px;
    left: 25%;
    width: 50%;
    height: 2px;
    background: var(--color-primary);
}

/* ACTIONS */
.cc-nav-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cc-theme-btn {
    width: 39px;
    height: 39px;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    color: var(--text-primary);
    cursor: pointer;
    font-size: 18px;
}

.cc-login,
.cc-register {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 17px;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
    text-decoration: none;
    transition: all var(--transition-fast);
}

.cc-login {
    border: 1px solid var(--border-default);
    color: var(--color-primary);
    background: var(--bg-surface);
}

.cc-register {
    color: white;
    background: var(--gradient-brand);
}

.cc-register:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* MOBILE */
.cc-menu-toggle {
    display: none;
    width: 40px;
    height: 40px;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    cursor: pointer;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.cc-menu-toggle span {
    width: 19px;
    height: 2px;
    background: var(--text-primary);
    border-radius: 5px;
}

.cc-mobile-actions {
    display: none;
}

/* PROFILE MENU */
.nav-profile-wrapper {
    position: relative;
}

.nav-profile-trigger {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    max-width: 230px;
    padding: 5px 11px 5px 5px;
    border: 1px solid var(--border-default);
    border-radius: 999px;
    background: var(--bg-surface-muted);
    color: var(--text-primary);
    cursor: pointer;
    text-align: left;
    transition: border-color var(--transition-fast), background-color var(--transition-fast);
}

.nav-profile-trigger:hover {
    border-color: var(--color-primary);
}

.nav-avatar-img,
.nav-avatar-fallback {
    display: grid;
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    place-items: center;
    border-radius: 50%;
    object-fit: cover;
}

.nav-avatar-fallback {
    background: var(--gradient-brand);
    color: var(--text-on-brand);
    font-size: 15px;
    font-weight: 700;
}

.nav-user-meta {
    display: flex;
    min-width: 0;
    flex-direction: column;
    line-height: 1.2;
}

.nav-user-name {
    max-width: 135px;
    overflow: hidden;
    color: var(--text-primary);
    font-size: 13px;
    font-weight: 650;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.nav-user-role {
    color: var(--text-secondary);
    font-size: 11px;
    text-transform: capitalize;
}

.nav-chevron {
    color: var(--text-secondary);
    font-size: 16px;
}

.nav-profile-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 1100;
    display: none;
    width: 210px;
    padding: 6px;
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    box-shadow: var(--shadow-lg);
}

.nav-profile-menu.show {
    display: block;
}

.nav-dropdown-item {
    display: flex;
    align-items: center;
    min-height: 38px;
    padding: 8px 10px;
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 13px;
    text-decoration: none;
    transition: background-color var(--transition-fast), color var(--transition-fast);
}

.nav-dropdown-item:hover {
    background: var(--bg-surface-muted);
    color: var(--color-primary);
}

.nav-dropdown-item.danger {
    color: var(--color-danger);
}

.nav-dropdown-divider {
    height: 1px;
    margin: 4px 0;
    background: var(--border-default);
}

@media(max-width: 1000px) {
    .cc-nav-container {
        gap: 10px;
    }

    .cc-nav-link {
        padding: 10px 7px;
        font-size: 12px;
    }
}

@media(max-width: 850px) {

    .cc-nav-container {
        min-height: 68px;
    }

    .cc-nav-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        padding: 18px 5%;
        background: var(--bg-header);
        border-bottom: 1px solid var(--border-default);
        box-shadow: var(--shadow-md);
        flex-direction: column;
        align-items: stretch;
    }

    .cc-nav-menu.open {
        display: flex;
    }

    .cc-nav-link {
        padding: 13px;
        font-size: 14px;
    }

    .cc-nav-actions > .cc-login,
    .cc-nav-actions > .cc-register {
        display: none;
    }

    .cc-nav-actions > .nav-profile-wrapper {
        display: none;
    }

    .cc-mobile-actions {
        display: flex;
        gap: 10px;
        margin-top: 12px;
    }

    .cc-mobile-actions > .nav-profile-wrapper {
        display: block;
        width: 100%;
    }

    .cc-mobile-actions a {
        flex: 1;
        text-align: center;
    }

    .cc-menu-toggle {
        display: flex;
    }
}

@media(max-width: 380px) {
    .cc-brand-text h2 {
        font-size: 15px;
    }

    .cc-brand-text span {
        font-size: 8px;
    }
}
</style>

<script>
(() => {
    const menu = document.getElementById("ccNavMenu");
    const menuBtn = document.getElementById("ccMenuToggle");
    const themeBtn = document.getElementById("ccThemeBtn");
    const themeIcon = document.getElementById("ccThemeIcon");
    const navLinks = Array.from(menu.querySelectorAll(".cc-nav-link"));
    const profileWrappers = Array.from(document.querySelectorAll(".nav-profile-wrapper"));

    const syncAnchorTab = () => {
        const activeAnchor = navLinks.find(link => {
            const target = new URL(link.href, window.location.href);
            return target.pathname === window.location.pathname &&
                target.hash !== "" &&
                target.hash === window.location.hash;
        });

        if (activeAnchor) {
            navLinks.forEach(link => {
                const active = link === activeAnchor;
                link.classList.toggle("active", active);
                if (active) {
                    link.setAttribute("aria-current", "page");
                } else {
                    link.removeAttribute("aria-current");
                }
            });
        }
    };

    syncAnchorTab();
    window.addEventListener("hashchange", syncAnchorTab);

    // MOBILE MENU
    menuBtn.addEventListener("click", () => {
        const open = menu.classList.toggle("open");

        menuBtn.setAttribute("aria-expanded", String(open));
        menuBtn.setAttribute(
            "aria-label",
            open ? "Close menu" : "Open menu"
        );
    });

    menu.querySelectorAll("a").forEach(link => {
        link.addEventListener("click", () => {
            menu.classList.remove("open");
            menuBtn.setAttribute("aria-expanded", "false");
        });
    });

    profileWrappers.forEach(wrapper => {
        const trigger = wrapper.querySelector(".nav-profile-trigger");
        const profileMenu = wrapper.querySelector(".nav-profile-menu");

        trigger.addEventListener("click", () => {
            const open = profileMenu.classList.toggle("show");
            trigger.setAttribute("aria-expanded", String(open));
        });
    });

    document.addEventListener("click", event => {
        profileWrappers.forEach(wrapper => {
            if (!wrapper.contains(event.target)) {
                wrapper.querySelector(".nav-profile-menu").classList.remove("show");
                wrapper.querySelector(".nav-profile-trigger").setAttribute("aria-expanded", "false");
            }
        });
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape") {
            profileWrappers.forEach(wrapper => {
                wrapper.querySelector(".nav-profile-menu").classList.remove("show");
                wrapper.querySelector(".nav-profile-trigger").setAttribute("aria-expanded", "false");
            });
        }
    });

    // DARK / LIGHT THEME
    const applyTheme = theme => {
        document.documentElement.dataset.theme = theme;
        localStorage.setItem("cc_theme", theme);
        document.cookie =
            `cc_theme=${theme}; path=/; max-age=31536000; SameSite=Lax`;

        themeIcon.textContent = theme === "dark" ? "☀" : "☾";
    };

    const savedTheme = localStorage.getItem("cc_theme");

    if (savedTheme === "dark" || savedTheme === "light") {
        applyTheme(savedTheme);
    } else {
        applyTheme(
            document.documentElement.dataset.theme || "light"
        );
    }

    themeBtn.addEventListener("click", () => {
        const current = document.documentElement.dataset.theme;
        applyTheme(current === "dark" ? "light" : "dark");
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 850) {
            menu.classList.remove("open");
            menuBtn.setAttribute("aria-expanded", "false");
        }
    });
})();
</script>
