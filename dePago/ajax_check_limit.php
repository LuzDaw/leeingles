<?php
/**
 * Endpoint para verificación rápida de límite de traducciones
 * Ubicación: dePago/ajax_check_limit.php
 */
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/subscription_functions.php';

header('Content-Type: application/json; charset=utf-8');
requireUserOrExitJson();

$user_id = $_SESSION['user_id'];
$is_active_reading = isset($_GET['active_reading']) && $_GET['active_reading'] === '1';
$limit_check = checkTranslationLimit($user_id, $is_active_reading);
echo json_encode($limit_check);
