<?php
session_start();
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
            header("Location: new_password.php?email=" . urlencode($email));
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
<title>Verify Code | Trash Management</title>

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
    --radius-lg: 24px;
    --shadow: 0 15px 40px rgba(46, 64, 43, 0.12);
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Inter', system-ui;
    background: linear-gradient(135deg, #f6fff7, #e9f7ef);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
}

/* Login Card */
.login-container {
    background: var(--card);
    width: 420px;
    padding: 40px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    text-align: center;
}

.login-logo {
    width: 120px;
    height: auto;
    margin-bottom: 15px;
}

.login-container h2 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    color: var(--text);
}

.login-container p {
    color: var(--muted);
    margin-bottom: 30px;
}

/* Code Input */
.code-input-container {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 25px;
}

.code-input {
    width: 50px;
    height: 60px;
    text-align: center;
    font-size: 24px;
    font-weight: 600;
    border: 2px solid #ddd;
    border-radius: 12px;
    background: #f9f9f9;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
}

.code-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(127, 196, 155, 0.1);
    background: white;
}

.code-input.filled {
    border-color: var(--accent);
    background: white;
}

/* Hidden input for form submission */
.hidden-code-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

/* Button */
.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
    margin-top: 10px;
}

.btn-submit:hover {
    opacity: 0.95;
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(127, 196, 155, 0.3);
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Links */
.links {
    margin-top: 20px;
    font-size: 13px;
    color: var(--muted);
}

.links a {
    color: var(--accent-dark);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.links a:hover {
    text-decoration: underline;
    color: var(--accent);
}

/* Back Button */
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
    transition: color 0.2s;
}

.btn-back:hover {
    color: var(--accent-dark);
}

/* Messages */
.message {
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    text-align: left;
}

.success {
    background: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
    border-left: 4px solid #2ecc71;
}

.error {
    background: rgba(255, 71, 87, 0.1);
    color: #ff4757;
    border-left: 4px solid #ff4757;
}

/* Email Display */
.email-display {
    background: rgba(127, 196, 155, 0.05);
    border: 1px solid rgba(127, 196, 155, 0.2);
    border-radius: 12px;
    padding: 12px 15px;
    margin-bottom: 25px;
    font-size: 14px;
    color: var(--muted);
}

.email-display strong {
    color: var(--text);
}

/* Instructions */
.instructions {
    background: rgba(127, 196, 155, 0.05);
    border: 1px solid rgba(127, 196, 155, 0.2);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 25px;
    font-size: 13px;
    color: var(--muted);
    text-align: left;
}

.instructions i {
    color: var(--accent);
    margin-right: 8px;
}

/* Timer */
.timer {
    font-size: 14px;
    color: var(--muted);
    margin-bottom: 20px;
}

.timer.expired {
    color: #ff4757;
}

.timer i {
    margin-right: 8px;
}
</style>
</head>

<body>

<div class="login-container">
    <img src="assets/ukmlogo.png" class="login-logo" alt="UKM Logo">
    <h2>Verify Reset Code</h2>
    <p>Enter the 6-digit code sent to your email</p>

    <?php if ($error): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="email-display">
        <i class="fas fa-envelope"></i> Code sent to: <strong><?= htmlspecialchars($email) ?></strong>
    </div>

    <div class="instructions">
        <p><i class="fas fa-clock"></i> The code expires in 10 minutes. Check your spam folder if you don't see it.</p>
    </div>

    <form method="POST" id="verifyForm">
        <!-- Hidden input for actual form submission -->
        <input type="text" name="code" id="hiddenCode" class="hidden-code-input" required>
        
        <!-- Visual 6-digit input -->
        <div class="code-input-container">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" 
                       maxlength="1" 
                       class="code-input" 
                       data-index="<?= $i ?>"
                       oninput="moveToNext(this)"
                       onkeydown="handleKeyDown(event, <?= $i ?>)"
                       onpaste="handlePaste(event)">
            <?php endfor; ?>
        </div>

        <div class="timer">
            <i class="fas fa-clock"></i> Code expires in: <span id="countdown">10:00</span>
        </div>

        <button type="submit" name="verify_code" class="btn-submit" id="submitBtn" disabled>
            <i class="fas fa-check-circle"></i> Verify Code
        </button>
    </form>

    <div class="links">
        <a href="forgot_password.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Email Entry
        </a>
        <br><br>
        <a href="index.php">
            <i class="fas fa-sign-in-alt"></i> Back to Login
        </a>
    </div>
</div>

<script>
// Combine individual inputs into one hidden input
const codeInputs = document.querySelectorAll('.code-input');
const hiddenInput = document.getElementById('hiddenCode');
const submitBtn = document.getElementById('submitBtn');

function updateHiddenInput() {
    let code = '';
    codeInputs.forEach(input => {
        code += input.value;
    });
    hiddenInput.value = code;
    
    // Enable submit button only when all 6 digits are entered
    submitBtn.disabled = code.length !== 6;
}

function moveToNext(input) {
    const index = parseInt(input.dataset.index);
    
    // Only allow numbers
    input.value = input.value.replace(/\D/g, '');
    
    updateHiddenInput();
    
    if (input.value.length === 1 && index < 5) {
        codeInputs[index + 1].focus();
    }
}

function handleKeyDown(event, index) {
    if (event.key === 'Backspace' && !codeInputs[index].value && index > 0) {
        codeInputs[index - 1].focus();
    }
}

function handlePaste(event) {
    event.preventDefault();
    const pastedData = event.clipboardData.getData('text').replace(/\D/g, '');
    
    for (let i = 0; i < Math.min(pastedData.length, 6); i++) {
        codeInputs[i].value = pastedData[i];
        codeInputs[i].classList.add('filled');
    }
    
    if (pastedData.length >= 6) {
        codeInputs[5].focus();
    } else if (pastedData.length > 0) {
        codeInputs[pastedData.length].focus();
    }
    
    updateHiddenInput();
}

// Add focus styling
codeInputs.forEach(input => {
    input.addEventListener('focus', function() {
        this.select();
    });
    
    input.addEventListener('input', function() {
        if (this.value) {
            this.classList.add('filled');
        } else {
            this.classList.remove('filled');
        }
    });
});

// Timer countdown (10 minutes)
let timeLeft = 10 * 60; // 10 minutes in seconds
const countdownElement = document.getElementById('countdown');
const timerElement = document.querySelector('.timer');

function updateCountdown() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    
    countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        countdownElement.textContent = "00:00";
        timerElement.classList.add('expired');
        countdownElement.innerHTML = '<span style="color:#ff4757;">Expired!</span>';
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-times-circle"></i> Code Expired';
    }
    
    timeLeft--;
}

const timerInterval = setInterval(updateCountdown, 1000);

// Auto-focus first input on page load
window.addEventListener('load', () => {
    codeInputs[0].focus();
});

// Form submission handler
document.getElementById('verifyForm').addEventListener('submit', function(e) {
    if (submitBtn.disabled) {
        e.preventDefault();
        return false;
    }
    
    // Change button to loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
});
</script>

</body>
</html>