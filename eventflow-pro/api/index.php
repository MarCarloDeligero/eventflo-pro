<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');
$path = $_GET['r'] ?? '';
if ($path === 'events') {
    $events = Event::all();
    echo json_encode(['status'=>'ok','data'=>$events]);
    exit;
}
echo json_encode(['status'=>'error','message'=>'Unknown endpoint']);
