<?php
require_once '../includes/config.php';

$auth = new Auth();
$auth->logout();

// Set success message
$_SESSION['success'] = 'You have been logged out successfully.';

// Redirect to login page
header('Location: login.php');
exit;
?>