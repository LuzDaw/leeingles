<?php
session_start();
require_once 'db/connection.php';
require_once 'includes/content_functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

if (!isset($_GET['text_id'])) {
    echo json_encode(['error' => 'ID de texto requerido']);
    exit();
}

$user_id = $_SESSION['user_id'];
$text_id = intval($_GET['text_id']);

// Verificar que el texto pertenece al usuario o es público
try {
    $stmt = $conn->prepare("SELECT id, title, content FROM texts WHERE id = ? AND (user_id = ? OR is_public = 1)");
    $stmt->bind_param("ii", $text_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Texto no encontrado o no autorizado']);
        exit();
    }
    
    $text_data = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Error verificando autorización']);
    exit();
}

// Obtener la traducción del contenido
$translation = getContentTranslation($text_id);

if ($translation) {
    // Verificar si es el nuevo formato JSON o el antiguo
    if (is_array($translation)) {
        // Nuevo formato JSON - array de traducciones
        echo json_encode([
            'success' => true,
            'text_id' => $text_id,
            'title' => $text_data['title'],
            'content' => $text_data['content'],
            'translation' => $translation,
            'format' => 'json',
            'source' => 'database'
        ]);
    } else {
        // Formato antiguo - texto plano
        echo json_encode([
            'success' => true,
            'text_id' => $text_id,
            'title' => $text_data['title'],
            'content' => $text_data['content'],
            'translation' => $translation,
            'format' => 'plain',
            'source' => 'database'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'text_id' => $text_id,
        'title' => $text_data['title'],
        'content' => $text_data['content'],
        'translation' => null,
        'needs_translation' => true
    ]);
}

$conn->close();
?>
