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

$topics = ['Admissions', 'Academics', 'Placements', 'Campus Life'];
$csrfToken = admin_csrf_token();
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfValue = is_string($_POST['csrf_token'] ?? null)
        ? $_POST['csrf_token']
        : null;
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    if (!admin_csrf_verify($csrfValue)) {
        $_SESSION['flash_message'] = 'Your session expired. Refresh and try again.';
    } elseif ($action === 'add') {
        $category = is_string($_POST['category'] ?? null)
            ? trim($_POST['category'])
            : '';
        $factText = is_string($_POST['fact_text'] ?? null)
            ? trim($_POST['fact_text'])
            : '';
        $sourceReference = is_string($_POST['source_reference'] ?? null)
            ? trim($_POST['source_reference'])
            : '';
        $factVerified = ($_POST['fact_verified'] ?? null) === '1';
        $adminId = filter_var(
            $_SESSION['admin_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $factLength = function_exists('mb_strlen')
            ? mb_strlen($factText, 'UTF-8')
            : strlen($factText);

        if (
            !in_array($category, $topics, true) ||
            $factLength < 10 ||
            $factLength > 5000 ||
            $sourceReference === '' ||
            strlen($sourceReference) > 500 ||
            !$factVerified ||
            $adminId === false
        ) {
            $_SESSION['flash_message'] = 'Enter a valid topic, fact, and source reference.';
        } else {
            try {
                $insert = $pdo->prepare(
                    'INSERT INTO verified_college_facts
                        (category, fact_text, source_reference, created_by, verified_by)
                     VALUES
                        (:category, :fact_text, :source_reference, :created_by, :verified_by)'
                );
                $insert->execute([
                    ':category' => $category,
                    ':fact_text' => $factText,
                    ':source_reference' => $sourceReference,
                    ':created_by' => $adminId,
                    ':verified_by' => $adminId,
                ]);
                $_SESSION['flash_message'] = 'Verified college fact added.';
            } catch (PDOException $exception) {
                error_log('Verified college fact insert failed: ' . $exception->getCode());
                $_SESSION['flash_message'] = 'Unable to save the verified fact.';
            }
        }
    } elseif ($action === 'retire') {
        $factIdInput = $_POST['fact_id'] ?? null;
        $factId = is_string($factIdInput) || is_int($factIdInput)
            ? filter_var(
                $factIdInput,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            )
            : false;

        if ($factId === false) {
            $_SESSION['flash_message'] = 'Invalid verified fact.';
        } else {
            try {
                $retire = $pdo->prepare(
                    "UPDATE verified_college_facts
                     SET status = 'RETIRED'
                     WHERE id = :id
                       AND status = 'APPROVED'"
                );
                $retire->execute([':id' => $factId]);
                $_SESSION['flash_message'] = $retire->rowCount() === 1
                    ? 'Verified fact retired.'
                    : 'The fact was not found or was already retired.';
            } catch (PDOException $exception) {
                error_log('Verified college fact retirement failed: ' . $exception->getCode());
                $_SESSION['flash_message'] = 'Unable to retire the verified fact.';
            }
        }
    } else {
        $_SESSION['flash_message'] = 'Invalid action.';
    }

    header('Location: verified-facts.php');
    exit;
}

try {
    $statement = $pdo->query(
        'SELECT id, category, fact_text, source_reference, status, verified_at
         FROM verified_college_facts
         ORDER BY id DESC
         LIMIT 200'
    );
    $facts = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('Verified college facts query failed: ' . $exception->getCode());
    $facts = [];
    $message = 'Unable to load verified facts. Apply the college content validation migration first.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verified College Facts | AITAM Slack</title>
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
            <h2>Verified College Facts</h2>
            <p>Add facts only from approved college references. These facts are the sole source used to validate answer claims.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-info" role="status"><?= e($message) ?></div>
        <?php endif; ?>

        <section class="card p-3 p-md-4 mb-4">
            <h3 class="h5">Add verified fact</h3>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label" for="factCategory">Topic</label>
                    <select class="form-select" id="factCategory" name="category" required>
                        <option value="">Choose a topic</option>
                        <?php foreach ($topics as $topic): ?>
                            <option value="<?= e($topic) ?>"><?= e($topic) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="factText">Verified fact</label>
                    <textarea class="form-control" id="factText" name="fact_text" rows="4" minlength="10" maxlength="5000" required></textarea>
                    <div class="form-text">Maximum 5,000 characters. Do not enter unverified claims.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="factSource">Approved source or reference</label>
                    <input class="form-control" id="factSource" name="source_reference" maxlength="500" required>
                    <div class="form-text">Record the approved FAQ, college page, or verified reference used.</div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="factVerified" name="fact_verified" value="1" required>
                    <label class="form-check-label" for="factVerified">I have verified this fact against the approved source above.</label>
                </div>
                <button class="btn btn-primary" type="submit">Add verified fact</button>
            </form>
        </section>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>Topic</th>
                    <th>Fact and source</th>
                    <th>State</th>
                    <th>Verified</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($facts === []): ?>
                    <tr><td colspan="5" class="text-center">No verified facts have been added.</td></tr>
                <?php else: ?>
                    <?php foreach ($facts as $fact): ?>
                        <tr>
                            <td><?= e($fact['category']) ?></td>
                            <td class="text-break">
                                <div><?= nl2br(e($fact['fact_text'])) ?></div>
                                <small class="text-muted"><?= e($fact['source_reference']) ?></small>
                            </td>
                            <td><?= e($fact['status']) ?></td>
                            <td><?= e($fact['verified_at']) ?></td>
                            <td>
                                <?php if ($fact['status'] === 'APPROVED'): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="retire">
                                        <input type="hidden" name="fact_id" value="<?= e($fact['id']) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Retire</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
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
