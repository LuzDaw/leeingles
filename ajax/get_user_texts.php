<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../db/connection.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (function_exists('ob_get_length')) { while (ob_get_level()>0) { ob_end_clean(); } }

requireUserOrExitJson();
$user_id = (int)$_SESSION['user_id'];

try {
    $texts = [];

    // Textos propios (excluyendo ocultos)
    $own = $conn->prepare("SELECT id, title, title_translation, user_id, 'own' as text_type FROM texts WHERE user_id = ? AND (is_public = 0 OR id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?)) ORDER BY created_at DESC LIMIT 500");
    $own->bind_param('ii', $user_id, $user_id);
    $own->execute();
    $ownRes = $own->get_result();
    while ($r = $ownRes->fetch_assoc()) { $texts[] = $r; }
    $own->close();

    // Textos públicos leídos por el usuario (según progreso de lectura)
    $pub = $conn->prepare("SELECT t.id, t.title, t.title_translation, t.user_id, 'public' as text_type FROM texts t INNER JOIN reading_progress rp ON rp.text_id = t.id WHERE rp.user_id = ? AND t.is_public = 1 AND t.user_id != ? AND (rp.percent > 0 OR rp.read_count > 0) AND t.id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?) GROUP BY t.id ORDER BY rp.updated_at DESC, t.title ASC LIMIT 500");
    $pub->bind_param('iii', $user_id, $user_id, $user_id);
    $pub->execute();
    $pubRes = $pub->get_result();
    while ($r = $pubRes->fetch_assoc()) { $texts[] = $r; }
    $pub->close();

    echo json_encode(['success' => true, 'texts' => $texts]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
} finally {
    if (isset($conn)) { $conn->close(); }
}
?>
