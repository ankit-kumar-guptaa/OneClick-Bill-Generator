<?php
// admin/dashboard.php - Redirect to main dashboard
session_start();
require_once '../includes/functions.php';

redirectIfNotLoggedIn('../admin/login.php');

// Redirect to main dashboard
header('Location: ../index.php');
exit;
?>
