<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$error_msg   = $_SESSION['login_error'] ?? '';
$success_msg = $_SESSION['register_success'] ?? '';
unset($_SESSION['login_error'], $_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — College Connect</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/root.css" />
  <link rel="stylesheet" href="assets/css/auth.css" />
</head>
<body>

  <!-- REPLACE INCLUDE PATH HERE: Your shared navbar file -->
  <?php include 'nav.php'; ?>

  <!-- FULL-BLEED CAMPUS HERO + LOGIN CARD -->
  <main class="hero-auth-section">
    <div class="hero-auth-container">

      <!-- Left Column: Welcome Back & 3 Stacked Glass Pills -->
      <div class="hero-left">
        <h1 class="hero-script-heading">
          Welcome <span class="accent-yellow">Back!</span>
        </h1>
        <div class="hero-yellow-underline"></div>

        <p class="hero-description" style="font-size: 1.05rem; margin-bottom: 1.75rem;">
          Login to continue your learning journey with AITAM students, faculty, and alumni.
        </p>

        <div class="glass-pill-stack">
          <div class="glass-pill-item">
            <div class="glass-pill-icon">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
              </svg>
            </div>
            <span>Access your<br />campus network</span>
          </div>

          <div class="glass-pill-item">
            <div class="glass-pill-icon">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
              </svg>
            </div>
            <span>Track your progress</span>
          </div>

          <div class="glass-pill-item">
            <div class="glass-pill-icon gold">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
              </svg>
            </div>
            <span>Be a part of<br />AITAM Community</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Floating Login Form Card -->
      <div class="hero-right">
        <div class="auth-floating-card login-card">
          <div class="card-header-flex">
            <div class="card-avatar-badge">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
            <div>
              <h2>Login to College Connect</h2>
              <p>Enter your credentials to continue</p>
            </div>
          </div>

          <!-- Success Flash Banner -->
          <div class="alert-banner alert-success <?php echo !empty($success_msg) ? 'show' : ''; ?>">
            <span><?php echo htmlspecialchars($success_msg); ?></span>
          </div>

          <!-- Error Flash Banner -->
          <div class="alert-banner alert-danger <?php echo !empty($error_msg) ? 'show' : ''; ?>">
            <span><?php echo htmlspecialchars($error_msg); ?></span>
          </div>

          <form id="loginForm" action="auth/login_process.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

            <!-- Email Address -->
            <div class="form-group">
              <label for="email" class="form-label">Email Address <span class="required-star">*</span></label>
              <div class="input-icon-group">
                <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
                <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" autocomplete="email" required />
              </div>
              <div class="field-error" id="emailError">Please enter a valid email address.</div>
            </div>

            <!-- Password -->
            <div class="form-group">
              <label for="password" class="form-label">Password <span class="required-star">*</span></label>
              <div class="input-icon-group">
                <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <input type="password" id="password" name="password" class="form-control has-toggle" placeholder="Enter password" autocomplete="current-password" required />
                <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                  <svg class="icon-eye-off" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                  </svg>
                  <svg class="icon-eye" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
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
                <span>Remember me</span>
              </label>
              <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-submit-primary">
              Login &rarr;
            </button>

            <p class="auth-switch-text">
              Don't have an account? <a href="register.php">Sign up here</a>
            </p>
          </form>
        </div>
      </div>

    </div>
  </main>

  <!-- REPLACE INCLUDE PATH HERE: Your shared footer file -->
  <?php include 'footer.php'; ?>

<script>
  // 1. Password Visibility Toggle
  document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      const eyeOff = btn.querySelector('.icon-eye-off');
      const eyeOn  = btn.querySelector('.icon-eye');

      const isHidden = target.type === 'password';
      target.type = isHidden ? 'text' : 'password';
      eyeOff.style.display = isHidden ? 'none' : 'block';
      eyeOn.style.display  = isHidden ? 'block' : 'none';
    });
  });

  // 2. Client-Side Validation
  document.getElementById('loginForm').addEventListener('submit', (e) => {
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
</body>
</html>