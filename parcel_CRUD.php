<?php
// Curd.php — simplified CRUD for `tbl_parcel_ezparcel`
// Accepts fields used by `parcelweightage.html` and returns JSON responses.

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
    // CREATE — accept parcelweightage.html names (`parcelID`, `phoneNumber`, `parcelWeight`, `storage`, `location`)
    if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) || $action === 'create') {
        $parcel_id = trim($_POST['parcelID'] ?? $_POST['parcel_id'] ?? '');
        $phone = trim($_POST['phoneNumber'] ?? $_POST['phone'] ?? '');
        $weight = $_POST['parcelWeight'] ?? $_POST['weight'] ?? null;
        $storage = trim($_POST['storage'] ?? $_POST['shelfTitle'] ?? '');
        $location = trim($_POST['location'] ?? $_POST['slot'] ?? '');
        $amount = $_POST['amount'] ?? 0;
        $status = $_POST['status'] ?? 'Incomplete';

        if ($parcel_id === '' || $phone === '' || $weight === null) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Missing required fields: parcelID, phoneNumber, parcelWeight"]);
            exit;
        }

        // Duplicate check: if a parcel with same fld_parcel_ID exists, return informative error
        $chk = $conn->prepare("SELECT fld_parcel_ID FROM tbl_parcel_ezparcel WHERE fld_parcel_ID = :id LIMIT 1");
        $chk->execute([':id' => $parcel_id]);
        $existing = $chk->fetch();
        if ($existing) {
            http_response_code(409);
            echo json_encode(["success" => false, "error" => "Parcel under that code has already been registered."]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO tbl_parcel_ezparcel
            (fld_parcel_ID, fld_parcel_status, fld_parcel_storage, fld_parcel_date, fld_parcel_amount, fld_parcel_weight, fld_parcel_location, fld_user_phone)
            VALUES (:id, :status, :storage, :date, :amount, :weight, :location, :phone)");

        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':id' => $parcel_id,
            ':status' => $status,
            ':storage' => $storage,
            ':date' => $now,
            ':amount' => $amount,
            ':weight' => $weight,
            ':location' => $location,
            ':phone' => $phone,
        ]);

        echo json_encode(["success" => true, "message" => "Parcel created", "parcel_id" => $parcel_id]);
        exit;
    }

    // LIST — return all parcels (for AJAX)
    if ($action === 'list') {
        $stmt = $conn->query("SELECT fld_parcel_ID, fld_parcel_status, fld_parcel_storage, fld_parcel_date, fld_parcel_amount, fld_parcel_weight, fld_parcel_location, fld_user_phone FROM tbl_parcel_ezparcel ORDER BY fld_parcel_date DESC");
        $rows = $stmt->fetchAll();
        echo json_encode(["success" => true, "data" => $rows]);
        exit;
    }

    // UPDATE — update by fld_parcel_ID
    if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) || $action === 'update') {
        $parcel_id = trim($_POST['parcelID'] ?? $_POST['parcel_id'] ?? '');
        if ($parcel_id === '') {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "parcelID is required"]);
            exit;
        }

        $allowed = ['status' => 'fld_parcel_status', 'storage' => 'fld_parcel_storage', 'amount' => 'fld_parcel_amount', 'weight' => 'fld_parcel_weight', 'location' => 'fld_parcel_location', 'phone' => 'fld_user_phone'];
        $sets = [];
        $params = [':id' => $parcel_id];

        foreach ($allowed as $k => $col) {
            if (isset($_POST[$k])) {
                $param = ':' . $k;
                $sets[] = "$col = $param";
                $params[$param] = $_POST[$k];
            }
        }

        if (empty($sets)) {
            echo json_encode(["success" => false, "message" => "No fields to update"]);
            exit;
        }

        $sql = "UPDATE tbl_parcel_ezparcel SET " . implode(', ', $sets) . " WHERE fld_parcel_ID = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        echo json_encode(["success" => true, "message" => "Parcel updated", "parcel_id" => $parcel_id]);
        exit;
    }

    // DELETE
    if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) || $action === 'delete') {
        $parcel_id = trim($_POST['parcelID'] ?? $_POST['parcel_id'] ?? $_POST['id'] ?? '');
        if ($parcel_id === '') {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "parcelID is required"]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM tbl_parcel_ezparcel WHERE fld_parcel_ID = :id");
        $stmt->execute([':id' => $parcel_id]);

        echo json_encode(["success" => true, "message" => "Parcel deleted", "parcel_id" => $parcel_id]);
        exit;
    }

    // If no known action provided, return helpful message
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No valid action. Use action=create|list|update|delete or submit form fields 'create','update','delete'."]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    exit;
}

?>
