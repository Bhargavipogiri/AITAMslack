
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AITAM Slack | My Profile</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Project CSS -->
    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/my_profile.css">
</head>

<body>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <!-- Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <main class="page-content profile-page">

            <!-- Page Heading -->
            <div class="page-heading">
                <h2>My Profile</h2>
                <p>View and manage your personal account information.</p>
            </div>

            <!-- Profile Layout -->
            <div class="profile-layout">

                <!-- Profile Overview -->
                <section class="profile-overview">

                    <div class="profile-cover"></div>

                    <div class="profile-overview-content">

                        <div class="profile-image-container">

                            <img
                                src="https://placehold.co/140x140/167D8D/FFFFFF?text=Admin"
                                id="profilePreview"
                                alt="Admin Profile Picture"
                            >

                            <label for="profilePicture" class="camera-icon"
                                   title="Change profile picture">
                                <i class="bi bi-camera-fill"></i>
                            </label>

                            <input type="file"
                                   id="profilePicture"
                                   accept="image/png,image/jpeg,image/webp"
                                   hidden>

                        </div>

                        <h3 id="profileName">Admin Name</h3>

                        <p class="profile-role">
                            <i class="bi bi-shield-check"></i>
                            Administrator
                        </p>

                        <p class="profile-email" id="profileEmail">
                            admin@aitam.edu
                        </p>

                        <span class="profile-active">
                            <i class="bi bi-circle-fill"></i>
                            Active Account
                        </span>

                    </div>

                    <div class="profile-overview-footer">
                        <div>
                            <i class="bi bi-calendar-check"></i>
                            <span>Member Since</span>
                            <strong>Not available</strong>
                        </div>
                    </div>

                </section>

                <!-- Personal Information -->
                <section class="profile-card">

                    <div class="section-heading">

                        <div>
                            <h4>Personal Information</h4>
                            <p>Manage your personal details.</p>
                        </div>

                        <button type="button"
                                class="edit-btn"
                                id="editProfileBtn">
                            <i class="bi bi-pencil-square"></i>
                            Edit Profile
                        </button>

                    </div>

                    <form id="profileForm">

                        <div class="profile-form-grid">

                            <div class="form-group">
                                <label for="fullName">Full Name</label>
                                <input type="text"
                                       id="fullName"
                                       value="Admin Name"
                                       disabled
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email"
                                       id="email"
                                       value="admin@aitam.edu"
                                       disabled
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text"
                                       id="department"
                                       value="Administration"
                                       disabled>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel"
                                       id="phone"
                                       value=""
                                       placeholder="Enter phone number"
                                       pattern="[0-9]{10}"
                                       maxlength="10"
                                       disabled>
                            </div>

                            <div class="form-group">
                                <label for="role">Account Role</label>
                                <input type="text"
                                       id="role"
                                       value="Administrator"
                                       disabled>
                            </div>

                            <div class="form-group">
                                <label for="accountStatus">Account Status</label>
                                <input type="text"
                                       id="accountStatus"
                                       value="Active"
                                       disabled>
                            </div>

                        </div>

                        <div class="profile-actions" id="profileActions">

                            <button type="submit" class="save-btn">
                                <i class="bi bi-check-lg"></i>
                                Save Changes
                            </button>

                            <button type="button"
                                    class="cancel-btn"
                                    id="cancelProfileBtn">
                                Cancel
                            </button>

                        </div>

                    </form>

                </section>

                <!-- Account Information -->
                <section class="profile-card">

                    <div class="section-heading">
                        <div>
                            <h4>Account Information</h4>
                            <p>Details about your administrator account.</p>
                        </div>
                    </div>

                    <div class="account-info-grid">

                        <div class="account-info-item">
                            <i class="bi bi-person-badge"></i>
                            <div>
                                <span>Account Type</span>
                                <strong>Administrator</strong>
                            </div>
                        </div>

                        <div class="account-info-item">
                            <i class="bi bi-patch-check"></i>
                            <div>
                                <span>Verification</span>
                                <strong>Not available</strong>
                            </div>
                        </div>

                        <div class="account-info-item">
                            <i class="bi bi-calendar3"></i>
                            <div>
                                <span>Registration Date</span>
                                <strong>Not available</strong>
                            </div>
                        </div>

                        <div class="account-info-item">
                            <i class="bi bi-shield-lock"></i>
                            <div>
                                <span>Account Security</span>
                                <strong>Login not configured</strong>
                            </div>
                        </div>

                    </div>

                </section>

                <!-- Change Password -->
                <section class="profile-card password-card">

                    <div class="section-heading">
                        <div>
                            <h4>Change Password</h4>
                            <p>Update your account password.</p>
                        </div>

                        <i class="bi bi-lock password-heading-icon"></i>
                    </div>

                    <form id="passwordForm">

                        <div class="password-grid">

                            <div class="form-group">
                                <label for="currentPassword">
                                    Current Password
                                </label>

                                <div class="password-input">
                                    <input type="password"
                                           id="currentPassword"
                                           placeholder="Enter current password"
                                           disabled>

                                    <button type="button"
                                            class="password-toggle"
                                            data-target="currentPassword"
                                            aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="newPassword">
                                    New Password
                                </label>

                                <div class="password-input">
                                    <input type="password"
                                           id="newPassword"
                                           placeholder="Enter new password"
                                           minlength="8"
                                           disabled>

                                    <button type="button"
                                            class="password-toggle"
                                            data-target="newPassword"
                                            aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">
                                    Confirm New Password
                                </label>

                                <div class="password-input">
                                    <input type="password"
                                           id="confirmPassword"
                                           placeholder="Confirm new password"
                                           minlength="8"
                                           disabled>

                                    <button type="button"
                                            class="password-toggle"
                                            data-target="confirmPassword"
                                            aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                        </div>

                        <button type="submit"
                                class="change-password-btn"
                                id="changePasswordBtn"
                                disabled>
                            <i class="bi bi-shield-lock"></i>
                            Change Password
                        </button>

                    </form>

                    <p class="security-note">
                        <i class="bi bi-info-circle"></i>
                        Password updates will be enabled after Admin Login
                        and authentication are implemented.
                    </p>

                </section>

            </div>

            <!-- Message -->
            <div id="profileMessage" class="profile-message"
                 role="status" aria-live="polite"></div>

        </main>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Project JS -->
    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/my_profile.js"></script>

</body>
</html>
