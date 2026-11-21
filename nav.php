<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Connect</title>
    <link rel="stylesheet" href="root.css">
</head>
<body>
<!-- College Connect Navbar -->

<nav class="cc-navbar">

    <div class="cc-nav-container">

        <!-- LOGO -->
        <a href="index.php" class="cc-brand">
            <div class="cc-logo"><img src="https://www.facultyplus.com/wp-content/uploads/2024/04/AITAM.png" alt="College Connect Logo" width="44" height="44"></div>

            <div class="cc-brand-text">
                <h2>College Connect</h2>
                <span>AITAM COMMUNITY</span>
            </div>
        </a>

        <!-- DESKTOP MENU -->
        <div class="cc-nav-menu" id="ccNavMenu">

            <a href="index.php" class="cc-nav-link active">Home</a>
            <a href="community.php" class="cc-nav-link">Community</a>
            <a href="questions.php" class="cc-nav-link">Questions</a>
              <a href="alumni.php" class="cc-nav-link">Answer</a>
            <a href="events.php" class="cc-nav-link">Events</a>
         
            <a href="about.php" class="cc-nav-link">About</a>
             <a href="alumni.php" class="cc-nav-link">Alumni</a>
           

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
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: var(--gradient-brand);
    color: white;
    font-size: 25px;
    font-weight: 800;
}

.cc-brand-text h2 {
    margin: 0;
    color: var(--text-primary);
    font-size: 18px;
    font-weight: 800;
}

.cc-brand-text span {
    color: var(--color-secondary);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.5px;
}

/* MENU */
.cc-nav-menu {
    display: flex;
    align-items: center;
    gap: 5px;
}

.cc-nav-link {
    position: relative;
    padding: 11px 12px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 13px;
    font-weight: 650;
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
    padding: 10px 17px;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 700;
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
</body>
</html>
