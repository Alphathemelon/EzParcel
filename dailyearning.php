<?php
include 'database.php';

// Gunakan DATE() untuk pastikan ia ambil tarikh sahaja (abaikan jam/minit jika ada)
// CURDATE() adalah function SQL yang bermaksud "Tarikh Hari Ini"
$sql = "
SELECT SUM(fld_parcel_amount) AS total_today 
FROM tbl_parcel_ezparcel 
WHERE DATE(fld_parcel_date) = CURDATE()
";

$result = $conn->query($sql);

$response = [];

if ($result) {
    $row = $result->fetch_assoc();
    // Jika tiada sales hari ni, database akan bagi NULL. Kita tukar jadi 0.
    $total = $row['total_today'] === null ? 0 : $row['total_today'];
    
    $response = [
        'total_today' => $total
    ];
} else {
    $response = [
        'total_today' => 0
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>