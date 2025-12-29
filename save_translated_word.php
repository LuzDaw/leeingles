<?php
session_start();
require_once 'db/connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

if (!isset($_POST['word']) || !isset($_POST['translation'])) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$user_id = $_SESSION['user_id'];
$word = trim($_POST['word']);
$translation = trim($_POST['translation']);
$context = isset($_POST['context']) ? trim($_POST['context']) : '';

// Obtener text_id de forma robusta
$text_id = null;
if (isset($_POST['text_id']) && is_numeric($_POST['text_id'])) {
    $text_id = intval($_POST['text_id']);
} elseif (isset($_GET['text_id']) && is_numeric($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
} elseif (isset($_SERVER['HTTP_REFERER'])) {
    // Intentar extraer text_id de la URL referer
    if (preg_match('/[?&]text_id=(\d+)/', $_SERVER['HTTP_REFERER'], $matches)) {
        $text_id = intval($matches[1]);
    }
}

if (!$text_id) {
    echo json_encode(['success' => false, 'error' => 'No se pudo determinar el id del texto']);
    exit;
}

if (empty($word) || empty($translation)) {
    echo json_encode(['error' => 'Palabra y traducción son requeridas']);
    exit();
}

try {
    // Verificar si la palabra ya existe para este usuario y texto
    $stmt = $conn->prepare("SELECT id FROM saved_words WHERE user_id = ? AND word = ? AND (text_id = ? OR (? IS NULL AND text_id IS NULL))");
    $stmt->bind_param("isis", $user_id, $word, $text_id, $text_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Actualizar traducción existente
        $stmt = $conn->prepare("UPDATE saved_words SET translation = ?, context = ?, text_id = ?, created_at = NOW() WHERE user_id = ? AND word = ? AND (text_id = ? OR (? IS NULL AND text_id IS NULL))");
        $stmt->bind_param("sssisis", $translation, $context, $text_id, $user_id, $word, $text_id, $text_id);
    } else {
        // Insertar nueva palabra
        $stmt = $conn->prepare("INSERT INTO saved_words (user_id, word, translation, context, text_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $user_id, $word, $translation, $context, $text_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Palabra guardada correctamente']);
    } else {
        echo json_encode(['error' => 'Error al guardar la palabra']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?>
