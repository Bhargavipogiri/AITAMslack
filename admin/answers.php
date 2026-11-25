
<?php

require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/ai_helpers.php';

/* -------------------------------------------------
   HELPER FUNCTION
------------------------------------------------- */

if (!function_exists('e')) {
    function e(mixed $value): string
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

$allowedStatuses = ['PENDING', 'APPROVED', 'REJECTED'];

$status = is_string($_GET['status'] ?? null)
    ? trim($_GET['status'])
    : 'PENDING';

$status = in_array($status, $allowedStatuses, true)
    ? $status
    : 'PENDING';

/* -------------------------------------------------
   CSRF AND FLASH MESSAGE
------------------------------------------------- */

$csrfToken = admin_csrf_token();

$message = $_SESSION['flash_message'] ?? '';
$message = is_string($message) ? $message : '';

unset($_SESSION['flash_message']);

/* -------------------------------------------------
   FETCH CATEGORIES
------------------------------------------------- */

$categoryStmt = $pdo->query(
    "SELECT DISTINCT category
     FROM questions
     WHERE category IS NOT NULL
       AND category <> ''
     ORDER BY category ASC"
);

$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array($category, $categories, true)) {
    $category = '';
}

/* -------------------------------------------------
   HANDLE PUBLISH / HIDE ACTION
------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $answerId = filter_var(
        $_POST['answer_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $action = is_string($_POST['action'] ?? null)
        ? $_POST['action']
        : '';

    $csrfValue = is_string($_POST['csrf_token'] ?? null)
        ? $_POST['csrf_token']
        : null;

    if (!admin_csrf_verify($csrfValue)) {

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
            ? 'APPROVED'
            : 'REJECTED';

        $publicationStatus = $answerStatus === 'Published'
            ? 'APPROVED'
            : 'REJECTED';

        try {

            $pdo->beginTransaction();

            /* FETCH ANSWER */

            $lookup = $pdo->prepare(
                'SELECT question_id, status
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
                 SET publication_status = :status
                 WHERE id = :id'
            );

            $update->execute([
                ':status' => $answerStatus,
                ':id' => $answerId
            ]);

            /* UPDATE RELATED QUESTION STATUS */

            if ($answerStatus === 'APPROVED') {

                $questionUpdate = $pdo->prepare(
                    "UPDATE questions
                     SET status = 'Answered'
                     WHERE id = :question_id
                       AND status = 'Pending'"
                );

                $questionUpdate->execute([
                    ':question_id' => $questionId
                ]);

            } else {

                /*
                 * Return the question to Pending only when
                 * no other published answers exist.
                 *
                 * Closed questions remain Closed.
                 */

                $questionUpdate = $pdo->prepare(
                    "UPDATE questions
                     SET status = 'Pending'
                     WHERE id = :question_id
                       AND status = 'Answered'
                       AND NOT EXISTS (
                           SELECT 1
                           FROM answers
                           WHERE question_id = :question_id
                             AND status = 'Published'
                       )"
                );

                $questionUpdate->execute([
                    ':question_id' => $questionId
                ]);
            }

            /* RECORD ADMIN MODERATION EVENT */

            $adminId = filter_var(
                $_SESSION['admin_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            recordModerationEvent(
                $pdo,
                'answer',
                (int)$answerId,
                'ADMIN',
                $publicationStatus,
                [],
                $adminId !== false ? (int)$adminId : null,
                $publicationStatus
            );

            $pdo->commit();

            $_SESSION['flash_message'] =
                $answerStatus === 'APPROVED'
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

    if ($redirectParams !== []) {
        $redirectUrl .= '?' . http_build_query($redirectParams);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

/* -------------------------------------------------
   ANSWER STATISTICS
------------------------------------------------- */

$statsStmt = $pdo->query(
    "SELECT
        COUNT(*) AS total_answers,

        COALESCE(
            SUM(CASE WHEN publication_status = 'APPROVED' THEN 1 ELSE 0 END),
            0
        ) AS published_answers,

        COALESCE(
            SUM(CASE WHEN publication_status = 'PENDING' THEN 1 ELSE 0 END),
            0
        ) AS pending_answers,

        COALESCE(
            SUM(CASE WHEN publication_status = 'REJECTED' THEN 1 ELSE 0 END),
            0
        ) AS hidden_answers

     FROM answers"
);

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalAnswers = (int)($stats['total_answers'] ?? 0);
$publishedAnswers = (int)($stats['published_answers'] ?? 0);
$pendingAnswers = (int)($stats['pending_answers'] ?? 0);
$hiddenAnswers = (int)($stats['hidden_answers'] ?? 0);

/* -------------------------------------------------
   FETCH ANSWERS
------------------------------------------------- */

$where = [];
$params = [];

/* SEARCH FILTER */

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

    $where[] = 'a.publication_status = :status';

    $params[':status'] = $status;
}

$whereSql = $where !== []
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

/* -------------------------------------------------
   ANSWERS QUERY
------------------------------------------------- */

$sql = "
    SELECT
        a.id,
        a.answer_text,
        a.question_id,
        a.user_id,
        a.publication_status,
        a.created_at,

        q.question_title,
        q.question_description,
        q.category,

        author.full_name AS author_name,
        author.email AS author_email,
        author.department AS author_department,
        author.role AS author_role,

        ai.ai_decision,
        ai.publication_status AS ai_publication_status,
        ai.ai_reason,
        ai.ai_confidence,
        ai.relevance_score,
        ai.detected_violations,
        ai.model AS ai_model,
        ai.api_status,
<<<<<<< HEAD

        admin_review.admin_decision,
        admin_review.publication_status AS admin_publication_status,
        admin_review.admin_reviewed_at,
        admin_review.admin_reviewer_id
=======
        validation.result_code AS validation_result,
        validation.reason AS validation_reason,
        validation.category AS validation_category
>>>>>>> 64e62b774cc80e4e2d0be33e0f90b0e23cf096ff

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

<<<<<<< HEAD
    LEFT JOIN moderation_events admin_review
        ON admin_review.id = (
            SELECT MAX(me.id)
            FROM moderation_events me
            WHERE me.content_type = 'answer'
              AND me.content_id = a.id
              AND me.event_type = 'ADMIN'
=======
    LEFT JOIN qa_validation_results validation
        ON validation.id = (
            SELECT MAX(qvr.id)
            FROM qa_validation_results qvr
            WHERE qvr.content_type = 'answer'
              AND qvr.content_id = a.id
>>>>>>> 64e62b774cc80e4e2d0be33e0f90b0e23cf096ff
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

            <div class="page-heading">

                <h2>Answer Management</h2>

                <p>Review and manage submitted answers.</p>

            </div>

            <!-- FLASH MESSAGE -->

            <?php if ($message !== ''): ?>

                <div class="alert alert-info alert-dismissible fade show" role="alert">

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

                        <p>Published Answers</p>

                        <h3><?= e($publishedAnswers) ?></h3>

                    </div>

                </div>

                <div class="answer-card">

                    <div class="answer-icon pending-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>

                    <div class="answer-details">

                        <p>Pending Answers</p>

                        <h3><?= e($pendingAnswers) ?></h3>

                    </div>

                </div>

                <div class="answer-card">

                    <div class="answer-icon hidden-icon">
                        <i class="bi bi-eye-slash"></i>
                    </div>

                    <div class="answer-details">

                        <p>Hidden Answers</p>

                        <h3><?= e($hiddenAnswers) ?></h3>

                    </div>

                </div>

            </div>

            <!-- ANSWERS TABLE PANEL -->

            <div class="answers-panel">

                <div class="panel-heading">

                    <h4>Answers</h4>

                    <p>
                        Review the original question and the AI moderation
                        details before deciding.
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

                    <select name="status">

                        <option value="">All Statuses</option>

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

                                        <td>
                                            #<?= e($answer['id']) ?>
                                        </td>

                                        <td class="answer-content">

                                            <?= e($answer['answer_text']) ?>

                                            <details>

                                                <summary>AI review details</summary>

                                                <p>
                                                    Decision:
                                                    <?= e($answer['ai_decision'] ?? 'Not available') ?>
                                                </p>

                                                <p>
                                                    AI Publication:
                                                    <?= e($answer['ai_publication_status'] ?? 'Not available') ?>
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

                                                <?php if (!empty($answer['validation_result'])): ?>

                                                    <hr>
                                                    <p>
                                                        College validation:
                                                        <?= e($answer['validation_result']) ?>
                                                    </p>
                                                    <p>
                                                        Topic:
                                                        <?= e($answer['validation_category']) ?>
                                                    </p>
                                                    <p>
                                                        <?= e($answer['validation_reason']) ?>
                                                    </p>

                                                <?php endif; ?>

                                                <?php if (!empty($answer['detected_violations'])): ?>

                                                    <p>
                                                        Flags:
                                                        <?= e($answer['detected_violations']) ?>
                                                    </p>

                                                <?php endif; ?>

                                            </details>

                                            <?php if (!empty($answer['admin_decision'])): ?>

                                                <details>

                                                    <summary>Admin review details</summary>

                                                    <p>
                                                        Decision:
                                                        <?= e($answer['admin_decision']) ?>
                                                    </p>

                                                    <p>
                                                        Publication:
                                                        <?= e($answer['admin_publication_status'] ?? '') ?>
                                                    </p>

                                                    <p>
                                                        Reviewer ID:
                                                        <?= e($answer['admin_reviewer_id'] ?? '') ?>
                                                    </p>

                                                    <p>
                                                        Reviewed:
                                                        <?= e($answer['admin_reviewed_at'] ?? '') ?>
                                                    </p>

                                                </details>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <strong>
                                                <?= e($answer['question_title'] ?? 'Question unavailable') ?>
                                            </strong>

                                            <?php if (!empty($answer['category'])): ?>

                                                <div class="small text-muted">
                                                    <?= e($answer['category']) ?>
                                                </div>

                                            <?php endif; ?>

                                            <?php if (!empty($answer['question_description'])): ?>

                                                <details>

                                                    <summary>Question context</summary>

                                                    <p>
                                                        <?= e($answer['question_description']) ?>
                                                    </p>

                                                </details>

                                            <?php endif; ?>

                                        </td>

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

                                        <td>

                                            <span class="status-badge <?= e(strtolower($answer['publication_status'])) ?>">

                                                <?= e($answer['publication_status']) ?>

                                            </span>

                                        </td>

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

                                                <?php if ($answer['publication_status'] !== 'APPROVED'): ?>

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

                                                <?php if ($answer['publication_status'] !== 'REJECTED'): ?>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../assets/js/admin.js"></script>

    <script src="../assets/js/answers.js"></script>

</body>
</html>
