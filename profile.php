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
        'SELECT full_name, email, department, role, profile_image, points
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

$defaultQuestions = [];
try {
    $questionsStmt = $pdo->prepare(
        'SELECT id, question_title, question_description, category, created_at
         FROM questions
         WHERE user_id = :user_id
         ORDER BY created_at DESC'
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
            'SELECT q.id, q.title AS question_title, q.body AS question_description,
                    c.name AS category, q.created_at
             FROM questions q
             LEFT JOIN categories c ON c.id = q.category_id
             WHERE q.user_id = :user_id
             ORDER BY q.created_at DESC'
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

try {
    $answersStmt = $pdo->prepare(
        'SELECT a.id, a.answer_text, a.created_at,
                q.id AS question_id, q.question_title,
                q.question_description, q.category
         FROM answers a
         INNER JOIN questions q ON q.id = a.question_id
         WHERE a.user_id = :user_id
         ORDER BY a.created_at DESC'
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
            'likes' => 0,
            'replies' => 0,
            'date' => $formatActivityDate($answer['created_at'] ?? null),
        ];
    }
} catch (PDOException $exception) {
    try {
        $answersStmt = $pdo->prepare(
            'SELECT a.id, a.answer_text, a.created_at,
                    q.id AS question_id, q.title AS question_title,
                    q.body AS question_description, c.name AS category
             FROM answers a
             INNER JOIN questions q ON q.id = a.question_id
             LEFT JOIN categories c ON c.id = q.category_id
             WHERE a.user_id = :user_id
             ORDER BY a.created_at DESC'
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

$defaultProfile = [
    'name' => (string)$user['full_name'],
    'email' => (string)$user['email'],
    'department' => (string)($user['department'] ?? ''),
    'role' => (string)($user['role'] ?? ''),
    'points' => (float)($user['points'] ?? 0),
    'profile_image' => (string)($user['profile_image'] ?? ''),
];
$displayProfileName = trim($defaultProfile['name']);
if (strcasecmp((string)$defaultProfile['role'], 'faculty') === 0) {
    $displayProfileName = trim(preg_replace('/^Sri\s+/iu', '', $displayProfileName) ?? $displayProfileName);
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

.page {
  width:min(1000px,calc(100% - 40px));
  margin:40px auto 60px;
}

.profile-card {
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:24px;
  overflow:hidden;
  box-shadow:var(--shadow);
}

.profile-cover {
  height:170px;
  background:
    radial-gradient(circle at 85% 20%,rgba(189,232,245,.45),transparent 25%),
    radial-gradient(circle at 15% 100%,rgba(73,136,196,.4),transparent 35%),
    linear-gradient(125deg,#0F2854,#1C4D8D 60%,#4988C4);
  position:relative;
}

.cover-decoration {
  position:absolute;
  right:35px;
  bottom:20px;
  color:rgba(255,255,255,.18);
  font-size:90px;
  font-weight:800;
  letter-spacing:-8px;
}

.profile-main {
  padding:0 38px 32px;
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

#photoInput {
  display:none;
}

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
}

.btn:hover {
  transform:translateY(-1px);
  border-color:var(--secondary);
}

.btn-primary {
  background:var(--blue);
  color:white;
  border-color:var(--blue);
}

.btn-primary:hover {
  background:var(--navy);
}

.profile-details {
  margin-top:20px;
}

.profile-name {
  font-size:30px;
  font-weight:750;
  letter-spacing:-.7px;
  margin:0 0 8px;
}

.profile-subtitle {
  color:var(--secondary-text);
  font-size:14px;
  margin-bottom:15px;
}


.profile-meta {
  display:flex;
  gap:20px;
  flex-wrap:wrap;
  margin-top:20px;
  color:var(--secondary-text);
  font-size:13px;
}

.meta-item {
  display:flex;
  align-items:center;
  gap:7px;
}

.meta-item span {
  color:var(--blue);
}

/* Registration information */

.content {
  margin-top:26px;
  display:grid;
  gap:24px;
}

.card {
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:18px;
  padding:28px;
  box-shadow:var(--shadow);
}

.section-title {
  font-size:19px;
  font-weight:750;
  margin:0 0 24px;
}

.info-grid {
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:25px 20px;
}

.info-item {
  min-width:0;
}

.info-label {
  display:block;
  color:var(--muted);
  font-size:12px;
  font-weight:650;
  margin-bottom:8px;
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




/* Questions and profile tabs */

.profile-tabs {
  display:flex;
  gap:8px;
  border-bottom:1px solid var(--border);
  margin:-4px 0 22px;
  overflow-x:auto;
}

.profile-tab {
  border:0;
  border-bottom:3px solid transparent;
  background:transparent;
  color:var(--secondary-text);
  padding:12px 16px;
  font-weight:700;
  white-space:nowrap;
}

.profile-tab.active {
  color:var(--blue);
  border-bottom-color:var(--blue);
}

.community-panel { color:var(--secondary-text); line-height:1.7; }

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

.question-item {
  padding:22px 0;
  border-bottom:1px solid var(--border);
}

.question-item:first-child {
  padding-top:0;
}

.question-item:last-child {
  border-bottom:none;
  padding-bottom:0;
}

.question-category {
  color:var(--blue);
  font-size:11px;
  font-weight:750;
  text-transform:uppercase;
  letter-spacing:.7px;
}

.question-title {
  font-size:16px;
  line-height:1.5;
  font-weight:700;
  margin:10px 0;
}

.question-description {
  font-size:14px;
  color:var(--secondary-text);
  line-height:1.7;
  margin:0 0 16px;
  white-space:pre-wrap;
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
  background:none;
  color:inherit;
  padding:0;
  font-size:12px;
}

.stat-button:hover,
.stat-button.liked {
  color:#C2414B;
}

.question-date {
  margin-left:auto;
}

.empty-state {
  padding:35px 15px;
  text-align:center;
  color:var(--muted);
  font-size:14px;
  line-height:1.8;
}

.empty-icon {
  font-size:30px;
  margin-bottom:10px;
}

.ask-button {
  display:inline-block;
  text-decoration:none;
  margin-top:12px;
}

/* Edit form */

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
}

.modal-overlay.show {
  display:flex;
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

.modal-actions {
  display:flex;
  justify-content:flex-end;
  gap:12px;
  margin-top:25px;
}

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

@media(max-width:700px) {
  .page {
    width:calc(100% - 24px);
    margin:18px auto 35px;
  }

  .profile-cover {
    height:125px;
  }

  .profile-main {
    padding:0 20px 23px;
  }

  .avatar-wrap,.avatar {
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


/* Polished, interactive profile enhancements */
body {
  background:
    radial-gradient(ellipse at 8% 0%, rgba(189,232,245,.32), transparent 32%),
    radial-gradient(ellipse at 92% 8%, rgba(73,136,196,.12), transparent 28%),
    var(--bg);
}
.page { max-width: 1080px; }
.profile-card, .card { transition: box-shadow .25s ease, transform .25s ease; }
.profile-card:hover, .card:hover { box-shadow: 0 16px 42px rgba(15,40,84,.12); }
.profile-cover { height:190px; overflow:hidden; }
.profile-cover::before, .profile-cover::after {
  content:""; position:absolute; border:1px solid rgba(255,255,255,.18); border-radius:50%;
  width:310px; height:310px; right:8%; top:-205px;
}
.profile-cover::after { width:210px; height:210px; right:24%; top:-125px; }
.cover-decoration { font-size:clamp(70px,10vw,118px); right:30px; bottom:8px; }
.profile-main { padding-bottom:35px; }
.profile-name { letter-spacing:-1px; }
.profile-subtitle { display:flex; align-items:center; gap:9px; flex-wrap:wrap; }
.profile-role-badge { font-size:11px; font-weight:750; letter-spacing:.3px; color:#14734f; background:#e6f7ef; border:1px solid #c6eedb; border-radius:999px; padding:4px 9px; }
.profile-meta { gap:10px; }
.meta-item { padding:9px 12px; border:1px solid var(--border); border-radius:999px; background:var(--surface); }
.meta-item span:first-child { font-size:15px; }
.btn { box-shadow:0 2px 5px rgba(15,40,84,.04); }
.btn-primary { box-shadow:0 7px 16px rgba(28,77,141,.2); }
.card { border-radius:20px; }
.section-title { display:flex; align-items:center; gap:10px; }
.section-title::before { content:""; width:5px; height:20px; border-radius:9px; background:linear-gradient(var(--secondary),var(--cyan)); }
.info-item { padding:14px 15px; border:1px solid var(--border); border-radius:13px; background:var(--surface); transition:transform .18s,border-color .18s; }
.info-item:hover { transform:translateY(-2px); border-color:#b9d4eb; }
.info-label { text-transform:uppercase; letter-spacing:.55px; font-size:10px; }
.profile-tabs { gap:4px; padding:5px; border:1px solid var(--border); border-radius:14px; background:var(--muted-bg); margin:0 0 24px; }
.profile-tab { border:0; border-radius:10px; padding:12px 17px; transition:background .2s,color .2s,box-shadow .2s; }
.profile-tab.active { background:var(--surface); color:var(--blue); box-shadow:0 3px 10px rgba(15,40,84,.09); }
.question-item { padding:20px; margin:0 -2px; border:1px solid transparent; border-bottom-color:var(--border); border-radius:14px; transition:background .2s,border-color .2s; }
.question-item:hover { background:var(--surface); border-color:var(--border); }
.question-category { display:inline-flex; padding:5px 9px; border-radius:999px; background:var(--muted-bg); }
.question-title { margin-top:12px; }
.stat-button { padding:7px 10px; border-radius:8px; background:var(--muted-bg); transition:background .2s; }
.stat-button:hover { background:var(--muted-bg); }
.empty-state { border:1px dashed var(--border); border-radius:15px; background:var(--surface); }
.modal { animation:modalIn .2s ease-out; }
@keyframes modalIn { from { opacity:0; transform:translateY(10px) scale(.985); } to { opacity:1; transform:translateY(0) scale(1); } }
.modal-overlay { opacity:0; transition:opacity .18s ease; }
.modal-overlay.show { opacity:1; }
.form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color:var(--secondary); background:var(--surface); box-shadow:0 0 0 4px rgba(73,136,196,.12); }
.toast { display:flex; align-items:center; gap:9px; }
.toast::before { content:"✓"; display:grid; place-items:center; width:21px; height:21px; border-radius:50%; background:#ffffff24; font-weight:800; }
.profile-progress { margin-top:23px; max-width:450px; }
.progress-top { display:flex; justify-content:space-between; gap:12px; color:var(--muted); font-size:12px; margin-bottom:8px; }
.progress-track { height:7px; border-radius:99px; overflow:hidden; background:#e8eff6; }
.progress-fill { height:100%; width:0; border-radius:inherit; background:linear-gradient(90deg,var(--secondary),#68c3d8); transition:width .35s ease; }
.profile-quick-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:15px; }
.quick-action { border:1px solid var(--border); background:var(--surface); color:var(--blue); border-radius:9px; padding:8px 11px; font-size:12px; font-weight:700; transition:.2s; }
.quick-action:hover { background:#eef6fc; border-color:#b8d4ed; transform:translateY(-1px); }
@media(max-width:700px) { .profile-cover { height:145px; } .profile-progress { max-width:none; } .profile-tabs { width:100%; } .profile-tab { flex:1; padding:11px 8px; font-size:13px; } }
@media(prefers-reduced-motion:reduce) { *,*::before,*::after { animation-duration:.01ms !important; transition-duration:.01ms !important; } }

</style>
</head>

<body>

<?php include __DIR__ . '/nav.php'; ?>

<main class="page">

  <!-- STUDENT PROFILE -->
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

          <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="profile-actions">

          <button class="btn btn-primary" id="editButton" type="button">
            ✎ Edit Profile
          </button>
        </div>

      </div>

      <div class="profile-details">
        <h1 class="profile-name" id="profileName"><?= htmlspecialchars($displayProfileName, ENT_QUOTES, 'UTF-8') ?></h1>

        <div class="profile-subtitle" id="profileSubtitle">
          <span id="profileSubtitleText"></span>
          <span class="profile-role-badge" id="profileRoleBadge"></span>
        </div>

        <div class="profile-meta">
          <div class="meta-item"><span>⌖</span><span id="profileDepartment">Department not provided</span></div>
          <div class="meta-item"><span>▣</span><span id="profileRole">Role not provided</span></div>
          <div class="meta-item"><span>✉</span><span id="profileEmail">Email not provided</span></div>
        </div>
        <div class="profile-progress" aria-label="Profile completion">
          <div class="progress-top"><span>Profile completeness</span><strong id="completionText">0%</strong></div>
          <div class="progress-track"><div class="progress-fill" id="completionFill"></div></div>
        </div>
        <div class="profile-quick-actions">
          <button class="quick-action" id="copyEmailButton" type="button">✉ Copy email</button>
          <button class="quick-action" id="viewQuestionsButton" type="button">▤ View my questions</button>
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

    <!-- QUESTIONS ONLY -->
    <section class="card">

      <nav class="profile-tabs" role="tablist" aria-label="My profile activity">
        <button class="profile-tab active" type="button" role="tab" aria-selected="true" data-tab="questions">Questions</button>
        <button class="profile-tab" type="button" role="tab" aria-selected="false" data-tab="answers">Answers</button>
        <button class="profile-tab" type="button" role="tab" aria-selected="false" data-tab="community">Community</button>
      </nav>

      <div class="questions-header">
        <h2 class="section-title" id="activityTitle">My Questions</h2>
        <span class="question-count" id="questionCount">0 Questions</span>
      </div>

      <div id="questionsList" role="tabpanel"></div>
      <div id="communityPanel" class="community-panel" hidden>
        <h3>Community</h3>
        <p>Connect with the AITAM Slack community, join discussions, and share helpful resources.</p>
        <p>Your community posts and activity will appear here when connected to the community feed.</p>
      </div>

    </section>

  </div>

</main>

<!-- EDIT PROFILE -->
<div class="modal-overlay" id="editModal">

  <div class="modal" role="dialog" aria-modal="true"
       aria-labelledby="editTitle">

    <div class="modal-header">
      <h2 id="editTitle">Edit Your Profile</h2>
      <button class="close-btn" id="closeModal" type="button"
              aria-label="Close">×</button>
    </div>

    <form id="editForm">
      <div class="form-grid" id="editFields"></div>
      <p class="password-note" style="font-size:12px;color:var(--secondary-text);margin:12px 0 0;line-height:1.5;">
        Leave the password blank to keep your current password. New passwords must be at least 8 characters.
      </p>

      <div class="modal-actions">
        <button class="btn" id="cancelEdit" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit">Save Changes</button>
      </div>
    </form>

  </div>
</div>

<div class="toast" id="toast" role="status"></div>

<script>
const $ = id => document.getElementById(id);
const defaultProfile = <?= json_encode($defaultProfile, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
const defaultQuestions = <?= json_encode($defaultQuestions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
const csrfToken = <?= json_encode($_SESSION['csrf_token'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

let profile = { ...defaultProfile };
let questions = defaultQuestions;
let currentPhoto = profile.profile_image || "";
let toastTimer;

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
    return ["https:", "http:"].includes(url.protocol) ? url.href : "";
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

function renderAvatar() {
  const avatar = $("profileAvatar");
  avatar.innerHTML = "";
  const imageURL = safeURL(currentPhoto);

  if (imageURL) {
    const image = document.createElement("img");
    image.src = imageURL;
    image.alt = "Profile picture";
    avatar.appendChild(image);
  } else {
    avatar.textContent = initials(displayName());
  }
}



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

function renderProfile() {
  $("profileName").textContent = displayName() || "Profile";
  $("profileSubtitleText").textContent = `B.Tech · ${profile.department || "Department not provided"}`;
  $("profileRoleBadge").textContent = roleLabel(profile.role);
  $("profileDepartment").textContent = profile.department || "Department not provided";
  $("profileRole").textContent = roleLabel(profile.role);
  $("profileEmail").textContent = profile.email || "Email not provided";
  renderAvatar();
  renderInfo();
  const completed = [profile.name, profile.email, profile.department, profile.role].filter(value => String(value || "").trim()).length;
  const percent = Math.round(completed / 4 * 100);
  $("completionText").textContent = `${percent}%`;
  $("completionFill").style.width = `${percent}%`;
  $("copyEmailButton").disabled = !profile.email;
  $("copyEmailButton").style.opacity = profile.email ? "1" : ".55";
}

function syncNavbarProfile() {
  const name = displayName();
  const avatarURL = safeURL(profile.profile_image || "");
  const avatarInitial = Array.from(name)[0] || "?";

  document.querySelectorAll(".nav-profile-wrapper").forEach(wrapper => {
    const nameElement = wrapper.querySelector(".nav-user-name");
    const roleElement = wrapper.querySelector(".nav-user-role");
    if (nameElement) nameElement.textContent = name;
    if (roleElement) roleElement.textContent = roleLabel(profile.role);

    let image = wrapper.querySelector(".nav-avatar-img");
    let fallback = wrapper.querySelector(".nav-avatar-fallback");
    if (avatarURL) {
      if (!image) {
        image = document.createElement("img");
        image.className = "nav-avatar-img";
        image.alt = "";
        fallback?.replaceWith(image);
      }
      image.src = avatarURL;
    } else {
      if (image) {
        fallback = document.createElement("span");
        fallback.className = "nav-avatar-fallback";
        fallback.setAttribute("aria-hidden", "true");
        image.replaceWith(fallback);
      }
      if (fallback) fallback.textContent = avatarInitial;
    }
  });
}



/* QUESTIONS */

let activeActivityTab = "questions";

function setActivityTab(tab) {
  activeActivityTab = tab;
  document.querySelectorAll(".profile-tab").forEach(button => {
    const selected = button.dataset.tab === tab;
    button.classList.toggle("active", selected);
    button.setAttribute("aria-selected", String(selected));
  });
  $("activityTitle").textContent = tab === "questions" ? "My Questions" : tab === "answers" ? "My Answers" : "Community";
  $("questionsList").hidden = tab === "community";
  $("communityPanel").hidden = tab !== "community";
  $("questionsList").style.display = tab === "community" ? "none" : "";
  renderQuestions();
}

function renderQuestions() {
  const container = $("questionsList");
  container.innerHTML = "";
  const visibleQuestions = questions.filter(question =>
    activeActivityTab === "answers"
      ? question.type === "answer"
      : question.type === "question"
  );

  $("questionCount").textContent =
    `${visibleQuestions.length} ${visibleQuestions.length === 1 ? (activeActivityTab === "answers" ? "Answer" : "Question") : (activeActivityTab === "answers" ? "Answers" : "Questions")}`;

  if (!visibleQuestions.length) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">?</div>
        <div>${activeActivityTab === "answers" ? "No answers posted yet." : "No questions posted yet."}</div>
        <p>${activeActivityTab === "answers" ? "Answers you post on AITAM Slack will appear here." : "Questions you post on AITAM Slack will appear here."}</p>
      </div>
    `;
    return;
  }

  visibleQuestions.forEach(question => {
    const article = document.createElement("article");
    article.className = "question-item";

    article.innerHTML = `
      <div class="question-category">
        ${escapeHTML(question.category || "General")}
      </div>

      <h3 class="question-title">
        ${escapeHTML(question.title)}
      </h3>

      <p class="question-description">
        ${escapeHTML(question.description)}
      </p>

      ${activeActivityTab === "answers" && question.answer ? `
        <div class="question-answer">
          <strong>Answer</strong>
          ${escapeHTML(question.answer)}
        </div>
      ` : ""}

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

/* PROFILE ACTIVITY TABS */

document.querySelectorAll(".profile-tab").forEach(button => {
  button.addEventListener("click", () => setActivityTab(button.dataset.tab));
});

/* LIKE QUESTIONS */

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

/* EDIT FORM */

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
    group.className = "form-group" +
      (key === "bio" || key === "address" || key === "skills" ||
       key === "interests" ? " full" : "");

    const labelElement = document.createElement("label");
    labelElement.htmlFor = "edit-" + key;
    labelElement.textContent = label + (required ? " *" : "");

    let field;

    if (type === "select") {
      field = document.createElement("select");
      const options = key === "department"
        ? [["", "Select Department"], ["CSE", "CSE (Computer Science)"], ["CSM", "CSM (AI & Machine Learning)"], ["CSD", "CSD (Data Science)"], ["IT", "Information Technology (IT)"], ["ECE", "ECE"], ["EEE", "EEE"], ["MECH", "Mechanical Engineering"], ["CIVIL", "Civil Engineering"], ["OTHER", "General / Basic Sciences"]]
        : [["", "Select Role"], ["fresher", "Fresher"], ["student", "Student"], ["faculty", "Faculty"], ["alumni", "Alumni"]];
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

    if (required) field.required = true;

    if (key === "password") {
      field.autocomplete = "new-password";
      field.placeholder = "Enter a new password (optional)";
      field.minLength = 8;
    }

    if (key === "bio") field.maxLength = 350;

    group.append(labelElement, field);
    container.appendChild(group);
  });
}

function openEditModal() {
  renderEditForm();

  editableFields.forEach(([key]) => {
    const field = $("edit-" + key);

    // Never prefill or persist a password in browser storage.
    field.value = key === "password" ? "" : (profile[key] || "");
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
  if (event.target === $("editModal")) closeEditModal();
});

$("editForm").addEventListener("submit", async event => {
  event.preventDefault();

  const updates = {};
  editableFields.forEach(([key]) => {
    const value = $("edit-" + key).value.trim();
    if (key === "password") {
      if (value) updates.password = value;
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
      throw new Error(result.message || "Unable to update your profile.");
    }

    profile = { ...profile, ...result.profile };
    $("edit-password").value = "";
    renderProfile();
    syncNavbarProfile();
    closeEditModal();
    showToast(result.message || "Changes saved successfully!");
  } catch (error) {
    showToast(error instanceof Error ? error.message : "Unable to update your profile.", "error");
  }
});

/* PROFILE QUICK ACTIONS */
$("copyEmailButton").addEventListener("click", async () => {
  if (!profile.email) return showToast("Add your email in Edit Profile first.");
  try {
    await navigator.clipboard.writeText(profile.email);
    showToast("Email copied to clipboard!");
  } catch {
    showToast("Copy is unavailable in this browser. Select the email from Student Information.");
  }
});

$("viewQuestionsButton").addEventListener("click", () => {
  setActivityTab("questions");
  document.querySelector(".profile-tabs").scrollIntoView({ behavior: "smooth", block: "center" });
});

/* PHOTO UPLOAD */

$("photoButton").addEventListener("click", () => {
  $("photoInput").click();
});

$("photoInput").addEventListener("change", async event => {
  const file = event.target.files[0];

  if (!file) return;

  if (file.size > 3 * 1024 * 1024) {
    showToast("Choose an image no larger than 3 MB.", "error");
    return;
  }

  const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
  if (file.type && !allowedTypes.includes(file.type)) {
    showToast("Choose a JPEG, PNG, or WebP image.", "error");
    return;
  }

  const formData = new FormData();
  formData.append("avatar", file);

  try {
    const response = await fetch("auth/upload_avatar.php", {
      method: "POST",
      headers: { "X-CSRF-Token": csrfToken },
      body: formData
    });
    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.message || "Unable to upload your profile picture.");
    }

    profile.profile_image = result.profile_image;
    currentPhoto = result.profile_image;
    renderAvatar();
    syncNavbarProfile();
    showToast("Profile picture updated!");
  } catch (error) {
    showToast(error instanceof Error ? error.message : "Unable to upload your profile picture.", "error");
  } finally {
    event.target.value = "";
  }
});

/* TOAST */

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

document.addEventListener("keydown", event => {
  if (event.key === "Escape") closeEditModal();
});

/* INITIALIZE */

renderProfile();
renderQuestions();
</script>

<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
