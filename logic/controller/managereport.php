<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

try {
    $query = "
SELECT 
    r.locationid, 
    r.userid,
    r.description, 
    r.report_timestamp, 
    r.report_date,
    l.state, 
    l.exact_location, 
    l.latitude, 
    l.longitude,
    i.image_path, 
    u.full_name,
    a.status_update, 
    a.action_taken,
    ga.erosion_risk
FROM report r
-- Essential link: Every report has a location
JOIN location l ON r.locationid = l.locationid
-- Optional links: Use LEFT JOIN so reports aren't hidden if these are missing
LEFT JOIN image_loc i ON l.locationid = i.locationid
LEFT JOIN users u ON r.userid = u.userid
LEFT JOIN authorities a ON r.authoritiesid = a.authoritiesid
LEFT JOIN generated_analysis ga ON l.locationid = ga.locationid
ORDER BY r.report_timestamp DESC;
    ";
    $stmt = $pdo->query($query);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reports = [];
    error_log("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authority Dashboard - ShoreSafe</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/interface/css/manage_report.css">
</head>
<body>

   <header class="navbar-top">
    <div class="logo">
        <span> 🌊 ShoreSafe</span>
    </div>
    <nav class ="navbar-menu">
        <a href="/interface/dashboard/authorities.php" class="btn-guest-header">Home</a>
    </nav>
    </header>

<div class="dashboard-wrapper">
    <header class="dashboard-header">
        <h1>Report Hub</h1>
        <p>Manage the status update, process here in just one click!</p>
    </header>

    <div class="reports-container">
        <?php if (empty($reports)): ?>
            <div class="empty-state">No reports currently available.</div>
        <?php else: ?>
            <?php foreach ($reports as $r): ?>
                <div class="report-card">
                    <div class="report-image-wrapper">
                        <?php if (!empty($r['image_path'])): ?>
                            <img src="/uploads/reports/<?= htmlspecialchars($r['image_path']) ?>" alt="Shoreline">
                        <?php else: ?>
                            <div class="no-image"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </div>

                    <div class="report-body">
                        <div class="report-top">
                            <div class="location-box">
                                <h3><?= htmlspecialchars($r['exact_location'] ?: 'Unknown Location') ?></h3>
                                <span class="lat-long">
                                    <i class="fas fa-location-dot"></i> <?= htmlspecialchars($r['latitude']) ?>, <?= htmlspecialchars($r['longitude']) ?>
                                </span>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 5px; align-items: flex-end;">
                                <span class="status-badge <?= strtolower(str_replace(' ', '-', $r['status_update'] ?? 'pending')) ?>">
                                    <?= htmlspecialchars($r['status_update'] ?? 'PENDING') ?>
                                </span>
                                <?php if (!empty($r['erosion_risk'])): ?>
                                    <span class="risk-badge <?= strtolower($r['erosion_risk']) ?>">
                                         Risk: <?= htmlspecialchars($r['erosion_risk']) ?>
                                     </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="description"><?= htmlspecialchars($r['description']) ?></p>

                        <div class="report-footer">
                            <div class="meta-info">
                               <p> <span><i class="far fa-user"></i> <?= ($r['userid'] === 'GUEST') ? 'Guest' : htmlspecialchars($r['full_name']) ?></span></p>
                               <p> <span><i class="far fa-calendar"></i> <?= date('d M Y', strtotime($r['report_date'])) ?></span></p>
                            
                               <form action="../controller/updatereport.php" method="POST" style="display:inline;" 
                                 onsubmit="return confirm('Warning: This will permanently remove this shoreline report from all tables. Proceed?');">
    
                                    <input type="hidden" name="locationid" value="<?= $r['locationid'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-delete">Delete Report</button>
                                </form>
                                <button type="button" class="btn-update" onclick="openUpdateModal(<?= $r['locationid'] ?>)">
                                    Update Status
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="updateModal" class="modal-overlay">
    <div class="modal-content glassmorphism">
        <div class="modal-header">
            <h2>Update Report Details</h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        
        <form action="../controller/updatereport.php" method="POST">
            <input type="hidden" name="locationid" id="modalLocationID">
            <input type="hidden" name="action" value="update">
            
            <div class="form-group">
                <label>Current Erosion Status</label>
                <select name="status_update" required >
                    <option value="" disabled selected>-- Select --</option>
                    <option value="Verified">Verified</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Action Taken">Action Taken</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>

            <div class="form-group">
                <label>Erosion Risk Level</label>
                <select name="erosion_risk" required>
                    <option value="" disabled selected>-- Select --</option>
                    <option value="Low">Low Risk</option>
                    <option value="Medium">Medium Risk</option>
                    <option value="High">High Risk</option>
                </select>
            </div>

            <div class="form-group">
                <label>Score Placement For Infrastructure (0.00 to 1.00)</label>
                <input type="number" name="suitability_score" step="0.01" min="0" max="1" placeholder="e.g., 0.85" required>
            </div>

            <div class="form-group">
                <label>Prediction Memo</label>
                <textarea name="prediction_memo" rows="4"  placeholder="e.g., Shoreline worsening with waves... "required></textarea>
            </div>

            <div class="form-group">
                <label>Action Taken</label>
                <textarea name="action_taken" rows="4" placeholder="Describe the action planned/taken..." required></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-guest-header" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-guest-header">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUpdateModal(id) {
        document.getElementById('modalLocationID').value = id;
        document.getElementById('updateModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('updateModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    window.onclick = function(e) {
        if (e.target.id === 'updateModal') {
            closeModal();
        }
    }
</script>

</body>
</html>