<?php
session_start();

if (
    !empty($_SESSION['admin_id']) &&
    ($_SESSION['admin_status'] ?? '') === 'active'
) {
    header('Location: admin/dashboard.php');
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve flash messages from login_process.php or register_process.php
$error_msg   = $_SESSION['login_error'] ?? '';
$success_msg = $_SESSION['register_success'] ?? '';
unset($_SESSION['login_error'], $_SESSION['register_success']);
if ($error_msg === '' && ($_GET['error'] ?? '') === 'blocked') {
    $error_msg = 'Your administrator account is blocked.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In — AITAM Slack</title>
  <!-- Google Font: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <!-- Design Tokens & Auth Styles -->
  <link rel="stylesheet" href="assets/css/root.css" />
  <link rel="stylesheet" href="assets/css/auth.css" />
</head>
<body>
<?php include 'nav.php';?>

<div class="auth-wrapper">
  <!-- LEFT PANEL: Brand Context & Campus Discussion Illustration -->
  <aside class="auth-brand-panel">
    <div>
      <a href="index.php" class="brand-logo">
        <span class="brand-badge">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </span>
        AITAM Slack
      </a>

      <div class="brand-hero">
        <span class="brand-pill">WELCOME BACK</span>
        <h1>Your campus community is waiting.</h1>
        <p>
          Sign in to ask your academic or campus doubts, share solutions with juniors, and stay connected with faculty and alumni.
        </p>

        <!-- Illustration Card -->
        <div class="illustration-card">
          <img 
            src="assets/images/campus-discussion.png" 
            alt="Freshers, students, faculty, and alumni discussing together" 
            class="illustration-img"
          />
        
        </div>
      </div>
    </div>

    <div class="brand-footer">
      &copy; <?php echo date('Y'); ?> AITAM Slack &bull; Built for Freshers, Students, Faculty &amp; Alumni
    </div>
  </aside>

  <!-- RIGHT PANEL: Login Form -->
  <main class="auth-form-panel">
    <div class="top-bar-actions login-bar">
      <a href="index.php" class="back-home-link">
        &larr; Back to Home
      </a>
      <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="Toggle dark theme">
        <span id="themeIcon">&#9790;</span> <span id="themeText">Dark Mode</span>
      </button>
    </div>

    <div class="auth-card login-card">
      <div class="auth-card-header">
        <h2>Sign in to your account</h2>
        <p>Enter your registered email and password to continue.</p>
      </div>

      <!-- Success Banner (e.g., after completing registration) -->
      <div class="alert-banner alert-success <?php echo !empty($success_msg) ? 'show' : ''; ?>">
        <span><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>

      <!-- Server-Side Error Banner -->
      <div class="alert-banner alert-danger <?php echo !empty($error_msg) ? 'show' : ''; ?>">
        <span><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>

      <form id="loginForm" action="auth/login_process.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

        <!-- Email Address -->
        <div class="form-group">
          <label for="email" class="form-label">Email Address <span class="required-star">*</span></label>
          <input
            type="email"
            id="email"
            name="email"
            class="form-control"
            placeholder="you@example.com"
            autocomplete="email"
            required
          />
          <div class="field-error" id="emailError">Please enter a valid email address.</div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password" class="form-label">Password <span class="required-star">*</span></label>
          <div class="input-wrapper">
            <input
              type="password"
              id="password"
              name="password"
              class="form-control has-toggle"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            />
            <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
          <div class="field-error" id="passwordError">Please enter your password.</div>
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="form-options-row">
          <label class="checkbox-label">
            <input type="checkbox" name="remember_me" value="1" />
            <span>Keep me signed in</span>
          </label>
          <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary">Sign In</button>

        <p class="auth-footer-text">
          New to AITAM Slack? <a href="register.php">Create an account</a>
        </p>
      </form>
    </div>
  </main>
</div>

<script>
  // 1. Theme Switcher (Synced across Login & Register via localStorage)
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');
  const themeText = document.getElementById('themeText');
  const htmlEl = document.documentElement;

  const savedTheme = localStorage.getItem('cc_theme') || 'light';
  if (savedTheme === 'dark') {
    htmlEl.setAttribute('data-theme', 'dark');
    themeIcon.innerHTML = '&#9728;';
    themeText.textContent = 'Light Mode';
  }

  themeToggle.addEventListener('click', () => {
    const isDark = htmlEl.getAttribute('data-theme') === 'dark';
    if (isDark) {
      htmlEl.removeAttribute('data-theme');
      localStorage.setItem('cc_theme', 'light');
      themeIcon.innerHTML = '&#9790;';
      themeText.textContent = 'Dark Mode';
    } else {
      htmlEl.setAttribute('data-theme', 'dark');
      localStorage.setItem('cc_theme', 'dark');
      themeIcon.innerHTML = '&#9728;';
      themeText.textContent = 'Light Mode';
    }
  });

  // 2. Password Visibility Toggle
  document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      target.type = target.type === 'password' ? 'text' : 'password';
    });
  });

  // 3. Client-Side Validation
  const loginForm = document.getElementById('loginForm');
  loginForm.addEventListener('submit', (e) => {
    let isValid = true;

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    document.getElementById('emailError').classList.toggle('visible', !emailValid);
    if (!emailValid) isValid = false;

    const passValid = password.length > 0;
    document.getElementById('passwordError').classList.toggle('visible', !passValid);
    if (!passValid) isValid = false;

    if (!isValid) e.preventDefault();
  });
</script>
<?php include 'footer.php';?>

</body>
</html>