<?php
// interface/forgotpw.php
require_once __DIR__ . '/../logic/controller/forgotpasswort.php'; 
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
        <a href="/interface/login.html" class="btn-reset-submit" style="display:block; text-decoration:none; margin-top:20px; line-height:2.5; text-align:center;">
            Back to Login Screen
        </a>

    <?php elseif (!empty($msg) && !$isValid): ?>
        <h2>Session Notice</h2>
        <div class="reset-alert <?= strpos($msg, 'Success') !== false ? 'reset-alert-success' : ''; ?>">
            <?= $msg ?>
        </div>
        
        <?php if (strpos($msg, 'Success') === false): ?>
            <a href="/interface/login.html" class="btn-reset-submit" style="display:block; text-decoration:none; margin-top:20px; line-height:2.5; text-align:center;">
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

        <form method="POST" action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME'] . '?token=' . $token) ?>">
            <input type="hidden" name="update_password" value="1">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <p class="reset-account-meta">Resetting password for: <strong><?= htmlspecialchars($email) ?></strong></p>
            
            <input type="password" name="password" placeholder="Enter new password (min 8 chars)" required minlength="8">
            <button type="submit" class="btn-reset-submit">Update Password</button>
        </form>
    <?php endif; ?>

</div>

</body>
</html>