<?php
session_start();

require_once __DIR__ . '/../../config/database.php';

try {
    $query = "SELECT r.*, l.state, l.exact_location, l.latitude, l.longitude, i.image_path,
                     u.full_name, a.status_update, a.action_taken, ga.erosion_risk
              FROM report r
              JOIN location l ON r.locationid = l.locationid
              LEFT JOIN image_loc i ON l.locationid = i.locationid
              LEFT JOIN users u ON r.userid = u.userid
              LEFT JOIN action_authorities a ON r.authoritiesid = a.authoritiesid
              LEFT JOIN generated_analysis ga ON l.locationid = ga.locationid
              ORDER BY r.report_date DESC";

    $stmt = $pdo->query($query);
    $allReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $groupedReports = [];

    foreach ($allReports as $row) {
        $lat = floatval($row['latitude'] ?? 0);
        $lng = floatval($row['longitude'] ?? 0);
        $currentName = strtolower(trim($row['exact_location']));
        $dayKey = $row['report_date'];

        $cleanKeyword = str_replace(['pantai', 'beach', ' '], '', $currentName);
        $matchedKey = null;

        foreach ($groupedReports as $existingKey => $group) {
            $existingCleanName = str_replace(['pantai', 'beach', ' '], '', strtolower($group['exact_location']));
            $nameMatch = ($cleanKeyword !== '' && (strpos($existingCleanName, $cleanKeyword) !== false || strpos($cleanKeyword, $existingCleanName) !== false));
            $geoMatch = (abs($lat - $group['latitude']) < 0.005 && abs($lng - $group['longitude']) < 0.005);

            if ($nameMatch || $geoMatch) {
                $matchedKey = $existingKey;
                break;
            }
        }

        if ($matchedKey === null) {
            $matchedKey = round($lat, 3) . '_' . round($lng, 3);
            $groupedReports[$matchedKey] = [
                'byDay'   => [],   
                'history' => [],
                'exact_location' => $row['exact_location'], 
                'latitude'       => $lat,
                'longitude'      => $lng
            ];
        }

        if (strlen($row['exact_location']) > strlen($groupedReports[$matchedKey]['exact_location'])) {
            $groupedReports[$matchedKey]['exact_location'] = $row['exact_location'];
        }

        if (!isset($groupedReports[$matchedKey]['byDay'][$dayKey])) {
            $groupedReports[$matchedKey]['byDay'][$dayKey] = $row;
        } else {
            $cur = $groupedReports[$matchedKey]['byDay'][$dayKey];
            if (strtotime($row['report_timestamp'] ?? '') > strtotime($cur['report_timestamp'] ?? '')) {
                $groupedReports[$matchedKey]['byDay'][$dayKey] = $row;
            }
        }
    }

    foreach ($groupedReports as $key => $data) {
        foreach ($data['byDay'] as $dayDate => $canonicalRow) {
            $groupedReports[$key]['history'][] = $canonicalRow;
        }
    }

} catch (PDOException $e) {
    $groupedReports = [];
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
        <div class="logo"><span> 🌊 ShoreSafe</span></div>
        <nav class="navbar-menu"><a href="/interface/dashboard/authorities.php" class="btn-guest-header">Home</a></nav>
    </header>

<div class="dashboard-wrapper">
    <header class="dashboard-header">
        <h1>Report Hub</h1>
        <p>Manage structural processes and review regional history directly.</p>
    </header>

    <div class="reports-container">
    <?php if (empty($groupedReports)): ?>
        <div class="empty-state">No reports currently available.</div>
    <?php else: ?>
        <?php foreach ($groupedReports as $locKey => $group): 
            usort($group['history'], function($a, $b) {
                return strtotime($b['report_date']) - strtotime($a['report_date']);
            });
            $r = $group['history'][0]; 
        ?>
            <div class="report-card">
                <div class="report-body" style="width: 100%; padding: 20px;">
                    <div class="report-top">
                        <div class="location-box">
                            <h3><?= htmlspecialchars($group['exact_location']) ?></h3>
                            <span class="lat-long"><i class="fas fa-location-dot"></i> <?= htmlspecialchars($group['latitude'] ?? 0) ?>, <?= htmlspecialchars($group['longitude'] ?? 0) ?></span>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px; align-items: flex-end;">
                            <span class="status-badge <?= strtolower(str_replace(' ', '-', $r['status_update'] ?? 'pending')) ?>"><?= htmlspecialchars($r['status_update'] ?? 'PENDING') ?></span>
                        </div>
                    </div>

                    <div style="margin: 15px 0; padding: 15px; background: #f8fafc; border-radius: 8px; border-left: 5px solid #3b82f6;">
                        <p style="margin: 0 0 10px 0; font-size: 0.9em; font-weight: bold; color: #1e293b;">Historical Timeline (<?= count($group['history']) ?>):</p>
                        
                        <?php foreach ($group['history'] as $h): 
                            $cleanImagePath = !empty($h['image_path']) ? (strpos($h['image_path'], 'uploads/') !== false ? '/' . ltrim($h['image_path'], '/') : '/uploads/reports/' . $h['image_path']) : '';
                            
                            $jsonData = json_encode([
                                'location' => $group['exact_location'],
                                'date' => date('d M Y', strtotime($h['report_date'])),
                                'status' => $h['status_update'] ?? 'PENDING',
                                'reporter' => !empty($h['full_name']) ? $h['full_name'] : (!empty($h['userid']) && strpos($h['userid'], 'guest_') !== false ? 'Anonymous Guest' : 'Guest'),
                                'description' => $h['description'] ?? 'No commentary provided.',
                                'action_taken' => $h['action_taken'] ?? 'No action log updated yet.',
                                'risk' => $h['erosion_risk'] ?? 'Unassigned',
                                'image' => $cleanImagePath
                            ]);
                        ?>
                            <button type="button" class="timeline-item-btn" onclick='openHistoryModal(<?= htmlspecialchars($jsonData, ENT_QUOTES, 'UTF-8') ?>)'>
        <div class="timeline-btn-left">
            <?php if (!empty($cleanImagePath)): ?>
                <img src="<?= htmlspecialchars($cleanImagePath) ?>" alt="Report" class="report-thumb">
            <?php else: ?>
                <div class="history-icon-box">
                    <i class="fas fa-calendar-day"></i>
                </div>
            <?php endif; ?>

            <div class="history-meta-text">
                <strong><?= date('d M Y', strtotime($h['report_date'])) ?></strong><br>
                <span>Status: <?= htmlspecialchars($h['status_update'] ?? 'PENDING') ?></span>
            </div>
        </div>
        <div class="timeline-btn-right">
            <i class="fas fa-chevron-right" style="color: #94a3b8; font-size: 0.85rem;"></i>
        </div>
    </button>
<?php endforeach; ?>
                    </div>

                    
                    <div class="report-footer" style="display: flex; justify-content: flex-end; gap: 12px;">
    <button type="button" class="btn-delete" onclick="confirmDelete(<?= (int)$r['locationid'] ?>)">
        <i class="fas fa-trash-alt"></i> Delete Report
    </button>
    
    <button type="button" class="btn-update" onclick="openUpdateModal(<?= $r['locationid'] ?>)">
        Update Status
    </button>
</div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>

<div id="historyModal" class="modal-overlay">
    <div class="modal-content glassmorphism">
        <div class="modal-header">
            <h2 id="histModalTitle">Timeline Record Details</h2>
            <button class="close-btn" onclick="closeHistoryModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding-top: 10px;">
            <img id="histModalImg" src="" class="history-popup-img" alt="Historical Evidence">
            
            <div class="popup-details-grid">
                <div>
                    <div class="popup-label">Report Date</div>
                    <div id="histModalDate" class="popup-value"></div>
                </div>
                <div>
                    <div class="popup-label">Reported By</div>
                    <div id="histModalReporter" class="popup-value"></div>
                </div>
                <div>
                    <div class="popup-label">System Status</div>
                    <div id="histModalStatus" class="popup-value"></div>
                </div>
                <div>
                    <div class="popup-label">Erosion Risk Evaluation</div>
                    <div id="histModalRisk" class="popup-value"></div>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <div class="popup-label">Report Description</div>
                <div id="histModalDesc" class="popup-value" style="background: rgba(255,255,255,0.4); padding: 10px; border-radius: 6px; font-style: italic;"></div>
            </div>

            <div style="margin-top: 15px;">
                <div class="popup-label">Action Taken Log</div>
                <div id="histModalAction" class="popup-value" style="background: rgba(40,167,69,0.1); padding: 10px; border-radius: 6px; border-left: 4px solid #28a745;"></div>
            </div>
        </div>
    </div>
</div>

<div id="updateModal" class="modal-overlay">
    <div class="modal-content glassmorphism">
        <div class="modal-header"><h2>Update Report Details</h2><button class="close-btn" onclick="closeModal()">&times;</button></div>
        <form action="/logic/controller/updatereport.php" method="POST">
            <input type="hidden" name="locationid" id="modalLocationID">
            <input type="hidden" name="action" value="update">
            <div class="form-group"><label>Status</label><select name="status_update" required><option value="Verified">Verified</option><option value="In Progress">In Progress</option><option value="Action Taken">Action Taken</option><option value="Resolved">Resolved</option></select></div>
            <div class="form-group"><label>Risk Level</label><select name="erosion_risk" required><option value="Low">Low</option><option value="Medium">Medium</option><option value="High">High</option></select></div>
            <div class="form-group"><label>Infrastructure Score (0-1)</label><input type="number" name="suitability_score" step="0.01" min="0" max="1" required></div>
            <div class="form-group"><label>Prediction Memo</label><textarea name="prediction_memo" rows="3" required></textarea></div>
            <div class="form-group"><label>Action Taken</label><textarea name="action_taken" rows="3" required></textarea></div>
            <div class="modal-footer"><button type="button" class="btn-guest-header" onclick="closeModal()">Cancel</button><button type="submit" class="btn-guest-header">Save</button></div>
        </form>
    </div>
</div>
<form id="globalDeleteForm" action="/logic/controller/updatereport.php" method="POST" style="display: none;">
    <input type="hidden" name="locationid" id="deleteLocationID">
    <input type="hidden" name="action" value="delete">
</form>
<script>
    function openUpdateModal(id) { 
        document.getElementById('modalLocationID').value = id; 
        document.getElementById('updateModal').style.display = 'flex'; 
    }
    function closeModal() { 
        document.getElementById('updateModal').style.display = 'none'; 
    }

    function openHistoryModal(data) {
        document.getElementById('histModalTitle').innerText = data.location;
        document.getElementById('histModalDate').innerText = data.date;
        document.getElementById('histModalReporter').innerText = data.reporter;
        document.getElementById('histModalStatus').innerText = data.status;
        document.getElementById('histModalRisk').innerText = data.risk;
        document.getElementById('histModalDesc').innerText = data.description;
        document.getElementById('histModalAction').innerText = data.action_taken;

        const imgElement = document.getElementById('histModalImg');
        if(data.image && data.image !== "") {
            imgElement.src = data.image;
            imgElement.style.display = 'block';
        } else {
            imgElement.style.display = 'none';
        }

        document.getElementById('historyModal').style.display = 'flex';
    }

    function closeHistoryModal() {
        document.getElementById('historyModal').style.display = 'none';
    }
</script>
<script>
    // Existing modal functions...
    function openUpdateModal(id) { 
        document.getElementById('modalLocationID').value = id; 
        document.getElementById('updateModal').style.display = 'flex'; 
    }
    function closeModal() { 
        document.getElementById('updateModal').style.display = 'none'; 
    }

    // 🗑️ New Deletion Confirmation Handler
    function confirmDelete(locationId) {
        if (confirm("🚨 WARNING: Are you confirm to delete this information? This cannot be back.")) {
            // Assign target location ID to the hidden form fields
            document.getElementById('deleteLocationID').value = locationId;
            // Submit form to /logic/controller/updatereport.php
            document.getElementById('globalDeleteForm').submit();
        }
    }
</script>
</body>
</html>