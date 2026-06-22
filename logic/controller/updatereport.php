<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// 1. Security Check: Only authorities can proceed
if (!isset($_SESSION['role_type']) || $_SESSION['role_type'] !== 'authorities') {
    die("Access Denied: Only authorities can perform this action.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationid = $_POST['locationid'] ?? null; 
    $action     = $_POST['action'] ?? 'update'; 

    if (!$locationid) {
        die("Error: Location ID is required.");
    }

    // =======================================================
    // 🗑️ WORKFLOW A: DELETE OPERATION
    // =======================================================
    if ($action === 'delete') {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM image_loc WHERE locationid = ?")->execute([$locationid]);
            $pdo->prepare("DELETE FROM generated_analysis WHERE locationid = ?")->execute([$locationid]);
            $pdo->prepare("DELETE FROM report WHERE locationid = ?")->execute([$locationid]);
            $pdo->prepare("DELETE FROM location WHERE locationid = ?")->execute([$locationid]);
            $pdo->commit();
            header("Location: /logic/controller/managereport.php");
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            die("Error deleting: " . $e->getMessage());
        }
    }

    // =======================================================
    // 📝 WORKFLOW B: UPDATE OPERATION (Location-Specific)
    // =======================================================
// =======================================================
// 📝 WORKFLOW B: UPDATE OPERATION (Always Create New)
// =======================================================
else if ($action === 'update') {
    $status_update = $_POST['status_update'] ?? null;
    $erosion_risk  = $_POST['erosion_risk'] ?? null; 
    $action_taken  = $_POST['action_taken'] ?? null;
    $suitability_score = $_POST['suitability_score'] ?? null; 
    $prediction_memo   = $_POST['prediction_memo'] ?? null;
    
    if (!$status_update || !$action_taken || !$erosion_risk) {
        die("Error: Missing required fields.");
    }

  try {
        $pdo->beginTransaction();

        // 1. Always insert a new Authority status record
        $stmtAuth = $pdo->prepare("INSERT INTO action_authorities (status_update, action_taken) VALUES (?, ?)");
        $stmtAuth->execute([$status_update, $action_taken]);
        $newAuthID = $pdo->lastInsertId();

        // 2. Always insert a new Analysis record (This keeps your prediction memo unique to this update)
        $sqlAnalysis = "INSERT INTO generated_analysis (locationid, erosion_risk, suitability_score, prediction_memo, anaysis_update) 
                        VALUES (?, ?, ?, ?, NOW())";
        $stmtAnalysis = $pdo->prepare($sqlAnalysis);
        $stmtAnalysis->execute([$locationid, $erosion_risk, $suitability_score, $prediction_memo]);
        $newAnalysisID = $pdo->lastInsertId();

        // 3. Link the report to these new, unique IDs
        $stmtLink = $pdo->prepare("UPDATE report SET authoritiesid = ?, analysisid = ? WHERE locationid = ?");
        $stmtLink->execute([$newAuthID, $newAnalysisID, $locationid]);

        $pdo->commit();
        header("Location: /logic/controller/managereport.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}
}
?>