<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/title_functions.php';
require_once __DIR__ . '/../includes/db_helpers.php';

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
$text_row = get_text_if_allowed($conn, $text_id, $user_id);
if ($text_row === false) {
    echo json_encode(['error' => 'Texto no encontrado o no autorizado']);
    exit();
}

// Guardar la traducción del título
// Guardar traducción del título (actualizar campo en DB)
$ok = update_text_title_translation($conn, $text_id, $translation);
if ($ok) {
    $result = ['success' => true, 'message' => 'Traducción guardada'];
} else {
    $result = ['success' => false, 'error' => 'Error al guardar traducción'];
}

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => $result['message']]);
} else {
    echo json_encode(['error' => $result['error']]);
}

$conn->close();
?>
