<?php
session_start();
include 'database.php';

$error = ""; // optional, to show errors later

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if 'id' and 'password' exist in POST
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($id) || empty($password)) {
        $error = "Please enter ID and password!";
    } else {
        $stmt = $conn->prepare("SELECT u.*, c.change_password 
                                FROM user u 
                                LEFT JOIN CleaningStaff c ON u.ID = c.ID 
                                WHERE u.ID = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password'])) {
                $_SESSION['ID'] = $row['ID'];
                $_SESSION['name'] = $row['name']; 
                $_SESSION['category'] = $row['category'];

                switch ($row['category']) {
                    case 'Cleaning Staff':
                        if (isset($row['change_password']) && $row['change_password'] == 0) {
                            header("Location: reset_password.php");
                            exit();
                        } else {
                            header("Location: cleaner_dashboard.php");
                            exit();
                        }

                    case 'Student':
                        header("Location: student_dashboard.php");
                        exit();

                    case 'Maintenance and Infrastructure Department':
                        header("Location: admin_dashboard.php");
                        exit();

                    default:
                        $error = "Unknown user category!";
                }

            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "User not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Efficient Trash Management System - Login</title>
    
    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* MATCHING LANDING PAGE COLOR SCHEME */
        :root {
            --bg: #f6fff7;              /* soft pastel mint - matches landing */
            --card: #ffffff;
            --text: #1f2d1f;
            --muted: #587165;
            --accent: #7fc49b;          /* pastel green accent - matches landing */
            --accent-2: #a8d9b8;        /* lighter */
            --accent-dark: #5fa87e;     /* slightly darker for depth */
            --glass: rgba(255,255,255,0.85);
            --radius: 16px;
            --radius-sm: 12px;
            --shadow: 0 8px 30px rgba(46,64,43,0.08);
            --shadow-light: 0 4px 20px rgba(127, 196, 155, 0.12);
            --transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Background decorative elements matching landing page */
        body::before {
            content: '';
            position: fixed;
            top: -200px;
            right: -200px;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(127, 196, 155, 0.08), transparent 70%);
            z-index: -1;
        }
        
        body::after {
            content: '';
            position: fixed;
            bottom: -150px;
            left: -150px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(168, 217, 184, 0.05), transparent 70%);
            z-index: -1;
        }
        
        /* Login Container - matches landing page cards */
        .login-container {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid rgba(160, 200, 170, 0.15);
            position: relative;
            z-index: 10;
        }
        
        /* Header with brand - matches landing page navigation */
        .login-header {
            background: var(--card);
            padding: 40px 30px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(160, 200, 170, 0.1);
        }
        
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 25px;
            text-decoration: none;
            color: var(--text);
        }
        
        .brand-logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: contain;
            background: white;
            padding: 8px;
            box-shadow: var(--shadow-light);
            border: 2px solid rgba(0,0,0,0.04);
        }
        
        .brand-text h1 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            text-align: left;
        }
        
        .brand-text p {
            font-size: 13px;
            color: var(--muted);
            margin: 0;
            text-align: left;
        }
        
        .system-tagline {
            font-size: 15px;
            color: var(--muted);
            margin-top: 8px;
            font-weight: 400;
        }
        
        /* Form Section - matches landing page feature cards */
        .login-form {
            padding: 40px 35px;
        }
        
        .form-title {
            color: var(--text);
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }
        
        .form-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            border-radius: 2px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            color: var(--text);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .input-container {
            position: relative;
        }
        
        input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 1.5px solid rgba(160, 200, 170, 0.3);
            border-radius: var(--radius-sm);
            font-size: 15px;
            color: var(--text);
            background: #fafdfa;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }
        
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(127, 196, 155, 0.15);
            background: white;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 18px;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: var(--accent);
        }
        
        /* Login Button - matches landing page buttons */
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 8px 20px rgba(124, 196, 153, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(124, 196, 153, 0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        /* Footer Links - matches landing page footer */
        .login-footer {
            padding: 0 35px 30px;
            text-align: center;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .footer-links a {
            color: var(--accent);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .footer-links a:hover {
            color: var(--accent-dark);
            transform: translateY(-2px);
        }
        
        .copyright {
            color: var(--muted);
            font-size: 13px;
            border-top: 1px solid rgba(160, 200, 170, 0.2);
            padding-top: 20px;
        }
        
        /* Messages - matches landing page style */
        .message {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid;
        }
        
        .success {
            background-color: rgba(127, 196, 155, 0.1);
            color: var(--accent-dark);
            border-left-color: var(--accent);
        }
        
        .error {
            background-color: rgba(255, 87, 87, 0.1);
            color: #ff5757;
            border-left-color: #ff5757;
        }
        
        /* Back to Home Link */
        .back-home {
            position: absolute;
            top: 30px;
            left: 30px;
            z-index: 20;
        }
        
        .back-home a {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 10px 16px;
            border-radius: 10px;
            background: rgba(127, 196, 155, 0.1);
            transition: var(--transition);
        }
        
        .back-home a:hover {
            background: rgba(127, 196, 155, 0.2);
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .login-container {
                max-width: 100%;
            }
            
            .login-header {
                padding: 30px 20px 25px;
            }
            
            .login-form {
                padding: 30px 25px;
            }
            
            .login-footer {
                padding: 0 25px 25px;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
            
            .back-home {
                top: 20px;
                left: 20px;
            }
            
            .brand {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
            
            .brand-text h1,
            .brand-text p {
                text-align: center;
            }
        }
        
        @media (max-width: 768px) {
            .back-home {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 20px;
                display: flex;
                justify-content: center;
            }
            
            body {
                padding-top: 60px;
            }
        }
        
        /* Animation for form */
        .fade-up {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeUp 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.1) forwards;
        }
        
        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .delay-1 {
            animation-delay: 0.1s;
        }
        
        .delay-2 {
            animation-delay: 0.2s;
        }
        
        .delay-3 {
            animation-delay: 0.3s;
        }
    </style>
</head>

<body>
    <!-- Back to Home Link (matches landing page navigation) -->
    <div class="back-home">
        <a href="landing.php">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>
    </div>
    
    <div class="login-container fade-up">
        <!-- Header with Brand - matches landing page -->
        <div class="login-header">
            <a href="landing.php" class="brand">
                <!-- Replace with your UKM logo -->
                <img src="assets/ukmlogo.png" alt="UKM logo" class="brand-logo" onerror="this.style.opacity=0.5;this.title='Place assets/ukmlogo.png'">
                <div class="brand-text">
                    <h1>Efficient Trash Management</h1>
                    <p>Our College, Our Home</p>
                </div>
            </a>
            <p class="system-tagline">Kolej Kediaman UKM</p>
        </div>
        
        <!-- Login Form - matches landing page cards -->
        <div class="login-form">
            <h2 class="form-title delay-1">Welcome Back</h2>
            
            <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
                <div class="message success delay-1">
                    <i class="fas fa-check-circle"></i>
                    Password reset successful. Please login with your new password.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="#">
                <div class="form-group delay-1">
                    <label for="username">Username</label>
                    <div class="input-container">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>
                
                <div class="form-group delay-2">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="login-btn delay-3">
                    <i class="fas fa-sign-in-alt"></i>
                    Login to System
                </button>
            </form>
        </div>
        
        <!-- Footer - matches landing page footer -->
        <div class="login-footer">
            <div class="footer-links">
                <a href="forgot_password.php">
                    <i class="fas fa-key"></i>
                    Forgot Password?
                </a>
                <a href="landing.php#how">
                    <i class="fas fa-question-circle"></i>
                    Need Help?
                </a>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> Efficient Trash Management System — Kolej Kediaman UKM
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="message error" style="margin: 0 35px 20px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Password visibility toggle
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        toggleBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const isPassword = passwordInput.type === 'password';
            
            passwordInput.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
        });
        
        // Form validation with landing page style feedback
        const form = document.querySelector('form');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Clear previous error styles
            [usernameInput, passwordInput].forEach(input => {
                input.style.borderColor = '';
            });
            
            // Validate username
            if (!usernameInput.value.trim()) {
                usernameInput.style.borderColor = '#ff5757';
                usernameInput.focus();
                isValid = false;
            }
            
            // Validate password
            if (!passwordInput.value) {
                passwordInput.style.borderColor = '#ff5757';
                if (isValid) passwordInput.focus();
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Add subtle shake animation for error
                const errorInputs = [usernameInput, passwordInput].filter(input => !input.value.trim() && input.type !== 'password');
                errorInputs.forEach(input => {
                    input.style.animation = 'none';
                    setTimeout(() => {
                        input.style.animation = 'shake 0.5s ease';
                    }, 10);
                });
                
                // Create shake animation
                if (!document.querySelector('#shake-animation')) {
                    const style = document.createElement('style');
                    style.id = 'shake-animation';
                    style.textContent = `
                        @keyframes shake {
                            0%, 100% { transform: translateX(0); }
                            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                            20%, 40%, 60%, 80% { transform: translateX(5px); }
                        }
                    `;
                    document.head.appendChild(style);
                }
            }
        });
        
        // Real-time feedback matching landing page theme
        usernameInput.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = 'var(--accent)';
            }
        });
        
        passwordInput.addEventListener('input', function() {
            if (this.value) {
                this.style.borderColor = 'var(--accent)';
            }
        });
        
        // Add focus styles matching landing page
        [usernameInput, passwordInput].forEach(input => {
            input.addEventListener('focus', function() {
                this.style.boxShadow = '0 0 0 3px rgba(127, 196, 155, 0.2)';
            });
            
            input.addEventListener('blur', function() {
                this.style.boxShadow = '';
            });
        });
        
        // Trigger fade-up animations on load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.fade-up, .delay-1, .delay-2, .delay-3').forEach(el => {
                el.style.animationPlayState = 'running';
            });
        });
    </script>
</body>
</html>