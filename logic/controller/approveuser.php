<?php
// /logic/controller/get_pending_users.php
session_start();

// Prevent page redirect loops on AJAX calls
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "<p style='color:#e53e3e; text-align:center; padding: 20px;'>Session expired. Please log in again.</p>";
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$role = $_GET['role'] ?? 'authorities';

// Build dynamic WHERE clause based on tab selection
if ($role === 'authorities' || $role === 'stakeholders') {
    $stmt = $pdo->prepare("SELECT userid, full_name, role_type, reg_number, email FROM users WHERE status = 'pending' AND role_type = :role");
    $stmt->execute(['role' => $role]);
} else {
    // Default or 'all' fallback
    $stmt = $pdo->query("SELECT userid, full_name, role_type, reg_number, email FROM users WHERE status = 'pending' AND role_type IN ('authorities', 'stakeholders')");
}

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "<p style='text-align:center; padding: 20px; color: #a0aec0;'>No pending registrations found.</p>";
    exit();
}
?>

<table class='audit-table' style='width:100%; border-collapse: collapse;'>
    <thead style='background: #f7fafc;'>
        <tr>
            <th style='padding: 10px; text-align: left; color: #4a5568;'>Name</th>
            <th style='padding: 10px; text-align: left; color: #4a5568;'>Role</th>
            <th style='padding: 10px; text-align: left; color: #4a5568;'>Reg#</th>
            <th style='padding: 10px; text-align: center; color: #4a5568;'>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
            <?php $jsonData = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); ?>
            <tr style='border-bottom: 1px solid #edf2f7;'>
                <td style='padding: 10px; font-weight: 500;'><?= htmlspecialchars($u['full_name']) ?></td>
                <td style='padding: 10px; color: #718096;'><?= htmlspecialchars($u['role_type']) ?></td>
                <td style='padding: 10px; color: #718096;'><?= htmlspecialchars($u['reg_number']) ?></td>
                <td style='padding: 10px; text-align: center;'>
                    <button type='button' onclick='openUserDetailModal(<?= $jsonData ?>)' 
                            style='background:#3182ce; color:white; border:none; padding:5px 12px; cursor:pointer; border-radius:4px;'>
                        <i class='fas fa-eye'></i> View
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>