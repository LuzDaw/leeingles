<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/title_functions.php';

header('Content-Type: application/json; charset=utf-8');
requireUserOrExitJson();

if (!isset($_GET['text_id'])) {
    echo json_encode(['error' => 'ID de texto requerido']);
    exit();
}

$user_id = $_SESSION['user_id'];
$text_id = intval($_GET['text_id']);

// Verificar que el texto pertenece al usuario o es público
try {
    $stmt = $conn->prepare("SELECT id, title, title_translation FROM texts WHERE id = ? AND (user_id = ? OR is_public = 1)");
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

// Obtener la traducción del título
$translation = getTitleTranslation($text_id);

if ($translation) {
    echo json_encode([
        'success' => true,
        'text_id' => $text_id,
        'title' => $text_data['title'],
        'translation' => $translation,
        'source' => 'database'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'text_id' => $text_id,
        'title' => $text_data['title'],
        'translation' => null,
        'needs_translation' => true
    ]);
}

$conn->close();
?>
