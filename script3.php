<?php
// File: report3.php (Pastikan file ini duduk sebaris dengan reportindex.php)
include 'database.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

$today = date('Y-m-d');

$sql = "
SELECT SUM(fld_parcel_amount) AS total_today 
FROM tbl_parcel_ezparcel 
WHERE DATE(fld_parcel_date) = '$today'
";

$result = $conn->query($sql);

$total = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $total = $row['total_today'] === null ? 0 : $row['total_today'];
}

// Pastikan output adalah JSON
header('Content-Type: application/json');
echo json_encode(['total_today' => $total]);
?>