<?php
require_once __DIR__ . '/../../config/database.php';

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$message = '';
$is_verified = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($email) ) {
        $message = "Please provide both your email and the 6-digit verification code.";
    } else {
        // Find user matching email and code
        $stmt = $pdo->prepare("SELECT userid, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['email_verified'] == 1) {
                $message = "Your email is already verified. Your account is waiting for Admin approval.";
                $is_verified = true;
            } else {
                // Update email_verified status and clear verification code
                $updateStmt = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE userid = ?");
                $updateStmt->execute([$user['userid']]);
                
                $message = "Email successfully verified! Please login back after 24h for administration approval.";
                $is_verified = true;
            }
        } else {
            $message = " email address. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | ShoreSafe</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f0f4f8; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 90%; }
        .code-input { font-size: 24px; letter-spacing: 10px; text-align: center; width: 180px; padding: 8px; margin: 15px 0; border: 2px solid #ccc; border-radius: 6px; }
        .btn-submit { background-color: #0077b6; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn-submit:hover { background-color: #005f92; }
        .form-group { text-align: left; margin-bottom: 12px; }
        .form-group label { font-size: 14px; color: #555; }
        .form-group input[type="email"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        a { display: inline-block; margin-top: 15px; text-decoration: none; color: #0077b6; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🌊 ShoreSafe Verification</h2>

        <?php if ($message): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (!$is_verified): ?>
            <form action="verify_email.php?email=<?php echo urlencode($email); ?>" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required placeholder="your@email.com">
                </div>

                <div class="form-group" style="text-align: center;">
                    <label for="verification_code">6-Digit Code</label><br>
                    <input type="text" name="verification_code" id="verification_code" class="code-input" maxlength="6" pattern="\d{6}" placeholder="123456" required autofocus>
                </div>

                <button type="submit" class="btn-submit">Verify Account</button>
            </form>
        <?php endif; ?>

        <a href="/index.php?page=login">Return to Login Page</a>
    </div>
</body>
</html>