<?php
// logic/controller/forgotpasswort.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep at 0 so raw errors don't corrupt AJAX responses

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// =========================================================================
// BRANCH 1: AJAX EMAIL SUBMISSION (From the Modal inside login.html)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    header('Content-Type: application/json');
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email address is required.']);
        exit;
    }

    try {
        // 1. Fetch user data
        $stmt = $pdo->prepare("SELECT userid FROM users WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'This email address is not registered.']);
            exit;
        }

        $userid = strval($user['userid']);
        
        // 🎯 FIX 1: Generate a 40-character token so it fits perfectly inside your character varying(64) column
        $token = bin2hex(random_bytes(20)); 



        // 3. Insert the new row using native PostgreSQL intervals to prevent timestamp errors
        $stmt = $pdo->prepare("INSERT INTO password_resets (token, userid, expires_at) VALUES (:token, :userid, NOW() + INTERVAL '1 hour')");
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':userid', $userid, PDO::PARAM_STR);
        $stmt->execute();

        // 4. Build the dynamic reset link pointing to your actual template interface path
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/interface/forgotpw.php?token=" . $token;

        echo json_encode([
            'success' => true,
            'message' => "Link generated successfully! " . $resetLink
        ]);
        exit; 

    } catch (PDOException $e) {
        // Return the exact error so you can see it in your browser console if it fails
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// =========================================================================
// BRANCH 2: TEMPLATE PAGE LOAD LOGIC (For interface/forgotpw.php)
// =========================================================================
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$isValid = false;
$msg = ''; 
$email = '';
$userid = '';

// Track 2.1: Verify incoming URL token parameters against PostgreSQL server time
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.userid, u.email 
            FROM password_resets r
            JOIN users u ON r.userid = u.userid
            WHERE r.token = ? AND r.expires_at > NOW()
        ");
        
        $stmt->execute([$token]);
        $resetRequest = $stmt->fetch();

        if ($resetRequest) {
            $isValid = true;
            $email = $resetRequest['email'];
            $userid = $resetRequest['userid'];
        } else {
            $msg = "This reset link is invalid or has expired.";
        }
    } catch (PDOException $e) {
        $msg = "Error verifying token: " . $e->getMessage();
    }
}

// Track 2.2: Handle password replacement updates 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    // 🎯 FIX 2: Only execute password changes if the token validation step set $isValid to TRUE
    if ($isValid) {
        $new_password = $_POST['password'] ?? '';
        
        if (strlen($new_password) < 8) {
            $msg = "Password must be at least 8 characters long.";
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update the user's password
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE userid = ?");
                $stmt->execute([$hashed_password, $userid]);
                
                
                $msg = "Success! Your password has been changed. <a href='/interface/login.html'>Click here to Log In</a>";
                $isValid = false; 
            } catch (PDOException $e) {
                $msg = "Error updating password: " . $e->getMessage();
            }
        }
    } else {
        $msg = "Session verification failed. Please return to the login page and request a new link.";
    }
}