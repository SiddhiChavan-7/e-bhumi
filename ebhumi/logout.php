<?php
session_start();

// Destroy citizen session variables
if (isset($_SESSION['citizen_logged_in'])) {
    unset($_SESSION['citizen_logged_in']);
    unset($_SESSION['citizen_id']);
    unset($_SESSION['citizen_name']);
}

// Redirect to home page
header("Location: index.php");
exit;
?>
