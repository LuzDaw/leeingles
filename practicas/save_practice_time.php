<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
$mode = isset($_POST['mode']) ? $_POST['mode'] : '';

// Validaciones
if ($duration <= 0 || $duration > 3600 || !$mode) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos: duración debe estar entre 1 y 3600 segundos']);
    exit;
}

$valid_modes = ['selection', 'writing', 'sentences'];
if (!in_array($mode, $valid_modes)) {
    echo json_encode(['success' => false, 'error' => 'Modo de práctica inválido']);
    exit;
}

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/practice_functions.php';

$res = savePracticeTime($user_id, $mode, $duration);
if ($res['success']) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $res['error'] ?? 'Error al guardar en BD']);
}

?>