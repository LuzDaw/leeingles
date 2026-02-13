<?php
session_start();
require_once '../db/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Manejar guardado de progreso de práctica
if (isset($_POST['save_practice_progress'])) {
    $user_id = $_SESSION['user_id'];
    $mode = $_POST['mode'] ?? '';
    $total_words = intval($_POST['total_words'] ?? 0);
    $correct_answers = intval($_POST['correct_answers'] ?? 0);
    $incorrect_answers = intval($_POST['incorrect_answers'] ?? 0);
    $text_id = isset($_POST['text_id']) ? intval($_POST['text_id']) : null;

    require_once __DIR__ . '/../db/connection.php';
    require_once __DIR__ . '/../includes/practice_functions.php';

    $res = savePracticeProgress($user_id, $mode, $total_words, $correct_answers, $incorrect_answers, $text_id);
    echo json_encode(['success' => $res['success'], 'error' => $res['error'] ?? null, 'accuracy' => $res['accuracy'] ?? null]);
    exit;
}

// Manejar otras peticiones de práctica si las hay
echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
?>
