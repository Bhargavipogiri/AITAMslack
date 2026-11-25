
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once dirname(__DIR__) . '/includes/ai_helpers.php';

function e(mixed $value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$csrfToken = admin_csrf_token();

/*
|--------------------------------------------------------------------------
| QUESTION MANAGEMENT FILTER
|--------------------------------------------------------------------------
| This page displays only approved questions.
| Approval can come from AI or an administrator.
*/

$status = 'APPROVED';

$search = is_string($_GET['search'] ?? null)
    ? trim($_GET['search'])
    : '';

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$perPage = 20;

$notice = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);


/*
|--------------------------------------------------------------------------
| LATEST MODERATION EVENT FOR EACH QUESTION
|--------------------------------------------------------------------------
*/

$reviewJoin = "
    LEFT JOIN moderation_events current_review
        ON current_review.id = (
            SELECT MAX(me.id)
            FROM moderation_events me
            WHERE me.content_type = 'question'
              AND me.content_id = q.id
        )
";


/*
|--------------------------------------------------------------------------
| ADMIN QUESTION REVIEW
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $questionId = filter_var(
        $_POST['question_id'] ?? null,
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
        $questionId === false ||
        !in_array($action, ['approve', 'reject'], true)
    ) {

        $_SESSION['flash_message'] =
            'Invalid question review details.';

    } else {

        $publicationStatus = $action === 'approve'
            ? 'APPROVED'
            : 'REJECTED';

        try {

            $pdo->beginTransaction();

            $exists = $pdo->prepare(
                'SELECT id FROM questions WHERE id = :id FOR UPDATE'
            );

            $exists->execute([
                ':id' => $questionId
            ]);

            if (!$exists->fetchColumn()) {
                throw new RuntimeException('Question not found.');
            }

            recordModerationEvent(
                $pdo,
                'question',
                (int)$questionId,
                'ADMIN',
                $publicationStatus,
                [],
                (int)($_SESSION['admin_id'] ?? 0),
                $publicationStatus
            );

            $pdo->commit();

            $_SESSION['flash_message'] =
                $action === 'approve'
                    ? 'Question approved successfully.'
                    : 'Question rejected successfully.';

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Question review update failed: ' .
                get_class($exception) .
                ' (' . $exception->getCode() . ')'
            );

            $_SESSION['flash_message'] =
                'Unable to save the question review decision.';
        }
    }

    $redirectUrl = 'questions.php';

    if ($search !== '') {
        $redirectUrl .= '?' . http_build_query([
            'search' => $search
        ]);
    }

    header('Location: ' . $redirectUrl);
    exit;
}


/*
|--------------------------------------------------------------------------
| SEARCH AND APPROVED-ONLY FILTER
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if ($search !== '') {

    $where[] = "(
        q.question_title LIKE :title
        OR q.question_description LIKE :description
        OR q.category LIKE :category
        OR CAST(q.user_id AS CHAR) LIKE :user_id
    )";

    $keyword = '%' . $search . '%';

    $params[':title'] = $keyword;
    $params[':description'] = $keyword;
    $params[':category'] = $keyword;
    $params[':user_id'] = $keyword;
}

/*
 * IMPORTANT:
 * Only the latest APPROVED moderation event is displayed.
 *
 * AI APPROVED  -> displayed
 * ADMIN APPROVED -> displayed
 * PENDING      -> hidden
 * REJECTED     -> hidden
 * REVIEW       -> hidden
 * FAILED       -> hidden
 */

$where[] = "
    current_review.publication_status = 'APPROVED'
";

$whereSql = ' WHERE ' . implode(' AND ', $where);


/*
|--------------------------------------------------------------------------
| TOTAL FILTERED QUESTIONS
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM questions q
     ' . $reviewJoin . '
     ' . $whereSql
);

$countStmt->execute($params);

$totalFilteredQuestions = (int)$countStmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalFilteredQuestions / $perPage)
);

$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| FETCH APPROVED QUESTIONS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        q.*,

        current_review.publication_status,

        author.full_name AS author_name,
        author.email AS author_email,
        author.department AS author_department,
        author.role AS author_role,

        ai.ai_decision,
        ai.ai_reason,
        ai.ai_confidence,
        ai.category AS ai_category,
        ai.detected_violations,
        ai.model AS ai_model,
        ai.api_status

    FROM questions q

    LEFT JOIN users author
        ON author.user_id = q.user_id

    $reviewJoin

    LEFT JOIN moderation_events ai
        ON ai.id = (
            SELECT MAX(me.id)
            FROM moderation_events me
            WHERE me.content_type = 'question'
              AND me.content_id = q.id
              AND me.event_type = 'AI'
        )

    $whereSql

    ORDER BY q.id DESC

    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| QUESTION STATISTICS
|--------------------------------------------------------------------------
| Statistics include all questions, not just approved questions.
|--------------------------------------------------------------------------
*/

$statsStmt = $pdo->query("
    SELECT

        COUNT(*) AS total_questions,

        SUM(
            CASE
                WHEN COALESCE(
                    current_review.publication_status,
                    'PENDING'
                ) = 'PENDING'
                THEN 1 ELSE 0
            END
        ) AS pending_questions,

        SUM(
            CASE
                WHEN current_review.publication_status = 'APPROVED'
                THEN 1 ELSE 0
            END
        ) AS published_questions,

        SUM(
            CASE
                WHEN current_review.publication_status = 'REJECTED'
                THEN 1 ELSE 0
            END
        ) AS hidden_questions

    FROM questions q

    $reviewJoin
");

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalQuestions = (int)($stats['total_questions'] ?? 0);
$pendingQuestions = (int)($stats['pending_questions'] ?? 0);
$publishedQuestions = (int)($stats['published_questions'] ?? 0);
$hiddenQuestions = (int)($stats['hidden_questions'] ?? 0);

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Question Management | AITAM Slack</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <!-- Project CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/questions.css?v=1">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "includes/topbar.php"; ?>

    <main class="page-content questions-page">

        <!-- Page Heading -->
        <div class="page-heading">

            <h2>Question Management</h2>

            <p>View and manage AI-approved and admin-approved questions</p>

        </div>

        <?php if ($notice !== ''): ?>
            <div class="alert alert-info" role="status">
                <?= e($notice) ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="question-stats">

            <div class="question-card">

                <i class="bi bi-chat-square-text"></i>

                <div>
                    <p>Total Questions</p>
                    <h3><?= e($totalQuestions) ?></h3>
                </div>

            </div>

            <div class="question-card">

                <i class="bi bi-clock-history"></i>

                <div>
                    <p>Pending Questions</p>
                    <h3><?= e($pendingQuestions) ?></h3>
                </div>

            </div>

            <div class="question-card">

                <i class="bi bi-check-circle"></i>

                <div>
                    <p>Published Questions</p>
                    <h3><?= e($publishedQuestions) ?></h3>
                </div>

            </div>

            <div class="question-card">

                <i class="bi bi-eye-slash"></i>

                <div>
                    <p>Hidden Questions</p>
                    <h3><?= e($hiddenQuestions) ?></h3>
                </div>

            </div>

        </div>

        <!-- Questions Panel -->
        <section class="questions-panel">

            <div class="panel-heading">

                <h4>Approved Questions</h4>

                <p>Only approved questions are displayed here</p>

            </div>

            <!-- Search and Filters -->
            <form method="GET" class="question-filters">

                <input
                    type="search"
                    name="search"
                    placeholder="Search approved questions..."
                    value="<?= e($search) ?>"
                >

                <select name="status" disabled>

                    <option value="APPROVED" selected>
                        Approved Questions Only
                    </option>

                </select>

                <button type="submit">
                    Search
                </button>

                <a href="questions.php">
                    Reset
                </a>

            </form>

            <!-- Questions Table -->
            <div class="table-responsive">

                <table class="table questions-table">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Question Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Publication</th>
                            <th>AI Review</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($questions) > 0): ?>

                        <?php foreach ($questions as $question): ?>

                            <tr>

                                <td>
                                    <?= e($question["id"]) ?>
                                </td>

                                <td>

                                    <div class="question-title">
                                        <?= e($question["question_title"]) ?>
                                    </div>

                                    <details class="question-details">

                                        <summary>
                                            View Description
                                        </summary>

                                        <p>
                                            <?= nl2br(e($question["question_description"])) ?>
                                        </p>

                                    </details>

                                </td>

                                <td>

                                    <span class="category-label">
                                        <?= e($question["category"]) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= e($question['author_name'] ?: 'User #' . $question['user_id']) ?>

                                    <div class="small text-muted">
                                        <?= e($question['author_email'] ?? '') ?>
                                    </div>

                                    <div class="small text-muted">
                                        <?= e($question['author_department'] ?? '') ?>
                                        <?= !empty($question['author_role']) ? ' · ' . e($question['author_role']) : '' ?>
                                    </div>
                                </td>

                                <td>

                                    <?php
                                    $currentStatus = (string)($question["publication_status"] ?? 'PENDING');
                                    ?>

                                    <span class="question-status <?= e(strtolower($currentStatus)) ?>">
                                        <?= e(ucfirst(strtolower($currentStatus))) ?>
                                    </span>

                                </td>

                                <td>

                                    <strong>
                                        <?= e($question['ai_decision'] ?? 'FAILED') ?>
                                    </strong>

                                    <div class="small text-muted">

                                        <?= e($question['ai_category'] ?? 'No category') ?>
                                        · <?= e($question['api_status'] ?? 'not checked') ?>

                                        <?php if ($question['ai_confidence'] !== null): ?>
                                            · <?= e($question['ai_confidence']) ?> confidence
                                        <?php endif; ?>

                                    </div>

                                    <?php if (!empty($question['ai_model'])): ?>

                                        <div class="small text-muted">
                                            <?= e($question['ai_model']) ?>
                                        </div>

                                    <?php endif; ?>

                                    <?php if (!empty($question['ai_reason'])): ?>

                                        <details class="question-details">

                                            <summary>
                                                Review details
                                            </summary>

                                            <p>
                                                <?= e($question['ai_reason']) ?>
                                            </p>

                                            <?php if (!empty($question['detected_violations'])): ?>

                                                <p>
                                                    Flags:
                                                    <?= e($question['detected_violations']) ?>
                                                </p>

                                            <?php endif; ?>

                                        </details>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?= !empty($question["created_at"])
                                        ? e(date("d M Y", strtotime($question["created_at"])))
                                        : "N/A" ?>

                                </td>

                                <td>

                                    <div class="question-actions">

                                        <?php if ($currentStatus !== 'REJECTED'): ?>

                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($csrfToken) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="question_id"
                                                    value="<?= e($question["id"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="reject"
                                                >

                                                <button
                                                    type="submit"
                                                    class="hide-btn"
                                                    title="Reject Question"
                                                    onclick="return confirm('Reject this question?')"
                                                >
                                                    <i class="bi bi-eye-slash"></i>
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" class="text-center">
                                No approved questions found.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>

                <div class="pagination-container">

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                        <a
                            href="?<?= e(http_build_query([
                                "search" => $search,
                                "page" => $i
                            ])) ?>"
                            class="<?= $page === $i ? "active" : "" ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Project JavaScript -->
<script src="../assets/js/admin.js"></script>

</body>
</html>
