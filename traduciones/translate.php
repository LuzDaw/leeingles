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

// Si el usuario no está logueado, no podemos usar la lógica de BD/Caché de usuario.
// En este caso, la función get_or_translate_word no es adecuada.
// Por ahora, mantenemos una llamada directa para usuarios no registrados.
// TODO: Considerar una caché pública o una lógica unificada para no registrados.
if ($user_id === null) {
    // Para usuarios no registrados, llamamos directamente a las funciones de API
    // que movimos a word_functions.php
    $lang_info = detectLanguage($text);
    $deepl_api_key = getenv('DEEPL_API_KEY') ?: '89bb7c47-40dc-4628-9efb-8882bb6f5fba:fx';
    $translation = translateWithDeepL($text, $lang_info['deepl_target'], $deepl_api_key);
    $source = 'DeepL';

    if ($translation === false) {
        $translation = translateWithGoogle($text, $lang_info['source'], $lang_info['google_target']);
        $source = 'Google Translate';
    }

    if ($translation === false) {
        $result = ['error' => 'No se pudo traducir el texto'];
    } else {
        $result = ['translation' => $translation, 'source' => $source];
    }

} else {
    // Usuario logueado: usar el sistema centralizado
    $result = get_or_translate_word($conn, $user_id, $text);
    if (isset($result['translation']) && $result['translation'] !== null) {
        // Registrar el uso si la traducción fue exitosa
        incrementTranslationUsage($user_id, $text);
    } else {
        $result['error'] = 'No se pudo traducir el texto';
    }
}

echo json_encode($result);
?>
