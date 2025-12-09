<?php 
$currentPage = basename($_SERVER['PHP_SELF']); 
?>
<nav style="background: #3454B4; padding: 12px 0; text-align:center;">
    <a href="orderhistory.php" class="<?= ($currentPage == 'orderhistory.php') ? 'active' : '' ?>">Order History</a>
    <a href="parcelweightage.html" class="<?= ($currentPage == 'parcelrecord.html') ? 'active' : '' ?>">Parcel Record</a>
    <a href="profile.php" class="<?= ($currentPage == 'profile.php') ? 'active' : '' ?>">Report</a>
</nav>

<style>
nav a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    margin: 0 20px;
    font-weight: 500;
    padding-bottom: 3px;
}

nav a:hover {
    text-decoration: underline;
}

nav a.active {
    border-bottom: 2px solid white;
}
</style>
