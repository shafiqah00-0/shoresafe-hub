<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $response['message'] = 'Please enter both username/email and password';
        echo json_encode($response);
        exit();
    }

    try {
        // Check username OR email
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT * FROM users WHERE email = :username";
        } else {
            $sql = "SELECT * FROM users WHERE username = :username";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) { 

            // --- STATUS GATEKEEPER CHECK ---
            if (isset($user['status']) && $user['status'] !== 'active') {
                $response['message'] = 'Your account is currently under review by an administrator. Please login back after 24 hour registration.';
                echo json_encode($response);
                exit();
            }
            // --- END STATUS GATEKEEPER ---

            $_SESSION['userid'] = $user['userid'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_type'] = $user['role_type'];

            // --- AUTOMATED AUDIT LOGGING: LOGIN ---
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'] 
                         ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
                         ?? $_SERVER['REMOTE_ADDR'] 
                         ?? '0.0.0.0';

            if (strpos($ipAddress, ',') !== false) {
                $ipAddress = trim(explode(',', $ipAddress)[0]);
            }

            // Capture context values into new_values JSON column
            $payloadData = [
                'username'  => $user['username'],
                'role_type' => $user['role_type'],
                'status'    => 'success'
            ];

            $auditStmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    userid, 
                    actiondo, 
                    auditable_type, 
                    auditable_id, 
                    new_values, 
                    ip_address, 
                    created_at
                ) 
                VALUES (?, 'LOGIN', 'UserSession', ?, ?, ?, NOW())
            ");

            $auditStmt->execute([
                $user['userid'],
                $user['id'] ?? 0, // Use user numerical table index for auditable_id if it exists
                json_encode($payloadData),
                $ipAddress
            ]);
            // --- END AUDIT LOGGING ---

            $response['success'] = true;
            if ($user['role_type'] === 'admin') {
                $response['redirect'] = '/interface/dashboard/admin.php';
            } elseif ($user['role_type'] === 'authorities') {
                 $response['redirect'] = '/interface/dashboard/authorities.php';
            } elseif ($user['role_type'] === 'stakeholders') {
                 $response['redirect'] = '/interface/dashboard/stakeholders.php';
            } else {
                $response['redirect'] = '/interface/dashboard/public.php';
            }

        } else {
            $response['message'] = 'Invalid username/email or password';
        }

    } catch (PDOException $e) {
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);