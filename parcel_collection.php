<?php
header('Content-Type: application/json; charset=utf-8');
include_once 'auth.php';
include_once 'database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userEmail = $_SESSION['user_email'] ?? '';
if (!$userEmail) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$parcelID = trim($_POST['parcelID'] ?? '');
if ($parcelID === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'parcelID is required']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lookup parcel status in main table
    $s = $conn->prepare("SELECT fld_parcel_ID, fld_parcel_status FROM tbl_parcel_ezparcel WHERE fld_parcel_ID = :id LIMIT 1");
    $s->execute([':id' => $parcelID]);
    $p = $s->fetch(PDO::FETCH_ASSOC);
    if (!$p) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Parcel not found in main table']);
        exit;
    }

    $status = $p['fld_parcel_status'] ?? '';
    if (strtolower($status) === 'collected') {
        // Treat as not allowed to add; return failure
        echo json_encode(['success' => false, 'error' => 'Parcel already collected']);
        exit;
    }

    // For this application the collection table has known columns: fld_id (auto), fld_user_email, fld_parcel_ID
    // Prevent duplicate for same user and parcel
    $chk = $conn->prepare("SELECT 1 FROM tbl_parcelcollection_ezparcel WHERE fld_parcel_ID = :id AND fld_user_email = :email LIMIT 1");
    $chk->execute([':id' => $parcelID, ':email' => $userEmail]);
    if ($chk->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Already recorded', 'parcel' => ['id' => $parcelID, 'status' => $status]]);
        exit;
    }

    $ins = $conn->prepare("INSERT INTO tbl_parcelcollection_ezparcel (fld_user_email, fld_parcel_ID) VALUES (:email, :id)");
    $ins->execute([':email' => $userEmail, ':id' => $parcelID]);

    echo json_encode(['success' => true, 'message' => 'Recorded', 'parcel' => ['id' => $parcelID, 'status' => $status]]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

?>