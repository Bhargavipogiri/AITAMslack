
<footer class="main-footer">

    <div class="footer-container">

        <!-- Brand Section -->
        <div class="footer-brand">

            <a href="index.php" class="footer-brand-link">
                <div class="footer-logo-box">
                    <img src="assets/images/logo.png"
                         alt="AITAM College Logo"
                         width="44"
                         height="44"
                         class="footer-logo">
                </div>

                <div class="footer-brand-text">
                    <h2>AITAM SLACK</h2>
                    <span>AITAM COMMUNITY</span>
                </div>
            </a>

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



    <!-- Bottom Section -->
    <div class="footer-bottom">

        <p>
            &copy; <?php echo date("Y"); ?> AITAM SLACK
        </p>

        <details class="footer-developer">
            <summary>Developed By <strong>CSM Dept</strong></summary>
            <ul class="footer-student-menu" aria-label="Student developers">
                <li>YAJNESWARARAO</li>
                <li>PURNACHNADRARAO</li>
                <li>LOKESH</li>
                <li>BHARGAVI</li>
                <li>LEKHANAPRIYA</li>
                <li>JAHNAVI</li>
                <li>POOJITHA</li>
            </ul>
        </details>

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
    align-items: center;
    gap: 12px;
}

.footer-brand-link {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    text-decoration: none;
    transition: var(--transition-fast);
}

.main-footer .footer-brand-link:hover,
.main-footer .footer-brand-link:focus-visible {
    transform: none;
}

.footer-logo-box {
    width: 56px;
    height: 56px;
    flex: 0 0 56px;
    display: grid;
    place-items: center;
    padding: 5px;
    overflow: hidden;
    border: 0;
    border-radius: 50%;
    background: var(--color-white);
    box-sizing: border-box;
}

.footer-logo {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
}

.footer-brand-text h2 {
    color: var(--text-on-brand);
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: normal;
    text-transform: none;
}

.footer-brand-text span {
    color: var(--color-accent);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.5px;
    line-height: 1.2;
    text-transform: none;
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
    column-gap: 34px;
    align-items: start;
}

.footer-links-column {
    display: flex;
    flex-direction: column;
    gap: 3px;
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
    color: var(--color-accent);
}

.footer-links a:focus-visible {
    color: var(--color-accent);
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

.main-footer a:hover {
    transform: translateY(-2px);
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

.footer-developer {
    position: relative;
    display: inline-block;
    margin: 5px 0;
    color: var(--color-accent);
    font-size: 12px;
}

.footer-developer summary {
    cursor: pointer;
    list-style: none;
}

.footer-developer summary::-webkit-details-marker {
    display: none;
}

.footer-developer summary:focus-visible {
    outline: 2px solid var(--color-accent);
    outline-offset: 3px;
    border-radius: var(--radius-sm);
}

.footer-student-menu {
    position: absolute;
    z-index: 5;
    left: 50%;
    bottom: calc(100% + 10px);
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 2px 6px;
    width: max-content;
    max-width: calc(100vw - 32px);
    margin: 0;
    padding: 8px;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    box-shadow: var(--shadow-md);
    list-style: none;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform: translateX(-50%) translateY(6px);
    transition: opacity 160ms ease, transform 160ms ease, visibility 0s linear 160ms;
}

.footer-student-menu li {
    padding: 5px 7px;
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 12px;
    line-height: 1.3;
    white-space: nowrap;
}

.footer-developer:hover .footer-student-menu,
.footer-developer:focus-within .footer-student-menu,
.footer-developer[open] .footer-student-menu {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transform: translateX(-50%) translateY(0);
    transition: opacity 160ms ease, transform 160ms ease, visibility 0s;
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

    .footer-brand-text h2 {
        font-size: 15px;
    }

    .footer-brand-text span {
        font-size: 8px;
    }

}

</style>
