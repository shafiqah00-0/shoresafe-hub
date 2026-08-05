<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name  = trim($_POST['full_name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $user_type  = $_POST['user_type'] ?? 'public';
    $reg_number = trim($_POST['reg_number'] ?? '');

    // Basic Validation
    if (!$full_name || !$username || !$email || !$password) {
        $response['message'] = "Please fill in all required fields.";
        echo json_encode($response);
        exit;
    }

    $email_parts = explode('@', $email);
    $domain = strtolower(end($email_parts));

    $blocked_public_domains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 
        'live.com', 'icloud.com', 'ymail.com', 'protonmail.com', 'aol.com'
    ];

    // Check Authority Domain (.gov requirement)
    if ($user_type === 'authorities') {
        if (!str_contains($domain, '.gov')) {
            $response['message'] = "Authorities must use an official government email address (e.g., officer@agency.gov.my).";
            echo json_encode($response);
            exit;
        }
    }

    // Check Stakeholder & Authority domain (Block free/public email providers)
    if ($user_type === 'authorities' || $user_type === 'stakeholders') {
        if (in_array($domain, $blocked_public_domains)) {
            $response['message'] = "Free domains like @{$domain} are not allowed for " . ucfirst($user_type) . " accounts. Please use an official work domain.";
            echo json_encode($response);
            exit;
        }
    }

    try {
        // 2. CHECK USERNAME UNIQUE
        $stmt = $pdo->prepare("SELECT userid FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $response['message'] = "Username is already taken.";
            echo json_encode($response);
            exit;
        }

        // 3. CHECK EMAIL UNIQUE
        $stmt = $pdo->prepare("SELECT userid FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $response['message'] = "Email address is already registered.";
            echo json_encode($response);
            exit;
        }

        // 4. GENERATE 6-DIGIT VERIFICATION CODE & PREPARE STATUS
        $verification_code = sprintf("%06d", random_int(100000, 999999));
        if ($user_type ==='authorities' || $user_type === 'stakeholders')
            {
            $status = 'pending';
            $email_verified = 0; 
        }
        else
                $status = 'active';
                $email_verified = 1; 
        // 5. INSERT USER RECORD
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role_type, reg_number, status, verification_token, email_verified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $username,
            $email,
            $hashed,
            $full_name,
            $user_type,
            !empty($reg_number) ? $reg_number : 'N/A',
            $status,
            $verification_code,
            $email_verified
        ]);


   
// Send Verification Email via Brevo HTTP API (Port 443)
        $email_sent = false;
        $apiKey = getenv('BREVO_API_KEY');

        if (!$apiKey) {
            error_log("Brevo API Key is missing from environment variables.");
        } else {
            $payload = [
                'sender'      => [
                    'name'  => 'ShoreSafe System',
                    'email' => 'iqashafiqaho9@gmail.com'
                ],
                'to'          => [
                    [
                        'email' => $email,
                        'name'  => $full_name
                    ]
                ],
                'subject'     => 'Your Verification Code - ShoreSafe',
                'htmlContent' => "<p>Hello " . htmlspecialchars($full_name) . ",</p><p>Your 6-digit verification code is: <strong>" . htmlspecialchars($verification_code) . "</strong></p>"
            ];

            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $result   = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 201 || $httpCode === 200) {
                $email_sent = true;
            } else {
                error_log("Brevo API Failure [Code $httpCode]: " . $result);
            }
        }

        // Always return success & set redirect URL so frontend AJAX can navigate
        if ($email_sent) {
    $response['success']  = true;
    $response['message']  = "Registration submitted! Please check your email for your 6-digit verification code.";
    $response['redirect'] = "/logic/controller/verify_email.php?email=" . urlencode($email);
} else {
    // Fail registration response so AJAX alerts the user and doesn't redirect
    $response['success'] = false;
    $response['message'] = "Registration failed: Unable to send verification email. Please try again later or contact support.";
}
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
exit;