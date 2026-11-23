
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once 'includes/db.php';

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Escape output
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$answersUnavailable = true;
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$allowedStatuses = [];
$totalAnswers = null;
$publishedAnswers = null;
$pendingAnswers = null;
$hiddenAnswers = null;
$answers = [];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>AITAM Slack | Answer Management</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Project CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/answers.css">

</head>

<body>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <!-- Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <main class="page-content answers-page">

            <!-- PAGE HEADING -->

            <div class="page-heading">

                <h2>Answer Management</h2>

                <p>
                    Answer records are unavailable because the existing database does not contain an answers table.
                </p>

            </div>

            <div class="alert alert-warning" role="alert">
                <strong>Feature unavailable:</strong>
                No SQL queries were executed against an answers table.
                <a href="questions.php" class="alert-link">View Questions</a>
            </div>

            <!-- STATISTICS CARDS -->

            <div class="answer-stats">

                <div class="answer-card">

                    <div class="answer-icon total-icon">
                        <i class="bi bi-chat-left-text"></i>
                    </div>

                    <div class="answer-details">

                        <p>Total Answers</p>

                        <h3>Unavailable</h3>

                    </div>

                </div>


                <div class="answer-card">

                    <div class="answer-icon published-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <div class="answer-details">

                        <p>Published Answers</p>

                        <h3>Unavailable</h3>

                    </div>

                </div>


                <div class="answer-card">

                    <div class="answer-icon pending-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>

                    <div class="answer-details">

                        <p>Pending Answers</p>

                        <h3>Unavailable</h3>

                    </div>

                </div>


                <div class="answer-card">

                    <div class="answer-icon hidden-icon">
                        <i class="bi bi-eye-slash"></i>
                    </div>

                    <div class="answer-details">

                        <p>Hidden Answers</p>

                        <h3>Unavailable</h3>

                    </div>

                </div>

            </div>


            <!-- ANSWERS TABLE PANEL -->

            <div class="answers-panel">

                <div class="panel-heading">

                    <div>

                        <h4>All Answers</h4>

                        <p>
                            Monitor and manage user-submitted answers.
                        </p>

                    </div>

                </div>


                <!-- SEARCH AND FILTER -->

                <form method="GET" class="answer-filters">

                    <div class="search-box">

                        <i class="bi bi-search"></i>

                        <input
                            type="text"
                            name="search"
                            placeholder="Search answers or questions..."
                            value="<?= e($search) ?>"
                        >

                    </div>


                    <select name="status">

                        <option value="">
                            All Statuses
                        </option>

                        <?php foreach ($allowedStatuses as $item): ?>

                            <option
                                value="<?= e($item) ?>"
                                <?= $status === $item ? 'selected' : '' ?>
                            >

                                <?= e($item) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <button type="submit" class="filter-btn">

                        <i class="bi bi-funnel"></i>

                        Filter

                    </button>


                    <a href="answers.php" class="reset-btn">
                        Reset
                    </a>

                </form>


                <!-- ANSWERS TABLE -->

                <div class="table-responsive">

                    <table class="table answers-table">

                        <thead>

                            <tr>

                                <th>Answer ID</th>

                                <th>Answer Content</th>

                                <th>Question</th>

                                <th>User ID</th>

                                <th>Status</th>

                                <th>Submitted Date</th>

                                <th>Manage</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (!empty($answers)): ?>

                            <?php foreach ($answers as $answer): ?>

                                <tr>

                                    <td>
                                        #<?= e($answer['id']) ?>
                                    </td>


                                    <td class="answer-content">

                                        <?= e($answer['answer_text']) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $answer['question_title']
                                            ?? 'Question unavailable'
                                        ) ?>

                                    </td>


                                    <td>
                                        <?= e($answer['user_id']) ?>
                                    </td>


                                    <td>

                                        <span class="status-badge <?= strtolower(e($answer['status'])) ?>">

                                            <?= e($answer['status']) ?>

                                        </span>

                                    </td>


                                    <td>
                                        <?= e($answer['created_at']) ?>
                                    </td>


                                    <td>

                                        <form method="POST"
                                              class="status-form">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="answer_id"
                                                value="<?= e($answer['id']) ?>"
                                            >


                                            <select
                                                name="status"
                                                class="form-select form-select-sm"
                                            >

                                                <?php foreach ($allowedStatuses as $item): ?>

                                                    <option
                                                        value="<?= e($item) ?>"
                                                        <?= $answer['status'] === $item ? 'selected' : '' ?>
                                                    >

                                                        <?= e($item) ?>

                                                    </option>

                                                <?php endforeach; ?>

                                            </select>


                                            <button
                                                type="submit"
                                                name="update_status"
                                                class="update-btn"
                                            >

                                                Update

                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7" class="no-data">

                                    <i class="bi bi-inbox"></i>

                                    <p>No answers found.</p>

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </main>

    </div>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Project JS -->
    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/answers.js"></script>

</body>

</html>
