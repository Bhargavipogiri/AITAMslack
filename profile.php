
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);

if ($userId === false || $userId < 1) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/db.php';

try {
    $userStmt = $pdo->prepare(
        'SELECT full_name, email, department, role, points, profile_image
         FROM users
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $userStmt->execute([':user_id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $exception) {
    error_log('Profile load error: ' . $exception->getMessage());
    http_response_code(500);
    exit('Unable to load your profile right now. Please try again later.');
}

if (!$user) {
    unset($_SESSION['user_id']);
    header('Location: login.php');
    exit;
}

$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['department'] = $user['department'];
$_SESSION['role'] = $user['role'];
$_SESSION['profile_image'] = $user['profile_image'] ?? '';

$formatActivityDate = static function ($value): string {
    if (!is_string($value) || $value === '') {
        return '';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? '' : date('M j, Y', $timestamp);
};

/* =====================================
   PROFILE QUESTIONS AND ANSWERS
===================================== */

$defaultQuestions = [];

/* =====================================
   FETCH MY QUESTIONS
===================================== */

try {
    $questionsStmt = $pdo->prepare(
        "SELECT id, question_title, question_description, category, created_at
         FROM questions
         WHERE user_id = :user_id
           AND publication_status = 'APPROVED'
         ORDER BY created_at DESC"
    );

    $questionsStmt->execute([':user_id' => $userId]);

    foreach ($questionsStmt->fetchAll(PDO::FETCH_ASSOC) as $question) {
        $defaultQuestions[] = [
            'id' => 'question-' . (int)$question['id'],
            'type' => 'question',
            'title' => (string)($question['question_title'] ?? ''),
            'description' => (string)($question['question_description'] ?? ''),
            'answer' => '',
            'category' => (string)($question['category'] ?? ''),
            'likes' => 0,
            'replies' => 0,
            'date' => $formatActivityDate($question['created_at'] ?? null),
        ];
    }

} catch (PDOException $exception) {
    try {
        $questionsStmt = $pdo->prepare(
            "SELECT q.id,
                    q.title AS question_title,
                    q.body AS question_description,
                    c.name AS category,
                    q.created_at
             FROM questions q
             LEFT JOIN categories c ON c.id = q.category_id
             WHERE q.user_id = :user_id
               AND q.publication_status = 'APPROVED'
             ORDER BY q.created_at DESC"
        );

        $questionsStmt->execute([':user_id' => $userId]);

        foreach ($questionsStmt->fetchAll(PDO::FETCH_ASSOC) as $question) {
            $defaultQuestions[] = [
                'id' => 'question-' . (int)$question['id'],
                'type' => 'question',
                'title' => (string)($question['question_title'] ?? ''),
                'description' => (string)($question['question_description'] ?? ''),
                'answer' => '',
                'category' => (string)($question['category'] ?? ''),
                'likes' => 0,
                'replies' => 0,
                'date' => $formatActivityDate($question['created_at'] ?? null),
            ];
        }

    } catch (PDOException $fallbackException) {
        error_log(
            'Profile question history unavailable: '
            . $exception->getMessage()
            . ' | Alternate schema: '
            . $fallbackException->getMessage()
        );
    }
}

/* =====================================
   FETCH MY ANSWERS
   answers.status:
   Published / Pending / Hidden
===================================== */

try {
    $answersStmt = $pdo->prepare(
        "SELECT
            a.id,
            a.answer_text,
            CASE a.status
                WHEN 'Published' THEN 'APPROVED'
                WHEN 'Pending' THEN 'PENDING'
                ELSE 'REJECTED'
            END AS publication_status,
            a.created_at,
            q.id AS question_id,
            q.question_title,
            q.question_description,
            q.category
         FROM answers a
         INNER JOIN questions q ON q.id = a.question_id
         WHERE a.user_id = :user_id
         ORDER BY a.created_at DESC"
    );

    $answersStmt->execute([':user_id' => $userId]);

    foreach ($answersStmt->fetchAll(PDO::FETCH_ASSOC) as $answer) {
        $defaultQuestions[] = [
            'id' => 'answer-' . (int)$answer['id'],
            'type' => 'answer',
            'title' => (string)($answer['question_title'] ?? ''),
            'description' => (string)($answer['question_description'] ?? ''),
            'answer' => (string)($answer['answer_text'] ?? ''),
            'category' => (string)($answer['category'] ?? ''),
            'publication_status' => strtoupper(
                (string)($answer['publication_status'] ?? 'PENDING')
            ),
            'likes' => 0,
            'replies' => 0,
            'date' => $formatActivityDate($answer['created_at'] ?? null),
        ];
    }

} catch (PDOException $exception) {
    try {
        $answersStmt = $pdo->prepare(
            "SELECT
                a.id,
                a.answer_text,
                CASE a.status
                    WHEN 'Published' THEN 'APPROVED'
                    WHEN 'Pending' THEN 'PENDING'
                    ELSE 'REJECTED'
                END AS publication_status,
                a.created_at,
                q.id AS question_id,
                q.title AS question_title,
                q.body AS question_description,
                c.name AS category
             FROM answers a
             INNER JOIN questions q ON q.id = a.question_id
             LEFT JOIN categories c ON c.id = q.category_id
             WHERE a.user_id = :user_id
             ORDER BY a.created_at DESC"
        );

        $answersStmt->execute([':user_id' => $userId]);

        foreach ($answersStmt->fetchAll(PDO::FETCH_ASSOC) as $answer) {
            $defaultQuestions[] = [
                'id' => 'answer-' . (int)$answer['id'],
                'type' => 'answer',
                'title' => (string)($answer['question_title'] ?? ''),
                'description' => (string)($answer['question_description'] ?? ''),
                'answer' => (string)($answer['answer_text'] ?? ''),
                'category' => (string)($answer['category'] ?? ''),
                'publication_status' => strtoupper(
                    (string)($answer['publication_status'] ?? 'PENDING')
                ),
                'likes' => 0,
                'replies' => 0,
                'date' => $formatActivityDate($answer['created_at'] ?? null),
            ];
        }

    } catch (PDOException $fallbackException) {
        error_log(
            'Profile answer history unavailable: '
            . $exception->getMessage()
            . ' | Alternate schema: '
            . $fallbackException->getMessage()
        );
    }
}

/* =====================================
   FETCH QUESTIONS FROM OTHER USERS
   ALL APPROVED QUESTIONS EXCEPT OWN
===================================== */

$availableQuestions = [];

try {
    $availableQuestionsStmt = $pdo->prepare(
        "SELECT
            q.id,
            q.user_id,
            q.question_title,
            q.question_description,
            q.category,
            q.created_at,
            u.full_name AS author_name
         FROM questions q
         LEFT JOIN users u ON u.user_id = q.user_id
         WHERE q.publication_status = 'APPROVED'
           AND q.user_id <> :user_id
         ORDER BY q.created_at DESC"
    );

    $availableQuestionsStmt->execute([
        ':user_id' => $userId
    ]);

    foreach ($availableQuestionsStmt->fetchAll(PDO::FETCH_ASSOC) as $question) {
        $availableQuestions[] = [
            'id' => (int)$question['id'],
            'title' => (string)($question['question_title'] ?? ''),
            'description' => (string)($question['question_description'] ?? ''),
            'category' => (string)($question['category'] ?? ''),
            'author' => (string)($question['author_name'] ?? 'Student'),
            'date' => $formatActivityDate($question['created_at'] ?? null),
        ];
    }

} catch (PDOException $exception) {
    error_log('Available questions fetch error: ' . $exception->getMessage());
}

/* =====================================
   DEFAULT PROFILE
===================================== */

$defaultProfile = [
    'name' => (string)$user['full_name'],
    'email' => (string)$user['email'],
    'department' => (string)($user['department'] ?? ''),
    'role' => (string)($user['role'] ?? ''),
    'points' => (float)($user['points'] ?? 0),
    'profile_image' => '',
];

$storedProfileImage = trim((string)($user['profile_image'] ?? ''));

$profileImageFile = $storedProfileImage !== ''
    ? ltrim(
        str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedProfileImage),
        DIRECTORY_SEPARATOR
    )
    : '';

if (
    $profileImageFile !== ''
    && is_file(__DIR__ . DIRECTORY_SEPARATOR . $profileImageFile)
) {
    $defaultProfile['profile_image'] = $storedProfileImage;
}

$displayProfileName = trim($defaultProfile['name']);

if (strcasecmp((string)$defaultProfile['role'], 'faculty') === 0) {
    $displayProfileName = trim(
        preg_replace('/^Sri\s+/iu', '', $displayProfileName) ?? $displayProfileName
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>AITAM Slack | My Profile</title>

<link rel="stylesheet" href="assets/css/root.css">

<style>

/* =====================================
   GLOBAL STYLES
===================================== */

:root {
  --navy:var(--color-navy-950);
  --blue:var(--color-blue-800);
  --secondary:var(--color-blue-500);
  --cyan:var(--color-cyan-100);
  --bg:var(--bg-app);
  --surface:var(--bg-surface);
  --muted-bg:var(--bg-surface-muted);
  --text:var(--text-primary);
  --secondary-text:var(--text-secondary);
  --muted:var(--text-muted);
  --border:var(--border-default);
  --success:var(--color-success);
  --shadow:var(--shadow-md);
}

* {
  box-sizing:border-box;
}

body {
  margin:0;
  font-family:Inter,"Segoe UI",Arial,sans-serif;
  background:var(--bg);
  color:var(--text);
}

button,input,textarea,select {
  font:inherit;
}

button {
  cursor:pointer;
}

button:focus-visible,
input:focus-visible,
textarea:focus-visible,
select:focus-visible {
  outline:3px solid rgba(73,136,196,.35);
  outline-offset:3px;
}

/* =====================================
   PAGE
===================================== */

.page {
  width:min(1000px,calc(100% - 40px));
  margin:40px auto 60px;
  max-width:1080px;
}

/* =====================================
   PROFILE CARD
===================================== */

.profile-card {
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:24px;
  overflow:hidden;
  box-shadow:var(--shadow);
  transition:box-shadow .25s ease, transform .25s ease;
}

.profile-card:hover {
  box-shadow:0 16px 42px rgba(15,40,84,.12);
}

.profile-cover {
  height:190px;
  background:
    radial-gradient(circle at 85% 20%,rgba(189,232,245,.45),transparent 25%),
    radial-gradient(circle at 15% 100%,rgba(73,136,196,.4),transparent 35%),
    linear-gradient(125deg,#0F2854,#1C4D8D 60%,#4988C4);
  position:relative;
  overflow:hidden;
}

.profile-cover::before,
.profile-cover::after {
  content:"";
  position:absolute;
  border:1px solid rgba(255,255,255,.18);
  border-radius:50%;
  width:310px;
  height:310px;
  right:8%;
  top:-205px;
}

.profile-cover::after {
  width:210px;
  height:210px;
  right:24%;
  top:-125px;
}

.cover-decoration {
  position:absolute;
  right:30px;
  bottom:8px;
  color:rgba(255,255,255,.18);
  font-size:clamp(70px,10vw,118px);
  font-weight:800;
  letter-spacing:-8px;
}

.profile-main {
  padding:0 38px 35px;
}

.profile-top {
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:20px;
}

.avatar-wrap {
  position:relative;
  width:130px;
  height:130px;
  margin-top:-62px;
  flex-shrink:0;
}

.avatar {
  width:130px;
  height:130px;
  border-radius:50%;
  border:5px solid var(--surface);
  background:linear-gradient(145deg,#BDE8F5,#4988C4);
  color:#0F2854;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:43px;
  font-weight:750;
  overflow:hidden;
}

.avatar img {
  width:100%;
  height:100%;
  object-fit:cover;
}

.photo-upload {
  position:absolute;
  bottom:4px;
  right:0;
  width:36px;
  height:36px;
  border-radius:50%;
  border:3px solid var(--surface);
  background:var(--blue);
  color:white;
  font-size:25px;
  line-height:1;
  display:grid;
  place-items:center;
}

.photo-upload:hover {
  background:var(--navy);
}

.photo-menu {
  position:absolute;
  z-index:30;
  bottom:48px;
  right:-82px;
  width:170px;
  padding:6px;
  border:1px solid var(--border);
  border-radius:10px;
  background:var(--surface);
  box-shadow:0 10px 30px rgba(15,23,42,.18);
}

.photo-menu[hidden] {
  display:none;
}

.photo-menu button {
  width:100%;
  border:0;
  border-radius:7px;
  padding:9px 10px;
  background:transparent;
  color:var(--text-primary);
  text-align:left;
  cursor:pointer;
  font-size:13px;
  font-weight:700;
}

.photo-menu button:hover {
  background:var(--bg-app);
}

.photo-menu .remove-photo {
  color:var(--color-danger);
}

#photoInput {
  display:none;
}

/* =====================================
   PROFILE ACTIONS
===================================== */

.profile-actions {
  display:flex;
  gap:10px;
  padding-top:20px;
  flex-wrap:wrap;
}

.btn {
  border:1px solid var(--border);
  border-radius:10px;
  padding:11px 17px;
  font-size:14px;
  font-weight:650;
  background:var(--surface);
  color:var(--text);
  transition:.2s;
  box-shadow:0 2px 5px rgba(15,40,84,.04);
}

.btn:hover {
  transform:translateY(-1px);
  border-color:var(--secondary);
}

.btn-primary {
  background:var(--blue);
  color:white;
  border-color:var(--blue);
  box-shadow:0 7px 16px rgba(28,77,141,.2);
}

.btn-primary:hover {
  background:var(--navy);
}

/* =====================================
   PROFILE DETAILS
===================================== */

.profile-details {
  margin-top:20px;
}

.profile-name {
  font-size:30px;
  font-weight:750;
  letter-spacing:-1px;
  margin:0 0 8px;
}

.profile-subtitle {
  color:var(--secondary-text);
  font-size:14px;
  margin-bottom:15px;
  display:flex;
  align-items:center;
  gap:9px;
  flex-wrap:wrap;
}

.profile-role-badge {
  font-size:11px;
  font-weight:750;
  letter-spacing:.3px;
  color:#14734f;
  background:#e6f7ef;
  border:1px solid #c6eedb;
  border-radius:999px;
  padding:4px 9px;
}

.profile-meta {
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-top:20px;
  color:var(--secondary-text);
  font-size:13px;
}

.meta-item {
  display:flex;
  align-items:center;
  gap:7px;
  padding:9px 12px;
  border:1px solid var(--border);
  border-radius:999px;
  background:var(--surface);
}

.meta-item span:first-child {
  font-size:15px;
  color:var(--blue);
}

/* =====================================
   PROFILE COMPLETION
===================================== */

.profile-progress {
  margin-top:23px;
  max-width:450px;
}

.progress-top {
  display:flex;
  justify-content:space-between;
  gap:12px;
  color:var(--muted);
  font-size:12px;
  margin-bottom:8px;
}

.progress-track {
  height:7px;
  border-radius:99px;
  overflow:hidden;
  background:#e8eff6;
}

.progress-fill {
  height:100%;
  width:0;
  border-radius:inherit;
  background:linear-gradient(90deg,var(--secondary),#68c3d8);
  transition:width .35s ease;
}

/* =====================================
   QUICK ACTIONS
===================================== */

.profile-quick-actions {
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:15px;
}

.quick-action {
  border:1px solid var(--border);
  background:var(--surface);
  color:var(--blue);
  border-radius:9px;
  padding:8px 11px;
  font-size:12px;
  font-weight:700;
  transition:.2s;
}

.quick-action:hover {
  background:#eef6fc;
  border-color:#b8d4ed;
  transform:translateY(-1px);
}

/* =====================================
   CONTENT
===================================== */

.content {
  margin-top:26px;
  display:grid;
  gap:24px;
}

.card {
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:20px;
  padding:28px;
  box-shadow:var(--shadow);
  transition:box-shadow .25s ease, transform .25s ease;
}

.card:hover {
  box-shadow:0 16px 42px rgba(15,40,84,.12);
}

.section-title {
  font-size:19px;
  font-weight:750;
  margin:0 0 24px;
  display:flex;
  align-items:center;
  gap:10px;
}

.section-title::before {
  content:"";
  width:5px;
  height:20px;
  border-radius:9px;
  background:linear-gradient(var(--secondary),var(--cyan));
}

/* =====================================
   PROFILE INFORMATION
===================================== */

.info-grid {
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:25px 20px;
}

.info-item {
  min-width:0;
  padding:14px 15px;
  border:1px solid var(--border);
  border-radius:13px;
  background:var(--surface);
  transition:transform .18s,border-color .18s;
}

.info-item:hover {
  transform:translateY(-2px);
  border-color:#b9d4eb;
}

.info-label {
  display:block;
  color:var(--muted);
  font-size:10px;
  font-weight:650;
  margin-bottom:8px;
  text-transform:uppercase;
  letter-spacing:.55px;
}

.info-value {
  color:var(--text);
  font-size:14px;
  font-weight:600;
  overflow-wrap:anywhere;
  line-height:1.6;
}

.info-value a {
  color:var(--blue);
  text-decoration:none;
}

.info-value a:hover {
  text-decoration:underline;
}

/* =====================================
   PROFILE TABS
===================================== */

.profile-tabs {
  display:flex;
  gap:4px;
  padding:5px;
  border:1px solid var(--border);
  border-radius:14px;
  background:var(--muted-bg);
  margin:0 0 24px;
  overflow-x:auto;
}

.profile-tab {
  border:0;
  border-radius:10px;
  background:transparent;
  color:var(--secondary-text);
  padding:12px 17px;
  font-weight:700;
  white-space:nowrap;
  transition:background .2s,color .2s,box-shadow .2s;
}

.profile-tab.active {
  background:var(--surface);
  color:var(--blue);
  box-shadow:0 3px 10px rgba(15,40,84,.09);
}

/* =====================================
   ACTIVITY HEADER
===================================== */

.community-panel {
  color:var(--secondary-text);
  line-height:1.7;
}

.questions-header {
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:15px;
  margin-bottom:20px;
}

.questions-header .section-title {
  margin:0;
}

.question-count {
  background:var(--muted-bg);
  color:var(--blue);
  font-size:12px;
  font-weight:700;
  padding:7px 12px;
  border-radius:8px;
}

/* =====================================
   QUESTION ITEMS
===================================== */

.question-item {
  padding:20px;
  margin:0 -2px;
  border:1px solid transparent;
  border-bottom-color:var(--border);
  border-radius:14px;
  transition:background .2s,border-color .2s;
}

.question-item:hover {
  background:var(--surface);
  border-color:var(--border);
}

.question-item:first-child {
  padding-top:0;
}

.question-item:last-child {
  border-bottom:none;
  padding-bottom:0;
}

.question-category {
  display:inline-flex;
  padding:5px 9px;
  border-radius:999px;
  background:var(--muted-bg);
  color:var(--blue);
  font-size:11px;
  font-weight:750;
  text-transform:uppercase;
  letter-spacing:.7px;
}

.activity-meta {
  display:flex;
  align-items:center;
  flex-wrap:wrap;
  gap:8px;
}

.question-title {
  font-size:16px;
  line-height:1.5;
  font-weight:700;
  margin:12px 0 10px;
}

.question-description {
  font-size:14px;
  color:var(--secondary-text);
  line-height:1.7;
  margin:0 0 16px;
  white-space:pre-wrap;
  overflow-wrap:anywhere;
}

.question-answer {
  margin:14px 0 16px;
  padding:14px 16px;
  border-left:4px solid var(--secondary);
  border-radius:0 10px 10px 0;
  background:var(--muted-bg);
  color:var(--text);
  font-size:14px;
  line-height:1.7;
  white-space:pre-wrap;
}

.question-answer strong {
  display:block;
  margin-bottom:5px;
  color:var(--blue);
}

/* =====================================
   ANSWER STATUS
===================================== */

.answer-status {
  display:inline-flex;
  padding:5px 9px;
  border-radius:999px;
  font-size:11px;
  font-weight:700;
}

.answer-status-published {
  background:#dcfce7;
  color:#166534;
}

.answer-status-pending {
  background:#fef3c7;
  color:#92400e;
}

.answer-status-rejected {
  background:#fee2e2;
  color:#991b1b;
}

/* =====================================
   QUESTION STATS
===================================== */

.question-stats {
  display:flex;
  align-items:center;
  gap:20px;
  color:var(--muted);
  font-size:12px;
  flex-wrap:wrap;
}

.stat-button {
  border:none;
  background:var(--muted-bg);
  color:inherit;
  padding:7px 10px;
  border-radius:8px;
  font-size:12px;
  transition:background .2s;
}

.stat-button:hover,
.stat-button.liked {
  color:#C2414B;
}

.question-date {
  margin-left:auto;
}

/* =====================================
   ANSWER QUESTION BUTTON
===================================== */

.answer-question-button {
  display:inline-block;
  margin-top:15px;
  padding:10px 17px;
  background:var(--blue);
  color:#fff;
  border:1px solid var(--blue);
  border-radius:9px;
  text-decoration:none;
  font-size:13px;
  font-weight:700;
  transition:.2s ease;
}

.answer-question-button:hover {
  background:var(--navy);
  transform:translateY(-2px);
}

.answer-author {
  color:var(--muted);
  font-size:12px;
  margin-top:10px;
}

/* =====================================
   EMPTY STATE
===================================== */

.empty-state {
  padding:35px 15px;
  text-align:center;
  color:var(--muted);
  font-size:14px;
  line-height:1.8;
  border:1px dashed var(--border);
  border-radius:15px;
  background:var(--surface);
}

.empty-icon {
  font-size:30px;
  margin-bottom:10px;
}

/* =====================================
   EDIT PROFILE MODAL
===================================== */

.modal-overlay {
  position:fixed;
  inset:0;
  z-index:100;
  display:none;
  align-items:center;
  justify-content:center;
  padding:20px;
  background:rgba(5,20,43,.65);
  backdrop-filter:blur(5px);
  opacity:0;
  transition:opacity .18s ease;
}

.modal-overlay.show {
  display:flex;
  opacity:1;
}

.modal {
  width:min(650px,100%);
  max-height:90vh;
  overflow-y:auto;
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:22px;
  padding:30px;
  box-shadow:0 25px 70px rgba(0,0,0,.2);
  animation:modalIn .2s ease-out;
}

@keyframes modalIn {
  from {
    opacity:0;
    transform:translateY(10px) scale(.985);
  }

  to {
    opacity:1;
    transform:translateY(0) scale(1);
  }
}

.modal-header {
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:24px;
}

.modal-header h2 {
  font-size:22px;
  margin:0;
}

.close-btn {
  width:35px;
  height:35px;
  border-radius:10px;
  border:1px solid var(--border);
  background:var(--muted-bg);
  color:var(--text);
  font-size:20px;
}

.form-grid {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:18px;
}

.form-group {
  display:flex;
  flex-direction:column;
  gap:8px;
}

.form-group.full {
  grid-column:1/-1;
}

.form-group label {
  font-size:13px;
  font-weight:650;
  color:var(--secondary-text);
}

.form-group input,
.form-group textarea,
.form-group select {
  width:100%;
  padding:12px 13px;
  border:1px solid var(--border);
  border-radius:10px;
  background:var(--bg);
  color:var(--text);
  outline:none;
}

.form-group textarea {
  resize:vertical;
  min-height:90px;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  border-color:var(--secondary);
  background:var(--surface);
  box-shadow:0 0 0 4px rgba(73,136,196,.12);
}

.modal-actions {
  display:flex;
  justify-content:flex-end;
  gap:12px;
  margin-top:25px;
}

/* =====================================
   TOAST
===================================== */

.toast {
  position:fixed;
  bottom:25px;
  left:50%;
  transform:translate(-50%,100px);
  background:var(--navy);
  color:white;
  padding:13px 22px;
  border-radius:12px;
  font-size:13px;
  z-index:200;
  box-shadow:var(--shadow);
  transition:transform .3s;
  display:flex;
  align-items:center;
  gap:9px;
}

.toast::before {
  content:"✓";
  display:grid;
  place-items:center;
  width:21px;
  height:21px;
  border-radius:50%;
  background:#ffffff24;
  font-weight:800;
}

.toast.show {
  transform:translate(-50%,0);
}

.toast.error {
  background:var(--color-danger);
}

.toast.error::before {
  content:"!";
}

/* =====================================
   RESPONSIVE DESIGN
===================================== */

@media(max-width:700px) {

  .page {
    width:calc(100% - 24px);
    margin:18px auto 35px;
  }

  .profile-cover {
    height:145px;
  }

  .profile-main {
    padding:0 20px 23px;
  }

  .avatar-wrap,
  .avatar {
    width:100px;
    height:100px;
  }

  .avatar-wrap {
    margin-top:-48px;
  }

  .avatar {
    font-size:34px;
  }

  .photo-upload {
    width:32px;
    height:32px;
    font-size:22px;
  }

  .profile-name {
    font-size:25px;
  }

  .profile-top {
    gap:10px;
  }

  .profile-actions {
    padding-top:14px;
    gap:7px;
  }

  .btn {
    padding:10px 12px;
    font-size:12px;
  }

  .profile-meta {
    gap:12px;
  }

  .card {
    padding:20px;
  }

  .info-grid {
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:22px 14px;
  }

  .form-grid {
    grid-template-columns:1fr;
  }

  .form-group.full {
    grid-column:auto;
  }

  .modal {
    padding:22px;
  }

  .question-date {
    margin-left:0;
  }

  .profile-progress {
    max-width:none;
  }

  .profile-tabs {
    width:100%;
  }

  .profile-tab {
    flex:1;
    padding:11px 8px;
    font-size:13px;
  }
}

@media(max-width:400px) {

  .profile-top {
    flex-direction:column;
  }

  .profile-actions {
    padding-top:0;
  }

  .info-grid {
    grid-template-columns:1fr;
  }
}

@media(prefers-reduced-motion:reduce) {
  *,
  *::before,
  *::after {
    animation-duration:.01ms !important;
    transition-duration:.01ms !important;
  }
}

</style>

</head>

<body>

<?php include __DIR__ . '/nav.php'; ?>

<main class="page">

  <!-- PROFILE -->

  <section class="profile-card">

    <div class="profile-cover">
      <div class="cover-decoration">AITAM</div>
    </div>

    <div class="profile-main">

      <div class="profile-top">

        <div class="avatar-wrap">

          <div class="avatar" id="profileAvatar"></div>

          <button class="photo-upload"
                  id="photoButton"
                  type="button"
                  aria-label="Upload profile picture">+</button>

          <div class="photo-menu" id="photoMenu" role="menu" hidden>

            <button type="button" id="updatePhotoButton" role="menuitem">
              Update Photo
            </button>

            <button type="button"
                    id="removePhotoButton"
                    class="remove-photo"
                    role="menuitem">
              Remove Photo
            </button>

          </div>

          <input type="file"
                 id="photoInput"
                 accept="image/jpeg,image/png,image/webp">

        </div>

        <div class="profile-actions">

          <button class="btn btn-primary" id="editButton" type="button">
            ✎ Edit Profile
          </button>

        </div>

      </div>

      <div class="profile-details">

        <h1 class="profile-name" id="profileName">
          <?= htmlspecialchars($displayProfileName, ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <div class="profile-subtitle" id="profileSubtitle">
          <span id="profileSubtitleText"></span>
          <span class="profile-role-badge" id="profileRoleBadge"></span>
        </div>

        <div class="profile-meta">

          <div class="meta-item">
            <span>⌖</span>
            <span id="profileDepartment">Department not provided</span>
          </div>

          <div class="meta-item">
            <span>▣</span>
            <span id="profileRole">Role not provided</span>
          </div>

          <div class="meta-item">
            <span>✉</span>
            <span id="profileEmail">Email not provided</span>
          </div>

        </div>

        <div class="profile-progress" aria-label="Profile completion">

          <div class="progress-top">
            <span>Profile completeness</span>
            <strong id="completionText">0%</strong>
          </div>

          <div class="progress-track">
            <div class="progress-fill" id="completionFill"></div>
          </div>

        </div>

        <div class="profile-quick-actions">

          <button class="quick-action" id="copyEmailButton" type="button">
            ✉ Copy email
          </button>

          <button class="quick-action" id="viewQuestionsButton" type="button">
            ▤ View my questions
          </button>

        </div>

      </div>

    </div>

  </section>

  <div class="content">

    <!-- PROFILE INFORMATION -->

    <section class="card">

      <h2 class="section-title">Profile Information</h2>

      <div class="info-grid" id="infoGrid"></div>

    </section>

    <!-- PROFILE ACTIVITY -->

    <section class="card">

      <nav class="profile-tabs"
           role="tablist"
           aria-label="My profile activity">

        <button class="profile-tab active"
                type="button"
                role="tab"
                aria-selected="true"
                data-tab="questions">
          My Questions
        </button>

        <button class="profile-tab"
                type="button"
                role="tab"
                aria-selected="false"
                data-tab="answers">
          My Answers
        </button>

        <button class="profile-tab"
                type="button"
                role="tab"
                aria-selected="false"
                data-tab="answer-questions">
          Answer Questions
        </button>

        <button class="profile-tab"
                type="button"
                role="tab"
                aria-selected="false"
                data-tab="community">
          Community
        </button>

      </nav>

      <div class="questions-header">

        <h2 class="section-title" id="activityTitle">
          My Questions
        </h2>

        <span class="question-count" id="questionCount">
          0 Questions
        </span>

      </div>

      <div id="questionsList" role="tabpanel"></div>

      <div id="communityPanel" class="community-panel" hidden>

        <h3>Community</h3>

        <p>
          Connect with the AITAM Slack community, join discussions,
          and share helpful resources.
        </p>

        <p>
          Your community posts and activity will appear here
          when connected to the community feed.
        </p>

      </div>

    </section>

  </div>

</main>

<!-- EDIT PROFILE MODAL -->

<div class="modal-overlay" id="editModal">

  <div class="modal"
       role="dialog"
       aria-modal="true"
       aria-labelledby="editTitle">

    <div class="modal-header">

      <h2 id="editTitle">Edit Your Profile</h2>

      <button class="close-btn"
              id="closeModal"
              type="button"
              aria-label="Close">
        ×
      </button>

    </div>

    <form id="editForm">

      <div class="form-grid" id="editFields"></div>

      <p class="password-note"
         style="font-size:12px;color:var(--secondary-text);margin:12px 0 0;line-height:1.5;">

        Leave the password blank to keep your current password.
        New passwords must be at least 8 characters.

      </p>

      <div class="modal-actions">

        <button class="btn" id="cancelEdit" type="button">
          Cancel
        </button>

        <button class="btn btn-primary" type="submit">
          Save Changes
        </button>

      </div>

    </form>

  </div>

</div>

<div class="toast" id="toast" role="status"></div>

<script>

const $ = id => document.getElementById(id);

const defaultProfile = <?= json_encode(
    $defaultProfile,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
) ?>;

const defaultQuestions = <?= json_encode(
    $defaultQuestions,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
) ?>;

const availableQuestions = <?= json_encode(
    $availableQuestions,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
) ?>;

const csrfToken = <?= json_encode(
    $_SESSION['csrf_token'],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

let profile = { ...defaultProfile };
let questions = defaultQuestions;
let currentPhoto = profile.profile_image || "";
let toastTimer;

/* =====================================
   HELPER FUNCTIONS
===================================== */

function escapeHTML(value) {
  return String(value ?? "").replace(/[&<>"']/g, char => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  })[char]);
}

function safeURL(value) {
  try {
    const url = new URL(value, window.location.href);

    return ["https:", "http:"].includes(url.protocol)
      ? url.href
      : "";

  } catch {
    return "";
  }
}

function displayName() {
  const name = String(profile.name || "").trim();

  return String(profile.role || "").toLowerCase() === "faculty"
    ? name.replace(/^Sri\s+/i, "")
    : name;
}

function roleLabel(role) {
  const labels = {
    fresher: "Fresher",
    student: "Student",
    faculty: "Faculty",
    alumni: "Alumni"
  };

  return labels[String(role || "").toLowerCase()] || "Role not provided";
}

function initials(name) {
  const initialName = String(name || "").trim().split(/\s+/).slice(0,2);

  return initialName
    .map(word => word[0]?.toUpperCase() || "")
    .join("") || "S";
}

/* =====================================
   AVATAR
===================================== */

function renderAvatar() {

  const avatar = $("profileAvatar");

  avatar.innerHTML = "";

  const imageURL = safeURL(currentPhoto);

  if (imageURL) {

    const image = document.createElement("img");

    image.src = imageURL;
    image.alt = "";

    image.addEventListener("error", () => {

      currentPhoto = "";
      profile.profile_image = "";

      renderAvatar();
      syncNavbarProfile();

    }, { once: true });

    avatar.appendChild(image);

  } else {

    avatar.textContent = initials(profile.name);

  }
}

/* =====================================
   PROFILE INFORMATION
===================================== */

function renderInfo() {

  const fields = [
    ["Full Name", displayName()],
    ["Email Address", profile.email],
    ["Department / Branch", profile.department],
    ["Role", roleLabel(profile.role)],
    ["Points", Number(profile.points || 0).toLocaleString()]
  ];

  $("infoGrid").innerHTML = "";

  fields.forEach(([label, value]) => {

    const item = document.createElement("div");

    item.className = "info-item";

    item.innerHTML = `
      <span class="info-label">${escapeHTML(label)}</span>
      <div class="info-value">${escapeHTML(value || "Not provided")}</div>
    `;

    $("infoGrid").appendChild(item);

  });

}

/* =====================================
   RENDER PROFILE
===================================== */

function renderProfile() {

  $("profileName").textContent = displayName() || "Profile";

  $("profileSubtitleText").textContent =
    `B.Tech · ${profile.department || "Department not provided"}`;

  $("profileRoleBadge").textContent = roleLabel(profile.role);

  $("profileDepartment").textContent =
    profile.department || "Department not provided";

  $("profileRole").textContent = roleLabel(profile.role);

  $("profileEmail").textContent =
    profile.email || "Email not provided";

  renderAvatar();
  renderInfo();

  const completed = [
    profile.name,
    profile.email,
    profile.department,
    profile.role
  ].filter(value => String(value || "").trim()).length;

  const percent = Math.round(completed / 4 * 100);

  $("completionText").textContent = `${percent}%`;

  $("completionFill").style.width = `${percent}%`;

  $("copyEmailButton").disabled = !profile.email;

  $("copyEmailButton").style.opacity =
    profile.email ? "1" : ".55";

}

/* =====================================
   NAVBAR PROFILE
===================================== */

function syncNavbarAvatar(photoUrl, fullName) {

  const trigger = document.querySelector("#profileMenuBtn");

  if (!trigger) return;

  const existingAvatar = trigger.querySelector(
    ".nav-avatar-img, .nav-avatar-fallback"
  );

  if (existingAvatar) existingAvatar.remove();

  const avatarURL = safeURL(photoUrl || "");

  if (avatarURL) {

    const image = document.createElement("img");

    image.className = "nav-avatar-img";
    image.src = avatarURL;
    image.alt = String(fullName || "").trim();

    image.onerror = function () {

      this.onerror = null;

      const fallback = document.createElement("span");

      fallback.className = "nav-avatar-fallback";

      fallback.setAttribute("aria-hidden", "true");

      fallback.textContent = initials(fullName);

      this.replaceWith(fallback);

    };

    trigger.prepend(image);

  } else {

    const fallback = document.createElement("span");

    fallback.className = "nav-avatar-fallback";

    fallback.setAttribute("aria-hidden", "true");

    fallback.textContent = initials(fullName);

    trigger.prepend(fallback);

  }

}

function syncNavbarProfile() {

  const name = displayName();

  document.querySelectorAll(".nav-profile-wrapper").forEach(wrapper => {

    const nameElement = wrapper.querySelector(".nav-user-name");

    const roleElement = wrapper.querySelector(".nav-user-role");

    if (nameElement) {
      nameElement.textContent = name;
    }

    if (roleElement) {
      roleElement.textContent = roleLabel(profile.role);
    }

  });

  syncNavbarAvatar(profile.profile_image || "", name);

}

/* =====================================
   PROFILE ACTIVITY TABS
===================================== */

let activeActivityTab = "questions";

function setActivityTab(tab) {

  activeActivityTab = tab;

  document.querySelectorAll(".profile-tab").forEach(button => {

    const selected = button.dataset.tab === tab;

    button.classList.toggle("active", selected);

    button.setAttribute("aria-selected", String(selected));

  });

  const titles = {
    questions: "My Questions",
    answers: "My Answers",
    "answer-questions": "Questions You Can Answer",
    community: "Community"
  };

  $("activityTitle").textContent =
    titles[tab] || "My Questions";

  $("questionsList").hidden = tab === "community";

  $("communityPanel").hidden = tab !== "community";

  $("questionsList").style.display =
    tab === "community" ? "none" : "";

  renderQuestions();

}

/* =====================================
   RENDER ANSWERABLE QUESTIONS
===================================== */

function renderAnswerableQuestions() {

  const container = $("questionsList");

  container.innerHTML = "";

  $("questionCount").textContent =
    `${availableQuestions.length} ${
      availableQuestions.length === 1 ? "Question" : "Questions"
    }`;

  if (!availableQuestions.length) {

    container.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">?</div>

        <div>No questions available to answer.</div>

        <p>
          Approved questions from other users will appear here.
        </p>
      </div>
    `;

    return;
  }

  availableQuestions.forEach(question => {

    const article = document.createElement("article");

    article.className = "question-item";

    article.innerHTML = `

      <div class="activity-meta">

        <div class="question-category">
          ${escapeHTML(question.category || "General")}
        </div>

      </div>

      <h3 class="question-title">
        ${escapeHTML(question.title)}
      </h3>

      <p class="question-description">
        ${escapeHTML(question.description)}
      </p>

      <div class="answer-author">
        Posted by ${escapeHTML(question.author)}
        ${question.date ? " · " + escapeHTML(question.date) : ""}
      </div>

      <a
        class="answer-question-button"
        href="answer.php?question_id=${encodeURIComponent(question.id)}"
      >
        Answer Question
      </a>

    `;

    container.appendChild(article);

  });

}

/* =====================================
   RENDER MY QUESTIONS AND ANSWERS
===================================== */

function renderQuestions() {

  const container = $("questionsList");

  container.innerHTML = "";

  if (activeActivityTab === "answer-questions") {

    renderAnswerableQuestions();

    return;
  }

  const visibleQuestions = questions.filter(question =>

    activeActivityTab === "answers"
      ? question.type === "answer"
      : question.type === "question"

  );

  $("questionCount").textContent =
    `${visibleQuestions.length} ${
      visibleQuestions.length === 1
        ? (activeActivityTab === "answers" ? "Answer" : "Question")
        : (activeActivityTab === "answers" ? "Answers" : "Questions")
    }`;

  if (!visibleQuestions.length) {

    container.innerHTML = `

      <div class="empty-state">

        <div class="empty-icon">?</div>

        <div>
          ${
            activeActivityTab === "answers"
              ? "No answers posted yet."
              : "No questions posted yet."
          }
        </div>

        <p>
          ${
            activeActivityTab === "answers"
              ? "Answers you post on AITAM Slack will appear here."
              : "Questions you post on AITAM Slack will appear here."
          }
        </p>

      </div>

    `;

    return;
  }

  visibleQuestions.forEach(question => {

    const article = document.createElement("article");

    article.className = "question-item";

    const publicationStatus =
      String(question.publication_status || "").toUpperCase();

    const answerStatus = {

      APPROVED: {
        label: "Published",
        className: "published"
      },

      PENDING: {
        label: "Pending review",
        className: "pending"
      },

      REJECTED: {
        label: "Not published",
        className: "rejected"
      }

    }[publicationStatus] || {
      label: "Pending review",
      className: "pending"
    };

    article.innerHTML = `

      <div class="activity-meta">

        <div class="question-category">
          ${escapeHTML(question.category || "General")}
        </div>

        ${
          question.type === "answer"
            ? `
              <span class="answer-status answer-status-${answerStatus.className}">
                ${escapeHTML(answerStatus.label)}
              </span>
            `
            : ""
        }

      </div>

      <h3 class="question-title">
        ${escapeHTML(question.title)}
      </h3>

      <p class="question-description">
        ${escapeHTML(question.description)}
      </p>

      ${
        activeActivityTab === "answers" && question.answer
          ? `
            <div class="question-answer">
              <strong>Answer</strong>
              ${escapeHTML(question.answer)}
            </div>
          `
          : ""
      }

      <div class="question-stats">

        <button class="stat-button like-button"
                data-id="${escapeHTML(question.id)}"
                aria-label="Like question">

          ♡ <span class="like-count">${question.likes || 0}</span> Likes

        </button>

        <span>◯ ${question.replies || 0} Replies</span>

        <span class="question-date">
          ${escapeHTML(question.date || "")}
        </span>

      </div>

    `;

    container.appendChild(article);

  });

}

/* =====================================
   TAB CLICK EVENTS
===================================== */

document.querySelectorAll(".profile-tab").forEach(button => {

  button.addEventListener("click", () => {

    setActivityTab(button.dataset.tab);

  });

});

/* =====================================
   LIKE QUESTIONS
===================================== */

$("questionsList").addEventListener("click", event => {

  const button = event.target.closest(".like-button");

  if (!button) return;

  const question = questions.find(
    item => String(item.id) === button.dataset.id
  );

  if (!question) return;

  const liked = button.classList.toggle("liked");

  question.likes = Math.max(
    0,
    (Number(question.likes) || 0) + (liked ? 1 : -1)
  );

  button.querySelector(".like-count").textContent = question.likes;

  button.firstChild.textContent = liked ? "♥ " : "♡ ";

});

/* =====================================
   EDIT PROFILE FORM
===================================== */

const editableFields = [

  ["name", "Full Name", "text", true],

  ["email", "Email Address", "email", true],

  ["department", "Department / Branch", "select", true],

  ["role", "Role", "select", true],

  ["password", "New Password (optional)", "password", false]

];

function renderEditForm() {

  const container = $("editFields");

  container.innerHTML = "";

  editableFields.forEach(([key, label, type, required]) => {

    const group = document.createElement("div");

    group.className = "form-group";

    const labelElement = document.createElement("label");

    labelElement.htmlFor = "edit-" + key;

    labelElement.textContent = label + (required ? " *" : "");

    let field;

    if (type === "select") {

      field = document.createElement("select");

      const options = key === "department"

        ? [
            ["", "Select Department"],
            ["CSE", "CSE (Computer Science)"],
            ["CSM", "CSM (AI & Machine Learning)"],
            ["CSD", "CSD (Data Science)"],
            ["IT", "Information Technology (IT)"],
            ["ECE", "ECE"],
            ["EEE", "EEE"],
            ["MECH", "Mechanical Engineering"],
            ["CIVIL", "Civil Engineering"],
            ["OTHER", "General / Basic Sciences"]
          ]

        : [
            ["", "Select Role"],
            ["fresher", "Fresher"],
            ["student", "Student"],
            ["faculty", "Faculty"],
            ["alumni", "Alumni"]
          ];

      options.forEach(([value, label]) => {

        const option = document.createElement("option");

        option.value = value;

        option.textContent = label;

        field.appendChild(option);

      });

    } else {

      field = document.createElement("input");

      field.type = type;

    }

    field.id = "edit-" + key;

    field.name = key;

    if (required) {
      field.required = true;
    }

    if (key === "password") {

      field.autocomplete = "new-password";

      field.placeholder = "Enter a new password (optional)";

      field.minLength = 8;

    }

    group.append(labelElement, field);

    container.appendChild(group);

  });

}

function openEditModal() {

  renderEditForm();

  editableFields.forEach(([key]) => {

    const field = $("edit-" + key);

    field.value =
      key === "password" ? "" : (profile[key] || "");

  });

  $("editModal").classList.add("show");

  $("edit-name").focus();

}

function closeEditModal() {

  $("editModal").classList.remove("show");

}

$("editButton").addEventListener("click", openEditModal);

$("closeModal").addEventListener("click", closeEditModal);

$("cancelEdit").addEventListener("click", closeEditModal);

$("editModal").addEventListener("click", event => {

  if (event.target === $("editModal")) {
    closeEditModal();
  }

});

/* =====================================
   SAVE PROFILE
===================================== */

$("editForm").addEventListener("submit", async event => {

  event.preventDefault();

  const updates = {};

  editableFields.forEach(([key]) => {

    const value = $("edit-" + key).value.trim();

    if (key === "password") {

      if (value) {
        updates.password = value;
      }

      return;
    }

    updates[key] = value;

  });

  try {

    const response = await fetch("auth/update_profile.php", {

      method: "POST",

      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": csrfToken
      },

      body: JSON.stringify(updates)

    });

    const result = await response.json();

    if (!response.ok || !result.success) {

      throw new Error(
        result.message || "Unable to update your profile."
      );

    }

    profile = {
      ...profile,
      ...result.profile
    };

    $("edit-password").value = "";

    renderProfile();

    syncNavbarProfile();

    closeEditModal();

    showToast(result.message || "Changes saved successfully!");

  } catch (error) {

    showToast(
      error instanceof Error
        ? error.message
        : "Unable to update your profile.",
      "error"
    );

  }

});

/* =====================================
   PROFILE QUICK ACTIONS
===================================== */

$("copyEmailButton").addEventListener("click", async () => {

  if (!profile.email) {
    return showToast("Add your email in Edit Profile first.");
  }

  try {

    await navigator.clipboard.writeText(profile.email);

    showToast("Email copied to clipboard!");

  } catch {

    showToast(
      "Copy is unavailable in this browser. Select the email from Student Information."
    );

  }

});

$("viewQuestionsButton").addEventListener("click", () => {

  setActivityTab("questions");

  document.querySelector(".profile-tabs").scrollIntoView({
    behavior: "smooth",
    block: "center"
  });

});

/* =====================================
   PHOTO UPLOAD
===================================== */

const photoButton = $("photoButton");

const photoMenu = $("photoMenu");

function closePhotoMenu() {

  photoMenu.hidden = true;

  photoButton.setAttribute("aria-expanded", "false");

}

photoButton.setAttribute("aria-haspopup", "menu");

photoButton.setAttribute("aria-expanded", "false");

photoButton.addEventListener("click", event => {

  event.stopPropagation();

  if (!safeURL(currentPhoto)) {

    closePhotoMenu();

    $("photoInput").click();

    return;

  }

  const willOpen = photoMenu.hidden;

  photoMenu.hidden = !willOpen;

  photoButton.setAttribute("aria-expanded", String(willOpen));

});

$("updatePhotoButton").addEventListener("click", event => {

  event.stopPropagation();

  closePhotoMenu();

  $("photoInput").click();

});

/* =====================================
   REMOVE PROFILE PHOTO
===================================== */

$("removePhotoButton").addEventListener("click", async event => {

  event.stopPropagation();

  closePhotoMenu();

  const formData = new FormData();

  formData.append("action", "remove");

  try {

    const response = await fetch("auth/upload_avatar.php", {

      method: "POST",

      headers: {
        "X-CSRF-Token": csrfToken
      },

      body: formData

    });

    const result = await response.json();

    if (!response.ok || !result.success) {

      throw new Error(
        result.message || "Unable to remove your profile picture."
      );

    }

    profile.profile_image = "";

    currentPhoto = "";

    renderAvatar();

    syncNavbarProfile();

    showToast("Profile picture removed!");

  } catch (error) {

    showToast(
      error instanceof Error
        ? error.message
        : "Unable to remove your profile picture.",
      "error"
    );

  }

});

/* =====================================
   PHOTO MENU EVENTS
===================================== */

document.addEventListener("click", event => {

  if (
    !photoMenu.hidden &&
    !photoMenu.contains(event.target) &&
    event.target !== photoButton
  ) {

    closePhotoMenu();

  }

});

document.addEventListener("keydown", event => {

  if (event.key === "Escape") {
    closePhotoMenu();
  }

});

/* =====================================
   UPLOAD PROFILE PHOTO
===================================== */

$("photoInput").addEventListener("change", async event => {

  const file = event.target.files[0];

  if (!file) return;

  if (file.size > 3 * 1024 * 1024) {

    showToast("Choose an image no larger than 3 MB.", "error");

    return;

  }

  const allowedTypes = [
    "image/jpeg",
    "image/png",
    "image/webp"
  ];

  if (file.type && !allowedTypes.includes(file.type)) {

    showToast("Choose a JPEG, PNG, or WebP image.", "error");

    return;

  }

  const formData = new FormData();

  formData.append("avatar", file);

  try {

    const response = await fetch("auth/upload_avatar.php", {

      method: "POST",

      headers: {
        "X-CSRF-Token": csrfToken
      },

      body: formData

    });

    const result = await response.json();

    if (!response.ok || !result.success) {

      throw new Error(
        result.message || "Unable to upload your profile picture."
      );

    }

    profile.profile_image = result.profile_image;

    currentPhoto = result.profile_image;

    renderAvatar();

    syncNavbarProfile();

    showToast("Profile picture updated!");

  } catch (error) {

    showToast(
      error instanceof Error
        ? error.message
        : "Unable to upload your profile picture.",
      "error"
    );

  } finally {

    event.target.value = "";

  }

});

/* =====================================
   TOAST
===================================== */

function showToast(message, type = "success") {

  const toast = $("toast");

  toast.textContent = message;

  toast.classList.add("show");

  toast.classList.toggle("error", type === "error");

  clearTimeout(toastTimer);

  toastTimer = setTimeout(() => {

    toast.classList.remove("show");

  }, 2800);

}

/* =====================================
   KEYBOARD EVENTS
===================================== */

document.addEventListener("keydown", event => {

  if (event.key === "Escape") {
    closeEditModal();
  }

});

/* =====================================
   INITIALIZE PROFILE
===================================== */

renderProfile();

renderQuestions();

</script>

<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
