<?php 
require_once __DIR__ . '/../../config/database.php';

// Use a specific query to get only pending users
$stmt = $pdo->query("SELECT COUNT(*) as total_pending FROM users WHERE status = 'pending'");
$pending_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_pending = $pending_data['total_pending'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Audit Trail - ShoreSafe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/interface/css/dashboard.css">
    <link rel="stylesheet" href="/interface/css/audit_logs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <button class="toggle-btn" id="toggle-btn">
            <i class="fas fa-chevron-left" id="toggle-icon"></i>
        </button>

        <div class="logo">
          <span> 🌊 ShoreSafe</span> 
        </div>

        <nav>

            <a href="/logic/controller/auditlogs.php" class="active">
                <i class="fas fa-shield-alt"></i> <span>System Audit Logs</span>
            </a>
           <a href="/logic/controller/logout.php" style="display: flex; align-items: center; gap: 12px; padding: 0.8rem 1rem; color: #fc8181; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: all 0.2s;" onmouseover="this.style.color='#fff'; this.style.background='rgba(229, 62, 62, 0.2)';" onmouseout="this.style.color='#fc8181'; this.style.background='transparent';">
                <i class="fas fa-sign-out-alt" style="width: 20px;"></i> <span>Logout</span>
            </a>
        </nav>
    </aside>

    <main class="main-content" id="main-content">
        <header>
            <div>
                <h1>System Security Audit Trail</h1>
                <p style="color: #718096;">Track all modifications, creations, and data integrity operations securely.</p>
            </div>
            <button onclick="openApprovalModal()" style="border: none; background: none; cursor: pointer; padding: 0;">
            <div class="stat-card" style="background: #fff5f5; border: 1px solid #feb2b2; padding: 10px 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0; font-size: 0.7rem; color: #e53e3e; text-transform: uppercase;">Pending Approval</h3>
                <p style="margin: 0; font-size: 1.5rem; font-weight: bold; color: #c53030;"><?= $total_pending ?></p>
             </div>
                </button>
 
            <div class="user-badge" style="background: #ebf8ff; color: #2b6cb0;">
                <i class="fas fa-shield-alt"></i> <?= ucfirst($role) ?>
            </div>
        
        </header>

        <form method="GET" class="filter-card">
            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label style="font-size: 0.8rem; font-weight: 600; color: #718096;">Action Type</label>
                <select name="actiondo" id="actiondo">
                    <option value="">All Actions</option>
                    <option value="CREATE" <?= $filter_action === 'CREATE' ? 'selected' : '' ?>>CREATE</option>
                    <option value="UPDATE" <?= $filter_action === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                    <option value="DELETE" <?= $filter_action === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                </select>
            </div>
               
            <button type="submit" style="margin-top: auto;"><i class="fas fa-filter"></i> Filter Logs</button>
            <?php if(!empty($filter_action)): ?>
                <a href="audit_logs.php" style="margin-top: auto; padding: 0.6rem; color: #e53e3e; text-decoration: none; font-size: 0.9rem;"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
            
            <button type="button" class="btn-view" onclick="triggerBackup()" style="background: #48bb78; color: white; border: none; padding: 0.5rem 1rem; cursor: pointer; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.5rem; margin-top: auto;">
                 <i class="fas fa-download"></i> Download Backup
            </button>
            <iframe id="download_frame" style="display:none;"></iframe>
        </form>

        <div class="table-container">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Clock-in-out</th>
                        <th>UserID</th>
                        <th>Action Performed</th>
                        <th>Role Type</th>
                        <th>Target ID</th>
                        <th>IP Address</th>
                        <th style="text-align: center;">Details Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #a0aec0; padding: 2rem;">No system audit entries found for chosen filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $badgeClass = 'badge-update';
                            if($log['actiondo'] === 'CREATE') $badgeClass = 'badge-create';
                            if($log['actiondo'] === 'DELETE') $badgeClass = 'badge-delete';
                        ?>
                            <tr>
                                <td style="font-weight: 500; color: #4a5568;"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <span style="font-family: monospace; background: #edf2f7; padding: 0.2rem 0.4rem; border-radius: 4px;">
                                        <?= $log['userid'] ? htmlspecialchars($log['userid']) : 'SYSTEM' ?>
                                    </span>
                                </td>
                                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($log['actiondo']) ?></span></td>
                                <td style="color: #4a5568; font-weight: 500;"><?= htmlspecialchars($log['auditable_type']) ?></td>
                                <td>#<?= htmlspecialchars($log['auditable_id']) ?></td>
                                <td style="color: #718096; font-family: monospace;"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></td>
                                <td style="text-align: center;">
                                   <button class="btn-view" onclick="openAuditModal(<?= htmlspecialchars(json_encode($log['new_values'] ?? '')) ?>)">
                                        <i class="fas fa-eye"></i> Inspect Changes
                                   </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="audit_logs.php?p=<?= $i ?>&actiondo=<?= urlencode($filter_action) ?>" class="<?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="audit-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 style="margin:0; font-weight:600; color:#2d3748;"><i class="fas fa-database" style="color:#3182ce;"></i> Action Details</h3>
                <button class="close-modal" onclick="closeAuditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color: #48bb78; font-weight: 600;">[New Action Details]</p>
                <pre id="modal-new-json"></pre>
            </div>
        </div>
    </div>
    <!-- Pending Approval Main Modal -->
<div id="approval-modal" class="modal-overlay" style="display:none;">
    <div class="modal-card" style="width: 80%; max-width: 650px;">
        <div class="modal-header">
            <h3><i class="fas fa-user-clock"></i> Pending Registrations</h3>
            <button class="close-modal" onclick="closeApprovalModal()">&times;</button>
        </div>
        
        <!-- Tab Choices -->
        <div class="pending-tabs" style="display: flex; gap: 10px; margin: 15px 0; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">
            <button id="tab-authorities" class="tab-btn active-tab" onclick="loadPendingUsers('authorities')" style="padding: 8px 16px; border: none; background: #ebf8ff; color: #2b6cb0; font-weight: 600; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-building"></i> Authorities
            </button>
            <button id="tab-stakeholders" class="tab-btn" onclick="loadPendingUsers('stakeholders')" style="padding: 8px 16px; border: none; background: #f7fafc; color: #4a5568; font-weight: 600; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-users"></i> Stakeholders
            </button>
        </div>

        <div class="modal-body" id="pending-list">
            <p style="color:#a0aec0;">Loading pending users...</p>
        </div>
    </div>
</div>

<!-- User Detail Sub-Modal -->
<div id="user-detail-modal" class="modal-overlay" style="display:none;">
    <div class="modal-card" style="width: 90%; max-width: 450px;">
        <div class="modal-header">
            <h3><i class="fas fa-id-card"></i> User Details</h3>
            <button class="close-modal" onclick="closeUserDetailModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.95rem;">
                <p><strong>Full Name:</strong> <span id="detail-name">-</span></p>
                <p><strong>Role Type:</strong> <span id="detail-role">-</span></p>
                <p><strong>Registration ID:</strong> <span id="detail-reg">-</span></p>
                <p><strong>Email:</strong> <span id="detail-email">-</span></p>
            </div>
            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeUserDetailModal()" style="padding: 8px 14px; background: #e2e8f0; border: none; border-radius: 6px; cursor: pointer;">Close</button>
                <button type="button" id="detail-approve-btn" class="btn-approve-user" style="padding: 8px 14px; background: #48bb78; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-check"></i> Approve User
                </button>
            </div>
        </div>
    </div>
</div>
</div>
    <script>
//         const sidebar = document.getElementById('sidebar');
//         const mainContent = document.getElementById('main-content');
//         const toggleBtn = document.getElementById('toggle-btn');
//         const toggleIcon = document.getElementById('toggle-icon');

//         toggleBtn.addEventListener('click', () => {
//             sidebar.classList.toggle('collapsed');
//             mainContent.classList.toggle('expanded');
            
//             if (sidebar.classList.contains('collapsed')) {
//                 toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
//             } else {
//                 toggleIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
//             }
//         });

//         // Function to trigger background download
//         function triggerBackup() {
//             document.getElementById('download_frame').src = '/logic/controller/db_backup.php';
//         }

//         const modal = document.getElementById('audit-modal');
//         const newPre = document.getElementById('modal-new-json');

//         function openAuditModal(newValuesRaw) {
//             try {
//                 if (newValuesRaw && newValuesRaw !== '""' && newValuesRaw !== 'null' && newValuesRaw.trim() !== '') {
//                     let parsedData = JSON.parse(newValuesRaw);
//                     if (typeof parsedData === 'string') {
//                         parsedData = JSON.parse(parsedData);
//                     }
//                     newPre.textContent = JSON.stringify(parsedData, null, 4);
//                     newPre.style.color = "#48bb78";
//                 } else {
//                     newPre.textContent = "NULL (Record Wiped out / Deleted / No Payload Data)";
//                     newPre.style.color = "#a0aec0";
//                 }
//             } catch (e) {
//                 console.error("JSON Parsing Error:", e);
//                 newPre.textContent = newValuesRaw || "EMPTY PAYLOAD";
//                 newPre.style.color = "#e53e3e";
//             }
//             modal.style.display = 'flex';
//         }

//         function closeAuditModal() {
//             modal.style.display = 'none';
//         }

//         window.onclick = function(event) {
//             if (event.target == modal) {
//                 closeAuditModal();
//             }
//         }
       

//     const approvalModal = document.getElementById('approval-modal');

//     function openApprovalModal() {
//         approvalModal.style.display = 'flex';
//         // Fetch the pending users list from your new controller
//         fetch('/logic/controller/pendingapprove.php')
//             .then(response => response.text())
//             .then(html => {
//                 document.getElementById('pending-list').innerHTML = html;
//             });
//     }

//     function closeApprovalModal() {
//         approvalModal.style.display = 'none';
//     }


// // Ensure this is clearly defined in your <script> block
// // Add this new listener to your <script> block
// document.getElementById('pending-list').addEventListener('click', function(e) {
//     // Check if the clicked element has the class 'btn-approve-user'
//     if (e.target && e.target.classList.contains('btn-approve-user')) {
//         const userid = e.target.getAttribute('data-userid');
        
//         if (confirm('Approve this user?')) {
//             fetch('/logic/controller/approveuser.php', {
//                 method: 'POST',
//                 headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//                 body: 'userid=' + encodeURIComponent(userid)
//             })
//             .then(res => res.json())
//             .then(data => {
//                 if (data.success) {
//                     alert('User approved!');
//                     openApprovalModal(); // Refresh modal content
//                 } else {
//                     alert('Error: ' + (data.message || 'Unknown error'));
//                 }
//             })
//             .catch(err => console.error('Fetch error:', err));
//         }
//     }
// });

// // Global click handler to close modals
// window.onclick = function(event) {
//     if (event.target == modal) closeAuditModal();
//     if (event.target == approvalModal) closeApprovalModal();
// };

       // ==========================================
// CONSOLIDATED POPUP & MODAL CONTROLLER
// ==========================================

// Element References
const auditModal = document.getElementById('audit-modal');
const approvalModal = document.getElementById('approval-modal');
const userDetailModal = document.getElementById('user-detail-modal');
const newPre = document.getElementById('modal-new-json');

// --- 1. Audit Inspection Modal ---
function openAuditModal(newValuesRaw) {
    try {
        if (newValuesRaw && newValuesRaw !== '""' && newValuesRaw !== 'null' && String(newValuesRaw).trim() !== '') {
            let parsedData = (typeof newValuesRaw === 'string') ? JSON.parse(newValuesRaw) : newValuesRaw;
            newPre.textContent = JSON.stringify(parsedData, null, 4);
            newPre.style.color = "#48bb78";
        } else {
            newPre.textContent = "NULL (Record Wiped out / Deleted / No Payload Data)";
            newPre.style.color = "#a0aec0";
        }
    } catch (e) {
        console.error("JSON Parsing Error:", e);
        newPre.textContent = newValuesRaw || "EMPTY PAYLOAD";
        newPre.style.color = "#e53e3e";
    }
    auditModal.style.display = 'flex';
}

function closeAuditModal() {
    auditModal.style.display = 'none';
}

// --- 2. Pending Approval Modal ---
function openApprovalModal() {
    approvalModal.style.display = 'flex';
    loadPendingUsers('authorities');
}

function closeApprovalModal() {
    approvalModal.style.display = 'none';
}

function loadPendingUsers(role) {
    document.querySelectorAll('.pending-tabs .tab-btn').forEach(btn => btn.classList.remove('active-tab'));
    const activeTab = document.getElementById('tab-' + role);
    if (activeTab) activeTab.classList.add('active-tab');

    document.getElementById('pending-list').innerHTML = "<p style='color:#a0aec0;'>Loading pending users...</p>";
    
    fetch('/logic/controller/get_pending_users.php?role=' + encodeURIComponent(role))
        .then(response => response.text())
        .then(html => {
            document.getElementById('pending-list').innerHTML = html;
        })
        .catch(err => {
            console.error('Error fetching pending users:', err);
            document.getElementById('pending-list').innerHTML = "<p style='color:#e53e3e;'>Error loading users.</p>";
        });
}

// --- 3. User Details Sub-Modal ---
function openUserDetailModal(user) {
    document.getElementById('detail-name').textContent = user.full_name || 'N/A';
    document.getElementById('detail-role').textContent = user.role_type || 'N/A';
    document.getElementById('detail-reg').textContent = user.reg_number || 'N/A';
    document.getElementById('detail-email').textContent = user.email || 'N/A';
    
    const approveBtn = document.getElementById('detail-approve-btn');
    if (approveBtn) {
        approveBtn.setAttribute('data-userid', user.userid);
    }
    
    userDetailModal.style.display = 'flex';
}

function closeUserDetailModal() {
    userDetailModal.style.display = 'none';
}

// --- 4. Global Event Handlers for Modals ---

// Handle User Approval Button Clicks
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('btn-approve-user')) {
        const userid = e.target.getAttribute('data-userid');
        if (!userid) return;
        
        if (confirm('Approve this user?')) {
            fetch('/logic/controller/approveuser.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'userid=' + encodeURIComponent(userid)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('User approved successfully!');
                    closeUserDetailModal();
                    openApprovalModal(); 
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => console.error('Fetch error:', err));
        }
    }
});

// Close Modals when clicking outside (Backdrop Click Handler)
window.addEventListener('click', function(event) {
    if (event.target === auditModal) closeAuditModal();
    if (event.target === approvalModal) closeApprovalModal();
    if (event.target === userDetailModal) closeUserDetailModal();
});             
    </script>
</body>
</html>