<?php
header('Content-Type: application/json; charset=utf-8');
include_once 'database.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "DB connect: " . $e->getMessage()]);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    // CREATE
    if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) || $action === 'create') {
        $parcel_id = trim($_POST['parcelID'] ?? '');
        $userName = trim($_POST['userName'] ?? ''); // <-- changed from phoneNumber
        $weight = $_POST['parcelWeight'] ?? null;
        $storage = trim($_POST['storage'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $posted_amount = $_POST['amount'] ?? null;
        $status = $_POST['status'] ?? null;

        if ($parcel_id === '' || $userName === '' || $weight === null) { // check name instead of phone
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Missing required fields: parcelID, userName, parcelWeight"]);
            exit;
        }

        // Normalize storage to short code
        $storage_uc = strtoupper($storage);
        $storage_code = '';
        if ($storage_uc === 'S' || strpos($storage_uc, 'S') === 0) $storage_code = 'S';
        elseif ($storage_uc === 'M' || strpos($storage_uc, 'M') === 0) $storage_code = 'M';
        elseif ($storage_uc === 'L' || strpos($storage_uc, 'L') === 0) $storage_code = 'L';
        else {
            if (stripos($storage, 'SMALL') !== false) $storage_code = 'S';
            elseif (stripos($storage, 'MEDIUM') !== false) $storage_code = 'M';
            elseif (stripos($storage, 'LARGE') !== false) $storage_code = 'L';
        }

        // Map storage code to amount
        $amount_map = ['S' => 2.0, 'M' => 3.0, 'L' => 5.0];
        $amount = $amount_map[$storage_code] ?? ($posted_amount !== null ? (float)$posted_amount : 0.0);

        // Default status
        $status = ($status && strtolower($status) === 'collected') ? 'Collected' : 'Uncollected';

        // Duplicate check
        $chk = $conn->prepare("SELECT fld_parcel_ID FROM tbl_parcel_ezparcel WHERE fld_parcel_ID = :id LIMIT 1");
        $chk->execute([':id' => $parcel_id]);
        $existing = $chk->fetch();
        if ($existing) {
            http_response_code(409);
            echo json_encode(["success" => false, "error" => "Parcel under that code has already been registered."]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO tbl_parcel_ezparcel
            (fld_parcel_ID, fld_parcel_status, fld_parcel_storage, fld_parcel_date, fld_parcel_amount, fld_parcel_weight, fld_parcel_location, fld_user_name)
            VALUES (:id, :status, :storage, :date, :amount, :weight, :location, :userName)");

        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':id' => $parcel_id,
            ':status' => $status,
            ':storage' => $storage_code,
            ':date' => $now,
            ':amount' => $amount,
            ':weight' => $weight,
            ':location' => $location,
            ':userName' => $userName, // <-- store name instead of phone
        ]);

        echo json_encode(["success" => true, "message" => "Parcel created", "parcel_id" => $parcel_id, "status" => $status, "amount" => $amount, "storage_code" => $storage_code]);
        exit;
    }

    // LIST
    if ($action === 'list') {
        $stmt = $conn->query("SELECT fld_parcel_ID, fld_parcel_status, fld_parcel_storage, fld_parcel_date, fld_parcel_amount, fld_parcel_weight, fld_parcel_location, fld_user_name FROM tbl_parcel_ezparcel ORDER BY fld_parcel_date DESC");
        $rows = $stmt->fetchAll();
        echo json_encode(["success" => true, "data" => $rows]);
        exit;
    }

    // UPDATE and DELETE remain unchanged, just make sure they also use fld_user_name instead of phone if needed

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    exit;
}
?>
