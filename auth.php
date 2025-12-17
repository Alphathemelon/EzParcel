<?php
// Simple authorization include
// Usage: include 'auth.php'; authorize([1]);

if (session_status() === PHP_SESSION_NONE) session_start();

// Require user to be logged in
if (empty($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header('Location: login.php');
    exit;
}

// Helper to authorize by allowed levels
function authorize(array $allowedLevels = []) {
    $lvl = isset($_SESSION['user_level']) ? (int)$_SESSION['user_level'] : 0;
    if (empty($allowedLevels)) return; // no restriction
    if (!in_array($lvl, $allowedLevels, true)) {
        // Redirect user to their default landing page
        if ($lvl === 1) {
            header('Location: orderhistory.php');
            exit;
        } elseif ($lvl === 2) {
            header('Location: parcelstatus.php');
            exit;
        } else {
            // Unknown level — log out
            session_unset();
            session_destroy();
            header('Location: login.php');
            exit;
        }
    }
}

?>
