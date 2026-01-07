<?php
include 'database.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $category = 'Student';

    // Basic validation
    if (empty($id) || empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        // Check if ID already exists
        $check = $conn->prepare("SELECT ID FROM User WHERE ID = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "Student ID already exists!";
        } else {
            // Check if email already exists
            $checkEmail = $conn->prepare("SELECT email FROM User WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            $checkEmail->store_result();
            
            if ($checkEmail->num_rows > 0) {
                $error = "Email already registered!";
            } else {
                // Insert into User table using prepared statement
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt1 = $conn->prepare("INSERT INTO User (ID, password, name, category, email) VALUES (?, ?, ?, ?, ?)");
                $stmt1->bind_param("sssss", $id, $hashedPassword, $name, $category, $email);
                
                // Insert into Student table
                $stmt2 = $conn->prepare("INSERT INTO Student (ID) VALUES (?)");
                $stmt2->bind_param("s", $id);
                
                if ($stmt1->execute() && $stmt2->execute()) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Registration - Trash Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f6fff7;
            --card: #ffffff;
            --text: #1f2d1f;
            --muted: #587165;
            --accent: #7fc49b;
            --accent-dark: #5fa87e;
            --accent-light: #a8d9b8;
            --radius: 16px;
            --radius-lg: 24px;
            --shadow: 0 10px 40px rgba(46, 64, 43, 0.08);
            --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            --success: rgba(46, 204, 113, 0.1);
            --success-text: #2ecc71;
            --error: rgba(255, 71, 87, 0.1);
            --error-text: #ff4757;
            --warning: rgba(241, 196, 15, 0.1);
            --warning-text: #f1c40f;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background-image: radial-gradient(circle at 10% 20%, rgba(127, 196, 155, 0.1) 0%, transparent 20%),
                            radial-gradient(circle at 90% 80%, rgba(95, 168, 126, 0.1) 0%, transparent 20%);
        }

        .registration-container {
            width: 100%;
            max-width: 480px;
        }

        .header-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(124, 196, 153, 0.25);
            color: white;
            font-size: 32px;
        }

        .header-logo h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .header-logo p {
            color: var(--muted);
            font-size: 14px;
        }

        .registration-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow);
            border-top: 6px solid var(--accent);
            position: relative;
            overflow: hidden;
        }

        .registration-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
        }

        .registration-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .registration-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 10px;
        }

        .registration-header p {
            color: var(--muted);
            font-size: 15px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.5s ease;
        }

        .alert-success {
            background: var(--success);
            border-left: 4px solid var(--success-text);
            color: var(--success-text);
        }

        .alert-error {
            background: var(--error);
            border-left: 4px solid var(--error-text);
            color: var(--error-text);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label .required {
            color: var(--error-text);
            font-size: 12px;
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
            font-size: 16px;
        }

        .form-input {
            width: 100%;
            padding: 14px 14px 14px 46px;
            border: 2px solid rgba(127, 196, 155, 0.2);
            border-radius: var(--radius);
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background: var(--bg);
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(127, 196, 155, 0.15);
        }

        .form-input::placeholder {
            color: rgba(88, 113, 101, 0.5);
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            border-radius: 2px;
            background: rgba(127, 196, 155, 0.2);
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak {
            background: var(--error-text);
            width: 33%;
        }

        .strength-medium {
            background: var(--warning-text);
            width: 66%;
        }

        .strength-strong {
            background: var(--success-text);
            width: 100%;
        }

        /* Button Styles - Made more prominent and clickable */
        .button-group {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px 24px;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            text-align: center;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        /* Primary Register Button - Made more prominent */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            box-shadow: 0 8px 25px rgba(124, 196, 153, 0.25);
            border: 2px solid var(--accent);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--accent-dark), #4f9e71);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(124, 196, 153, 0.35);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        /* Secondary Login Button - Good contrast but different */
        .btn-secondary {
            background: white;
            color: var(--accent-dark);
            border: 2px solid var(--accent);
            box-shadow: 0 4px 15px rgba(127, 196, 155, 0.15);
        }

        .btn-secondary:hover {
            background: var(--accent-light);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(127, 196, 155, 0.25);
        }

        .btn i {
            font-size: 18px;
        }

        /* Additional info */
        .form-info {
            margin-top: 20px;
            padding: 15px;
            background: rgba(127, 196, 155, 0.08);
            border-radius: var(--radius);
            font-size: 13px;
            color: var(--muted);
            text-align: center;
        }

        .form-info a {
            color: var(--accent-dark);
            font-weight: 600;
            text-decoration: none;
        }

        .form-info a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .registration-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--muted);
            font-size: 13px;
            padding-top: 20px;
            border-top: 1px solid rgba(127, 196, 155, 0.1);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .registration-card {
                padding: 30px 20px;
            }
            
            .registration-header h2 {
                font-size: 1.5rem;
            }
            
            .btn {
                padding: 14px 20px;
                font-size: 15px;
            }
        }

        /* Focus states for accessibility */
        .btn:focus, .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(127, 196, 155, 0.3);
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="header-logo">
            <div class="logo-circle">
                <i class="fas fa-recycle"></i>
            </div>
            <h1>Trash Management System</h1>
            <p>University Kebangsaan Malaysia</p>
        </div>

        <div class="registration-card">
            <div class="registration-header">
                <h2><i class="fas fa-user-plus"></i> Student Registration</h2>
                <p>Create your account to submit and track complaints</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registrationForm">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i> Student ID
                        <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user-graduate"></i>
                        <input type="text" class="form-input" name="id" placeholder="e.g., UKM123456" required
                               value="<?= isset($_POST['id']) ? htmlspecialchars($_POST['id']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Full Name
                        <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-signature"></i>
                        <input type="text" class="form-input" name="name" placeholder="Enter your full name" required
                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                        <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-at"></i>
                        <input type="email" class="form-input" name="email" placeholder="student@example.com" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                        <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" class="form-input" name="password" id="password" 
                               placeholder="Minimum 6 characters" required
                               oninput="checkPasswordStrength(this.value)">
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <small style="display: block; margin-top: 5px; color: var(--muted); font-size: 12px;">
                        <i class="fas fa-info-circle"></i> Password must be at least 6 characters long
                    </small>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                    
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Back to Login
                    </a>
                </div>
            </form>

            <div class="form-info">
                <p><i class="fas fa-shield-alt"></i> Your information is securely stored. By registering, you agree to our terms of service.</p>
            </div>

            <div class="registration-footer">
                <p>© <?= date('Y') ?> University Kebangsaan Malaysia - Trash Management System</p>
                <p style="margin-top: 5px; font-size: 12px;">For assistance, contact system administrator</p>
            </div>
        </div>
    </div>

    <script>
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('strengthBar');
        let strength = 0;
        
        // Reset
        strengthBar.className = 'strength-bar';
        
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        // Apply strength classes
        if (password.length > 0) {
            if (strength < 2) {
                strengthBar.className = 'strength-bar strength-weak';
            } else if (strength < 4) {
                strengthBar.className = 'strength-bar strength-medium';
            } else {
                strengthBar.className = 'strength-bar strength-strong';
            }
        }
    }

    // Form validation
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long!');
            return false;
        }
    });

    // Add focus effects
    const inputs = document.querySelectorAll('.form-input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
    </script>
</body>
</html>