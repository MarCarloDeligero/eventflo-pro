<?php
// api/cancel-event.php
require_once '../includes/config.php';
require_once '../classes/Event.php';

// Session is already started in config.php, so remove session_start()

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Event ID required']);
    exit;
}

$event_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$event = new Event();
$eventData = $event->getById($event_id);

// Check if the event exists and belongs to the user
if (!$eventData || $eventData->user_id != $user_id) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'You do not have permission to cancel this event']);
    exit;
}

// Cancel the event
if ($event->cancelEvent($event_id)) {
    $_SESSION['success_message'] = 'Event cancelled successfully';
    header('Location: ../pages/my-events.php');
} else {
    $_SESSION['error_message'] = 'Failed to cancel event';
    header('Location: ../pages/my-events.php');
}
exit;