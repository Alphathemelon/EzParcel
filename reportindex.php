<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stylereport.css?v=1.3">
    <title>Report EzParcel</title>
    <link rel="icon" href="images/logo.png" type="image/png">
</head>
<body>
    
<?php
include_once 'auth.php';
authorize([1]); 
?>

<?php

if (isset($_GET['report_type'])) {
    $_SESSION['report_type'] = $_GET['report_type'];
    
    // Reset specific selections to ensure clean state or update them if provided
    if (isset($_GET['week'])) $_SESSION['selected_week'] = $_GET['week'];
    if (isset($_GET['month'])) $_SESSION['selected_month'] = $_GET['month'];
    if (isset($_GET['year'])) $_SESSION['selected_year'] = $_GET['year'];
}

// Set defaults
$reportType = isset($_SESSION['report_type']) ? $_SESSION['report_type'] : 'weekly';
$selectedWeek = isset($_SESSION['selected_week']) ? $_SESSION['selected_week'] : date('Y-\WW'); 
$selectedMonth = isset($_SESSION['selected_month']) ? $_SESSION['selected_month'] : date('Y-m');
$selectedYear = isset($_SESSION['selected_year']) ? $_SESSION['selected_year'] : date('Y');

// Determine display title
$chartTitle = "Weekly Amount";
if ($reportType == 'monthly') $chartTitle = "Monthly Amount";
if ($reportType == 'yearly') $chartTitle = "Yearly Amount";
?>

<?php include 'navbar.php'; ?>

    <div class="week-selection-container">
        <form method="GET" action="" class="week-form" id="reportForm">
            <div class="input-group">
                <label for="reportType">Report Type</label>
                <select name="report_type" id="reportType" onchange="toggleInputs()" style="padding: 5px; border-radius: 5px; border: 1px solid #ccc; margin-right: 10px;">
                    <option value="weekly" <?php echo $reportType == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="monthly" <?php echo $reportType == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    <option value="yearly" <?php echo $reportType == 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                </select>

                <span id="weekInput" style="display:none;">
                    <input type="week" name="week" value="<?php echo htmlspecialchars($selectedWeek); ?>" style="border:none; outline:none; background:transparent;">
                </span>

                <span id="yearInput" style="display:none;">
                    <input type="number" name="year" value="<?php echo htmlspecialchars($selectedYear); ?>" min="2020" max="2099" style="width: 80px; padding: 5px; border:none; outline:none; background:transparent;">
                </span>

                <button type="submit" class="btn-apply">Apply</button>
            </div>
        </form>
    </div>

    <script>
        function toggleInputs() {
            var type = document.getElementById('reportType').value;
            // Weekly: Show Week
            document.getElementById('weekInput').style.display = (type == 'weekly') ? 'inline-block' : 'none';
            // Monthly: Show Year (to pick which year's months to see)
            document.getElementById('yearInput').style.display = (type == 'monthly') ? 'inline-block' : 'none';
            // Yearly: Show Nothing (shows all years)
            // Note: If type is yearly, both inputs are hidden
        }
        // Run on load
        toggleInputs();
    </script>

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
                <h4>Income Comparison</h4>
                <h1 id="compEarning">...</h1>
                <p id="compEarningDesc">Waiting data...</p>
            </div>
        </div>

        <div class="div7">
            <div class="card">
                <h4>Parcel Quantity Comparison</h4>
                <h1 id="compParcel">...</h1>
                <p id="compParcelDesc">Waiting data...</p>
            </div>
        </div>


        <div class="div3">
            <div class="card">
                <h4><?php echo $chartTitle; ?></h4>
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