<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Allow resetting back to Step 1 if user clicks "Change email"
if (isset($_GET['reset'])) {
    unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_masked_email'], $_SESSION['fp_otp_hash'], $_SESSION['fp_verified'], $_SESSION['fp_dev_otp']);
    header('Location: forgot_password.php');
    exit;
}

$step         = $_SESSION['fp_step'] ?? 'enter_email';
$masked_email = $_SESSION['fp_masked_email'] ?? 'j**********@gmail.com';
$error_msg    = $_SESSION['fp_error'] ?? '';
$dev_otp      = $_SESSION['fp_dev_otp'] ?? '';
unset($_SESSION['fp_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password — AITAM Slack</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/root.css" />
  <link rel="stylesheet" href="assets/css/auth.css" />
</head>
<body>

  <?php include 'nav.php'; ?>

  <main class="hero-auth-section">
    <div class="hero-auth-container">

      <!-- Left Column -->
      <div class="hero-left">
        <span class="hero-badge">ACCOUNT RECOVERY</span>
        <h1>Recover access to your <span class="highlight-gold">campus account.</span></h1>
        <p class="hero-description">
          Verify your registered email with a 6-digit one-time passcode and set a strong new password in seconds.
        </p>

        <div class="glass-pill-stack">
          <div class="glass-pill-item">
            <div class="glass-pill-icon">1</div>
            <span>Enter your registered email</span>
          </div>
          <div class="glass-pill-item">
            <div class="glass-pill-icon">2</div>
            <span>Verify 6-digit OTP code</span>
          </div>
          <div class="glass-pill-item">
            <div class="glass-pill-icon gold">3</div>
            <span>Create your new password</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Floating Reset Card -->
      <div class="hero-right">
        <div class="auth-floating-card login-card">

          <!-- Error Banner -->
          <div class="alert-banner alert-danger <?php echo !empty($error_msg) ? 'show' : ''; ?>">
            <span><?php echo htmlspecialchars($error_msg); ?></span>
          </div>

          <!-- Localhost XAMPP Testing Helper Banner -->
          <?php if (!empty($dev_otp) && $step === 'verify_otp'): ?>
            <div class="alert-banner alert-info-dev show">
              <span>
                <strong>Local XAMPP Test OTP:</strong> <?php echo htmlspecialchars($dev_otp); ?>
                Real Gmail delivery activates after configuring <code>config/mail_config.php</code>.
              </span>
            </div>
          <?php endif; ?>

          <?php if ($step === 'enter_email'): ?>
            <!-- ==========================================
                 STEP 1: ENTER REGISTERED EMAIL
                 ========================================== -->
            <div class="card-header-flex">
              <div class="card-avatar-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
              </div>
              <div>
                <h2>Forgot Password?</h2>
                <p>Enter your registered email to receive a 6-digit OTP.</p>
              </div>
            </div>

            <form action="auth/forgot_password_process.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
              <input type="hidden" name="action" value="send_otp" />

              <div class="form-group">
                <label for="email" class="form-label">Registered Email Address <span class="required-star">*</span></label>
                <div class="input-icon-group">
                  <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                  </svg>
                  <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required />
                </div>
              </div>

              <button type="submit" class="btn-submit-primary">Send OTP &rarr;</button>

              <p class="auth-switch-text">
                Remembered your password? <a href="login.php">Back to Login</a>
              </p>
            </form>

          <?php elseif ($step === 'verify_otp'): ?>
            <!-- ==========================================
                 STEP 2: 6 EMPTY BOXES + VERIFY OTP BUTTON
                 ========================================== -->
            <div class="card-header-flex">
              <div class="card-avatar-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
              </div>
              <div>
                <h2>Verify OTP</h2>
                <p>Enter OTP sent to your registered email</p>
              </div>
            </div>

            <div style="margin-bottom: 0.5rem;">
              <span class="masked-email-badge"><?php echo htmlspecialchars($masked_email); ?></span>
            </div>

            <form id="otpForm" action="auth/forgot_password_process.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
              <input type="hidden" name="action" value="verify_otp" />

              <!-- 6 Empty OTP Digit Boxes -->
              <div class="otp-grid" id="otpGrid">
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" required />
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
              </div>

              <div class="otp-meta-row">
                <a href="forgot_password.php?reset=1" class="forgot-link">&larr; Change email</a>
                <button type="submit" form="resendForm" class="btn-link-inline">Resend OTP</button>
              </div>

              <button type="submit" class="btn-submit-primary">Verify OTP &rarr;</button>
            </form>

            <!-- Hidden Resend Form -->
            <form id="resendForm" action="auth/forgot_password_process.php" method="POST" style="display:none;">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
              <input type="hidden" name="action" value="send_otp" />
              <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['fp_email'] ?? ''); ?>" />
            </form>

          <?php elseif ($step === 'reset_password'): ?>
            <!-- ==========================================
                 STEP 3: CHANGE PASSWORD & CONFIRM PASSWORD
                 ========================================== -->
            <div class="card-header-flex">
              <div class="card-avatar-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </div>
              <div>
                <h2>Set New Password</h2>
                <p>Create a strong password for <?php echo htmlspecialchars($masked_email); ?></p>
              </div>
            </div>

            <form id="resetPasswordForm" action="auth/forgot_password_process.php" method="POST" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
              <input type="hidden" name="action" value="reset_password" />

              <!-- Change Password -->
              <div class="form-group">
                <label for="new_password" class="form-label">Change Password <span class="required-star">*</span></label>
                <div class="input-icon-group">
                  <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                  <input type="password" id="new_password" name="new_password" class="form-control has-toggle" placeholder="Min. 8 characters" required />
                  <button type="button" class="password-toggle" data-target="new_password" aria-label="Show password">
                    <svg class="icon-eye-off" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                    <svg class="icon-eye" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                </div>
                <div class="field-error" id="newPassError">Password must be at least 8 characters.</div>
              </div>

              <!-- Confirm New Password -->
              <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password <span class="required-star">*</span></label>
                <div class="input-icon-group">
                  <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                  <input type="password" id="confirm_password" name="confirm_password" class="form-control has-toggle" placeholder="Re-enter new password" required />
                  <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show confirm password">
                    <svg class="icon-eye-off" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                    <svg class="icon-eye" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                </div>
                <div class="field-error" id="confirmPassError">Passwords do not match.</div>
              </div>

              <button type="submit" class="btn-submit-primary">Change Password &rarr;</button>
            </form>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

<script>
  // 1. 6-Box OTP Auto-Advance, Backspace & Paste Handler
  const otpBoxes = document.querySelectorAll('.otp-box');
  if (otpBoxes.length) {
    otpBoxes[0].focus();

    otpBoxes.forEach((box, idx) => {
      box.addEventListener('input', (e) => {
        box.value = box.value.replace(/[^0-9]/g, '').slice(0, 1);
        if (box.value && idx < otpBoxes.length - 1) {
          otpBoxes[idx + 1].focus();
        }
      });

      box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && idx > 0) {
          otpBoxes[idx - 1].focus();
        }
      });

      box.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData)
          .getData('text')
          .replace(/\D/g, '')
          .slice(0, 6);

        pasted.split('').forEach((char, i) => {
          if (otpBoxes[i]) otpBoxes[i].value = char;
        });

        const nextFocus = otpBoxes[Math.min(pasted.length, otpBoxes.length - 1)];
        if (nextFocus) nextFocus.focus();
      });
    });
  }

  // 2. Password Visibility Toggle
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

  // 3. Reset Password Client Validation
  const resetForm = document.getElementById('resetPasswordForm');
  if (resetForm) {
    resetForm.addEventListener('submit', (e) => {
      let valid = true;
      const newPass = document.getElementById('new_password').value;
      const confirmPass = document.getElementById('confirm_password').value;

      document.getElementById('newPassError').classList.toggle('visible', newPass.length < 8);
      if (newPass.length < 8) valid = false;

      const match = newPass === confirmPass && confirmPass.length > 0;
      document.getElementById('confirmPassError').classList.toggle('visible', !match);
      if (!match) valid = false;

      if (!valid) e.preventDefault();
    });
  }
</script>
</body>
</html>