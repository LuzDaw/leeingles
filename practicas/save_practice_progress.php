<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit();
}

if (!isset($_POST['mode']) || !isset($_POST['total_words']) || !isset($_POST['correct_answers']) || !isset($_POST['incorrect_answers'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

$user_id = $_SESSION['user_id'];
$mode = $_POST['mode'];
$total_words = intval($_POST['total_words']);
$correct_answers = intval($_POST['correct_answers']);
$incorrect_answers = intval($_POST['incorrect_answers']);
$text_id = isset($_POST['text_id']) ? intval($_POST['text_id']) : null;

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/practice_functions.php';

$res = savePracticeProgress($user_id, $mode, $total_words, $correct_answers, $incorrect_answers, $text_id);
if ($res['success']) {
    echo json_encode(['success' => true, 'message' => $res['message'] ?? 'Progreso guardado', 'accuracy' => $res['accuracy'] ?? null]);
} else {
    echo json_encode(['success' => false, 'error' => $res['error'] ?? 'Error al guardar el progreso']);
}

?>
