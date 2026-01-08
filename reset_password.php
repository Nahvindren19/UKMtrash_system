<?php
session_start();
include 'database.php';

if(!isset($_SESSION['ID'])){
    header("Location: index.php");
    exit();
}

$id = $_SESSION['ID'];
$category = $_SESSION['category'];

$error = "";
$success = "";

// Determine if user must reset password
$force_reset = false;

if($category == 'Cleaning Staff'){
    $stmt = $conn->prepare("SELECT change_password FROM CleaningStaff WHERE ID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if($row && $row['change_password'] == 0){
        $force_reset = true; // must reset first login
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if($new_password != $confirm_password){
        $error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in User table
        $stmt = $conn->prepare("UPDATE User SET password = ? WHERE ID = ?");
        $stmt->bind_param("ss", $hashed_password, $id);
        $stmt->execute();

        // Update CleaningStaff change_password flag if applicable
        if($category == 'Cleaning Staff' && $force_reset){
            $stmt = $conn->prepare("UPDATE CleaningStaff SET change_password = 1 WHERE ID = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
        }

        $success = "Password successfully updated!";

        // Redirect based on user category
        if($category == 'Cleaning Staff'){
            header("Location: cleaner_dashboard.php");
            exit();
        } elseif($category == 'Maintenance and Infrastructure Department'){
            header("Location: admin_dashboard.php");
            exit();
        } else { // students or others
            header("Location: student_dashboard.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Trash Management System</title>
    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f6fff7;
            --card: #ffffff;
            --text: #1f2d1f;
            --muted: #587165;
            --accent: #7fc49b;
            --accent-2: #a8d9b8;
            --accent-dark: #5fa87e;
            --glass: rgba(255,255,255,0.85);
            --radius: 16px;
            --radius-lg: 24px;
            --shadow: 0 10px 40px rgba(46, 64, 43, 0.08);
            --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            --success: rgba(46, 204, 113, 0.1);
            --success-text: #2ecc71;
            --error: rgba(255, 71, 87, 0.1);
            --error-text: #ff4757;
            --warning: rgba(255, 165, 0, 0.1);
            --warning-text: #ff9500;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-container {
            width: 100%;
            max-width: 480px;
        }

        .reset-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            border-top: 6px solid var(--accent);
        }

        .reset-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
        }

        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-logo {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            box-shadow: 0 8px 20px rgba(124, 196, 153, 0.25);
        }

        .reset-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .reset-header p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.5;
        }

        .force-reset-notice {
            background: var(--warning);
            color: var(--warning-text);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid rgba(255, 165, 0, 0.2);
            font-weight: 500;
        }

        .force-reset-notice i {
            margin-right: 8px;
        }

        .success {
            background: var(--success);
            color: var(--success-text);
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid rgba(46, 204, 113, 0.2);
            font-weight: 500;
        }

        .error {
            background: var(--error);
            color: var(--error-text);
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid rgba(255, 71, 87, 0.2);
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text);
            display: block;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 18px;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border-radius: 12px;
            border: 2px solid rgba(127, 196, 155, 0.2);
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background: var(--bg);
            transition: var(--transition);
        }

        .input-wrapper input::placeholder {
            color: var(--muted);
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(127, 196, 155, 0.15);
        }

        button[type="submit"] {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            box-shadow: 0 8px 25px rgba(124, 196, 153, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(124, 196, 153, 0.35);
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.85rem;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .reset-card {
                padding: 30px 25px;
            }
            
            .reset-header h2 {
                font-size: 1.5rem;
            }
            
            body {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .reset-card {
                padding: 25px 20px;
            }
            
            .reset-logo {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <div class="reset-logo">
                    <i class="fas fa-lock"></i>
                </div>
                <h2>Reset Password</h2>
                <p>Create a new password for your account</p>
            </div>

            <?php if($force_reset): ?>
                <div class="force-reset-notice">
                    <i class="fas fa-exclamation-triangle"></i>
                    Welcome! As this is your first login, you must reset your password.
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="passwordForm">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" 
                               name="new_password" 
                               id="new_password"
                               placeholder="New Password" 
                               required
                               minlength="6">
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <i class="fas fa-info-circle"></i>
                        <span>Password must be at least 6 characters</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" 
                               name="confirm_password" 
                               id="confirm_password"
                               placeholder="Confirm Password" 
                               required
                               minlength="6">
                    </div>
                    <div class="password-strength" id="passwordMatch" style="display: none;">
                        <i class="fas fa-check-circle" style="color: var(--success-text);"></i>
                        <span style="color: var(--success-text);">Passwords match</span>
                    </div>
                    <div class="password-strength" id="passwordMismatch" style="display: none;">
                        <i class="fas fa-times-circle" style="color: var(--error-text);"></i>
                        <span style="color: var(--error-text);">Passwords don't match</span>
                    </div>
                </div>

                <button type="submit">
                    <i class="fas fa-sync-alt"></i> Reset Password
                </button>
            </form>
        </div>
    </div>

    <script>
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        const passwordMismatch = document.getElementById('passwordMismatch');
        const submitBtn = document.querySelector('button[type="submit"]');
        const passwordStrength = document.getElementById('passwordStrength');

        function checkPasswordMatch() {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword === '') {
                passwordMatch.style.display = 'none';
                passwordMismatch.style.display = 'none';
                submitBtn.disabled = false;
                return;
            }
            
            if (password === confirmPassword) {
                passwordMatch.style.display = 'flex';
                passwordMismatch.style.display = 'none';
                submitBtn.disabled = false;
            } else {
                passwordMatch.style.display = 'none';
                passwordMismatch.style.display = 'flex';
                submitBtn.disabled = true;
            }
        }

        function updatePasswordStrength() {
            const password = newPasswordInput.value;
            
            if (password.length === 0) {
                passwordStrength.innerHTML = '<i class="fas fa-info-circle"></i><span>Password must be at least 6 characters</span>';
                passwordStrength.style.color = 'var(--muted)';
            } else if (password.length < 6) {
                passwordStrength.innerHTML = '<i class="fas fa-exclamation-circle" style="color: var(--error-text);"></i><span style="color: var(--error-text);">Too short (min 6 characters)</span>';
            } else if (password.length < 8) {
                passwordStrength.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: var(--warning-text);"></i><span style="color: var(--warning-text);">Weak password</span>';
            } else if (password.length < 10) {
                passwordStrength.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success-text);"></i><span style="color: var(--success-text);">Good password</span>';
            } else {
                passwordStrength.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success-text);"></i><span style="color: var(--success-text);">Strong password</span>';
            }
            
            checkPasswordMatch();
        }

        newPasswordInput.addEventListener('input', updatePasswordStrength);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        // Initialize on load
        updatePasswordStrength();
    </script>
</body>
</html>