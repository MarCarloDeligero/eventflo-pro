<?php
/**
 * Common utility functions for EventFlow Pro
 */

// Redirect with message
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}

// Get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        return [
            'message' => $message,
            'type' => $type
        ];
    }
    return null;
}

// Display flash message
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alert_class = $flash['type'] === 'error' ? 'danger' : $flash['type'];
        echo '<div class="alert alert-' . $alert_class . ' alert-dismissible fade show">';
        echo '<i class="fas fa-' . ($flash['type'] === 'success' ? 'check' : 'exclamation') . '-circle me-2"></i>';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Generate ticket number
function generateTicketNumber() {
    return 'TKT-' . strtoupper(uniqid());
}

// Format date for display
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

// Format time for display
function formatTime($time, $format = 'g:i A') {
    return date($format, strtotime($time));
}

// Format currency
function formatCurrency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

// Truncate text
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// Sanitize input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Check if string is JSON
function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

// Get file extension
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Check if image file
function isImageFile($filename) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = getFileExtension($filename);
    return in_array($extension, $allowed);
}

// Upload file
function uploadFile($file, $upload_dir, $allowed_types = [], $max_size = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds maximum allowed size');
    }

    $extension = getFileExtension($file['name']);
    if (!empty($allowed_types) && !in_array($extension, $allowed_types)) {
        throw new Exception('File type not allowed');
    }

    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = uniqid() . '.' . $extension;
    $filepath = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }

    return $filename;
}

// Delete file
function deleteFile($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// Get relative time (e.g., "2 hours ago")
function getRelativeTime($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

// Check if event is upcoming
function isEventUpcoming($event_date, $event_time) {
    $event_datetime = strtotime($event_date . ' ' . $event_time);
    return $event_datetime > time();
}

// Check if event registration is open
function isRegistrationOpen($event_date, $registration_deadline = null) {
    $now = time();
    $event_time = strtotime($event_date);
    
    if ($registration_deadline) {
        $deadline = strtotime($registration_deadline);
        return $now < $deadline;
    }
    
    return $now < $event_time;
}

// Calculate event progress
function calculateEventProgress($event_capacity, $registered_count) {
    if ($event_capacity <= 0) return 0;
    return min(100, round(($registered_count / $event_capacity) * 100));
}

// Send email notification
function sendEmail($to, $subject, $body, $headers = []) {
    $default_headers = [
        'From: ' . SITE_EMAIL,
        'Reply-To: ' . SITE_EMAIL,
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $all_headers = array_merge($default_headers, $headers);
    $header_string = implode("\r\n", $all_headers);
    
    return mail($to, $subject, $body, $header_string);
}

// Log activity
function logActivity($user_id, $action, $details = '') {
    $db = new Database();
    $db->query('INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
               VALUES (:user_id, :action, :details, :ip_address, :user_agent)');
    
    $db->bind(':user_id', $user_id);
    $db->bind(':action', $action);
    $db->bind(':details', $details);
    $db->bind(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
    $db->bind(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
    
    return $db->execute();
}

// Get user IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

// Check if request is AJAX
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Error response
function errorResponse($message, $status = 400) {
    return jsonResponse([
        'success' => false,
        'message' => $message
    ], $status);
}

// Success response
function successResponse($data = null, $message = '') {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    return jsonResponse($response);
}
?>