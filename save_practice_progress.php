<?php
session_start();
require_once 'db/connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

if (!isset($_POST['mode']) || !isset($_POST['total_words']) || !isset($_POST['correct_answers']) || !isset($_POST['incorrect_answers'])) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$user_id = $_SESSION['user_id'];
$mode = $_POST['mode'];
$total_words = intval($_POST['total_words']);
$correct_answers = intval($_POST['correct_answers']);
$incorrect_answers = intval($_POST['incorrect_answers']);
$text_id = isset($_POST['text_id']) ? intval($_POST['text_id']) : null;

// Calcular precisión (correctas / total de respuestas)
$total_answers = $correct_answers + $incorrect_answers;
$accuracy = $total_answers > 0 ? round(($correct_answers / $total_answers) * 100, 2) : 0;

try {
    $stmt = $conn->prepare("INSERT INTO practice_progress (user_id, text_id, mode, total_words, correct_answers, incorrect_answers, accuracy) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisiiid", $user_id, $text_id, $mode, $total_words, $correct_answers, $incorrect_answers, $accuracy);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Progreso guardado correctamente',
            'accuracy' => $accuracy
        ]);
    } else {
        echo json_encode(['error' => 'Error al guardar el progreso']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?> 