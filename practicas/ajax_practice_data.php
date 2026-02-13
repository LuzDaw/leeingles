<?php
session_start();
require_once '../db/connection.php';
require_once __DIR__ . '/../includes/word_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Endpoint para obtener el número de palabras de un texto específico
if (isset($_GET['get_word_count']) && isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
    
    $count = countSavedWords($user_id, $text_id);
    echo json_encode(['word_count' => $count]);
    $conn->close();
    exit();
}

// Obtener palabras guardadas del usuario para práctica
$words = getRandomWordsForPractice($user_id, 0);
echo json_encode(['words' => $words]);

$conn->close();
?>
