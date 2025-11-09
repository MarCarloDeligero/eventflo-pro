<?php
require_once '../includes/config.php';

$auth = new Auth();
$auth->logout();

// Set admin logout message
$_SESSION['flash_message'] = 'You have been logged out from admin panel.';
$_SESSION['flash_type'] = 'info';

// Redirect to admin login
header('Location: login.php');
exit;
?>