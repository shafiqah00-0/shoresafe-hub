<?php
// /logic/controller/get_pending_users.php
require_once __DIR__ . '/../../config/database.php';

// Fetch all pending users
$stmt = $pdo->query("SELECT userid, full_name, role_type, reg_number FROM users WHERE status = 'pending'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "<p style='text-align:center; padding: 20px;'>No pending registrations found.</p>";
} else {
    echo "<table class='audit-table' style='width:100%; border-collapse: collapse;'>";
    echo "<thead style='background: #f7fafc;'><tr>
            <th style='padding: 10px; text-align: left;'>Name</th>
            <th style='padding: 10px; text-align: left;'>Role</th>
            <th style='padding: 10px; text-align: left;'>Reg#</th>
            <th style='padding: 10px; text-align: center;'>Action</th>
          </tr></thead><tbody>";
    
    foreach ($users as $u) {
        echo "<tr>
            <td style='padding: 10px; color: white;'>{$u['full_name']}</td>
            <td style='padding: 10px; color: white;'>{$u['role_type']}</td>
            <td style='padding: 10px; color: white;'>{$u['reg_number']}</td>
            <td style='padding: 10px; text-align: center;'>
                <button type='button' class='btn-approve-user' data-userid='{$u['userid']}' 
                        style='background:#48bb78; color:white; border:none; padding:5px 12px; cursor:pointer; border-radius:4px;'>
                    Approve
                </button>
            </td>
        </tr>";
    }
    echo "</tbody></table>";
}
?>