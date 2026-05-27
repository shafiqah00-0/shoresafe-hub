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

        // TEMP: plain password check (you can upgrade later)
        // Replace your old plain check with this smart check:
            if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) { 

            $_SESSION['userid'] = $user['userid'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_type'] = $user['role_type'];

            $response['success'] = true;
            if ($user['role_type'] === 'admin') {
             $response['redirect'] = '/interface/admin-dashboard.php';
            } elseif ($user['role_type'] === 'authorities') {
                 $response['redirect'] = '/interface/dashboard/authorities.php';
            } elseif ($user['role_type'] === 'stakeholders') {
                 $response['redirect'] = '/interface/dashboard/stakeholders.php';
            } else {
            // Default fallback (e.g., for general users or guests)
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