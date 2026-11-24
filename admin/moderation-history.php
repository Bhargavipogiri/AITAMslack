<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

function e(mixed $value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$contentType = is_string($_GET['content_type'] ?? null)
    ? $_GET['content_type']
    : '';
$eventType = is_string($_GET['event_type'] ?? null)
    ? $_GET['event_type']
    : '';
$contentTypes = ['', 'question', 'answer'];
$eventTypes = ['', 'AI', 'ADMIN'];

if (!in_array($contentType, $contentTypes, true)) {
    $contentType = '';
}
if (!in_array($eventType, $eventTypes, true)) {
    $eventType = '';
}

$conditions = [];
$params = [];
if ($contentType !== '') {
    $conditions[] = 'me.content_type = :content_type';
    $params[':content_type'] = $contentType;
}
if ($eventType !== '') {
    $conditions[] = 'me.event_type = :event_type';
    $params[':event_type'] = $eventType;
}
$whereSql = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

$statement = $pdo->prepare("
    SELECT
        me.*,
        q.question_title,
        a.answer_text
    FROM moderation_events me
    LEFT JOIN questions q
        ON me.content_type = 'question'
       AND q.id = me.content_id
    LEFT JOIN answers a
        ON me.content_type = 'answer'
       AND a.id = me.content_id
    $whereSql
    ORDER BY me.id DESC
    LIMIT 300
");
$statement->execute($params);
$events = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderation History | AITAM Slack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
    <?php include 'includes/topbar.php'; ?>
    <main class="page-content">
        <div class="page-heading">
            <h2>Moderation History</h2>
            <p>Append-only AI and administrator decisions for submitted content.</p>
        </div>

        <form method="GET" class="d-flex flex-wrap gap-2 mb-4">
            <select name="content_type" class="form-select" style="max-width: 220px">
                <option value="">All content</option>
                <option value="question" <?= $contentType === 'question' ? 'selected' : '' ?>>Questions</option>
                <option value="answer" <?= $contentType === 'answer' ? 'selected' : '' ?>>Answers</option>
            </select>
            <select name="event_type" class="form-select" style="max-width: 220px">
                <option value="">All event types</option>
                <option value="AI" <?= $eventType === 'AI' ? 'selected' : '' ?>>AI moderation</option>
                <option value="ADMIN" <?= $eventType === 'ADMIN' ? 'selected' : '' ?>>Admin review</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="moderation-history.php" class="btn btn-outline-secondary">Reset</a>
        </form>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Content</th>
                    <th>Event</th>
                    <th>AI decision</th>
                    <th>Admin decision</th>
                    <th>Publication</th>
                    <th>Details</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($events === []): ?>
                    <tr><td colspan="7" class="text-center">No moderation events found.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <?php
                        $content = $event['content_type'] === 'question'
                            ? ($event['question_title'] ?? 'Question unavailable')
                            : ($event['answer_text'] ?? 'Answer unavailable');
                        $contentPreview = function_exists('mb_substr')
                            ? mb_substr((string)$content, 0, 180, 'UTF-8')
                            : substr((string)$content, 0, 180);
                        $violations = json_decode(
                            (string)($event['detected_violations'] ?? '[]'),
                            true
                        );
                        ?>
                        <tr>
                            <td><?= e($event['created_at']) ?></td>
                            <td>
                                <span class="badge text-bg-secondary"><?= e(ucfirst($event['content_type'])) ?></span>
                                #<?= e($event['content_id']) ?>
                                <div class="text-break"><?= e($contentPreview) ?></div>
                            </td>
                            <td><?= e($event['event_type']) ?></td>
                            <td>
                                <?= e($event['ai_decision'] ?? '—') ?>
                                <div class="small text-muted">
                                    <?= e($event['api_status']) ?>
                                    <?= !empty($event['model']) ? ' · ' . e($event['model']) : '' ?>
                                </div>
                            </td>
                            <td>
                                <?= e($event['admin_decision'] ?? '—') ?>
                                <?php if ($event['admin_reviewer_id'] !== null): ?>
                                    <div class="small text-muted">
                                        Admin #<?= e($event['admin_reviewer_id']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($event['publication_status']) ?></td>
                            <td>
                                <details>
                                    <summary>View</summary>
                                    <?php if (!empty($event['ai_reason'])): ?>
                                        <p><?= e($event['ai_reason']) ?></p>
                                    <?php endif; ?>
                                    <?php if (is_array($violations) && $violations !== []): ?>
                                        <p>Flags: <?= e(implode(', ', $violations)) ?></p>
                                    <?php endif; ?>
                                    <?php if ($event['ai_confidence'] !== null): ?>
                                        <p>Confidence: <?= e($event['ai_confidence']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($event['relevance_score'] !== null): ?>
                                        <p>Relevance: <?= e($event['relevance_score']) ?></p>
                                    <?php endif; ?>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
