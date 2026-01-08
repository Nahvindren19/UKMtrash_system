<?php
include 'database.php';

$email = $_GET['email'] ?? '';
$error = "";

if(isset($_POST['verify_code'])){
    $code = $_POST['code'];

    $check = $conn->prepare("SELECT * FROM user WHERE email=? AND reset_code=?");
    $check->bind_param("ss", $email, $code);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();

        // ✅ Check expiry
        if(strtotime($user['reset_expiry']) >= time()){
            header("Location: new_password.php?email=$email");
            exit();
        } else {
            $error = "Reset code has expired!";
        }
    } else {
        $error = "Invalid reset code!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code | Trash Management System</title>
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
            --error: rgba(255, 71, 87, 0.1);
            --error-text: #ff4757;
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

        .auth-container {
            width: 100%;
            max-width: 480px;
        }

        .auth-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            border-top: 6px solid var(--accent);
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-logo {
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

        .auth-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .auth-header p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.5;
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
            text-align: center;
            letter-spacing: 8px;
            font-weight: 600;
        }

        .input-wrapper input::placeholder {
            letter-spacing: normal;
            font-weight: normal;
            color: var(--muted);
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(127, 196, 155, 0.15);
            letter-spacing: 8px;
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

        /* Responsive */
        @media (max-width: 768px) {
            .auth-card {
                padding: 30px 25px;
            }
            
            .auth-header h2 {
                font-size: 1.5rem;
            }
            
            body {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 25px 20px;
            }
            
            .auth-logo {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2>Verify Reset Code</h2>
                <p>Enter the 6-digit verification code sent to your email</p>
            </div>

            <?php if($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>6-Digit Verification Code</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="text" 
                               name="code" 
                               placeholder="Enter 6-digit code" 
                               required 
                               maxlength="6"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>

                <button type="submit" name="verify_code">
                    <i class="fas fa-check"></i> Verify Code
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto-focus on input
        document.querySelector('input[name="code"]').focus();
        
        // Format code input
        document.querySelector('input[name="code"]').addEventListener('keyup', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 6) value = value.substring(0, 6);
            this.value = value;
        });
    </script>
</body>
</html>