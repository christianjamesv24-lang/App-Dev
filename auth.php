<?php
require_once 'includes/functions.php';
requireValidCsrfToken();

$mode = isset($_GET['mode']) && is_string($_GET['mode']) ? $_GET['mode'] : 'signin';
if (!in_array($mode, ['signin', 'signup', 'recover', 'reset'], true)) {
    $mode = 'signin';
}

if ($mode !== 'reset' && isset($_SESSION['user_id'], $_SESSION['role'])) {
    $dashboard = getAuthenticatedDashboardUrl();
    if ($dashboard !== null) {
        header('Location: ' . $dashboard);
        exit();
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');

$error = '';
$success = isset($_SESSION['auth_success']) && is_string($_SESSION['auth_success'])
    ? $_SESSION['auth_success']
    : '';
$recoveryWarning = isset($_SESSION['recovery_warning']);
unset($_SESSION['auth_success']);
unset($_SESSION['recovery_warning']);
$resetToken = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';

// Handle Sign In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE is_active = 1 AND (username = ? OR email = ?)");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['last_activity'] = time();
            
            switch($user['role']) {
                case 'admin': header('Location: admin.php'); break;
                case 'assistant': header('Location: assistant.php'); break;
                default: header('Location: customer.php'); break;
            }
            exit();
        } else {
            $error = 'Invalid password!';
        }
    } else {
        $error = 'User not found!';
    }
}

// Handle Sign Up - UPDATED with First Name, Last Name, Email, Password, Confirm Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    // Check if fields are empty
    if (empty($firstname)) {
        $errors[] = 'First name is required!';
    }
    if (empty($lastname)) {
        $errors[] = 'Last name is required!';
    }
    if (empty($email)) {
        $errors[] = 'Email is required!';
    }
    if (empty($password)) {
        $errors[] = 'Password is required!';
    }
    if (empty($confirm_password)) {
        $errors[] = 'Please confirm your password!';
    }
    
    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format!';
    }
    
    // Validate password length
    if (!empty($password) && !isValidPassword($password)) {
        $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters!';
    }
    
    // Check if passwords match
    if (!empty($password) && !empty($confirm_password) && $password !== $confirm_password) {
        $errors[] = 'Passwords do not match!';
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Combine firstname and lastname
        $fullname = $firstname . ' ' . $lastname;
        
        // Check if email already exists
        $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email already registered! Please use a different email.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate username from firstname.lastname
            $username = strtolower($firstname . '.' . $lastname);
            
            // Make sure username is unique
            $base_username = $username;
            $counter = 1;
            while (true) {
                $check_user = $conn->prepare("SELECT * FROM users WHERE username = ?");
                $check_user->bind_param("s", $username);
                $check_user->execute();
                if ($check_user->get_result()->num_rows == 0) {
                    break;
                }
                $username = $base_username . $counter;
                $counter++;
            }
            
            // Insert user with role 'customer' by default
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, fullname, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->bind_param("ssss", $username, $hashed_password, $email, $fullname);
            
            if ($stmt->execute()) {
                $success = 'Account created successfully! Please sign in.';
                $mode = 'signin';
            } else {
                $error = 'Registration failed! Please try again.';
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Handle Account Recovery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recover'])) {
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            sendPasswordResetEmail($email);
        } catch (Throwable $recoveryError) {
            error_log('Password recovery unavailable: ' . $recoveryError->getMessage());
            $_SESSION['recovery_warning'] = true;
        }
    }

    if (!isset($_SESSION['recovery_warning'])) {
        $_SESSION['auth_success'] = 'If an account exists for that email, a password reset link will be sent shortly. Check your inbox and spam folder.';
    }
    header('Location: auth.php?mode=recover');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $resetToken = isset($_POST['token']) && is_string($_POST['token']) ? $_POST['token'] : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
        ? $_POST['confirm_password']
        : '';

    if (!isValidPassword($password)) {
        $error = 'Choose a password with at least ' . MIN_PASSWORD_LENGTH . ' characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        try {
            if (resetPasswordWithToken($resetToken, $password)) {
                $_SESSION['auth_success'] = 'Your password has been changed. You can now sign in.';
                header('Location: auth.php?mode=signin');
                exit();
            } else {
                $error = 'This reset link is invalid or has expired. Request a new one.';
                $mode = 'reset';
            }
        } catch (Throwable $resetError) {
            error_log('Password reset failed: ' . $resetError->getMessage());
            $error = 'We could not change your password right now. Please try again.';
            $mode = 'reset';
        }
    }
}

$resetTokenValid = false;
if ($mode === 'reset') {
    try {
        $resetTokenValid = isPasswordResetTokenValid($resetToken);
    } catch (Throwable $resetTokenError) {
        error_log('Password reset token check failed: ' . $resetTokenError->getMessage());
        $error = 'Password recovery is temporarily unavailable. Please try again later.';
    }
    if (!$resetTokenValid && $error === '') {
        $error = 'This reset link is invalid or has expired. Request a new one.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BakeTrack - Authentication</title>
    <link rel="stylesheet" href="css/style.css?v=20261111">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><span class="brand-mark" aria-hidden="true">BT</span> BakeTrack</h2>
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($recoveryWarning): ?>
                <div class="alert alert-error" role="alert">
                    Password recovery email could not be sent. Check the server’s mail configuration and error log, then try again.
                </div>
            <?php endif; ?>

            <!-- Mode Switcher -->
            <?php if ($mode !== 'reset'): ?>
            <div class="auth-tabs">
                <a href="?mode=signin" class="tab <?= $mode === 'signin' ? 'active' : '' ?>">Sign In</a>
                <a href="?mode=signup" class="tab <?= $mode === 'signup' ? 'active' : '' ?>">Sign Up</a>
                <a href="?mode=recover" class="tab <?= $mode === 'recover' ? 'active' : '' ?>">Forgot?</a>
            </div>
            <?php endif; ?>

            <!-- Sign In Form -->
            <?php if ($mode === 'signin'): ?>
            <form method="POST" class="auth-form">
                <?= csrfField() ?>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username or Email</label>
                    <input type="text" name="username" required placeholder="Enter username or email">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="signinPassword" name="password" required placeholder="Enter password">
                    <div class="show-password-control">
                        <input type="checkbox" id="showSigninPassword" class="show-password-checkbox" data-target-id="signinPassword">
                        <label for="showSigninPassword">Show password</label>
                    </div>
                </div>
                <button type="submit" name="signin" class="btn btn-primary btn-block">Sign In</button>
            </form>
            <?php endif; ?>

            <!-- Sign Up Form - UPDATED with First Name, Last Name, Email, Password, Confirm Password -->
            <?php if ($mode === 'signup'): ?>
            <form method="POST" class="auth-form" id="signupForm">
                <?= csrfField() ?>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> First Name</label>
                    <input type="text" name="firstname" required placeholder="Enter your first name">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Last Name</label>
                    <input type="text" name="lastname" required placeholder="Enter your last name">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" id="signupPassword" minlength="<?= MIN_PASSWORD_LENGTH ?>" required placeholder="Create password (min <?= MIN_PASSWORD_LENGTH ?> chars)" onkeyup="checkPasswordStrength()">
                    <div class="show-password-control">
                        <input type="checkbox" id="showSignupPassword" class="show-password-checkbox" data-target-id="signupPassword">
                        <label for="showSignupPassword">Show password</label>
                    </div>
                    <small id="passwordStrength" style="display: block; margin-top: 5px;"></small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirmPassword" required placeholder="Confirm your password" onkeyup="checkPasswordMatch()">
                    <div class="show-password-control">
                        <input type="checkbox" id="showConfirmPassword" class="show-password-checkbox" data-target-id="confirmPassword">
                        <label for="showConfirmPassword">Show password</label>
                    </div>
                    <small id="passwordMatch" style="display: block; margin-top: 5px;"></small>
                </div>
                <button type="submit" name="signup" class="btn btn-primary btn-block">Create Account</button>
            </form>
            <?php endif; ?>

            <!-- Recover Form -->
            <?php if ($mode === 'recover'): ?>
            <form method="POST" class="auth-form">
                <?= csrfField() ?>
                <p class="auth-description">Enter the email address on your account. If it matches, we’ll send you a secure link that expires in one hour.</p>
                <div class="form-group">
                    <label for="recoveryEmail"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="recoveryEmail" name="email" autocomplete="email" required placeholder="Enter your email address">
                </div>
                <button type="submit" name="recover" class="btn btn-primary btn-block">Send reset link</button>
            </form>
            <?php endif; ?>

            <?php if ($mode === 'reset' && $resetTokenValid): ?>
            <h3 class="auth-form-title">Choose a new password</h3>
            <form method="POST" class="auth-form">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="resetPassword"><i class="fas fa-lock"></i> New Password</label>
                    <input type="password" id="resetPassword" name="password" autocomplete="new-password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required placeholder="At least <?= MIN_PASSWORD_LENGTH ?> characters">
                </div>
                <div class="form-group">
                    <label for="confirmResetPassword"><i class="fas fa-check-circle"></i> Confirm Password</label>
                    <input type="password" id="confirmResetPassword" name="confirm_password" autocomplete="new-password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required placeholder="Enter the new password again">
                </div>
                <button type="submit" name="reset_password" value="1" class="btn btn-primary btn-block">Update password</button>
            </form>
            <?php elseif ($mode === 'reset' && !$resetTokenValid): ?>
                <a href="?mode=recover" class="btn btn-primary btn-block">Request a new reset link</a>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/script.js?v=20261106"></script>
    <script>
    // Password strength checker
    function checkPasswordStrength() {
        const password = document.getElementById('signupPassword').value;
        const strengthDiv = document.getElementById('passwordStrength');
        
        if (password.length === 0) {
            strengthDiv.innerHTML = '';
            strengthDiv.style.color = '';
            return;
        }
        
        let strength = 0;
        let message = '';
        let color = '';
        
        // Check length
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Check for uppercase
        if (/[A-Z]/.test(password)) strength++;
        
        // Check for lowercase
        if (/[a-z]/.test(password)) strength++;
        
        // Check for numbers
        if (/[0-9]/.test(password)) strength++;
        
        // Check for special characters
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        switch(strength) {
            case 0:
            case 1:
                message = 'Weak password';
                color = '#E74C3C';
                break;
            case 2:
            case 3:
                message = 'Fair password';
                color = '#F39C12';
                break;
            case 4:
            case 5:
                message = 'Good password';
                color = '#3498DB';
                break;
            case 6:
                message = 'Strong password!';
                color = '#2ECC71';
                break;
        }
        
        strengthDiv.innerHTML = message;
        strengthDiv.style.color = color;
    }
    
    // Password match checker
    function checkPasswordMatch() {
        const password = document.getElementById('signupPassword').value;
        const confirm = document.getElementById('confirmPassword').value;
        const matchDiv = document.getElementById('passwordMatch');
        
        if (confirm.length === 0) {
            matchDiv.innerHTML = '';
            matchDiv.style.color = '';
            return;
        }
        
        if (password === confirm) {
            matchDiv.innerHTML = '✅ Passwords match!';
            matchDiv.style.color = '#2ECC71';
        } else {
            matchDiv.innerHTML = '❌ Passwords do not match!';
            matchDiv.style.color = '#E74C3C';
        }
    }

    // Form validation before submit
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            const password = document.getElementById('signupPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < <?= MIN_PASSWORD_LENGTH ?>) {
                e.preventDefault();
                alert('Password must be at least <?= MIN_PASSWORD_LENGTH ?> characters!');
                return false;
            }
        });
    }
    </script>
</body>
</html>