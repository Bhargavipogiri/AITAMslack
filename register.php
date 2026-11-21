<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$error_msg = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account — AITAM Slack</title>
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
  <!-- LEFT PANEL: Campus Context & Discussion Illustration -->
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
        <span class="brand-pill">CAMPUS Q&amp;A COMMUNITY</span>
        <h1>Got campus doubts? Get real answers from seniors &amp; faculty.</h1>
        <p>
          Whether you are a fresher figuring out fee procedures or a student preparing for placements, connect directly with HODs, professors, seniors, and alumni.
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

  </aside>

  <!-- RIGHT PANEL: Registration Form -->
  <main class="auth-form-panel">
    <div class="top-bar-actions">
      <a href="index.php" class="back-home-link">
        &larr; Back to Home
      </a>
      <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="Toggle dark theme">
        <span id="themeIcon">&#9790;</span> <span id="themeText">Dark Mode</span>
      </button>
    </div>

    <div class="auth-card">
      <div class="auth-card-header">
        <h2>Create your account</h2>
        <p>Join your campus network to ask doubts or help others.</p>
      </div>

      <!-- Server-side Error Display -->
      <div class="alert-banner alert-danger <?php echo !empty($error_msg) ? 'show' : ''; ?>" id="serverAlert">
        <span><?php echo htmlspecialchars($error_msg); ?></span>
      </div>

      <form id="registerForm" action="auth/register_process.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

        <!-- Row 1: Full Name & Email -->
        <div class="form-grid-2">
          <div class="form-group">
            <label for="full_name" class="form-label">Full Name <span class="required-star">*</span></label>
            <input
              type="text"
              id="full_name"
              name="full_name"
              class="form-control"
              placeholder="e.g., Konna Lokesh"
              required
            />
            <div class="field-error" id="nameError">Please enter your full name (min 3 characters).</div>
          </div>

          <div class="form-group">
            <label for="email" class="form-label">Email Address <span class="required-star">*</span></label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              placeholder="you@example.com"
              required
            />
            <div class="field-error" id="emailError">Please enter a valid email address.</div>
          </div>
        </div>

        <!-- Row 2: Department & Role Dropdown -->
        <div class="form-grid-2">
          <div class="form-group">
            <label for="department" class="form-label">Department / Branch <span class="required-star">*</span></label>
            <select id="department" name="department" class="form-select" required>
              <option value="" disabled selected>Select Department</option>
              <option value="CSE">CSE (Computer Science)</option>
              <option value="CSM">CSM (AI &amp; Machine Learning)</option>
              <option value="CSD">CSD (Data Science)</option>
              <option value="IT">Information Technology (IT)</option>
              <option value="ECE">ECE</option>
              <option value="EEE">EEE</option>
              <option value="MECH">Mechanical Engineering</option>
              <option value="CIVIL">Civil Engineering</option>
              <option value="OTHER">General / Basic Sciences</option>
            </select>
            <div class="field-error" id="deptError">Please select your department.</div>
          </div>

          <div class="form-group">
            <label for="role" class="form-label">Role <span class="required-star">*</span></label>
            <select id="role" name="role" class="form-select" required>
              <option value="" disabled selected>Select Role</option>
              <option value="fresher">Fresher</option>
              <option value="student">Student</option>
              <option value="faculty">Faculty</option>
              <option value="alumni">Alumni</option>
            </select>
            <div class="field-error" id="roleError">Please select your role.</div>
          </div>
        </div>

        <!-- Row 3: Password & Confirm Password -->
        <div class="form-grid-2">
          <div class="form-group">
            <label for="password" class="form-label">Password <span class="required-star">*</span></label>
            <div class="input-wrapper">
              <input
                type="password"
                id="password"
                name="password"
                class="form-control has-toggle"
                placeholder="Min. 8 characters"
                required
              />
              <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <div class="strength-meter"><div class="strength-bar" id="strengthBar"></div></div>
            <div class="field-hint" id="strengthText">Use 8+ chars with a number &amp; uppercase letter.</div>
          </div>

          <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm Password <span class="required-star">*</span></label>
            <div class="input-wrapper">
              <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                class="form-control has-toggle"
                placeholder="Re-enter password"
                required
              />
              <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show confirm password">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <div class="field-error" id="confirmError">Passwords do not match.</div>
          </div>
        </div>

        <button type="submit" class="btn-primary">Create Account</button>

        <p class="auth-footer-text">
          Already have an account? <a href="login.php">Sign in here</a>
        </p>
      </form>
    </div>
  </main>
</div>

<script>
  // 1. Theme Switcher (Matches root.css [data-theme="dark"])
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

  // 2. Password Visibility Toggles
  document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      target.type = target.type === 'password' ? 'text' : 'password';
    });
  });

  // 3. Live Password Strength Meter
  const passwordInput = document.getElementById('password');
  const strengthBar = document.getElementById('strengthBar');
  const strengthText = document.getElementById('strengthText');

  passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const states = [
      { width: '0%', color: 'transparent', text: 'Use 8+ chars with a number & uppercase letter.' },
      { width: '30%', color: 'var(--color-danger)', text: 'Weak password' },
      { width: '55%', color: 'var(--color-warning)', text: 'Fair — add numbers or symbols' },
      { width: '80%', color: 'var(--color-info)', text: 'Good password' },
      { width: '100%', color: 'var(--color-success)', text: 'Strong password' }
    ];

    const state = val.length === 0 ? states[0] : states[score];
    strengthBar.style.width = state.width;
    strengthBar.style.backgroundColor = state.color;
    strengthText.textContent = state.text;
  });

  // 4. Client-Side Validation before PHP Submission
  const form = document.getElementById('registerForm');
  form.addEventListener('submit', (e) => {
    let isValid = true;

    const fullName = document.getElementById('full_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const dept = document.getElementById('department').value;
    const role = document.getElementById('role').value;
    const pass = passwordInput.value;
    const confirm = document.getElementById('confirm_password').value;

    document.getElementById('nameError').classList.toggle('visible', fullName.length < 3);
    if (fullName.length < 3) isValid = false;

    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    document.getElementById('emailError').classList.toggle('visible', !emailValid);
    if (!emailValid) isValid = false;

    document.getElementById('deptError').classList.toggle('visible', !dept);
    if (!dept) isValid = false;

    document.getElementById('roleError').classList.toggle('visible', !role);
    if (!role) isValid = false;

    if (pass.length < 8) isValid = false;

    const match = pass === confirm && confirm.length > 0;
    document.getElementById('confirmError').classList.toggle('visible', !match);
    if (!match) isValid = false;

    if (!isValid) e.preventDefault();
  });
</script>
<?php include 'footer.php'; ?>

</body>
</html>