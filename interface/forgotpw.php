<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? ($_POST['token_verify'] ?? '');
$isValid = false;
$msg = ''; // Initialize as completely empty
$email = '';
$userid = '';

// ==========================================
// TRACK 2: VERIFY THE CLICKED TOKEN IN URL
// ==========================================
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.userid, u.email 
            FROM password_resets r
            JOIN users u ON r.userid = u.userid
            WHERE r.token = ? AND r.expires_at > ?
        ");
        
        $currentPhpTime = date('Y-m-d H:i:s');
        $stmt->execute([$token, $currentPhpTime]);
        $resetRequest = $stmt->fetch();

        if ($resetRequest) {
            $isValid = true;
            $email = $resetRequest['email'];
            $userid = $resetRequest['userid'];
            $msg = ''; // 🎯 THE FIX: Explicitly guarantee the message stays empty on validation success!
        } else {
            $msg = "This reset link is invalid or has expired.";
        }
    } catch (PDOException $e) {
        $msg = "Error verifying token: " . $e->getMessage();
    }
}

// ==========================================
// TRACK 2.5: HANDLING THE NEW PASSWORD SUBMISSION FORM
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password']) && $isValid) {
    $new_password = $_POST['password'] ?? '';
    
    if (strlen($new_password) < 8) {
        $msg = "Password must be at least 8 characters long.";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE userid = ?");
            $stmt->execute([$hashed_password, $userid]);
            
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE userid = ?");
            $stmt->execute([$userid]);
            
            $msg = "Success! Your password has been changed. <a href='/interface/login.html'>Click here to Log In</a>";
            $isValid = false; 
        } catch (PDOException $e) {
            $msg = "Error updating password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | ShoreSafe</title>
    <link rel="stylesheet" href="/interface/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="reset-password-body">
    
<div class="reset-password-box">
    
    <?php if (!$isValid && empty($msg)): ?>
        <h2>Invalid Request</h2>
        <div class="reset-alert">
            <i class="fas fa-exclamation-circle"></i> No secure token session was detected.
        </div>
        <a href="/interface/login.html" class="btn-reset-submit" style="display:block; text-decoration:none; margin-top:20px; line-height:2.5;">
            Back to Login Screen
        </a>

    <?php elseif (!empty($msg) && !$isValid): ?>
        <h2>Session Notice</h2>
        <div class="reset-alert <?= strpos($msg, 'Success') !== false ? 'reset-alert-success' : ''; ?>">
            <?= $msg ?>
        </div>
        
        <?php if (strpos($msg, 'Success') === false): ?>
            <a href="/interface/login.html" class="btn-reset-submit" style="display:block; text-decoration:none; margin-top:20px; line-height:2.5;">
                Return to Login & Retry
            </a>
        <?php endif; ?>

<?php else: ?>
        <h2>Reset Your Password</h2>
        
        <?php if (!empty($msg)): ?>
            <div class="reset-alert <?= strpos($msg, 'Success') !== false ? 'reset-alert-success' : ''; ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']) ?>">
            <input type="hidden" name="update_password" value="1">
            <input type="hidden" name="token_verify" value="<?= htmlspecialchars($token) ?>">
            
            <p class="reset-account-meta"></p>
            
            <input type="password" name="password" placeholder="Enter new password (min 8 chars)" required minlength="8">
            <button type="submit" class="btn-reset-submit">Update Password</button>
        </form>
    <?php endif; ?>

</div>

</body>
</html>