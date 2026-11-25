
<?php

require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/ai_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================================
   HELPER FUNCTION
========================================= */

function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =========================================
   HANDLE ADMIN ACTIONS
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die('Invalid request.');
    }

    $questionId = filter_input(
        INPUT_POST,
        'question_id',
        FILTER_VALIDATE_INT
    );

    $action = $_POST['action'] ?? '';

    $adminActions = [
        'approve' => 'APPROVED',
        'reject'  => 'REJECTED'
    ];

    if (
        $questionId &&
        isset($adminActions[$action])
    ) {

        $adminDecision = $adminActions[$action];

        try {

            /*
            |--------------------------------------------------------------------------
            | Verify that the question is still awaiting AI moderation review
            |--------------------------------------------------------------------------
            */

            $checkSql = "
                SELECT me.id

                FROM moderation_events me

                INNER JOIN (
                    SELECT
                        content_type,
                        content_id,
                        MAX(id) AS latest_id

                    FROM moderation_events

                    WHERE content_type = 'question'

                    GROUP BY content_type, content_id

                ) latest
                    ON latest.latest_id = me.id

                INNER JOIN questions q
                    ON q.id = me.content_id

                WHERE me.content_type = 'question'
                  AND me.event_type = 'AI'
                  AND me.publication_status = 'PENDING'
                  AND me.ai_decision IN ('REVIEW', 'REJECTED', 'FAILED')
                  AND me.content_id = ?

                LIMIT 1
            ";

            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$questionId]);

            $eligibleQuestion = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($eligibleQuestion) {

                /*
                |--------------------------------------------------------------------------
                | Record ADMIN moderation event
                |--------------------------------------------------------------------------
                */

                $adminId = (int)(
                    $_SESSION['admin_id']
                    ?? $_SESSION['user_id']
                    ?? 0
                );

                recordModerationEvent(
                    $pdo,
                    'question',
                    (int)$questionId,
                    'ADMIN',
                    $adminDecision,
                    [],
                    $adminId > 0 ? $adminId : null,
                    $adminDecision
                );

                header(
                    'Location: reported-questions.php?updated=' .
                    strtolower($adminDecision)
                );

                exit;

            } else {

                header('Location: reported-questions.php?updated=unavailable');
                exit;

            }

        } catch (Throwable $exception) {

            error_log(
                'Reported question admin action failed: ' .
                $exception->getMessage()
            );

            header('Location: reported-questions.php?updated=error');
            exit;
        }
    }

    header('Location: reported-questions.php');
    exit;
}

/* =========================================
   SEARCH AND FILTERS
========================================= */

$search = trim($_GET['search'] ?? '');
$decision = strtoupper(trim($_GET['decision'] ?? ''));

$allowedDecisions = [
    'REVIEW',
    'REJECTED',
    'FAILED'
];

/* =========================================
   LATEST AI-FLAGGED QUESTIONS
========================================= */

$sql = "
    SELECT

        me.id AS moderation_id,
        me.content_id AS question_id,

        me.event_type,
        me.ai_decision,
        me.publication_status,

        me.ai_reason,
        me.ai_confidence,
        me.category,
        me.detected_violations,

        me.model,
        me.api_status,
        me.created_at AS submitted_at,

        q.question_title,
        q.question_description,
        q.status AS question_status,

        u.full_name AS author_name,
        u.email AS author_email,
        u.department AS author_department

    FROM moderation_events me

    INNER JOIN (

        SELECT
            content_type,
            content_id,
            MAX(id) AS latest_id

        FROM moderation_events

        WHERE content_type = 'question'

        GROUP BY content_type, content_id

    ) latest

        ON latest.latest_id = me.id

    INNER JOIN questions q
        ON q.id = me.content_id

    LEFT JOIN users u
        ON q.user_id = u.user_id

    WHERE me.content_type = 'question'

      AND me.event_type = 'AI'

      AND me.publication_status = 'PENDING'

      AND me.ai_decision IN ('REVIEW', 'REJECTED', 'FAILED')
";

$params = [];

/* SEARCH */

if ($search !== '') {

    $sql .= "
        AND (
            q.question_title LIKE ?
            OR q.question_description LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
            OR me.ai_reason LIKE ?
            OR me.category LIKE ?
        )
    ";

    $keyword = '%' . $search . '%';

    $params = array_fill(0, 6, $keyword);
}

/* DECISION FILTER */

if (in_array($decision, $allowedDecisions, true)) {

    $sql .= " AND me.ai_decision = ?";

    $params[] = $decision;
}

$sql .= "
    ORDER BY me.created_at DESC, me.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   SUMMARY STATISTICS
========================================= */

$statsSql = "

    SELECT

        COUNT(*) AS total_flagged,

        COALESCE(
            SUM(me.ai_decision = 'REVIEW'),
            0
        ) AS review_count,

        COALESCE(
            SUM(me.ai_decision = 'REJECTED'),
            0
        ) AS rejected_count,

        COALESCE(
            SUM(me.ai_decision = 'FAILED'),
            0
        ) AS failed_count

    FROM moderation_events me

    INNER JOIN (

        SELECT
            content_type,
            content_id,
            MAX(id) AS latest_id

        FROM moderation_events

        WHERE content_type = 'question'

        GROUP BY content_type, content_id

    ) latest

        ON latest.latest_id = me.id

    INNER JOIN questions q
        ON q.id = me.content_id

    WHERE me.content_type = 'question'

      AND me.event_type = 'AI'

      AND me.publication_status = 'PENDING'

      AND me.ai_decision IN ('REVIEW', 'REJECTED', 'FAILED')
";

$stats = $pdo->query($statsSql)->fetch(PDO::FETCH_ASSOC);

$totalFlagged = (int)($stats['total_flagged'] ?? 0);
$reviewCount = (int)($stats['review_count'] ?? 0);
$rejectedCount = (int)($stats['rejected_count'] ?? 0);
$failedCount = (int)($stats['failed_count'] ?? 0);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reported Questions | AITAM Slack</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/reported-questions.css">

</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <?php include 'includes/topbar.php'; ?>

    <main class="page-content reported-page">

        <!-- PAGE HEADING -->

        <div class="page-heading reported-heading">

            <div>

                <h2>Reported Questions</h2>

                <p>
                    Review questions flagged by AI moderation
                    and decide whether they should be published.
                </p>

            </div>

        </div>


        <!-- ACTION MESSAGES -->

        <?php if (isset($_GET['updated'])): ?>

            <?php

            $messages = [
                'approved' => [
                    'type' => 'success',
                    'text' => 'Question approved successfully.'
                ],
                'rejected' => [
                    'type' => 'success',
                    'text' => 'Question rejected successfully.'
                ],
                'unavailable' => [
                    'type' => 'warning',
                    'text' => 'This question has already been reviewed or is no longer available.'
                ],
                'error' => [
                    'type' => 'danger',
                    'text' => 'Unable to process the action. Please try again.'
                ]
            ];

            $message = $messages[$_GET['updated']] ?? null;

            ?>

            <?php if ($message): ?>

                <div class="alert alert-<?= e($message['type']) ?> alert-dismissible fade show">

                    <?= e($message['text']) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>

        <?php endif; ?>


        <!-- SUMMARY CARDS -->

        <div class="report-stats">

            <div class="report-stat-card">

                <div class="report-stat-icon total-icon">
                    <i class="bi bi-shield-exclamation"></i>
                </div>

                <div>

                    <p>Total Flagged</p>

                    <h3><?= e($totalFlagged) ?></h3>

                </div>

            </div>


            <div class="report-stat-card">

                <div class="report-stat-icon pending-icon">
                    <i class="bi bi-eye"></i>
                </div>

                <div>

                    <p>Needs Review</p>

                    <h3><?= e($reviewCount) ?></h3>

                </div>

            </div>


            <div class="report-stat-card">

                <div class="report-stat-icon dismissed-icon">
                    <i class="bi bi-x-circle"></i>
                </div>

                <div>

                    <p>AI Rejected</p>

                    <h3><?= e($rejectedCount) ?></h3>

                </div>

            </div>


            <div class="report-stat-card">

                <div class="report-stat-icon resolved-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>

                <div>

                    <p>Moderation Failed</p>

                    <h3><?= e($failedCount) ?></h3>

                </div>

            </div>

        </div>


        <!-- QUESTIONS PANEL -->

        <section class="reports-panel">

            <div class="reports-panel-header">

                <div>

                    <h4>AI-Flagged Questions</h4>

                    <p>
                        Review AI moderation results and manage question publication.
                    </p>

                </div>

            </div>


            <!-- SEARCH AND FILTER -->

            <form method="GET" class="report-filter-bar">

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="search"
                        name="search"
                        placeholder="Search questions or authors..."
                        value="<?= e($search) ?>"
                    >

                </div>


                <select name="decision">

                    <option value="">All AI Decisions</option>

                    <?php foreach ($allowedDecisions as $item): ?>

                        <option
                            value="<?= e($item) ?>"
                            <?= $decision === $item ? 'selected' : '' ?>
                        >

                            <?= e(ucfirst(strtolower($item))) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <button type="submit" class="filter-btn">

                    <i class="bi bi-funnel"></i>

                    Filter

                </button>


                <a href="reported-questions.php" class="reset-btn">
                    Reset
                </a>

            </form>


            <!-- QUESTIONS TABLE -->

            <div class="table-responsive">

                <table class="table reports-table">

                    <thead>

                        <tr>

                            <th>Question ID</th>
                            <th>Question</th>
                            <th>Author</th>
                            <th>AI Decision</th>
                            <th>Confidence</th>
                            <th>Submitted Date</th>
                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!empty($questions)): ?>

                        <?php foreach ($questions as $question): ?>

                            <?php

                            $violations = json_decode(
                                $question['detected_violations'] ?? '[]',
                                true
                            );

                            if (!is_array($violations)) {
                                $violations = [];
                            }

                            $decisionLabel = ucfirst(
                                strtolower($question['ai_decision'] ?? 'Unknown')
                            );

                            ?>

                            <tr>

                                <!-- QUESTION ID -->

                                <td>
                                    #<?= e($question['question_id']) ?>
                                </td>


                                <!-- QUESTION -->

                                <td>

                                    <div class="question-cell">

                                        <strong>
                                            <?= e($question['question_title']) ?>
                                        </strong>

                                        <small>
                                            Category:
                                            <?= e($question['category'] ?: 'Not available') ?>
                                        </small>

                                    </div>

                                </td>


                                <!-- AUTHOR -->

                                <td>

                                    <div class="reporter-cell">

                                        <strong>
                                            <?= e($question['author_name'] ?: 'Unknown User') ?>
                                        </strong>

                                        <small>
                                            <?= e($question['author_email'] ?: 'Email unavailable') ?>
                                        </small>

                                    </div>

                                </td>


                                <!-- AI DECISION -->

                                <td>

                                    <span class="report-status <?= e(strtolower($question['ai_decision'])) ?>">

                                        <?= e($decisionLabel) ?>

                                    </span>

                                </td>


                                <!-- CONFIDENCE -->

                                <td>

                                    <?php if ($question['ai_confidence'] !== null): ?>

                                        <?= e(number_format(
                                            (float)$question['ai_confidence'] * 100,
                                            1
                                        )) ?>%

                                    <?php else: ?>

                                        <span class="text-muted">
                                            N/A
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- SUBMITTED DATE -->

                                <td>

                                    <?= e(
                                        date(
                                            'd M Y, h:i A',
                                            strtotime($question['submitted_at'])
                                        )
                                    ) ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="report-actions">

                                        <!-- VIEW BUTTON -->

                                        <button
                                            type="button"
                                            class="action-btn view-btn"
                                            title="View Question"
                                            data-bs-toggle="modal"
                                            data-bs-target="#questionModal"

                                            data-question-id="<?= e($question['question_id']) ?>"
                                            data-title="<?= e($question['question_title']) ?>"
                                            data-description="<?= e($question['question_description']) ?>"
                                            data-category="<?= e($question['category']) ?>"
                                            data-author="<?= e($question['author_name']) ?>"
                                            data-email="<?= e($question['author_email']) ?>"
                                            data-department="<?= e($question['author_department']) ?>"
                                            data-decision="<?= e($question['ai_decision']) ?>"
                                            data-confidence="<?= e($question['ai_confidence']) ?>"
                                            data-reason="<?= e($question['ai_reason']) ?>"
                                            data-violations="<?= e(implode(', ', $violations)) ?>"
                                            data-model="<?= e($question['model']) ?>"
                                            data-api-status="<?= e($question['api_status']) ?>"
                                            data-date="<?= e($question['submitted_at']) ?>"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </button>


                                        <!-- APPROVE BUTTON -->

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Approve this question and make it visible to users?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="question_id"
                                                value="<?= e($question['question_id']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="approve"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn resolve-btn"
                                                title="Approve Question"
                                            >

                                                <i class="bi bi-check-lg"></i>

                                            </button>

                                        </form>


                                        <!-- REJECT BUTTON -->

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Reject this question? It will remain hidden from users.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="question_id"
                                                value="<?= e($question['question_id']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reject"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn dismiss-btn"
                                                title="Reject Question"
                                            >

                                                <i class="bi bi-x-lg"></i>

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="empty-state">

                                <i class="bi bi-inbox"></i>

                                <p>No AI-flagged questions found.</p>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>


<!-- QUESTION DETAILS MODAL -->

<div class="modal fade" id="questionModal" tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Question Moderation Details
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                <!-- QUESTION DETAILS -->

                <div class="detail-section">

                    <h6>Question Details</h6>

                    <p>
                        <strong>Question ID:</strong>
                        <span id="modalQuestionId"></span>
                    </p>

                    <p>
                        <strong>Title:</strong>
                        <span id="modalTitle"></span>
                    </p>

                    <p>
                        <strong>Description:</strong>
                    </p>

                    <p
                        class="detail-description"
                        id="modalDescription"
                    ></p>

                    <p>
                        <strong>Category:</strong>
                        <span id="modalCategory"></span>
                    </p>

                </div>


                <hr>


                <!-- AUTHOR DETAILS -->

                <div class="detail-section">

                    <h6>Author Details</h6>

                    <p>
                        <strong>Author:</strong>
                        <span id="modalAuthor"></span>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <span id="modalEmail"></span>
                    </p>

                    <p>
                        <strong>Department:</strong>
                        <span id="modalDepartment"></span>
                    </p>

                </div>


                <hr>


                <!-- AI MODERATION DETAILS -->

                <div class="detail-section">

                    <h6>AI Moderation Details</h6>

                    <p>
                        <strong>AI Decision:</strong>
                        <span id="modalDecision"></span>
                    </p>

                    <p>
                        <strong>Confidence:</strong>
                        <span id="modalConfidence"></span>
                    </p>

                    <p>
                        <strong>AI Reason:</strong>
                    </p>

                    <p
                        class="detail-description"
                        id="modalReason"
                    ></p>

                    <p>
                        <strong>Detected Violations:</strong>
                    </p>

                    <p
                        class="detail-description"
                        id="modalViolations"
                    ></p>

                    <p>
                        <strong>AI Model:</strong>
                        <span id="modalModel"></span>
                    </p>

                    <p>
                        <strong>API Status:</strong>
                        <span id="modalApiStatus"></span>
                    </p>

                    <p>
                        <strong>Moderation Date:</strong>
                        <span id="modalDate"></span>
                    </p>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>

            </div>

        </div>

    </div>

</div>


<!-- JAVASCRIPT -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/admin.js"></script>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const questionModal = document.getElementById('questionModal');

    if (!questionModal) {
        return;
    }

    questionModal.addEventListener('show.bs.modal', function (event) {

        const button = event.relatedTarget;

        if (!button) {
            return;
        }

        function setText(id, value) {

            const element = document.getElementById(id);

            if (element) {
                element.textContent = value || 'Not available';
            }

        }

        setText('modalQuestionId', button.dataset.questionId);
        setText('modalTitle', button.dataset.title);
        setText('modalDescription', button.dataset.description);
        setText('modalCategory', button.dataset.category);

        setText('modalAuthor', button.dataset.author);
        setText('modalEmail', button.dataset.email);
        setText('modalDepartment', button.dataset.department);

        setText('modalDecision', button.dataset.decision);

        const confidence = button.dataset.confidence;

        setText(
            'modalConfidence',
            confidence !== '' && confidence !== undefined
                ? (Number(confidence) * 100).toFixed(1) + '%'
                : 'Not available'
        );

        setText('modalReason', button.dataset.reason);
        setText('modalViolations', button.dataset.violations);
        setText('modalModel', button.dataset.model);
        setText('modalApiStatus', button.dataset.apiStatus);
        setText('modalDate', button.dataset.date);

    });

});

</script>

</body>
</html>
