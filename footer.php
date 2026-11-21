<html>
<head>
    <title>AITAM Slack</title>
      <link rel="stylesheet" href="assets/css/root.css">

<style>
/* ==========================
   AITAM SLACK FOOTER
========================== */

.main-footer {
    background: var(--gradient-brand);
    color: var(--text-on-brand);
    padding: 45px 6% 15px;
    margin-top: 40px;
    font-family: var(--font-family-base);
}

.footer-container {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr 1.3fr;
    gap: 50px;
    max-width: 1300px;
    margin: auto;
    padding-bottom: 35px;
}

/* Brand Section */
.footer-brand h2 {
    font-size: 30px;
    font-weight: 800;
    margin: 0 0 10px;
    color: var(--color-white);
}

.footer-brand h2 span {
    color: var(--color-cyan-100);
}

.brand-description {
    font-size: 14px;
    color: var(--color-white);
}

.footer-tagline {
    color: var(--color-cyan-100);
    font-size: 15px;
    font-weight: 600;
    font-style: italic;
    margin-top: 25px;
}

/* Headings */
.footer-links h3,
.footer-about h3 {
    font-size: 18px;
    color: var(--color-cyan-100);
    margin: 0 0 20px;
}

/* Quick Links */
.footer-links {
    display: flex;
    flex-direction: column;
    gap: 13px;
}

.footer-links a {
    color: var(--color-white);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition-fast);
}

.footer-links a:hover {
    color: var(--color-cyan-100);
    transform: translateX(5px);
}

/* About Section */
.footer-about p {
    font-size: 14px;
    line-height: 1.8;
    color: var(--color-white);
}

/* Copyright */
.footer-bottom {
    border-top: 1px solid var(--color-blue-500);
    text-align: center;
    padding-top: 18px;
}

.footer-bottom p {
    color: var(--color-cyan-100);
    font-size: 13px;
    margin: 7px 0;
}
.footer-container {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr 1.3fr;
    gap: 50px;
    max-width: 1300px;
    margin: auto;
    padding-bottom: 35px;
}

/* Brand Section */

.footer-brand {
    position: relative;
    padding-left: 115px;
    min-height: 110px;
}

.footer-logo {
    position: absolute;
    top: 0;
    left: 0;
    width: 100px;
    height: 100px;
    object-fit: contain;
}

.footer-brand h2 {
    font-size: 30px;
    font-weight: 800;
    margin: 0 0 10px;
    color: var(--color-white);
}

.footer-brand h2 span {
    color: var(--color-cyan-100);
}

/* Responsive Design */
@media (max-width: 768px) {
    .footer-container {
        grid-template-columns: 1fr;
        gap: 25px;
        text-align: center;
    }

    .footer-links {
        align-items: center;
    }

    .main-footer {
        padding: 35px 20px 15px;
    }
}
@media (max-width: 768px) {

    .footer-brand {
        padding-left: 0;
        text-align: center;
    }

    .footer-logo {
        position: static;
        display: block;
        margin: 0 auto 15px;
    }

    .footer-container {
        grid-template-columns: 1fr;
        gap: 25px;
        text-align: center;
    }

    .footer-links {
        align-items: center;
    }
}
</style>
</head>

<body>
<!-- FOOTER HTML -->

<footer class="main-footer">

    <div class="footer-container">

       <div class="footer-brand">

    <img src="/aitam-slack/AITAMslack/assets/images/Aitamlogo.png"
         alt="AITAM College Logo"
         class="footer-logo">

    <h2>AITAM <span>Slack</span></h2>

    <p class="brand-description">
        College Communication Platform
    </p>

    <p class="footer-tagline">
        Connect. Communicate. Collaborate.
    </p>

</div>


        <!-- Quick Links -->
        <div class="footer-links">

            <h3>Quick Links</h3>

            <a href="dashboard.php">Home</a>
            <a href="channels.php">Channels</a>
            <a href="messages.php">Messages</a>
            <a href="profile.php">My Profile</a>

        </div>


        <!-- About -->
        <div class="footer-about">

            <h3>About AITAM Slack</h3>

            <p>
                A centralized communication platform
                for students, faculty and administrators.
            </p>

            <p>
                Share academic information, discuss ideas
                and collaborate with your college community.
            </p>

        </div>

    </div>


    <!-- Copyright -->
    <div class="footer-bottom">

        <p>
            &copy; <?php echo date("Y"); ?>
            AITAM Slack
        </p>

        <p>
           <a href="" style="color: var(--color-cyan-100); text-decoration:none;">csm</a>
        </p>

    </div>

    

</footer>

</body>
</html>
