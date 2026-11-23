<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

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
  <title>Create Account — College Connect</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/root.css" />
  <link rel="stylesheet" href="assets/css/auth.css" />
</head>
<body>

  <!-- REPLACE INCLUDE PATH HERE: Your shared navbar file -->
  <?php include 'nav.php'; ?>

  <!-- FULL-BLEED CAMPUS HERO + REGISTRATION CARD -->
  <main class="hero-auth-section">
    <div class="hero-auth-container">

      <!-- Left Column -->
      <div class="hero-left">
        <span class="hero-badge">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
          CAMPUS Q&amp;A COMMUNITY
        </span>

        <h1>Got campus doubts? Get <span class="highlight-gold">real answers</span> from seniors &amp; faculty.</h1>

        <p class="hero-description">
          Whether you are a fresher figuring out fee procedures or a student preparing for placements, connect directly with HODs, professors, seniors, and alumni.
        </p>

        <div class="glass-feature-grid">
          <div class="glass-mini-card">
            <div class="glass-mini-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </div>
            <span>Connect with Experts</span>
          </div>

          <div class="glass-mini-card">
            <div class="glass-mini-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
              </svg>
            </div>
            <span>Ask &amp; Get Answers</span>
          </div>

          <div class="glass-mini-card">
            <div class="glass-mini-icon gold">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
              </svg>
            </div>
            <span>Track Your Progress</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Floating Registration Form Card -->
      <div class="hero-right">
        <div class="auth-floating-card">
          <div class="card-header-flex">
            <div class="card-avatar-badge">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <div>
              <h2>Create your account</h2>
              <p>Join your campus network to ask doubts or help others.</p>
            </div>
          </div>

          <!-- Server Error Alert -->
          <div class="alert-banner alert-danger <?php echo !empty($error_msg) ? 'show' : ''; ?>">
            <span><?php echo htmlspecialchars($error_msg); ?></span>
          </div>

          <form id="registerForm" action="auth/register_process.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

            <!-- Row 1: Full Name & Email -->
            <div class="form-grid-2">
              <div class="form-group">
                <label for="full_name" class="form-label">Full Name <span class="required-star">*</span></label>
                <div class="input-icon-group">
                  <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                  </svg>
                  <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g., Konna Lokesh" required />
                </div>
                <div class="field-error" id="nameError">Please enter your full name (min 3 chars).</div>
              </div>

              <div class="form-group">
                <label for="email" class="form-label">Email Address <span class="required-star">*</span></label>
                <div class="input-icon-group">
                  <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                  </svg>
                  <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required />
                </div>
                <div class="field-error" id="emailError">Please enter a valid email address.</div>
              </div>
            </div>

            <!-- Row 2: Department & Role Dropdowns -->
            <div class="form-grid-2">
              <div class="form-group">
                <label for="department" class="form-label">Department / Branch <span class="required-star">*</span></label>
                <div class="input-icon-group">
                  <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/>
                  </svg>
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
                </div>
                <div class="field-error" id="deptError">Please select your department.</div>
              </div>

              <div class="form-group">
                <label for="role" class="form-label">Role <span class="required-star">*</span></label>
                <div class="input-icon-group">
                  <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  </svg>
                  <select id="role" name="role" class="form-select" required>
                    <option value="" disabled selected>Select Role</option>
                    <option value="fresher">Fresher</option>
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                    <option value="alumni">Alumni</option>
                  </select>
                </div>
                <div class="field-error" id="roleError">Please select your role.</div>
              </div>
            </div>

            <!-- Row 3: Password & Confirm Password -->
            <div class="form-grid-2">
              <div class="form-group">
                <label for="password" class="form-label">Password <span class="required-star">*</span></label>
                <div class="input-icon-group">
                  <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                  <input type="password" id="password" name="password" class="form-control has-toggle" placeholder="Min. 8 characters" required />
                  <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                    <svg class="icon-eye-off" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                    <svg class="icon-eye" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                </div>
                <div class="strength-meter"><div class="strength-bar" id="strengthBar"></div></div>
              </div>

              <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password <span class="required-star">*</span></label>
                <div class="input-icon-group">
                  <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                  <input type="password" id="confirm_password" name="confirm_password" class="form-control has-toggle" placeholder="Re-enter password" required />
                  <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show confirm password">
                    <svg class="icon-eye-off" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                    <svg class="icon-eye" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                </div>
                <div class="field-error" id="confirmError">Passwords do not match.</div>
              </div>
            </div>

            <!-- Password Requirement Callout Strip -->
            <div class="info-callout-bar" id="strengthText">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
              </svg>
              <span>Use 8+ chars with a number &amp; uppercase letter.</span>
            </div>

            <button type="submit" class="btn-submit-primary">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>
              </svg>
              Create Account &rarr;
            </button>

            <p class="auth-switch-text">
              Already have an account? <a href="login.php">Sign in here</a>
            </p>
          </form>
        </div>
      </div>

    </div>
  </main>

  <!-- REPLACE INCLUDE PATH HERE: Your shared footer file -->
  <?php include 'footer.php'; ?>

<script>
  // 1. Password Visibility Toggle (Crossed Eye -> Open Eye)
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

  // 2. Password Strength Meter
  const passwordInput = document.getElementById('password');
  const strengthBar = document.getElementById('strengthBar');
  const strengthTextSpan = document.querySelector('#strengthText span');

  passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const states = [
      { width: '0%', color: 'transparent', text: 'Use 8+ chars with a number & uppercase letter.' },
      { width: '30%', color: 'var(--color-danger)', text: 'Weak password — add uppercase & numbers.' },
      { width: '55%', color: 'var(--color-warning)', text: 'Fair password — add symbols for extra security.' },
      { width: '80%', color: 'var(--color-info)', text: 'Good password.' },
      { width: '100%', color: 'var(--color-success)', text: 'Strong password!' }
    ];

    const state = val.length === 0 ? states[0] : states[score];
    strengthBar.style.width = state.width;
    strengthBar.style.backgroundColor = state.color;
    strengthTextSpan.textContent = state.text;
  });

  // 3. Client-Side Form Validation
  document.getElementById('registerForm').addEventListener('submit', (e) => {
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
</body>
</html>