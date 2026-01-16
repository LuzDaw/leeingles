<?php
session_start();
require_once '../db/connection.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (function_exists('ob_get_length')) { while (ob_get_level()>0) { ob_end_clean(); } }

// Log para debugging
error_log("ajax_user_texts.php - Inicio de la petición");

if (!isset($_SESSION['user_id'])) {
    error_log("ajax_user_texts.php - Usuario no autenticado");
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];
error_log("ajax_user_texts.php - User ID: " . $user_id);

try {
    // Nuevo modo API JSON para práctica: listar textos propios y públicos leídos
    if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && isset($_POST['action']) && $_POST['action'] === 'list') {
        $texts = [];

        // Textos propios que tienen palabras guardadas (excluyendo ocultos)
        $own = $conn->prepare("SELECT DISTINCT t.id, t.title, t.title_translation, t.user_id, 'own' as text_type, COUNT(DISTINCT sw.id) as saved_word_count FROM texts t INNER JOIN saved_words sw ON t.id = sw.text_id WHERE t.user_id = ? AND sw.user_id = ? AND (t.is_public = 0 OR t.id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?)) GROUP BY t.id ORDER BY t.created_at DESC LIMIT 500");
        $own->bind_param('iii', $user_id, $user_id, $user_id);
        $own->execute();
        $ownRes = $own->get_result();
        while ($r = $ownRes->fetch_assoc()) { $texts[] = $r; }
        $own->close();

        // Textos públicos que tienen palabras guardadas (según progreso de lectura)
        $pub = $conn->prepare("SELECT DISTINCT t.id, t.title, t.title_translation, t.user_id, 'public' as text_type, COUNT(DISTINCT sw.id) as saved_word_count FROM texts t INNER JOIN saved_words sw ON t.id = sw.text_id INNER JOIN reading_progress rp ON rp.text_id = t.id WHERE rp.user_id = ? AND sw.user_id = ? AND t.is_public = 1 AND t.user_id != ? AND (rp.percent > 0 OR rp.read_count > 0) AND t.id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?) GROUP BY t.id ORDER BY rp.updated_at DESC, t.title ASC LIMIT 500");
        $pub->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
        $pub->execute();
        $pubRes = $pub->get_result();
        while ($r = $pubRes->fetch_assoc()) { $texts[] = $r; }
        $pub->close();

        echo json_encode(['success' => true, 'texts' => $texts]);
        $conn->close();
        exit();
    }

    // Modo anterior: basado en saved_words (se mantiene por compatibilidad)
    $stmt = $conn->prepare(
        "SELECT DISTINCT t.id, t.title, t.title_translation, t.user_id, CASE WHEN t.user_id = ? THEN 'own' ELSE 'public' END as text_type, COUNT(DISTINCT sw.id) as saved_word_count FROM texts t INNER JOIN saved_words sw ON t.id = sw.text_id WHERE sw.user_id = ? GROUP BY t.id ORDER BY t.title ASC"
    );
    if (!$stmt) {
        error_log("ajax_user_texts.php - Error preparando statement: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
        exit();
    }
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $texts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'texts' => $texts]);

} catch (Exception $e) {
    error_log("ajax_user_texts.php - Excepción: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
