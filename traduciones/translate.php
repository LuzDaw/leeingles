<?php
// =================================
// SISTEMA DE TRADUCCIÓN CENTRALIZADO
// =================================
// Utiliza la función get_or_translate_word para orquestar la traducción
// a través de BD, Caché y API.

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../dePago/subscription_functions.php';
require_once __DIR__ . '/../includes/word_functions.php'; // Nueva función central

session_start();

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

$user_id = null;
// Verificar límite de suscripción solo si el usuario está logueado
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
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

// Liberar bloqueo de sesión inmediatamente
session_write_close();

// Usuario logueado o no, usamos el sistema centralizado.
// La función get_or_translate_word ya maneja el caso de $user_id = null.
$result = get_or_translate_word($conn, $user_id, $text);

if ($user_id !== null && isset($result['translation']) && $result['translation'] !== null) {
    // Registrar el uso solo si el usuario está logueado y la traducción fue exitosa
    incrementTranslationUsage($user_id, $text);
} elseif (!isset($result['translation']) || $result['translation'] === null) {
    $result['error'] = 'No se pudo traducir el texto';
}

echo json_encode($result);
?>
