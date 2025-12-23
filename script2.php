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
    SUM(fld_parcel_status = 'Collected') AS collected,
    SUM(fld_parcel_status = 'Uncollected') AS uncollected
    FROM tbl_parcel_ezparcel
    $whereClause
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
