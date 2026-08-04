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

    // 1. EXTRACT AND CHECK DOMAIN VALIDATION
    // $email_parts = explode('@', $email);
    // $domain = strtolower(end($email_parts));

    // $blocked_public_domains = [
    //     'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 
    //     'live.com', 'icloud.com', 'ymail.com', 'protonmail.com', 'aol.com'
    // ];

    // // Check Authority Domain (.gov requirement)
    // if ($user_type === 'authorities') {
    //     if (!str_contains($domain, '.gov')) {
    //         $response['message'] = "Authorities must use an official government email address (e.g., officer@agency.gov.my).";
    //         echo json_encode($response);
    //         exit;
    //     }
    // }

    // // Check Stakeholder & Authority domain (Block free/public email providers)
    // if ($user_type === 'authorities' || $user_type === 'stakeholders') {
    //     if (in_array($domain, $blocked_public_domains)) {
    //         $response['message'] = "Free domains like @{$domain} are not allowed for " . ucfirst($user_type) . " accounts. Please use an official work domain.";
    //         echo json_encode($response);
    //         exit;
    //     }
    // }

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

        $status = 'pending';
        $email_verified = 0; 

        // Public users are auto-active and auto-verified (1 / true)
        // Authorities & Stakeholders start as pending and unverified (0 / false)
        // if ($user_type === 'public') 
        //     THEN
        //     $status = 'active';
        //     $email_verified = 0; 
        // } else {
            // $status = 'pending';
            // $email_verified = 0; 
        // }

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
            !empty($reg_number) ? $reg_number : null,
            $status,
            $verification_code,
            $email_verified
        ]);

        // 6. SEND VERIFICATION CODE EMAIL FOR AUTHORITIES & STAKEHOLDERS
        // if ($user_type == 'public') {
        //     $subject = "Your Verification Code - ShoreSafe";
        //     $message = "Hello " . htmlspecialchars($full_name) . ",\n\n";
        //     $message .= "Thank you for registering on ShoreSafe.\n\n";
        //     // $message .= "Thank you for registering as an official " . ucfirst($user_type) . " on ShoreSafe.\n\n";
        //     $message .= "Your 6-digit verification code is:\n";
        //     $message .= "========================\n";
        //     $message .= "        " . $verification_code . "\n";
        //     $message .= "========================\n\n";
        //     $message .= "Please enter this code on the verification page to complete your registration.\n";
        //     // $message .= "Once verified, your account will be sent to the administrator for final review.";

        //     $headers = "From: no-reply@shoresafe.com\r\n";
        //     $headers .= "Reply-To: support@shoresafe.com\r\n";

        //     // Send email using PHP mail()
        //     @mail($email, $subject, $message, $headers);

        //     $response['success'] = true;
        //     $response['message'] = "Registration submitted! Please check your email for your 6-digit verification code.";
        //     // Redirect user to your code entry page (e.g., verify_code page passing email)
        //     $response['redirect'] = "/index.php?page=verify_email=" . urlencode($email);
        // } else {
        //     $response['success'] = true;
        //     $response['message'] = "Registration successful!";
        //     $response['redirect'] = "/index.php?page=login";
        // }

        $mail = new PHPMailer(true);
        // Replace PHPMailer block with Resend HTTP API (Port 443 - Never Blocked)
        $email_sent = false;
        $apiKey = getenv('RESEND_API_KEY');

if (!$apiKey) {
    error_log("Resend API Key is missing from environment variables.");
}
        $payload = [
            'from'    => 'ShoreSafe <iqashafiqaho9@gmail.com>',
            'to'      => [$email],
            'subject' => 'Your Verification Code - ShoreSafe',
            'html'    => "<p>Hello " . htmlspecialchars($full_name) . ",</p><p>Your 6-digit verification code is: <strong>" . $verification_code . "</strong></p>"
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            $email_sent = true;
        } else {
            error_log("Resend API Failure [Code $httpCode]: " . $result);
        }

//         try {
//             $mail->isSMTP();
//             $mail->Host       = 'smtp.gmail.com';
//             $mail->SMTPAuth   = true;
//             $mail->Username   = getenv('SMTP_USER') ?: 'iqashafiqaho9@gmail.com'; 
//             $mail->Password   = getenv('SMTP_PASS') ?: 'azwuytwflpcabfid';
//             $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
//             $mail->Port       = 465;

//             // Optional: Timeout setting to prevent long hanging connections
//             $mail->Timeout    = 20; 

//             // Disable SSL certificate verification for local development
// $mail->SMTPOptions = array(
//     'ssl' => array(
//         'verify_peer' => false,
//         'verify_peer_name' => false,
//         'allow_self_signed' => true
//     )
// );

//             $mail->setFrom('iqashafiqaho9@gmail.com', 'ShoreSafe System');
//             $mail->addAddress($email, $full_name);

//             $mail->isHTML(false);
//             $mail->Subject = 'Your Verification Code - ShoreSafe';
//             $mail->Body    = "Hello " . $full_name . ",\n\nYour 6-digit verification code is: " . $verification_code;

//             $mail->send();
//             $email_sent = true;
//         } catch (Exception $e) {
//             // Log mail error message to PHP error log for debugging
//             error_log("Mailer Error: " . $mail->ErrorInfo);
//         }

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