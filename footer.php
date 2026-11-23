
<footer class="main-footer">

    <div class="footer-container">

        <!-- Brand Section -->
        <div class="footer-brand">

            <div class="footer-logo-box">
                <img src="./assets/images/Aitamlogo.png"
                     alt="AITAM College Logo"
                     class="footer-logo">
            </div>

            <div class="footer-brand-text">
                <h2>AITAM <span>SLACK</span></h2>
                <p>College Communication Platform</p>
                <p class="footer-tagline">
                    Connect. Communicate. Collaborate.
                </p>
            </div>

        </div>

        <!-- Quick Links -->
        <div class="footer-links">

            <h3>Quick Links</h3>

            <div class="footer-links-columns">

                <div class="footer-links-column">
                    <a href="index.php">Home</a>
                    <a href="question-history.php">Questions</a>
                    <a href="index.php#events">Events</a>
                    <a href="answer.php">Answers</a>
                </div>

                <div class="footer-links-column">
                    <a href="leaderboard.php">Leaderboard</a>
                    <a href="report.php">Report</a>
                    <a href="terms.php">Terms and Conditions</a>
                </div>

            </div>

        </div>

        <!-- About Section -->
        <div class="footer-about">

            <h3>About AITAM Slack</h3>

            <p>
                A platform connecting students and faculty
                to share knowledge and information.
            </p>

        </div>

        <!-- Contact Us -->
        <div class="footer-contact">

            <h3>Contact Us</h3>

            <p>
                <span class="contact-label">Email</span>
                <a href="mailto:csmdepartment987@gmail.com">
                    csmdepartment987@gmail.com
                </a>
            </p>

            <p>
                <span class="contact-label">Support</span>
                <a href="report.php">Report an Issue</a>
            </p>

        </div>

    </div>

    <!-- Divider -->
    <div class="footer-divider"></div>

    <!-- Bottom Section -->
    <div class="footer-bottom">

        <p>
            &copy; <?php echo date("Y"); ?> AITAM SLACK
        </p>

        <p>
            Developed By <strong>© CSM dep</strong>
        </p>

        <p>
            Built for a more connected campus.
        </p>

    </div>

</footer>


<style>

/* Main Footer */
.main-footer {
    width: 100%;
    background: var(--gradient-brand);
    color: var(--text-on-brand);
    padding: 35px 5% 20px;
    margin-top: 30px;
    font-family: var(--font-family-base);
    box-sizing: border-box;
}

/* Main Container */
.footer-container {
    max-width: 1300px;
    margin: 0 auto;

    display: grid;
    grid-template-columns: 1.2fr 1.15fr 1fr 1.25fr;

    column-gap: 35px;
    row-gap: 25px;
    align-items: start;
    padding-bottom: 25px;
}

/* Brand Section */
.footer-brand {
    display: flex;
    align-items: center;
    gap: 14px;
}

/* Logo Container */
.footer-logo-box {
    width: 68px;
    height: 68px;
    min-width: 68px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-surface);
    border-radius: 50%;
    padding: 6px;
    box-sizing: border-box;
    overflow: hidden;
}

/* Logo Image */
.footer-logo {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    border-radius: 50%;
}

/* Brand Text */
.footer-brand-text {
    min-width: 0;
}

.footer-brand-text h2 {
    color: var(--text-on-brand);
    font-size: 23px;
    margin: 0 0 8px;
    font-weight: 800;
    line-height: 1.2;
}

.footer-brand-text h2 span {
    color: var(--color-accent);
}

.footer-brand-text p {
    color: var(--text-on-brand);
    font-size: 14px;
    line-height: 1.6;
    margin: 5px 0;
}

.footer-brand-text .footer-tagline {
    color: var(--color-accent);
    font-weight: 600;
    font-style: italic;
    margin-top: 12px;
}

/* Footer Headings */
.footer-links h3,
.footer-about h3,
.footer-contact h3 {
    color: var(--color-accent);
    font-size: 19px;
    margin: 0 0 17px;
    font-weight: 700;
}

/* Quick Links */
.footer-links {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding-left: 10px;
}

/* Quick Links Two Columns */
.footer-links-columns {
    display: grid;
    grid-template-columns: max-content max-content;
    column-gap: 28px;
    align-items: start;
}

.footer-links-column {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

/* Quick Links Text */
.footer-links a {
    display: block;
    width: fit-content;
    max-width: 100%;
    color: var(--text-on-brand);
    text-decoration: none;
    font-size: 15px;
    line-height: 1.6;
    transition: var(--transition-fast);
}

/* Simple Hover Like Contact Us */
.footer-links a:hover {
    color: var(--color-accent);
    text-decoration: none;
}

/* Keyboard Focus */
.footer-links a:focus-visible {
    color: var(--color-accent);
    outline: 2px solid var(--text-on-brand);
    outline-offset: 3px;
}

/* About Section */
.footer-about p {
    color: var(--text-on-brand);
    font-size: 15px;
    line-height: 1.8;
    margin: 0;
}

/* Contact Us */
.footer-contact p {
    display: flex;
    flex-direction: column;
    gap: 5px;
    color: var(--text-on-brand);
    font-size: 15px;
    line-height: 1.7;
    margin: 0 0 14px;
    overflow-wrap: anywhere;
}

.contact-label {
    color: var(--color-accent);
    font-weight: 700;
    font-size: 15px;
}

.footer-contact a {
    color: var(--text-on-brand);
    text-decoration: none;
    font-size: 15px;
    transition: var(--transition-fast);
}

.footer-contact a:hover {
    color: var(--color-accent);
}

/* Divider */
.footer-divider {
    max-width: 1300px;
    margin: 0 auto 18px;
    height: 1px;
    background: var(--border-strong);
}

/* Bottom Footer */
.footer-bottom {
    max-width: 1300px;
    margin: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
}

.footer-bottom p {
    color: var(--color-accent);
    font-size: 13px;
    margin: 5px 0;
}

.footer-bottom strong {
    color: var(--text-on-brand);
}

/* Tablet Responsive */
@media (max-width: 1050px) {

    .footer-container {
        grid-template-columns: 1fr 1fr;
        column-gap: 35px;
        row-gap: 30px;
    }

    .footer-links {
        padding-left: 0;
    }

}

/* Mobile Responsive */
@media (max-width: 600px) {

    .main-footer {
        padding: 28px 20px 18px;
    }

    .footer-container {
        grid-template-columns: 1fr;
        gap: 25px;
    }

    .footer-brand {
        align-items: center;
    }

    .footer-logo-box {
        width: 60px;
        height: 60px;
        min-width: 60px;
    }

    .footer-brand-text h2 {
        font-size: 21px;
    }

    .footer-links-columns {
        column-gap: 30px;
    }

    .footer-bottom {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 8px;
    }

}

/* Small Mobile */
@media (max-width: 380px) {

    .footer-links-columns {
        column-gap: 18px;
    }

    .footer-links a {
        font-size: 14px;
    }

    .footer-brand {
        gap: 10px;
    }

    .footer-logo-box {
        width: 54px;
        height: 54px;
        min-width: 54px;
    }

}

</style>
