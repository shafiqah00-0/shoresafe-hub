<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Password | ShoreSafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="/interface/css/login.css">
</head>
<body>

<div class="box">
    <h2>Reset Your Password</h2>
    
    <?php if (!empty($msg)): ?>
        <div class="alert" style="<?= strpos($msg, 'Success') !== false ? 'background:#d1edda;color:#155724;' : ''; ?>">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <?php if ($isValid): ?>
        <form method="POST">
            <input type="hidden" name="update_password" value="1">
            <p style="font-size: 14px; color: #666;">Account ID: <strong><?= htmlspecialchars($userid) ?></strong> (<?= htmlspecialchars($email) ?>)</p>
            <input type="password" name="password" placeholder="Enter new password (min 8 chars)" required minlength="8">
            <button type="submit">Update Password</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
