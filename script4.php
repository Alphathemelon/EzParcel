<?php
include 'database.php';

$sql = "
SELECT 
    fld_parcel_date,
    SUM(fld_parcel_amount) AS total_amount
FROM tbl_parcel_ezparcel
GROUP BY fld_parcel_date
ORDER BY fld_parcel_date
";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
