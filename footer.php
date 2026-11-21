<html>
    <head>
        <title>Question History - AITAM SLACK</title>
        <link rel="stylesheet" href="assets/css/root.css">
</head>
<body>
<footer class="main-footer">

    <div class="footer-container">

        <!-- Top Footer Section -->
        <div class="footer-top">

            <!-- Brand and Logo -->
            <div class="footer-brand">

                <img
                    src="/aitam-slack/AITAMslack/assets/images/Aitamlogo.png"
                    alt="AITAM College Logo"
                    class="footer-logo"
                >

                <h2>AITAM <span>SLACK</span></h2>

            </div>

            <!-- Tagline -->
            <p class="footer-tagline">
                A community where every question can find a helpful answer.
            </p>

            <!-- Navigation Links -->
            <nav class="footer-links">

                <a href="dashboard.php">Home</a>
                <a href="question-history.php">Questions</a>
                <a href="college-updates.php">College updates</a>
                <a href="topics.php">Topics</a>
                <a href="privacy.php">Privacy</a>
                <a href="terms.php">Terms</a>

            </nav>

        </div>

        <!-- Divider -->
        <div class="footer-divider"></div>

        <!-- Bottom Footer Section -->
        <div class="footer-bottom">

            <p class="copyright">
                &copy; <?php echo date("Y"); ?> AITAM SLACK
            </p>

            <p class="developer">
                Developed By
                <strong>© CSM dep</strong>
            </p>

            <p class="footer-message">
                Built for a more connected campus.
            </p>

        </div>

    </div>

</footer>


<style>

/* ==========================
   AITAM SLACK FOOTER
========================== */

.main-footer {
    width: 100%;
    background: var(--color-navy-950);
    color: var(--text-on-brand);
    padding: 35px 6% 25px;
    margin-top: 40px;
    font-family: var(--font-family-base);
}

/* Main Container */
.footer-container {
    max-width: 1140px;
    width: 100%;
    margin: auto;
}

/* Top Section */
.footer-top {
    display: grid;
    grid-template-columns: 1fr 1.5fr 1.5fr;
    align-items: center;
    gap: 25px;
    min-height: 75px;
}

/* Brand Section */
.footer-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.footer-logo {
    width: 58px;
    height: 58px;
    object-fit: contain;
}

.footer-brand h2 {
    font-size: 18px;
    font-weight: 800;
    margin: 0;
    color: var(--color-white);
    white-space: nowrap;
}

.footer-brand h2 span {
    font-weight: 400;
    color: var(--color-white);
}

/* Tagline */
.footer-tagline {
    font-size: 12px;
    color: var(--color-white);
    text-align: center;
    margin: 0;
    line-height: 1.6;
}

/* Navigation */
.footer-links {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 18px;
}

.footer-links a {
    color: var(--color-white);
    text-decoration: none;
    font-size: 12px;
    white-space: nowrap;
    transition: var(--transition-fast);
}

.footer-links a:hover {
    color: var(--color-cyan-100);
}

/* Divider */
.footer-divider {
    width: 100%;
    height: 1px;
    background: var(--color-blue-500);
    margin: 30px 0 25px;
}

/* Bottom Section */
.footer-bottom {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    align-items: center;
    gap: 15px;
}

.footer-bottom p {
    color: var(--color-white);
    font-size: 11px;
    margin: 0;
}

.copyright {
    text-align: left;
}

.developer {
    text-align: center;
}

.developer strong {
    margin-left: 8px;
}

.footer-message {
    text-align: right;
}

/* Responsive Design */
@media (max-width: 1000px) {

    .footer-top {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 20px;
    }

    .footer-brand {
        justify-content: center;
    }

    .footer-links {
        justify-content: center;
        flex-wrap: wrap;
    }

}

@media (max-width: 600px) {

    .main-footer {
        padding: 30px 20px 20px;
    }

    .footer-logo {
        width: 50px;
        height: 50px;
    }

    .footer-links {
        gap: 15px;
    }

    .footer-bottom {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 15px;
    }

    .copyright,
    .developer,
    .footer-message {
        text-align: center;
    }

    .footer-divider {
        margin: 22px 0;
    }

}

</style>
</body>
</html>
