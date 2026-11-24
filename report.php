
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'lesder-db.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$success = '';
$error = '';

$report_type = '';
$subject = '';
$description = '';
$priority = 'normal';

$allowed_types = [
    'inappropriate_content',
    'harassment',
    'spam',
    'fake_information',
    'technical_issue',
    'other'
];

$allowed_priorities = ['normal', 'high', 'urgent'];

/* Create CSRF token */
if (empty($_SESSION['report_csrf'])) {
    $_SESSION['report_csrf'] = bin2hex(random_bytes(32));
}

/* Handle report submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $report_type = trim($_POST['report_type'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'normal');

    $csrfToken = $_POST['csrf_token'] ?? '';

    $userId = filter_var(
        $_SESSION['user_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!hash_equals($_SESSION['report_csrf'], $csrfToken)) {

        $error = 'Invalid form submission. Please refresh and try again.';

    } elseif (!$userId || $userId <= 0) {

        $error = 'Please log in before submitting a report.';

    } elseif (
        !in_array($report_type, $allowed_types, true) ||
        !in_array($priority, $allowed_priorities, true) ||
        $subject === '' ||
        $description === ''
    ) {

        $error = 'Please complete all required fields correctly.';

    } elseif (mb_strlen($subject) > 150) {

        $error = 'The subject must not exceed 150 characters.';

    } elseif (mb_strlen($description) > 3000) {

        $error = 'The description must not exceed 3000 characters.';

    } else {

        try {

            $sql = "INSERT INTO user_reports
                    (user_id, report_type, subject, description, priority)
                    VALUES
                    (:user_id, :report_type, :subject, :description, :priority)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':user_id' => $userId,
                ':report_type' => $report_type,
                ':subject' => $subject,
                ':description' => $description,
                ':priority' => $priority
            ]);

            $success = 'Your report has been submitted successfully.';

            /* Clear submitted values */
            $report_type = '';
            $subject = '';
            $description = '';
            $priority = 'normal';

            /* Generate a fresh token */
            $_SESSION['report_csrf'] = bin2hex(random_bytes(32));

        } catch (PDOException $ex) {

            error_log('Report submission error: ' . $ex->getMessage());

            $error = 'Unable to submit your report. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report an Issue | AITAM Slack</title>

    <link rel="stylesheet" href="./assets/css/root.css">

    <style>

/* =================================
   GLOBAL STYLES
================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-family-base, Arial, sans-serif);
    background: var(--bg-app, var(--color-white, #f4f7fc));
    color: var(--text-primary, #172b4d);
    transition: background 0.3s ease, color 0.3s ease;
}

/* =================================
   REPORT CONTAINER
================================= */

.report-container {

    --report-bg: var(--bg-app, #f4f7fc);
    --report-card: var(--bg-surface, #ffffff);
    --report-text: var(--text-primary, #172b4d);
    --report-muted: var(--text-secondary, #77849a);
    --report-border: var(--border-color, #dce3ee);
    --report-soft: var(--surface-secondary, #edf3fc);
    --report-heading: var(--color-navy-950, #0F2854);

    max-width: 1150px;
    width: 100%;

    margin: 40px auto;
    padding: 20px;

    color: var(--report-text);
}

/* =================================
   DARK THEME
================================= */

html[data-theme="dark"] .report-container,
body[data-theme="dark"] .report-container {

    --report-bg: #0b1730;
    --report-card: #112544;
    --report-text: #f5f7ff;
    --report-muted: #c1cee0;
    --report-border: #29476b;
    --report-soft: #192f4e;
    --report-heading: #ffffff;
}

/* =================================
   PAGE HEADER
================================= */

.page-header {
    margin-bottom: 28px;
}

.page-header h1 {
    font-size: 30px;
    margin-bottom: 10px;
    color: var(--report-heading);
    font-weight: 800;
}

.page-header p {
    color: var(--report-muted);
    font-size: 15px;
    line-height: 1.7;
}

/* =================================
   REPORT LAYOUT
================================= */

.report-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 25px;
    align-items: start;
}

/* =================================
   CARDS
================================= */

.report-card,
.info-card {

    background: var(--report-card);

    border: 1px solid var(--report-border);
    border-radius: 14px;

    padding: 28px;

    box-shadow: 0 5px 22px rgba(0, 0, 0, 0.05);

    transition: background 0.3s ease, border 0.3s ease;
}

html[data-theme="dark"] .report-card,
html[data-theme="dark"] .info-card,
body[data-theme="dark"] .report-card,
body[data-theme="dark"] .info-card {
    box-shadow: 0 5px 22px rgba(0, 0, 0, 0.18);
}

.report-card h2 {
    color: var(--report-heading);
    font-size: 22px;
    margin-bottom: 9px;
}

.card-description {
    font-size: 14px;
    color: var(--report-muted);
    margin-bottom: 26px;
    line-height: 1.6;
}

/* =================================
   FORM
================================= */

.form-group {
    margin-bottom: 21px;
}

.form-group label {
    display: block;
    color: var(--report-text);
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 9px;
}

.form-group input,
.form-group select,
.form-group textarea {

    width: 100%;

    border: 1px solid var(--report-border);
    border-radius: 8px;

    padding: 13px 14px;

    font-size: 14px;
    font-family: inherit;

    color: var(--report-text);
    background: var(--report-card);

    outline: none;

    transition: border 0.2s ease, box-shadow 0.2s ease;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: var(--report-muted);
    opacity: 0.8;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--color-blue-500, #315f9e);
    box-shadow: 0 0 0 3px rgba(49, 95, 158, 0.15);
}

.form-group select option {
    background: var(--report-card);
    color: var(--report-text);
}

.form-group textarea {
    min-height: 145px;
    resize: vertical;
    line-height: 1.7;
}

.form-group small {
    display: block;
    color: var(--report-muted);
    font-size: 12px;
    margin-top: 7px;
}

/* =================================
   SUBMIT BUTTON
================================= */

.submit-btn {

    width: 100%;
    padding: 15px;

    background: var(--gradient-brand);
    color: var(--text-on-brand, #ffffff);

    border: none;
    border-radius: 9px;

    font-weight: 700;
    font-size: 15px;

    cursor: pointer;

    transition: var(--transition-fast, 0.2s ease);
}

.submit-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.submit-btn:focus-visible {
    outline: 2px solid var(--color-blue-500);
    outline-offset: 3px;
}

/* =================================
   INFORMATION CARDS
================================= */

.info-card {
    margin-bottom: 20px;
}

.info-card h3 {
    color: var(--report-heading);
    font-size: 18px;
    margin-bottom: 20px;
}

/* =================================
   INFORMATION ITEMS
================================= */

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    margin-bottom: 22px;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-icon {

    width: 40px;
    height: 40px;
    min-width: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: var(--report-soft);
    color: var(--color-blue-500, #315f9e);

    flex-shrink: 0;
}

.info-icon svg {
    width: 21px;
    height: 21px;

    stroke: currentColor;
    fill: none;

    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.info-item h4 {
    color: var(--report-text);
    font-size: 14px;
    margin-bottom: 6px;
}

.info-item p {
    color: var(--report-muted);
    font-size: 13px;
    line-height: 1.6;
}

/* =================================
   NOTICE
================================= */

.notice {

    background: var(--report-soft);
    border: 1px solid var(--report-border);

    padding: 17px;
    border-radius: 9px;

    font-size: 13px;
    line-height: 1.8;

    color: var(--report-text);
}

/* =================================
   ALERT MESSAGES
================================= */

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.6;
}

.alert.success {
    background: #e9f8ef;
    color: #187442;
    border: 1px solid #b7e4c7;
}

.alert.error {
    background: #fff0f0;
    color: #b42318;
    border: 1px solid #f3c1c1;
}

/* =================================
   RESPONSIVE
================================= */

@media (max-width: 850px) {

    .report-layout {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 600px) {

    .report-container {
        margin: 22px auto;
        padding: 15px;
    }

    .page-header h1 {
        font-size: 26px;
    }

    .report-card,
    .info-card {
        padding: 21px;
    }

}

    </style>

</head>

<body>

<?php include 'nav.php'; ?>

<main class="report-container">

    <header class="page-header">

        <h1>Report an Issue</h1>

        <p>
            Help us maintain a safe and respectful college community.
            Submit a report and our moderation team will review it.
        </p>

    </header>


    <div class="report-layout">

        <!-- REPORT FORM -->
        <section class="report-card">

            <h2>Submit a Report</h2>

            <p class="card-description">
                Provide the details of the issue you want to report.
            </p>


            <!-- SUCCESS MESSAGE -->
            <?php if ($success !== ''): ?>

                <div class="alert success" role="status">
                    <?= e($success) ?>
                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->
            <?php if ($error !== ''): ?>

                <div class="alert error" role="alert">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="report.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['report_csrf']) ?>"
                >


                <!-- REPORT CATEGORY -->
                <div class="form-group">

                    <label for="report_type">
                        Report Category *
                    </label>

                    <select name="report_type" id="report_type" required>

                        <option value="">Select a category</option>

                        <option value="inappropriate_content"
                            <?= $report_type === 'inappropriate_content' ? 'selected' : '' ?>>
                            Inappropriate Content
                        </option>

                        <option value="harassment"
                            <?= $report_type === 'harassment' ? 'selected' : '' ?>>
                            Harassment or Bullying
                        </option>

                        <option value="spam"
                            <?= $report_type === 'spam' ? 'selected' : '' ?>>
                            Spam or Promotional Content
                        </option>

                        <option value="fake_information"
                            <?= $report_type === 'fake_information' ? 'selected' : '' ?>>
                            Fake or Misleading Information
                        </option>

                        <option value="technical_issue"
                            <?= $report_type === 'technical_issue' ? 'selected' : '' ?>>
                            Technical Issue
                        </option>

                        <option value="other"
                            <?= $report_type === 'other' ? 'selected' : '' ?>>
                            Other
                        </option>

                    </select>

                </div>


                <!-- SUBJECT -->
                <div class="form-group">

                    <label for="subject">
                        Report Subject *
                    </label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        placeholder="Enter a short subject"
                        maxlength="150"
                        value="<?= e($subject) ?>"
                        required
                    >

                </div>


                <!-- PRIORITY -->
                <div class="form-group">

                    <label for="priority">
                        Priority
                    </label>

                    <select name="priority" id="priority">

                        <option value="normal"
                            <?= $priority === 'normal' ? 'selected' : '' ?>>
                            Normal
                        </option>

                        <option value="high"
                            <?= $priority === 'high' ? 'selected' : '' ?>>
                            High
                        </option>

                        <option value="urgent"
                            <?= $priority === 'urgent' ? 'selected' : '' ?>>
                            Urgent
                        </option>

                    </select>

                </div>


                <!-- DESCRIPTION -->
                <div class="form-group">

                    <label for="description">
                        Describe the Issue *
                    </label>

                    <textarea
                        name="description"
                        id="description"
                        placeholder="Explain what happened and provide relevant details..."
                        maxlength="3000"
                        required
                    ><?= e($description) ?></textarea>

                    <small>Maximum 3000 characters.</small>

                </div>


                <!-- SUBMIT -->
                <button type="submit" class="submit-btn">
                    Submit Report →
                </button>

            </form>

        </section>


        <!-- INFORMATION SECTION -->
        <aside>

            <!-- WHAT HAPPENS NEXT -->
            <div class="info-card">

                <h3>What happens next?</h3>


                <!-- STEP 1 -->
                <div class="info-item">

                    <div class="info-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="5" y="4" width="14" height="17" rx="2"></rect>
                            <path d="M9 4V2h6v2"></path>
                            <path d="M9 11h6"></path>
                            <path d="M9 15h6"></path>
                        </svg>

                    </div>

                    <div>
                        <h4>1. Report Submitted</h4>
                        <p>Your report is recorded for review.</p>
                    </div>

                </div>


                <!-- STEP 2 -->
                <div class="info-item">

                    <div class="info-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="6.5"></circle>
                            <path d="m16 16 5 5"></path>
                        </svg>

                    </div>

                    <div>
                        <h4>2. Admin Review</h4>
                        <p>Our moderation team examines the issue.</p>
                    </div>

                </div>


                <!-- STEP 3 -->
                <div class="info-item">

                    <div class="info-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="m8 12 2.5 2.5L16 9"></path>
                        </svg>

                    </div>

                    <div>
                        <h4>3. Action Taken</h4>
                        <p>
                            Appropriate action is taken according to
                            college policies.
                        </p>
                    </div>

                </div>

            </div>


            <!-- REPORTING GUIDELINES -->
            <div class="info-card">

                <h3>Reporting Guidelines</h3>

                <div class="notice">

                    Please provide accurate information.

                    <br><br>

                    Do not submit false reports or use this feature
                    to target other members.

                    <br><br>

                    Reports should be used only for genuine safety,
                    content, or technical concerns.

                </div>

            </div>

        </aside>

    </div>

</main>

<?php include 'footer.php'; ?>

</body>
</html>
