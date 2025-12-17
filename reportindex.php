<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stylereport.css">

    <title>Report EzParcel</title>
</head>
<body>


    <div class="parent">

        <div class="div1">
            <h4>Total Earning Today</h4>
            <h1 id="displayAmount"></h1>
            <script src="js/report3.js"></script> 
        </div>

        <div class="div2">
            total parcel arrived today
        </div>

        <div class="div3">
            <h4>Weekly Amount Parcel</h4>
            <canvas id="amountChart"></canvas>
        </div>

        <div class="div4">
            <h4>Weekly Parcel Weigthage </h4>
       	    <canvas id="myChart"></canvas>
        </div>
    
        
        <div class="div5">
            <h4>Weekly Parcel Status </h4>
            <canvas id="statusChart"></canvas>
        </div>

    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom scripts -->
    <script src="js/report4.js"></script>
    <script src="js/report.js"></script>
    <script src="js/report2.js"></script>


</body>
</html>
