<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/practice_functions.php';

$stats = getPracticeStats($user_id);
echo json_encode(['success' => true, 'stats' => $stats]);

$conn->close();
?>