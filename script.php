<?php
include 'database.php';

$sql = "
SELECT 
    fld_parcel_date,
    SUM(fld_parcel_storage LIKE '%S%') AS size_s,
    SUM(fld_parcel_storage LIKE '%M%') AS size_m,
    SUM(fld_parcel_storage LIKE '%L%') AS size_l
    FROM tbl_parcel_ezparcel
    GROUP BY fld_parcel_date
    ORDER BY fld_parcel_date
";


$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    $data = [];
}

header('Content-Type: application/json');
echo json_encode($data);
