<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
// Determine user level if available
$user_level = isset($_SESSION['user_level']) ? (int)$_SESSION['user_level'] : 0;
?>
<nav>
    <div class="nav-center">
        <?php if ($user_level === 1): ?>
            <a href="orderhistory.php" class="<?= ($currentPage == 'orderhistory.php') ? 'active' : '' ?>">Order History</a>
            <a href="parcelrecord.php" class="<?= ($currentPage == 'parcelrecord.php') ? 'active' : '' ?>">Parcel Record</a>
            <a href="reportindex.php" class="<?= ($currentPage == 'reportindex.php') ? 'active' : '' ?>">Report</a>
        <?php elseif ($user_level === 2): ?>
            <a href="parcelstatus.php" class="<?= ($currentPage == 'parcelstatus.php') ? 'active' : '' ?>">Parcel Status</a>
        <?php else: ?>
            <!-- Default links for unauthenticated or unknown -->
            <a href="login.php">Home</a>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <?php if (!empty($_SESSION['loggedIn'])): ?>
            <span style="color:white;margin-right:12px;">Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
            <a href="logout.php" class="<?= ($currentPage == 'logout.php') ? 'active' : '' ?>">Log Out</a>
        <?php else: ?>
            <a href="login.php">Sign In</a>
        <?php endif; ?>
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
