
<?php
session_start();

// Add your authentication check here.
// Example: require_once 'includes/auth.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $report_type = trim($_POST['report_type'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'normal');

    $allowed_types = [
        'inappropriate_content',
        'harassment',
        'spam',
        'fake_information',
        'technical_issue',
        'other'
    ];

    if (
        !in_array($report_type, $allowed_types, true) ||
        empty($subject) ||
        empty($description)
    ) {
        $error = "Please complete all required fields correctly.";
    } else {
        /*
         * Database insertion should be implemented here.
         * Store the report using PDO prepared statements.
         * Obtain the reporter ID from the authenticated session.
         */

        $error = "Report submission is not connected to the database yet.";
    }
}
?>
<?php include 'nav.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Report an Issue | College Connect</title>

<link rel="stylesheet" href="assets/css/root.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f4f7fc;
    font-family: Arial, sans-serif;
    color: #172b4d;
}

.report-container {
    max-width: 1050px;
    margin: 45px auto;
    padding: 0 20px;
}

.page-header {
    margin-bottom: 28px;
}

.page-header h1 {
    font-size: 30px;
    margin-bottom: 10px;
    color: #0F2854;
}

.page-header p {
    color: #77849a;
    font-size: 14px;
    line-height: 1.6;
}

.report-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 25px;
    align-items: start;
}

.report-card,
.info-card {
    background: #fff;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 5px 25px rgba(15,40,84,.06);
}

.report-card h2 {
    font-size: 21px;
    margin-bottom: 8px;
}

.card-description {
    font-size: 13px;
    color: #8591a4;
    margin-bottom: 27px;
}

.form-group {
    margin-bottom: 21px;
}

.form-group label {
    display: block;
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 9px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    border: 1px solid #dce3ee;
    border-radius: 8px;
    padding: 13px 14px;
    font-size: 14px;
    outline: none;
    font-family: inherit;
    color: #243b65;
    background: #fff;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #315f9e;
    box-shadow: 0 0 0 3px rgba(49,95,158,.09);
}

.form-group textarea {
    min-height: 145px;
    resize: vertical;
    line-height: 1.6;
}

.form-group small {
    display: block;
    color: #929daf;
    font-size: 11px;
    margin-top: 7px;
}

.submit-btn {
    width: 100%;
    padding: 15px;
    background: #0F2854;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: .2s;
}

.submit-btn:hover {
    background: #243b65;
}

.info-card {
    margin-bottom: 18px;
}

.info-card h3 {
    font-size: 17px;
    margin-bottom: 17px;
}

.info-item {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.info-icon {
    width: 37px;
    height: 37px;
    border-radius: 9px;
    background: #edf3fc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

.info-item h4 {
    font-size: 13px;
    margin-bottom: 5px;
}

.info-item p {
    font-size: 12px;
    color: #8490a3;
    line-height: 1.6;
}

.notice {
    background: #edf4ff;
    border: 1px solid #d6e5fa;
    padding: 16px;
    border-radius: 9px;
    font-size: 12px;
    line-height: 1.7;
    color: #34527b;
}

.alert {
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
}

.alert.success {
    background: #e9f8ef;
    color: #187442;
}

.alert.error {
    background: #fff0f0;
    color: #b42318;
}

@media(max-width: 800px) {
    .report-layout {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 480px) {
    .report-container {
        margin: 25px auto;
    }

    .report-card,
    .info-card {
        padding: 22px;
    }

    .page-header h1 {
        font-size: 25px;
    }
}
</style>
</head>

<body>

<main class="report-container">

    <header class="page-header">
        <h1>Report an Issue</h1>
        <p>
            Help us maintain a safe and respectful college community.
            Submit a report and our moderation team will review it.
        </p>
    </header>

    <div class="report-layout">

        <section class="report-card">

            <h2>Submit a Report</h2>

            <p class="card-description">
                Provide the details of the issue you want to report.
            </p>

            <?php if ($success): ?>
                <div class="alert success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="report_type">Report Category *</label>

                    <select name="report_type" id="report_type" required>
                        <option value="">Select a category</option>
                        <option value="inappropriate_content">
                            Inappropriate Content
                        </option>
                        <option value="harassment">
                            Harassment or Bullying
                        </option>
                        <option value="spam">
                            Spam or Promotional Content
                        </option>
                        <option value="fake_information">
                            Fake or Misleading Information
                        </option>
                        <option value="technical_issue">
                            Technical Issue
                        </option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Report Subject *</label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        placeholder="Enter a short subject"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>

                    <select name="priority" id="priority">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

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
                    ></textarea>

                    <small>Maximum 3000 characters.</small>
                </div>

                <button type="submit" class="submit-btn">
                    Submit Report →
                </button>

            </form>

        </section>

        <aside>

            <div class="info-card">

                <h3>What happens next?</h3>

                <div class="info-item">
                    <div class="info-icon">📝</div>
                    <div>
                        <h4>1. Report Submitted</h4>
                        <p>Your report is recorded for review.</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">🔍</div>
                    <div>
                        <h4>2. Admin Review</h4>
                        <p>Our moderation team examines the issue.</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">✓</div>
                    <div>
                        <h4>3. Action Taken</h4>
                        <p>Appropriate action is taken according to college policies.</p>
                    </div>
                </div>

            </div>

            <div class="info-card">

                <h3>Reporting Guidelines</h3>

                <div class="notice">
                    Please provide accurate information.

                    <br><br>

                    Do not submit false reports or use this
                    feature to target other members.

                    <br><br>

                    Reports should be used only for genuine
                    safety, content, or technical concerns.
                </div>

            </div>

        </aside>

    </div>

</main>
<?php include 'footer.php'; ?>
</body>
</html>
