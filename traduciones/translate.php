<?php
// =============================
// SISTEMA HÍBRIDO DE TRADUCCIÓN
// =============================
// 1. Intenta DeepL API
// 2. Si falla, usa Google Translate API
// 3. Si ambos fallan, devuelve error

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../dePago/subscription_functions.php';

session_start();

// La clave de DeepL se obtiene desde la variable de entorno `DEEPL_API_KEY` en includes/translation_service.php

// Aceptar tanto 'text' como 'word' como parámetro
$text = $_POST['text'] ?? $_POST['word'] ?? null;
header('Content-Type: application/json; charset=utf-8');

if (!$text) {
    echo json_encode(['error' => 'No se proporcionó ningún texto']);
    exit();
}

// Limpiar texto
$text = trim($text);
if ($text === '') {
    echo json_encode(['error' => 'Texto vacío']);
    exit();
}

// Verificar límite de suscripción si el usuario está logueado
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // Guardar ID antes de cerrar sesión
    $is_active_reading = isset($_POST['active_reading']) && $_POST['active_reading'] === '1';
    $limit_check = checkTranslationLimit($user_id, $is_active_reading);
    
    if (!$limit_check['can_translate']) {
        echo json_encode([
            'error' => 'Has alcanzado tu límite semanal de traducciones.',
            'limit_reached' => true,
            'next_reset' => $limit_check['next_reset'] ?? null
        ]);
        exit();
    }
}

// Liberar bloqueo de sesión inmediatamente para permitir otras peticiones paralelas
// Especialmente importante ya que este script hace llamadas a APIs externas lentas
session_write_close();

require_once __DIR__ . '/../includes/translation_service.php';

$result = translateText($text);
if (isset($result['error'])) {
    echo json_encode(['error' => $result['error']]);
    exit();
}

// Registrar el uso si la traducción fue exitosa y el usuario está logueado
if (isset($user_id)) {
    incrementTranslationUsage($user_id, $text);
}

echo json_encode($result);
?>
