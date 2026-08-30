<?php
// /logic/controller/get_pending_users.php
require_once __DIR__ . '/../../config/database.php';


// Fetch pending users based on tab selection
$stmt = $pdo->prepare("SELECT userid, full_name, role_type, reg_number, email FROM users WHERE status = 'pending' ");
 $stmt->execute(['role_type' => $role]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "<p style='text-align:center; padding: 20px; color: #a0aec0;'>No pending " . htmlspecialchars($role) . " found.</p>";
} else {
    echo "<table class='audit-table' style='width:100%; border-collapse: collapse;'>";
    echo "<thead style='background: #f7fafc;'><tr>
            <th style='padding: 10px; text-align: left; color: #4a5568;'>Name</th>
            <th style='padding: 10px; text-align: left; color: #4a5568;'>Role</th>
            <th style='padding: 10px; text-align: left; color: #4a5568;'>Reg#</th>
            <th style='padding: 10px; text-align: center; color: #4a5568;'>Action</th>
          </tr></thead><tbody>";
    
    foreach ($users as $u) {
        // Encode user object safely for JavaScript consumption
        $jsonUserData = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8');
        
        echo "<tr style='border-bottom: 1px solid #edf2f7;'>
            <td style='padding: 10px; font-weight: 500;'>" . htmlspecialchars($u['full_name']) . "</td>
            <td style='padding: 10px; color: #718096;'>" . htmlspecialchars($u['role_type']) . "</td>
            <td style='padding: 10px; color: #718096;'>" . htmlspecialchars($u['reg_number']) . "</td>
            <td style='padding: 10px; text-align: center;'>
                <button type='button' onclick='openUserDetailModal({$jsonUserData})' 
                        style='background:#3182ce; color:white; border:none; padding:5px 12px; cursor:pointer; border-radius:4px;'>
                    <i class='fas fa-eye'></i> View Details
                </button>
            </td>
        </tr>";
    }
    echo "</tbody></table>";
}
?>