<?php
include 'database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$whereClause = "";
$selectedWeek = "";

if (isset($_GET['week']) && !empty($_GET['week'])) {
    $selectedWeek = $_GET['week'];
} elseif (isset($_SESSION['selected_week']) && !empty($_SESSION['selected_week'])) {
    $selectedWeek = $_SESSION['selected_week'];
}

if (!empty($selectedWeek)) {
    $weekParts = explode('-W', $selectedWeek);
    if (count($weekParts) === 2) {
        $year = $weekParts[0];
        $week = $weekParts[1];
        
        $dto = new DateTime();
        $dto->setISODate($year, $week);
        $startDate = $dto->format('Y-m-d') . ' 00:00:00';
        $dto->modify('+6 days');
        $endDate = $dto->format('Y-m-d') . ' 23:59:59';
        
        $whereClause = "WHERE fld_parcel_date BETWEEN '$startDate' AND '$endDate'";
    }
}
$sql = "
SELECT 
    fld_parcel_date,
    SUM(fld_parcel_storage LIKE '%S%') AS size_s,
    SUM(fld_parcel_storage LIKE '%M%') AS size_m,
    SUM(fld_parcel_storage LIKE '%L%') AS size_l
    FROM tbl_parcel_ezparcel
    $whereClause
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
