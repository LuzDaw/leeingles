<?php
/**
 * Endpoint para incrementar el uso de traducciones sin realizar una traducción nueva
 * (Para cuando se recuperan del caché pero deben contar en el cupo semanal)
 */
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../dePago/subscription_functions.php';

header('Content-Type: application/json; charset=utf-8');
requireUserOrExitJson();

$text = $_POST['text'] ?? null;
if (!$text || trim($text) === '') {
    echo json_encode(['error' => 'No se proporcionó texto']);
    exit();
}

$user_id = $_SESSION['user_id'];
$success = incrementTranslationUsage($user_id, $text);
echo json_encode(['success' => $success]);
