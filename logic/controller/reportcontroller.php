<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$userID = $_SESSION['userid'] ?? null;

if (!$userID && !isset($_SESSION['guest_session'])) {
    $_SESSION['guest_session'] = uniqid('guest_');
}

$guestSession = $_SESSION['guest_session'] ?? null;

$state = $_POST['state'] ?? '';
$exactLoc = $_POST['exact_location'] ?? '';
$lat = $_POST['latitude'] ?? null;
$lng = $_POST['longitude'] ?? null;
$description = $_POST['description'] ?? '';
$reportDate = $_POST['date'] ?? date('Y-m-d');

// --- 1. Handle File Upload Processing ---
$imagePath = null;
$newFileName = null;
// Use clean directory separator routing
$uploadFileDir = __DIR__ . '/../../uploads/reports/'; 

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = $_FILES['image']['name'];
    
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate image types explicitly to keep your directory clean and safe
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file extension type. Upload rejected.'
        ]);
        exit;
    }

    $newFileName = uniqid('img_', true) . '.' . $fileExtension;
    
    if (!is_dir($uploadFileDir)) {
        mkdir($uploadFileDir, 0755, true);
    }
    
    $dest_path = $uploadFileDir . $newFileName;
    
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        // Keeps path system web-friendly for frontend rendering
        $imagePath =  $newFileName;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to move uploaded file to target directory.'
        ]);
        exit;
    }
}

try {
    // Single transaction wrapper for relational consistency
    $pdo->beginTransaction();

    // --- 2. Insert into the main location table ---
    $locStmt = $pdo->prepare("
        INSERT INTO location (state, exact_location, latitude, longitude) 
        VALUES (?, ?, ?, ?)
    ");
    $locStmt->execute([$state, $exactLoc, $lat, $lng]);
    
    // Grab the auto-incremented location primary key
    $locationID = $pdo->lastInsertId();

    // --- 3. Insert into image_loc ---
    if ($imagePath) {
        $imgStmt = $pdo->prepare("
            INSERT INTO image_loc (locationid, image_path, time_upload) 
            VALUES (?, ?, NOW())
        ");
        $imgStmt->execute([$locationID, $imagePath]);
    }

    // --- 4. Insert into report table ---
    $stmt = $pdo->prepare("
        INSERT INTO report (
            userid,
            locationid,
            report_timestamp,
            description,
            report_date,
            guest_session,
            authoritiesid
        )
        VALUES (?, ?, NOW(), ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userID, 
        $locationID, 
        $description,
        $reportDate,
        $guestSession,
        null
    ]);

    // Commit changes simultaneously if everything passes rules above
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Report submitted successfully!'
    ]);

} catch (Exception $e) {
    // If anything fails, rollback database states immediately
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Clean up uploaded image storage if database actions failed to maintain integrity
    if ($imagePath && file_exists($uploadFileDir . $newFileName)) {
        unlink($uploadFileDir . $newFileName);
    }

    echo json_encode([
        'success' => false,
        'message' => 'Database failure: ' . $e->getMessage()
    ]);
}