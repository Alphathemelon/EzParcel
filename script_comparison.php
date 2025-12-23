<?php
include 'database.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// 1. Earning Comparison
$sqlEarning = "
    SELECT 
        SUM(CASE WHEN DATE(fld_parcel_date) = '$today' THEN fld_parcel_amount ELSE 0 END) as earning_today,
        SUM(CASE WHEN DATE(fld_parcel_date) = '$yesterday' THEN fld_parcel_amount ELSE 0 END) as earning_yesterday
    FROM tbl_parcel_ezparcel
    WHERE DATE(fld_parcel_date) IN ('$today', '$yesterday')
";

$resultEarning = $conn->query($sqlEarning);
$earningData = $resultEarning->fetch_assoc();

$earningToday = (float)($earningData['earning_today'] ?? 0);
$earningYesterday = (float)($earningData['earning_yesterday'] ?? 0);
$earningDiff = $earningToday - $earningYesterday;

$earningPercent = 0;
if ($earningYesterday > 0) {
    $earningPercent = ($earningDiff / $earningYesterday) * 100;
} elseif ($earningToday > 0) {
    $earningPercent = 100; // From 0 to something is 100% increase
}

// 2. Parcel Comparison
$sqlParcel = "
    SELECT 
        COUNT(CASE WHEN DATE(fld_parcel_date) = '$today' THEN 1 END) as parcel_today,
        COUNT(CASE WHEN DATE(fld_parcel_date) = '$yesterday' THEN 1 END) as parcel_yesterday
    FROM tbl_parcel_ezparcel
    WHERE DATE(fld_parcel_date) IN ('$today', '$yesterday')
";

$resultParcel = $conn->query($sqlParcel);
$parcelData = $resultParcel->fetch_assoc();

$parcelToday = (int)($parcelData['parcel_today'] ?? 0);
$parcelYesterday = (int)($parcelData['parcel_yesterday'] ?? 0);
$parcelDiff = $parcelToday - $parcelYesterday;

$response = [
    'earning' => [
        'today' => $earningToday,
        'yesterday' => $earningYesterday,
        'diff' => $earningDiff,
        'percent' => round($earningPercent, 1),
        'trend' => $earningDiff >= 0 ? 'up' : 'down'
    ],
    'parcel' => [
        'today' => $parcelToday,
        'yesterday' => $parcelYesterday,
        'diff' => $parcelDiff,
        'trend' => $parcelDiff >= 0 ? 'up' : 'down'
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
?>
