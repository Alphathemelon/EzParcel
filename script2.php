<?php
include 'database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$whereClause = "";
$selectedWeek = "";

$reportType = isset($_SESSION['report_type']) ? $_SESSION['report_type'] : 'weekly';
$groupBy = "GROUP BY fld_parcel_date";
$dateSelect = "fld_parcel_date";
$orderBy = "ORDER BY fld_parcel_date";

if ($reportType == 'monthly') {
    // Show months of a specific year
    $selectedYear = isset($_GET['year']) ? $_GET['year'] : (isset($_SESSION['selected_year']) ? $_SESSION['selected_year'] : date('Y'));
    
    $whereClause = "WHERE YEAR(fld_parcel_date) = '$selectedYear'";
    $groupBy = "GROUP BY MONTH(fld_parcel_date)";
    // Return Full Month Name (January, February)
    $dateSelect = "DATE_FORMAT(fld_parcel_date, '%M') AS fld_parcel_date";
    $orderBy = "ORDER BY MONTH(fld_parcel_date)";
    
} elseif ($reportType == 'yearly') {
    // Show Years (All time)
    $whereClause = "WHERE 1=1"; // No filter, show all years
    $groupBy = "GROUP BY YEAR(fld_parcel_date)";
    $dateSelect = "DATE_FORMAT(fld_parcel_date, '%Y') AS fld_parcel_date";
    $orderBy = "ORDER BY YEAR(fld_parcel_date)";

} else {
    // Weekly (Default)
    $selectedWeek = isset($_GET['week']) ? $_GET['week'] : (isset($_SESSION['selected_week']) ? $_SESSION['selected_week'] : '');
    
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
}

$sql = "
SELECT 
    $dateSelect,
    SUM(fld_parcel_status = 'Collected') AS collected,
    SUM(fld_parcel_status = 'Uncollected') AS uncollected
    FROM tbl_parcel_ezparcel
    $whereClause
    $groupBy
    $orderBy
";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
