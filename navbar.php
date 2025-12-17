<?php 
$currentPage = basename($_SERVER['PHP_SELF']); 
?>
<nav>
    <div class="nav-center">
        <a href="orderhistory.php" class="<?= ($currentPage == 'orderhistory.php') ? 'active' : '' ?>">Order History</a>
        <a href="parcelhistory.php" class="<?= ($currentPage == 'parcelhistory.php') ? 'active' : '' ?>">Parcel Record</a>
        <a href="reportindex.php" class="<?= ($currentPage == 'reportindex.php') ? 'active' : '' ?>">Report</a>
    </div>
    <div class="nav-right">
        <a href="logout.php" class="<?= ($currentPage == 'logout.php') ? 'active' : '' ?>">Log Out</a>
    </div>
</nav>


<style>
nav {
    width: 100%;
    background: #3454B4;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between; /* kiri/kanan */
    align-items: center;
    box-sizing: border-box;
}

.nav-center {
    margin: 0 auto; /* center the middle links */
}

.nav-center a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    margin: 0 15px;
    font-weight: 500;
    padding-bottom: 3px;
}

.nav-right a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    margin-left: 20px;
    font-weight: 500;
    padding-bottom: 3px;
}

.nav-center a:hover,
.nav-right a:hover {
    text-decoration: underline;
}

.nav-center a.active,
.nav-right a.active {
    border-bottom: 2px solid white;
}

</style>
