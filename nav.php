<?php
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php');
?>
<!-- College Connect Navbar -->

<nav class="cc-navbar">

    <div class="cc-nav-container">

        <!-- LOGO -->
        <a href="index.php" class="cc-brand">
            <div class="cc-logo"><img src="assets/images/logo.png" alt="AITAM College Logo" width="44" height="44"></div>

            <div class="cc-brand-text">
                <h2>AITAM SLACK</h2>
                <span>AITAM COMMUNITY</span>
            </div>
        </a>

        <!-- DESKTOP MENU -->
        <div class="cc-nav-menu" id="ccNavMenu">

            <a href="index.php" class="cc-nav-link active">Home</a>
            <a href="ask_question.php" class="cc-nav-link">Ask Question</a>
            <a href="leaderboard.php" class="cc-nav-link">Leaderboard</a>
            <a href="question-history.php" class="cc-nav-link">Questions</a>
              <a href="answer.php" class="cc-nav-link">Answer</a>
            <a href="events.php" class="cc-nav-link">Events</a>
         
            <a href="about.php" class="cc-nav-link">About</a>
             <!-- <a href="alumni.php" class="cc-nav-link">Alumni</a> -->
           
            <!-- <a href="index.php" class="cc-nav-link<?= $currentPage === 'index.php' ? ' active' : '' ?>"<?= $currentPage === 'index.php' ? ' aria-current="page"' : '' ?>>Home</a>
            <a href="question-history.php" class="cc-nav-link<?= $currentPage === 'question-history.php' ? ' active' : '' ?>"<?= $currentPage === 'question-history.php' ? ' aria-current="page"' : '' ?>>Questions</a>
            <a href="answer.php" class="cc-nav-link<?= $currentPage === 'answer.php' ? ' active' : '' ?>"<?= $currentPage === 'answer.php' ? ' aria-current="page"' : '' ?>>Answer</a>
            <a href="leaderboard.php" class="cc-nav-link<?= $currentPage === 'leaderboard.php' ? ' active' : '' ?>"<?= $currentPage === 'leaderboard.php' ? ' aria-current="page"' : '' ?>>Leaderboard</a>
            <a href="report.php" class="cc-nav-link<?= $currentPage === 'report.php' ? ' active' : '' ?>"<?= $currentPage === 'report.php' ? ' aria-current="page"' : '' ?>>Report</a>
            <a href="index.php#events" class="cc-nav-link">Events</a>
            <a href="about.php" class="cc-nav-link<?= $currentPage === 'about.php' ? ' active' : '' ?>"<?= $currentPage === 'about.php' ? ' aria-current="page"' : '' ?>>About</a> -->

            <!-- MOBILE ACTIONS -->
            <div class="cc-mobile-actions">
                <a href="login.php" class="cc-login">Login</a>
                <a href="register.php" class="cc-register">Join Now</a>
            </div>

        </div>

        <!-- RIGHT ACTIONS -->
        <div class="cc-nav-actions">

            <button class="cc-theme-btn" id="ccThemeBtn"
                    type="button" aria-label="Toggle theme">
                <span id="ccThemeIcon">☾</span>
            </button>

            <a href="login.php" class="cc-login">Login</a>
            <a href="register.php" class="cc-register">Join Now</a>

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

    .cc-mobile-actions {
        display: flex;
        gap: 10px;
        margin-top: 12px;
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
