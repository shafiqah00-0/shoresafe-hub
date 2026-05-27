<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'public';

    if (!$full_name || !$username || !$email || !$password) {
        $response['message'] = "Please fill in all fields";
        echo json_encode($response);
        exit;
    }

    try {

        // check username
        $stmt = $pdo->prepare("SELECT userid FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $response['message'] = "Username already taken";
            echo json_encode($response);
            exit;
        }

        // check email
        $stmt = $pdo->prepare("SELECT userid FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $response['message'] = "Email already registered";
            echo json_encode($response);
            exit;
        }

        // insert user
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role_type)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $username,
            $email,
            $hashed,
            $full_name,
            $user_type
        ]);

        $response['success'] = true;
        $response['message'] = "Registration successful!";
        $response['redirect'] = "/index.php?page=login";

    } catch (PDOException $e) {
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
exit;