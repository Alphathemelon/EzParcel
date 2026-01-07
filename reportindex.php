<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stylereport.css?v=1.3">
    <title>Report EzParcel</title>
</head>
<body>
    
<?php
include_once 'auth.php';
authorize([1]); 
?>

<?php
if (isset($_GET['week'])) {
    $_SESSION['selected_week'] = $_GET['week'];
}
$selectedWeek = isset($_SESSION['selected_week']) ? $_SESSION['selected_week'] : '';
?>

<?php include 'navbar.php'; ?>

    <div class="week-selection-container">
        <form method="GET" action="" class="week-form">
            <div class="input-group">
                <label for="weekPicker">Select Week</label>
                <input type="week" name="week" id="weekPicker" value="<?php echo htmlspecialchars($selectedWeek); ?>" style="border:none; outline:none; background:transparent;">
                <button type="submit" class="btn-apply">Apply</button>
            </div>
        </form>
    </div>

    <div class="parent">
        
        <div class="div1">
            <div class="card">
                <h4>Today's Earning</h4>
                <h1 id="displayAmount">...</h1>
                <p>Updated: <span id="lastUpdated">--:--</span></p>
            </div>
        </div>

        <div class="div2">
            <div class="card">
                <h4>Today's Parcels</h4>
                <h1 id="displayCollected">0</h1>
                <p>Updated: <span id="lastUpdatedCollected">--:--</span></p>
            </div>
        </div>

        <div class="div6">
            <div class="card">
                <h4>vs Yesterday ($)</h4>
                <h1 id="compEarning">...</h1>
                <p id="compEarningDesc">Waiting data...</p>
            </div>
        </div>

        <div class="div7">
            <div class="card">
                <h4>vs Yesterday (Qty)</h4>
                <h1 id="compParcel">...</h1>
                <p id="compParcelDesc">Waiting data...</p>
            </div>
        </div>


        <div class="div3">
            <div class="card">
                <h4>Weekly Amount</h4>
                <div class="chart-container">
                    <canvas id="amountChart"></canvas>
                </div>
            </div>
        </div>

        <div class="div4">
            <div class="card">
                <h4>Parcel Status</h4>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="div5">
            <div class="card">
                <h4>Parcel Weightage Distribution</h4>
                <div class="chart-container">
                    <canvas id="myChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

    <script src="js/report.js"></script>
    <script src="js/report2.js"></script>
    <script src="js/report3.js"></script> 
    <script src="js/report4.js"></script>
    <script src="js/report5.js"></script> 
    <script src="js/report_comparison.js"></script>
</body>
</html>