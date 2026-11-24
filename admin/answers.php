
<?php

require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once dirname(__DIR__) . '/includes/ai_helpers.php';
require_once __DIR__ . '/includes/db.php';

/* -------------------------------------------------
   HELPER FUNCTION
------------------------------------------------- */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string)($value ?? ''),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}

/* -------------------------------------------------
   SEARCH, CATEGORY AND STATUS FILTERS
------------------------------------------------- */

$search = is_string($_GET['search'] ?? null)
    ? trim($_GET['search'])
    : '';

$category = is_string($_GET['category'] ?? null)
    ? trim($_GET['category'])
    : '';

$allowedStatuses = ['Pending', 'Published', 'Hidden'];

$status = is_string($_GET['status'] ?? null)
    ? trim($_GET['status'])
    : 'Pending';

$status = in_array($status, $allowedStatuses, true)
    ? $status
    : 'Pending';

/* -------------------------------------------------
   FLASH MESSAGE AND CSRF
------------------------------------------------- */

$csrfToken = admin_csrf_token();

$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

/* -------------------------------------------------
   HANDLE APPROVE / REJECT
------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $answerId = filter_var(
        $_POST['answer_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $action = $_POST['action'] ?? '';

    if (!admin_csrf_verify(
        is_string($_POST['csrf_token'] ?? null)
            ? $_POST['csrf_token']
            : null
    )) {

        $_SESSION['flash_message'] =
            'Your session expired. Refresh and try again.';

    } elseif (
        $answerId === false ||
        !in_array($action, ['approve', 'reject'], true)
    ) {

        $_SESSION['flash_message'] =
            'Invalid answer review details.';

    } else {

        $answerStatus = $action === 'approve'
            ? 'Published'
            : 'Hidden';

        try {

            $pdo->beginTransaction();

            /* FETCH ANSWER */

            $lookup = $pdo->prepare(
                'SELECT question_id
                 FROM answers
                 WHERE id = :id
                 FOR UPDATE'
            );

            $lookup->execute([
                ':id' => $answerId
            ]);

            $answerDetails = $lookup->fetch(PDO::FETCH_ASSOC);

            if (!$answerDetails) {
                throw new RuntimeException('Answer not found.');
            }

            $questionId = (int)$answerDetails['question_id'];

            /* UPDATE ANSWER STATUS */

            $update = $pdo->prepare(
                'UPDATE answers
                 SET status = :status
                 WHERE id = :id'
            );

            $update->execute([
                ':status' => $answerStatus,
                ':id' => $answerId
            ]);

            /* UPDATE RELATED QUESTION STATUS */

            if ($answerStatus === 'Published') {

                $questionUpdate = $pdo->prepare(
                    "UPDATE questions
                     SET status = 'Answered'
                     WHERE id = :question_id
                       AND status = 'Pending'"
                );

                $questionUpdate->execute([
                    ':question_id' => $questionId
                ]);
            }

            /* RECORD ADMIN MODERATION EVENT */

            recordModerationEvent(
                $pdo,
                'answer',
                (int)$answerId,
                'ADMIN',
                $answerStatus,
                [],
                (int)($_SESSION['admin_id'] ?? 0),
                $answerStatus
            );

            $pdo->commit();

            $_SESSION['flash_message'] =
                $answerStatus === 'Published'
                    ? 'Answer published successfully.'
                    : 'Answer hidden successfully.';

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Answer review update failed: ' .
                get_class($exception) .
                ' (' . $exception->getCode() . ')'
            );

            $_SESSION['flash_message'] =
                'Unable to save the answer review decision.';
        }
    }

    /* REDIRECT WITH FILTERS */

    $redirectParams = [];

    if ($search !== '') {
        $redirectParams['search'] = $search;
    }

    if ($category !== '') {
        $redirectParams['category'] = $category;
    }

    if ($status !== '') {
        $redirectParams['status'] = $status;
    }

    $redirectUrl = 'answers.php';

    if (!empty($redirectParams)) {
        $redirectUrl .= '?' . http_build_query($redirectParams);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

/* -------------------------------------------------
   FETCH CATEGORIES
------------------------------------------------- */

$categoryStmt = $pdo->query(
    "SELECT DISTINCT category
     FROM questions
     WHERE category IS NOT NULL
       AND category != ''
     ORDER BY category ASC"
);

$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array($category, $categories, true)) {
    $category = '';
}

/* -------------------------------------------------
   ANSWER STATISTICS
------------------------------------------------- */

$statsStmt = $pdo->query(
    "SELECT
        COUNT(*) AS total_answers,
        COUNT(DISTINCT question_id) AS answered_questions,
        COUNT(DISTINCT user_id) AS total_contributors,
        COALESCE(
            SUM(
                CASE
                    WHEN DATE(created_at) = CURDATE()
                    THEN 1
                    ELSE 0
                END
            ), 0
        ) AS answers_today
     FROM answers"
);

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalAnswers = (int)($stats['total_answers'] ?? 0);
$answeredQuestions = (int)($stats['answered_questions'] ?? 0);
$totalContributors = (int)($stats['total_contributors'] ?? 0);
$answersToday = (int)($stats['answers_today'] ?? 0);

/* -------------------------------------------------
   FETCH ANSWERS
------------------------------------------------- */

$where = [];
$params = [];

/* SEARCH */

if ($search !== '') {

    $where[] = "(
        a.answer_text LIKE :answer
        OR q.question_title LIKE :title
        OR author.full_name LIKE :author
        OR author.email LIKE :email
        OR CAST(a.user_id AS CHAR) LIKE :user_id
        OR CAST(a.id AS CHAR) LIKE :answer_id
    )";

    $searchValue = '%' . $search . '%';

    $params[':answer'] = $searchValue;
    $params[':title'] = $searchValue;
    $params[':author'] = $searchValue;
    $params[':email'] = $searchValue;
    $params[':user_id'] = $searchValue;
    $params[':answer_id'] = $searchValue;
}

/* CATEGORY FILTER */

if ($category !== '') {

    $where[] = 'q.category = :category';

    $params[':category'] = $category;
}

/* STATUS FILTER */

if ($status !== '') {

    $where[] = 'a.status = :status';

    $params[':status'] = $status;
}

$whereSql = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

/* FETCH QUERY */

$sql = "
    SELECT
        a.id,
        a.answer_text,
        a.question_id,
        a.user_id,
        a.status,
        a.created_at,

        author.full_name AS author_name,
        author.email AS author_email,
        author.department AS author_department,
        author.role AS author_role,

        q.question_title,
        q.question_description,
        q.category,

        ai.ai_decision,
        ai.ai_reason,
        ai.ai_confidence,
        ai.relevance_score,
        ai.detected_violations,
        ai.model AS ai_model,
        ai.api_status

    FROM answers a

    LEFT JOIN questions q
        ON q.id = a.question_id

    LEFT JOIN users author
        ON author.user_id = a.user_id

    LEFT JOIN moderation_events ai
        ON ai.id = (
            SELECT MAX(me.id)
            FROM moderation_events me
            WHERE me.content_type = 'answer'
              AND me.content_id = a.id
              AND me.event_type = 'AI'
        )

    $whereSql

    ORDER BY a.created_at DESC, a.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AITAM Slack | Answer Management</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/answers.css">

</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <?php include 'includes/topbar.php'; ?>

    <main class="page-content answers-page">

        <!-- PAGE HEADING -->

        <div class="page-heading">

            <h2>Answer Management</h2>

            <p>
                Manage and monitor all answers submitted by users.
            </p>

        </div>

        <!-- FLASH MESSAGE -->

        <?php if ($message !== ''): ?>

            <div class="alert alert-info alert-dismissible fade show">

                <?= e($message) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>

            </div>

        <?php endif; ?>

        <!-- STATISTICS -->

        <div class="answer-stats">

            <div class="answer-card">

                <div class="answer-icon total-icon">
                    <i class="bi bi-chat-left-text"></i>
                </div>

                <div class="answer-details">

                    <p>Total Answers</p>

                    <h3><?= e($totalAnswers) ?></h3>

                </div>

            </div>

            <div class="answer-card">

                <div class="answer-icon published-icon">
                    <i class="bi bi-check-circle"></i>
                </div>

                <div class="answer-details">

                    <p>Questions with Answers</p>

                    <h3><?= e($answeredQuestions) ?></h3>

                </div>

            </div>

            <div class="answer-card">

                <div class="answer-icon pending-icon">
                    <i class="bi bi-people"></i>
                </div>

                <div class="answer-details">

                    <p>Answer Contributors</p>

                    <h3><?= e($totalContributors) ?></h3>

                </div>

            </div>

            <div class="answer-card">

                <div class="answer-icon hidden-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>

                <div class="answer-details">

                    <p>Answers Today</p>

                    <h3><?= e($answersToday) ?></h3>

                </div>

            </div>

        </div>

        <!-- ANSWERS TABLE PANEL -->

        <div class="answers-panel">

            <div class="panel-heading">

                <h4>All Answers</h4>

                <p>
                    View and monitor answers submitted by users.
                </p>

            </div>

            <!-- SEARCH AND FILTER -->

            <form method="GET" class="answer-filters">

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search answers, questions or users..."
                        value="<?= e($search) ?>"
                    >

                </div>

                <!-- CATEGORY FILTER -->

                <select name="category">

                    <option value="">All Categories</option>

                    <?php foreach ($categories as $cat): ?>

                        <option
                            value="<?= e($cat) ?>"
                            <?= $category === $cat ? 'selected' : '' ?>
                        >
                            <?= e($cat) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <!-- STATUS FILTER -->

                <select name="status">

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
                            <th>Author</th>
                            <th>Status</th>
                            <th>Submitted Date</th>
                            <th>Manage</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($answers)): ?>

                            <?php foreach ($answers as $answer): ?>

                                <tr>

                                    <!-- ANSWER ID -->

                                    <td>
                                        #<?= e($answer['id']) ?>
                                    </td>

                                    <!-- ANSWER CONTENT -->

                                    <td class="answer-content">

                                        <?= e($answer['answer_text']) ?>

                                        <details>

                                            <summary>AI review details</summary>

                                            <p>
                                                Decision:
                                                <?= e($answer['ai_decision'] ?? 'Not available') ?>
                                            </p>

                                            <p>
                                                API:
                                                <?= e($answer['api_status'] ?? 'Not checked') ?>
                                            </p>

                                            <?php if (!empty($answer['ai_reason'])): ?>

                                                <p>
                                                    <?= e($answer['ai_reason']) ?>
                                                </p>

                                            <?php endif; ?>

                                            <?php if ($answer['ai_confidence'] !== null): ?>

                                                <p>
                                                    Confidence:
                                                    <?= e($answer['ai_confidence']) ?>
                                                </p>

                                            <?php endif; ?>

                                            <?php if ($answer['relevance_score'] !== null): ?>

                                                <p>
                                                    Relevance:
                                                    <?= e($answer['relevance_score']) ?>
                                                </p>

                                            <?php endif; ?>

                                            <?php if (!empty($answer['ai_model'])): ?>

                                                <p>
                                                    Model:
                                                    <?= e($answer['ai_model']) ?>
                                                </p>

                                            <?php endif; ?>

                                            <?php if (!empty($answer['detected_violations'])): ?>

                                                <p>
                                                    Flags:
                                                    <?= e($answer['detected_violations']) ?>
                                                </p>

                                            <?php endif; ?>

                                        </details>

                                    </td>

                                    <!-- QUESTION -->

                                    <td>

                                        <strong>
                                            <?= e($answer['question_title'] ?? 'Question unavailable') ?>
                                        </strong>

                                        <?php if (!empty($answer['question_description'])): ?>

                                            <details>

                                                <summary>Question context</summary>

                                                <p>
                                                    <?= e($answer['question_description']) ?>
                                                </p>

                                            </details>

                                        <?php endif; ?>

                                    </td>

                                    <!-- AUTHOR -->

                                    <td>

                                        <?= e(
                                            $answer['author_name']
                                            ?: 'User #' . $answer['user_id']
                                        ) ?>

                                        <div class="small text-muted">
                                            <?= e($answer['author_email'] ?? '') ?>
                                        </div>

                                        <div class="small text-muted">

                                            <?= e($answer['author_department'] ?? '') ?>

                                            <?= !empty($answer['author_role'])
                                                ? ' · ' . e($answer['author_role'])
                                                : '' ?>

                                        </div>

                                    </td>

                                    <!-- STATUS -->

                                    <td>

                                        <span class="status-badge <?= e(strtolower($answer['status'])) ?>">

                                            <?= e($answer['status']) ?>

                                        </span>

                                    </td>

                                    <!-- SUBMITTED DATE -->

                                    <td>

                                        <?php if (!empty($answer['created_at'])): ?>

                                            <?= e(
                                                date(
                                                    'd M Y, h:i A',
                                                    strtotime($answer['created_at'])
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            Not available

                                        <?php endif; ?>

                                    </td>

                                    <!-- MANAGE -->

                                    <td>

                                        <form method="POST" class="status-form">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="answer_id"
                                                value="<?= e($answer['id']) ?>"
                                            >

                                            <?php if ($answer['status'] !== 'Published'): ?>

                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="approve"
                                                    class="update-btn"
                                                    onclick="return confirm('Publish this answer?')"
                                                >
                                                    Publish
                                                </button>

                                            <?php endif; ?>

                                            <?php if ($answer['status'] !== 'Hidden'): ?>

                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="reject"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Hide this answer?')"
                                                >
                                                    Hide
                                                </button>

                                            <?php endif; ?>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7" class="no-data text-center">

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

<!-- JAVASCRIPT -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/admin.js"></script>

<script src="../assets/js/answers.js"></script>

</body>
</html>
