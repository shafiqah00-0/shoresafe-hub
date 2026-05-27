<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../config/database.php';

// Check that this is a POST request from your login form modal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $response['message'] = "Please enter your email address.";
        echo json_encode($response);
        exit;
    }

    try {
        // 1. Find the user by email
        $stmt = $pdo->prepare("SELECT userid FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Security Best Practice: Don't confirm or deny registered status explicitly
            $response['success'] = true;
            $response['message'] = "If this is yours, lets proceed to change password.";
            echo json_encode($response);
            exit;
        }

        $userid = $user['userid'];
        $token = bin2hex(random_bytes(32)); 
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 2. Clear out old stale tokens
      // 🎯 FIX: Added the missing dollar sign ($) to your execute wrapper
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE userid = ?");
        $stmt->execute([$userid]);

        // 3. Save the fresh token session
        $stmt = $pdo->prepare("INSERT INTO password_resets (token, userid, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$token, $userid, $expires]);

        // 🎯 THE FIX: The link now points directly to forgotpw.php (the UI form file)
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/interface/forgotpw.php?token=" . $token;

        $response['success'] = true;
        $response['message'] = "Please click the link below to continue: " . $resetLink;

    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}