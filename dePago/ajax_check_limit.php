<?php
/**
 * Endpoint para verificación rápida de límite de traducciones
 * Ubicación: dePago/ajax_check_limit.php
 */
session_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/subscription_functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_active_reading = isset($_GET['active_reading']) && $_GET['active_reading'] === '1';

// Reutilizar la función maestra de verificación
$limit_check = checkTranslationLimit($user_id, $is_active_reading);

echo json_encode($limit_check);
