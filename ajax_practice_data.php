<?php
session_start();
require_once 'db/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Endpoint para obtener el número de palabras de un texto específico
if (isset($_GET['get_word_count']) && isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as word_count FROM saved_words WHERE user_id = ? AND text_id = ?");
    $stmt->bind_param("ii", $user_id, $text_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    echo json_encode(['word_count' => $data['word_count']]);
    $stmt->close();
    $conn->close();
    exit();
}

// Obtener palabras guardadas del usuario para práctica
$stmt = $conn->prepare("SELECT word, translation, context FROM saved_words WHERE user_id = ? ORDER BY RAND()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$words = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['words' => $words]);

$stmt->close();
$conn->close();
?>
