<?php
require_once __DIR__ . '/../../includes/ajax_common.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';
noCacheHeaders();
requireUserOrExitJson();

$user_id = $_SESSION['user_id'];
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
$text_id = isset($_POST['text_id']) ? intval($_POST['text_id']) : 0;

// Validaciones
if ($duration <= 0 || $duration > 3600 || $text_id <= 0) {
    ajax_error('Datos inválidos: duración debe estar entre 1 y 3600 segundos', 400);
}

require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../../includes/practice_functions.php';

$res = saveReadingTime($user_id, $text_id, $duration);
if ($res['success']) {
    ajax_success();
} else {
    ajax_error('Error al guardar en BD', 500, $res['error'] ?? null);
}

?>