<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';
require_once __DIR__ . '/../db/connection.php';

noCacheHeaders();
requireUserOrExitJson();
$user_id = $_SESSION['user_id'];

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

        $conn->close();
        ajax_success(['texts' => $texts]);
    }

    // Modo anterior: basado en saved_words (se mantiene por compatibilidad)
    $stmt = $conn->prepare(
        "SELECT DISTINCT t.id, t.title, t.title_translation, t.user_id, CASE WHEN t.user_id = ? THEN 'own' ELSE 'public' END as text_type, COUNT(DISTINCT sw.id) as saved_word_count FROM texts t INNER JOIN saved_words sw ON t.id = sw.text_id WHERE sw.user_id = ? GROUP BY t.id ORDER BY t.title ASC"
    );
    if (!$stmt) {
        error_log("ajax_user_texts.php - Error preparando statement: " . $conn->error);
        if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
        ajax_error('Error de db', 500, $conn->error);
    }
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $texts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    ajax_success(['texts' => $texts]);

} catch (Exception $e) {
    error_log("ajax_user_texts.php - Excepción: " . $e->getMessage());
    if (isset($stmt) && $stmt instanceof mysqli_stmt) { @ $stmt->close(); }
    if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
    ajax_error('Error interno del servidor', 500, $e->getMessage());
}
?>
