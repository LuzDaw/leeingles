<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/title_functions.php';

header('Content-Type: application/json; charset=utf-8');
requireUserOrExitJson();

if (!isset($_POST['text_id']) || !isset($_POST['title']) || !isset($_POST['translation'])) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$user_id = $_SESSION['user_id'];
$text_id = intval($_POST['text_id']);
$title = trim($_POST['title']);
$translation = trim($_POST['translation']);

if (empty($title) || empty($translation)) {
    echo json_encode(['error' => 'Título y traducción son requeridos']);
    exit();
}

// Verificar que el texto pertenece al usuario o es público
try {
    $stmt = $conn->prepare("SELECT id FROM texts WHERE id = ? AND (user_id = ? OR is_public = 1)");
    $stmt->bind_param("ii", $text_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Texto no encontrado o no autorizado']);
        exit();
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Error verificando autorización']);
    exit();
}

// Guardar la traducción del título
$result = saveTitleTranslation($text_id, $title, $translation);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => $result['message']]);
} else {
    echo json_encode(['error' => $result['error']]);
}

$conn->close();
?>
