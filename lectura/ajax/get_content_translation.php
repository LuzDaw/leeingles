<?php
require_once __DIR__ . '/../../includes/ajax_common.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';
require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../../includes/content_functions.php';
require_once __DIR__ . '/../../dePago/subscription_functions.php';

noCacheHeaders();
requireUserOrExitJson();

if (!isset($_GET['text_id'])) {
    ajax_error('ID de texto requerido', 400);
}

$user_id = $_SESSION['user_id'];
$text_id = intval($_GET['text_id']);

// Verificar límite de suscripción antes de permitir el acceso a la traducción
$is_active_reading = isset($_GET['active_reading']) && $_GET['active_reading'] === '1';
$limit_check = checkTranslationLimit($user_id, $is_active_reading);

if (!$limit_check['can_translate']) {
    ajax_error('Has alcanzado tu límite semanal de traducciones.', 429, json_encode($limit_check));
}

// Verificar que el texto pertenece al usuario o es público
try {
    $stmt = $conn->prepare("SELECT id, title, content FROM texts WHERE id = ? AND (user_id = ? OR is_public = 1)");
    $stmt->bind_param("ii", $text_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        if (isset($stmt) && $stmt instanceof mysqli_stmt) { @ $stmt->close(); }
        if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
        ajax_error('Texto no encontrado o no autorizado', 404);
    }
    
    $text_data = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) { @ $stmt->close(); }
    if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
    ajax_error('Error verificando autorización', 500, $e->getMessage());
}

// Obtener la traducción del contenido
$translation = getContentTranslation($text_id);

if ($translation) {
    // ELIMINADO: Ya no cobramos el texto completo al cargarlo del caché.
    // El cobro se hará párrafo a párrafo conforme se muestren en el lector.
    // incrementTranslationUsage($user_id, $text_data['content']);

    // Verificar si es el nuevo formato JSON o el antiguo
    if ($translation) {
        if (is_array($translation)) {
            ajax_success([
                'text_id' => $text_id,
                'title' => $text_data['title'],
                'content' => $text_data['content'],
                'translation' => $translation,
                'format' => 'json',
                'source' => 'database'
            ]);
        } else {
            ajax_success([
                'text_id' => $text_id,
                'title' => $text_data['title'],
                'content' => $text_data['content'],
                'translation' => $translation,
                'format' => 'plain',
                'source' => 'database'
            ]);
        }
    } else {
        ajax_success([
            'text_id' => $text_id,
            'title' => $text_data['title'],
            'content' => $text_data['content'],
            'translation' => null,
            'needs_translation' => true
        ]);
    }

    if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
?>
