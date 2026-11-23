
<footer class="main-footer">

    <div class="footer-container">

        <!-- Brand Section -->
        <div class="footer-brand">

            <div class="footer-logo-box">
                <img src="./assets/images/logo.png"
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

                <!-- First Four Links -->
                <div class="footer-links-column">
                    <a href="index.php">Home</a>
                    <a href="question-history.php">Questions</a>
                    <a href="index.php#events">Events</a>
                    <a href="answer.php">Answers</a>
                </div>

                <!-- Remaining Three Links -->
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
    padding: 30px 4% 18px;
    font-family: var(--font-family-base);
    box-sizing: border-box;
}

/* Main Container */
.footer-container {
    max-width: 1300px;
    margin: auto;

    display: grid;
    grid-template-columns: 1.25fr 1fr 1fr 1.15fr;

    column-gap: 40px;
    align-items: start;
    padding-bottom: 22px;
}

/* Brand Section */
.footer-brand {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.footer-logo-box {
    width: 60px;
    height: 60px;
    min-width: 60px;
    background: var(--bg-surface);
    border-radius: 50%;
    padding: 5px;
    box-sizing: border-box;
}

.footer-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 50%;
}

.footer-brand-text h2 {
    color: var(--text-on-brand);
    font-size: 21px;
    margin: 0 0 7px;
    font-weight: 800;
}

.footer-brand-text h2 span {
    color: var(--color-accent);
}

.footer-brand-text p {
    color: var(--text-on-brand);
    font-size: 13px;
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
    font-size: 17px;
    margin: 0 0 14px;
    font-weight: 700;
}

/* Quick Links Main */
.footer-links {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding-left: 36px;
}

/* Quick Links Two Columns */
.footer-links-columns {
    display: grid;
    grid-template-columns: max-content max-content;
    column-gap: 24px;
    align-items: start;
}

.footer-links-column {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.footer-links a {
    display: block;
    width: max-content;
    max-width: 100%;
    color: var(--text-on-brand);
    text-decoration: none;
    font-size: 13px;
    line-height: 1.5;
    padding: 4px 8px;
    margin: 0 -8px;
    border-radius: var(--radius-sm);
    transition: var(--transition-fast);
}

.footer-links a:hover {
    color: var(--color-navy-950);
    background: var(--color-accent);
    text-decoration: underline;
    text-decoration-thickness: 1px;
    text-underline-offset: 3px;
}

.footer-links a:focus-visible {
    color: var(--color-navy-950);
    background: var(--color-accent);
    outline: 2px solid var(--text-on-brand);
    outline-offset: 2px;
}

/* About Section */
.footer-about p {
    color: var(--text-on-brand);
    font-size: 13px;
    line-height: 1.7;
    margin: 0;
}

/* Contact Us */
.footer-contact p {
    display: flex;
    flex-direction: column;
    gap: 4px;
    color: var(--text-on-brand);
    font-size: 13px;
    line-height: 1.6;
    margin: 0 0 12px;
    overflow-wrap: anywhere;
}

.contact-label {
    color: var(--color-accent);
    font-weight: 600;
}

.footer-contact a {
    color: var(--text-on-brand);
    text-decoration: none;
    transition: var(--transition-fast);
}

.footer-contact a:hover {
    color: var(--color-accent);
}

/* Divider */
.footer-divider {
    max-width: 1300px;
    margin: 0 auto 16px;
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
    gap: 12px;
    flex-wrap: wrap;
}

.footer-bottom p {
    color: var(--color-accent);
    font-size: 12px;
    margin: 5px 0;
}

.footer-bottom strong {
    color: var(--text-on-brand);
}

/* Tablet Responsive */
@media (max-width: 1050px) {

    .footer-container {
        grid-template-columns: 1fr 1.3fr;
        gap: 28px;
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
        gap: 24px;
    }

    .footer-brand {
        justify-content: flex-start;
    }

    .footer-links-columns {
        column-gap: 25px;
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
        column-gap: 15px;
    }

    .footer-links a {
        font-size: 12px;
    }

}

</style>
