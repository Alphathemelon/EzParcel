<?php
include 'database.php';

// Ambil tarikh hari ini
$today = date('Y-m-d');

// Query untuk kira total parcel hari ini
$sql = "
    SELECT 
        COUNT(fld_parcel_ID) AS total_collected_today,
        fld_parcel_date
    FROM tbl_parcel_ezparcel
    WHERE fld_parcel_date = '$today'
    GROUP BY fld_parcel_date
";

// Jalankan query
$result = $conn->query($sql);

$data = [];

// Jika ada result
if ($result && $row = $result->fetch_assoc()) {
    $data = $row; // ambil terus associative array
} else {
    // kalau tiada data hari ini
    $data = [
        'total_collected_today' => 0,
        'fld_parcel_date' => $today
    ];
}

// Hantar response JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
