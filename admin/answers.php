
<?php

require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once dirname(__DIR__) . '/includes/ai_helpers.php';

<<<<<<< HEAD
function e(mixed $value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}
=======
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
   SEARCH AND FILTER
------------------------------------------------- */

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

/* -------------------------------------------------
   FLASH MESSAGE
------------------------------------------------- */
>>>>>>> 39cf30da041aa7847606a412a24977294b39497a

$csrfToken = admin_csrf_token();
$allowedStatuses = ['PENDING', 'APPROVED', 'REJECTED'];
$search = is_string($_GET['search'] ?? null)
    ? trim($_GET['search'])
    : '';
$status = is_string($_GET['status'] ?? null)
    ? trim($_GET['status'])
    : 'PENDING';
$status = in_array($status, $allowedStatuses, true) ? $status : 'PENDING';
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

<<<<<<< HEAD
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
        $_SESSION['flash_message'] = 'Your session expired. Refresh and try again.';
    } elseif ($answerId === false || !in_array($action, ['approve', 'reject'], true)) {
        $_SESSION['flash_message'] = 'Invalid answer review details.';
    } else {
        $publicationStatus = $action === 'approve' ? 'APPROVED' : 'REJECTED';

        try {
            $pdo->beginTransaction();

            $lookup = $pdo->prepare(
                'SELECT a.question_id, q.publication_status AS question_publication_status
                 FROM answers a
                 INNER JOIN questions q ON q.id = a.question_id
                 WHERE a.id = :id
                 FOR UPDATE'
            );
            $lookup->execute([':id' => $answerId]);
            $answerDetails = $lookup->fetch(PDO::FETCH_ASSOC);

            if (!$answerDetails) {
                throw new RuntimeException('Answer not found.');
            }
            $questionId = (int)$answerDetails['question_id'];

            if (
                $publicationStatus === 'APPROVED' &&
                $answerDetails['question_publication_status'] !== 'APPROVED'
            ) {
                throw new DomainException(
                    'Approve the related question before publishing this answer.'
                );
            }

            $update = $pdo->prepare(
                'UPDATE answers
                 SET publication_status = :publication_status
                 WHERE id = :id'
            );
            $update->execute([
                ':publication_status' => $publicationStatus,
                ':id' => $answerId,
            ]);

            if ($publicationStatus === 'APPROVED') {
                $questionUpdate = $pdo->prepare(
                    "UPDATE questions
                     SET status = 'Answered'
                     WHERE id = :question_id
                       AND publication_status = 'APPROVED'
                       AND status = 'Pending'"
                );
                $questionUpdate->execute([':question_id' => $questionId]);
            }

            recordModerationEvent(
                $pdo,
                'answer',
                (int)$answerId,
                'ADMIN',
                $publicationStatus,
                [],
                (int)($_SESSION['admin_id'] ?? 0),
                $publicationStatus
            );

            $pdo->commit();
            $_SESSION['flash_message'] = 'Answer review decision saved.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Answer review update failed: ' .
                get_class($exception) . ' (' . $exception->getCode() . ')'
            );
            $_SESSION['flash_message'] = $exception instanceof DomainException
                ? $exception->getMessage()
                : 'Unable to save the answer review decision.';
        }
    }

    $redirectParams = [];
    if ($search !== '') {
        $redirectParams['search'] = $search;
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

$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_answers,
        SUM(CASE WHEN publication_status = 'APPROVED' THEN 1 ELSE 0 END) AS published_answers,
        SUM(CASE WHEN publication_status = 'PENDING' THEN 1 ELSE 0 END) AS pending_answers,
        SUM(CASE WHEN publication_status = 'REJECTED' THEN 1 ELSE 0 END) AS hidden_answers
=======
/* -------------------------------------------------
   FETCH CATEGORIES
------------------------------------------------- */

$categoryStmt = $pdo->query("
    SELECT DISTINCT category
    FROM questions
    WHERE category IS NOT NULL
      AND category != ''
    ORDER BY category ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array($category, $categories, true)) {
    $category = '';
}

/* -------------------------------------------------
   ANSWER STATISTICS
------------------------------------------------- */

$statsStmt = $pdo->query("
    SELECT
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
>>>>>>> 39cf30da041aa7847606a412a24977294b39497a
    FROM answers
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
<<<<<<< HEAD
$totalAnswers = (int)$stats['total_answers'];
$publishedAnswers = (int)$stats['published_answers'];
$pendingAnswers = (int)$stats['pending_answers'];
$hiddenAnswers = (int)$stats['hidden_answers'];

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(
        a.answer_text LIKE :answer
        OR q.question_title LIKE :title
        OR CAST(a.user_id AS CHAR) LIKE :user_id
        OR CAST(a.id AS CHAR) LIKE :answer_id
    )";
    $searchValue = '%' . $search . '%';
    $params[':answer'] = $searchValue;
    $params[':title'] = $searchValue;
    $params[':user_id'] = $searchValue;
    $params[':answer_id'] = $searchValue;
}
if ($status !== '') {
    $where[] = 'a.publication_status = :publication_status';
    $params[':publication_status'] = $status;
}
$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
=======

$totalAnswers = (int)($stats['total_answers'] ?? 0);
$answeredQuestions = (int)($stats['answered_questions'] ?? 0);
$totalContributors = (int)($stats['total_contributors'] ?? 0);
$answersToday = (int)($stats['answers_today'] ?? 0);

/* -------------------------------------------------
   FETCH ANSWERS
------------------------------------------------- */

>>>>>>> 39cf30da041aa7847606a412a24977294b39497a
$sql = "
    SELECT
        a.id,
        a.answer_text,
        a.question_id,
        a.user_id,
<<<<<<< HEAD
        author.full_name AS author_name,
        author.email AS author_email,
        author.department AS author_department,
        author.role AS author_role,
        a.publication_status,
        a.created_at,
        q.question_title,
        q.question_description,
        ai.ai_decision,
        ai.ai_reason,
        ai.ai_confidence,
        ai.relevance_score,
        ai.detected_violations,
        ai.model AS ai_model,
        ai.api_status
    FROM answers a
    LEFT JOIN questions q ON q.id = a.question_id
    LEFT JOIN users author ON author.user_id = a.user_id
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
=======
        a.created_at,

        u.full_name,
        u.email,

        q.question_title,
        q.category

    FROM answers a

    LEFT JOIN questions q
        ON q.id = a.question_id

    LEFT JOIN users u
        ON u.user_id = a.user_id

    WHERE 1 = 1
";

$params = [];

/* SEARCH */

if ($search !== '') {

    $sql .= "
        AND (
            a.answer_text LIKE :search1
            OR q.question_title LIKE :search2
            OR u.full_name LIKE :search3
            OR u.email LIKE :search4
            OR CAST(a.user_id AS CHAR) LIKE :search5
            OR CAST(a.id AS CHAR) LIKE :search6
        )
    ";

    $keyword = '%' . $search . '%';

    $params[':search1'] = $keyword;
    $params[':search2'] = $keyword;
    $params[':search3'] = $keyword;
    $params[':search4'] = $keyword;
    $params[':search5'] = $keyword;
    $params[':search6'] = $keyword;
}

/* CATEGORY FILTER */

if ($category !== '') {

    $sql .= " AND q.category = :category";

    $params[':category'] = $category;
}

/* ORDER */

$sql .= " ORDER BY a.created_at DESC";

/* EXECUTE */

>>>>>>> 39cf30da041aa7847606a412a24977294b39497a
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

    <!-- Existing Project CSS -->

    <link rel="stylesheet" href="../assets/css/root.css">

    <link rel="stylesheet" href="../assets/css/admin.css">

    <link rel="stylesheet" href="../assets/css/answers.css">

</head>

<body>

<!-- SIDEBAR: KEEP EXISTING SIDEBAR -->

<?php include 'includes/sidebar.php'; ?>


<!-- MAIN CONTENT: SAME STRUCTURE AS questions.php -->

<div class="main-content">

    <!-- TOP NAVBAR: KEEP EXISTING TOPBAR -->

    <?php include 'includes/topbar.php'; ?>


    <!-- PAGE CONTENT -->

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


            <!-- TOTAL ANSWERS -->

            <div class="answer-card">

                <div class="answer-icon total-icon">

                    <i class="bi bi-chat-left-text"></i>

                </div>

                <div class="answer-details">

                    <p>Total Answers</p>

                    <h3><?= e($totalAnswers) ?></h3>

                </div>

            </div>


            <!-- ANSWERED QUESTIONS -->

            <div class="answer-card">

                <div class="answer-icon published-icon">

                    <i class="bi bi-check-circle"></i>

                </div>

                <div class="answer-details">

                    <p>Questions with Answers</p>

                    <h3><?= e($answeredQuestions) ?></h3>

                </div>

            </div>


            <!-- CONTRIBUTORS -->

            <div class="answer-card">

                <div class="answer-icon pending-icon">

                    <i class="bi bi-people"></i>

                </div>

                <div class="answer-details">

                    <p>Answer Contributors</p>

                    <h3><?= e($totalContributors) ?></h3>

                </div>

            </div>


            <!-- ANSWERS TODAY -->

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


            <!-- PANEL HEADING -->

            <div class="panel-heading">

                <h4>All Answers</h4>

                <p>
                    View and monitor answers submitted by users.
                </p>

            </div>


            <!-- SEARCH AND FILTER -->

            <form method="GET" class="answer-filters">


                <!-- SEARCH BOX -->

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search answers, questions or users..."
                        value="<?= e($search) ?>"
                    >

                </div>


                <!-- CATEGORY -->

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


                <!-- FILTER BUTTON -->

                <button type="submit" class="filter-btn">

                    <i class="bi bi-funnel"></i>

<<<<<<< HEAD
                            <option
                                value="<?= e($item) ?>"
                                <?= $status === $item ? 'selected' : '' ?>
                            >
                                <?= e(ucfirst(strtolower($item))) ?>
                            </option>
=======
                    Filter
>>>>>>> 39cf30da041aa7847606a412a24977294b39497a

                </button>


                <!-- RESET BUTTON -->

                <a href="answers.php" class="reset-btn">

                    Reset

                </a>


            </form>


            <!-- ANSWERS TABLE -->

            <div class="table-responsive">

<<<<<<< HEAD
                    <table class="table answers-table">

                        <thead>

                            <tr>

                                <th>Answer ID</th>
                                <th>Answer Content</th>
                                <th>Question</th>
                                <th>Author</th>
                                <th>Publication</th>
                                <th>Submitted Date</th>
                                <th>Manage</th>

                            </tr>

                        </thead>
=======
                <table class="table answers-table">
>>>>>>> 39cf30da041aa7847606a412a24977294b39497a


                    <!-- TABLE HEADER -->

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


<<<<<<< HEAD
                                        <td class="answer-content">
                                            <?= e($answer['answer_text']) ?>
                                            <details>
                                                <summary>AI review details</summary>
                                                <p>
                                                    Decision: <?= e($answer['ai_decision'] ?? 'FAILED') ?>
                                                    · API: <?= e($answer['api_status'] ?? 'not checked') ?>
                                                </p>
                                                <?php if (!empty($answer['ai_reason'])): ?>
                                                    <p><?= e($answer['ai_reason']) ?></p>
                                                <?php endif; ?>
                                                <?php if ($answer['ai_confidence'] !== null): ?>
                                                    <p>Confidence: <?= e($answer['ai_confidence']) ?></p>
                                                <?php endif; ?>
                                                <?php if ($answer['relevance_score'] !== null): ?>
                                                    <p>Relevance: <?= e($answer['relevance_score']) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($answer['ai_model'])): ?>
                                                    <p>Model: <?= e($answer['ai_model']) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($answer['detected_violations'])): ?>
                                                    <p>Flags: <?= e($answer['detected_violations']) ?></p>
                                                <?php endif; ?>
                                            </details>
                                        </td>


                                        <td>
                                            <strong>
                                                <?= e($answer['question_title'] ?? 'Question unavailable') ?>
                                            </strong>
                                            <?php if (!empty($answer['question_description'])): ?>
                                                <details>
                                                    <summary>Question context</summary>
                                                    <p><?= e($answer['question_description']) ?></p>
                                                </details>
                                            <?php endif; ?>
                                        </td>


                                        <td>
                                            <?= e($answer['author_name'] ?: 'User #' . $answer['user_id']) ?>
                                            <div class="small text-muted">
                                                <?= e($answer['author_email'] ?? '') ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= e($answer['author_department'] ?? '') ?>
                                                <?= !empty($answer['author_role']) ? ' · ' . e($answer['author_role']) : '' ?>
                                            </div>
                                        </td>


                                        <td>

                                            <span class="status-badge <?= e(strtolower($answer['publication_status'])) ?>">
                                                <?= e(ucfirst(strtolower($answer['publication_status']))) ?>
                                            </span>

                                        </td>


                                        <td>
                                            <?= e($answer['created_at']) ?>
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
                                                        onclick="return confirm('Approve and publish this answer?')"
                                                    >
                                                        Approve
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($answer['publication_status'] !== 'REJECTED'): ?>
                                                    <button
                                                        type="submit"
                                                        name="action"
                                                        value="reject"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Reject this answer?')"
                                                    >
                                                        Reject
                                                    </button>
                                                <?php endif; ?>

                                            </form>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

=======
                    <!-- TABLE BODY -->

                    <tbody>


                        <?php if (!empty($answers)): ?>


                            <?php foreach ($answers as $answer): ?>


>>>>>>> 39cf30da041aa7847606a412a24977294b39497a
                                <tr>


                                    <!-- ANSWER ID -->

                                    <td>

                                        #<?= e($answer['id']) ?>

                                    </td>


                                    <!-- ANSWER CONTENT -->

                                    <td class="answer-content">

                                        <?= e($answer['answer_text']) ?>

                                    </td>


                                    <!-- QUESTION TITLE -->

                                    <td>

                                        <?= e(
                                            $answer['question_title']
                                            ?? 'Question unavailable'
                                        ) ?>

                                    </td>


                                    <!-- USER INFORMATION -->

                                    <td>

                                        <strong>
                                            <?= e($answer['user_id']) ?>
                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            <?= e(
                                                $answer['full_name']
                                                ?? 'User unavailable'
                                            ) ?>

                                        </small>

                                    </td>


                                    <!-- SUBMISSION INFORMATION -->

                                    <td>

                                        <span class="status-badge submitted">

                                            Submitted

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

                                        <span class="text-muted">—</span>

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
