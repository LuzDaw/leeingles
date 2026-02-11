<?php
require_once __DIR__ . '/../../includes/ajax_common.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';
require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../../includes/content_functions.php';

noCacheHeaders();
requireUserOrExitJson();

if (!isset($_POST['text_id']) || !isset($_POST['content']) || !isset($_POST['translation'])) {
    ajax_error('Datos incompletos', 400);
}

$user_id = $_SESSION['user_id'];
$text_id = intval($_POST['text_id']);
$content = trim($_POST['content']);
$translation = trim($_POST['translation']);

if (empty($content) || empty($translation)) {
    ajax_error('Contenido y traducción son requeridos', 400);
}

// Verificar que el texto pertenece al usuario o es público
try {
    $stmt = $conn->prepare("SELECT id FROM texts WHERE id = ? AND (user_id = ? OR is_public = 1)");
    $stmt->bind_param("ii", $text_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        if (isset($stmt) && $stmt instanceof mysqli_stmt) { @ $stmt->close(); }
        if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
        ajax_error('Texto no encontrado o no autorizado', 404);
    }
    $stmt->close();
} catch (Exception $e) {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) { @ $stmt->close(); }
    if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
    ajax_error('Error verificando autorización', 500, $e->getMessage());
}

// Guardar la traducción del contenido
$result = saveContentTranslation($text_id, $content, $translation);

if ($result['success']) {
    if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
    ajax_success(['message' => 'Traducción de contenido guardada correctamente']);
} else {
    if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
    ajax_error($result['error'] ?? 'Error al guardar', 500, json_encode($result));
}
?>
