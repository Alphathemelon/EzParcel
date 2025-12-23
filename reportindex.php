<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stylereport.css">

    <title>Report EzParcel</title>
</head>
<body>
    
<?php
include_once 'auth.php';
authorize([1]); // Hanya admin (level 1) yang boleh akses halaman ini
?>

<?php include 'navbar.php'; ?>
    <div class="parent">
        <div class="div1">
            <div class="card">
                <h4>Total Earning Today</h4>
                <h1 id="displayAmount"></h1>
                <p class="update-time">Last updated: <span id="lastUpdated"></span></p>
            </div>
        </div>

       <div class="div2">
            <div class="card">
                <h4>Total Parcel Today</h4>
                <h1 id="displayCollected">0</h1>
                <p class="update-time">Last updated: <span id="lastUpdatedCollected"></span></p>
            </div>
        </div>


        <div class="div3">
            <div class="card">
                <h4>Weekly Amount Parcel</h4>
                <div class="chart-container">
                    <canvas id="amountChart"></canvas>
                </div>
            </div>
        </div>

        <div class="div4">
            <div class="card">
                <h4>Weekly Parcel Weightage </h4>
                <div class="chart-container">
                    <canvas id="myChart"></canvas>
                </div>
            </div>
        </div>
    
        
        <div class="div5">
            <div class="card">
                <h4>Weekly Parcel Status </h4>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

    <!-- Custom scripts -->

    <script src="js/report.js"></script>
    <script src="js/report2.js"></script>
    <script src="js/report3.js"></script> 
    <script src="js/report4.js"></script>
    <script src="js/report5.js"></script> 




</body>
</html>
