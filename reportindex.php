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

        <div class="div1">1</div>
        <div class="div2">2</div>
        <div class="div3">3</div>

        <!-- Graph 1 -->
        <div class="div4">
            <canvas id="myChart"></canvas>
        </div>

        <!-- Graph 2 -->
        <div class="div5">
            <canvas id="statusChart"></canvas>
        </div>

    </div>

    <!-- Load Chart.js ONCE at top of all scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Load custom scripts AFTER Chart.js -->
    <script src="js/report.js"></script>
    <script src="js/report2.js"></script>
    
</body>
</html>
