<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Terms and Conditions | AITAM Slack</title>

    <link rel="stylesheet" href="assets/css/root.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-family-base);
            background: var(--color-white);
            color: var(--text-primary);
        }

        /* Main Container */
        .terms-container {
            max-width: 1100px;
            margin: 45px auto;
            padding: 20px;
        }

        /* Header */
        .terms-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .terms-header h1 {
            color: var(--color-navy-950);
            font-size: 32px;
            margin-bottom: 12px;
        }

        .terms-header p {
            color: var(--text-secondary);
            font-size: 15px;
            line-height: 1.7;
        }

        .last-updated {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background: var(--color-cyan-100);
            color: var(--color-navy-950);
            border-radius: 20px;
            font-size: 13px;
        }

        /* Terms Content */
        .terms-content {
            background: var(--color-white);
            border: 1px solid var(--color-cyan-100);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .terms-section {
            margin-bottom: 28px;
        }

        .terms-section h2 {
            color: var(--color-navy-950);
            font-size: 20px;
            margin-bottom: 12px;
        }

        .terms-section p {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .terms-section ul {
            padding-left: 25px;
            color: var(--text-secondary);
        }

        .terms-section li {
            font-size: 14px;
            line-height: 1.9;
            margin-bottom: 7px;
        }

        /* Contact Section */
        .terms-contact {
            background: var(--surface-secondary);
            border-left: 4px solid var(--color-blue-500);
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
        }

        .terms-contact h2 {
            color: var(--color-navy-950);
            font-size: 19px;
            margin-top: 0;
        }

        .terms-contact p {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.7;
        }

        /* Back Button */
        .terms-back {
            display: inline-block;
            margin-top: 25px;
            padding: 11px 22px;
            background: var(--color-blue-600);
            color: var(--text-on-brand);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition-fast);
        }

        .terms-back:hover {
            opacity: 0.9;
        }

        /* Responsive */
        @media (max-width: 600px) {

            .terms-container {
                margin: 20px auto;
                padding: 15px;
            }

            .terms-header h1 {
                font-size: 25px;
            }

            .terms-content {
                padding: 20px;
            }

            .terms-section h2 {
                font-size: 18px;
            }
        }

    </style>

</head>

<body>

<?php include 'nav.php'; ?>


<main class="terms-container">

    <!-- Page Header -->
    <header class="terms-header">

        <h1>Terms and Conditions</h1>

        <p>
            Welcome to AITAM Slack. Please read these terms and
            conditions carefully before using our college
            communication platform.
        </p>

        <span class="last-updated">
            Last Updated: November 2026
        </span>

    </header>


    <div class="terms-content">

        <!-- 1 -->
        <section class="terms-section">

            <h2>1. Introduction</h2>

            <p>
                AITAM Slack is a college communication platform
                designed to connect students, faculty members,
                and administrators. It provides a common space
                for sharing academic information, asking questions,
                discussing topics, and collaborating with the
                college community.
            </p>

            <p>
                By accessing or using this platform, you agree
                to follow the terms and conditions mentioned
                on this page.
            </p>

        </section>


        <!-- 2 -->
        <section class="terms-section">

            <h2>2. User Accounts</h2>

            <p>
                Users are responsible for maintaining the security
                of their accounts and providing accurate information.
            </p>

            <ul>
                <li>Use your account for genuine college-related activities.</li>
                <li>Do not share your login credentials with others.</li>
                <li>Do not access another user's account without permission.</li>
                <li>Keep your profile information accurate and updated.</li>
                <li>Report suspicious account activity to the administrator.</li>
            </ul>

        </section>


        <!-- 3 -->
        <section class="terms-section">

            <h2>3. Acceptable Use</h2>

            <p>
                AITAM Slack should be used responsibly and respectfully.
                All users must maintain a positive and professional
                communication environment.
            </p>

            <ul>
                <li>Share only relevant and appropriate information.</li>
                <li>Respect other students, faculty, and administrators.</li>
                <li>Do not post abusive, offensive, or threatening content.</li>
                <li>Do not share false information or misleading announcements.</li>
                <li>Do not use the platform for spam or unauthorized promotions.</li>
            </ul>

        </section>


        <!-- 4 -->
        <section class="terms-section">

            <h2>4. Questions and Answers</h2>

            <p>
                Users can post academic questions and submit answers
                to help other members of the college community.
            </p>

            <ul>
                <li>Questions should be clear and related to the discussion.</li>
                <li>Answers should be helpful, relevant, and respectful.</li>
                <li>Do not copy or submit another person's work as your own.</li>
                <li>Users are responsible for the content they submit.</li>
                <li>Administrators may review or moderate inappropriate content.</li>
            </ul>

        </section>


        <!-- 5 -->
        <section class="terms-section">

            <h2>5. Messages and Communication</h2>

            <p>
                The messaging and communication features are provided
                to support interaction among authorized platform users.
            </p>

            <ul>
                <li>Communicate respectfully with other users.</li>
                <li>Do not send threatening or harassing messages.</li>
                <li>Do not misuse messaging features for spam.</li>
                <li>Do not share another person's private information without permission.</li>
            </ul>

        </section>


        <!-- 6 -->
        <section class="terms-section">

            <h2>6. College Updates and Topics</h2>

            <p>
                College updates and topics help users stay informed
                about academic activities, events, discussions,
                and other college-related information.
            </p>

            <p>
                Users should verify important announcements through
                official college sources before relying on them.
                Only authorized users should publish official
                college announcements.
            </p>

        </section>


        <!-- 7 -->
        <section class="terms-section">

            <h2>7. Privacy and Data Protection</h2>

            <p>
                Users should respect the privacy of other members
                and avoid sharing personal or confidential information
                without permission.
            </p>

            <ul>
                <li>Do not misuse personal information available on the platform.</li>
                <li>Do not share private messages without appropriate permission.</li>
                <li>Protect your account credentials and personal details.</li>
            </ul>

            <p>
                Please refer to our Privacy Policy for more information
                about how platform information is handled.
            </p>

        </section>


        <!-- 8 -->
        <section class="terms-section">

            <h2>8. User Responsibilities</h2>

            <p>
                Every user is responsible for their activities
                and the content they share on AITAM Slack.
            </p>

            <p>
                Users must ensure that their activities do not
                violate college rules, applicable laws, or the
                rights of other individuals.
            </p>

        </section>


        <!-- 9 -->
        <section class="terms-section">

            <h2>9. Account Suspension</h2>

            <p>
                The platform administrators may review reported
                activities and take appropriate action when users
                violate these terms.
            </p>

            <p>
                Depending on the situation, actions may include
                removing inappropriate content, restricting access,
                or suspending an account in accordance with
                applicable college policies.
            </p>

        </section>


        <!-- 10 -->
        <section class="terms-section">

            <h2>10. Changes to These Terms</h2>

            <p>
                These terms and conditions may be updated from
                time to time to reflect changes in platform
                features, college requirements, or applicable policies.
            </p>

            <p>
                Users are encouraged to review this page periodically
                to stay informed about any changes.
            </p>

        </section>


        <!-- 11 -->
        <section class="terms-section">

            <h2>11. Contact and Support</h2>

            <p>
                If you have questions, concerns, or complaints
                regarding these terms, please contact the
                platform administrator or the designated
                college support team.
            </p>

        </section>


        <!-- Contact Box -->
        <div class="terms-contact">

            <h2>Need Help?</h2>

            <p>
                For assistance regarding AITAM Slack, contact
                the platform administrator or use the available
                feedback and complaint facilities.
            </p>

        </div>

    </div>


    <a href="dashboard.php" class="terms-back">
        Back to Home
    </a>

</main>


<?php include 'footer.php'; ?>


</body>
</html>
