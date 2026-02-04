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

// Configuración DeepL
$deepl_api_key = '89bb7c47-40dc-4628-9efb-8882bb6f5fba:fx';

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

// Función simplificada para detectar idioma
/**
 * Detecta el idioma de un texto basándose en la presencia de caracteres especiales del español.
 *
 * Si el texto contiene caracteres como 'áéíóúñÁÉÍÓÚÑüÜ', se asume que es español.
 * De lo contrario, se asume que es inglés.
 *
 * @param string $text El texto cuyo idioma se desea detectar.
 * @return array Un array asociativo con 'source' (idioma detectado), 'target' (idioma objetivo para traducción),
 *               'deepl_target' (idioma objetivo para DeepL) y 'google_target' (idioma objetivo para Google Translate).
 */
function detectLanguage($text) {
    // Si contiene caracteres especiales del español, asumir español
    if (preg_match('/[áéíóúñÁÉÍÓÚÑüÜ]/u', $text)) {
        return ['source' => 'es', 'target' => 'en', 'deepl_target' => 'EN', 'google_target' => 'en'];
    }
    
    // Por defecto, asumir inglés
    return ['source' => 'en', 'target' => 'es', 'deepl_target' => 'ES', 'google_target' => 'es'];
}

// Función para traducir con DeepL (optimizada)
/**
 * Traduce un texto utilizando la API de DeepL.
 *
 * Realiza una solicitud POST a la API de DeepL con un timeout corto.
 *
 * @param string $text El texto a traducir.
 * @param string $target_lang El idioma objetivo para la traducción (código de idioma DeepL, ej. 'ES', 'EN').
 * @param string $api_key La clave de API de DeepL.
 * @return string|false La traducción del texto si es exitosa, o `false` en caso de error.
 */
function translateWithDeepL($text, $target_lang, $api_key) {
    $deepl_url = 'https://api-free.deepl.com/v2/translate';
    $params = [
        'auth_key' => $api_key,
        'text' => $text,
        'target_lang' => $target_lang
    ];

    $ch = curl_init($deepl_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout más corto
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Timeout de conexión
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        return false;
    }

    $data = json_decode($response, true);
    if (isset($data['translations'][0]['text'])) {
        return $data['translations'][0]['text'];
    }

    return false;
}

// Función para traducir con Google Translate (optimizada)
/**
 * Traduce un texto utilizando la API de Google Translate.
 *
 * Realiza una solicitud HTTP a la API de Google Translate con un timeout corto.
 *
 * @param string $text El texto a traducir.
 * @param string $source_lang El idioma de origen del texto (código de idioma, ej. 'en', 'es').
 * @param string $target_lang El idioma objetivo para la traducción (código de idioma, ej. 'es', 'en').
 * @return string|false La traducción del texto si es exitosa, o `false` en caso de error.
 */
function translateWithGoogle($text, $source_lang, $target_lang) {
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=$source_lang&tl=$target_lang&dt=t&q=" . urlencode($text);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 3, // Timeout más corto
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    if (isset($data[0][0][0])) {
        return $data[0][0][0];
    }
    
    return false;
}

// Detectar idioma
$lang_info = detectLanguage($text);

// Intentar traducción con DeepL primero
$translation = translateWithDeepL($text, $lang_info['deepl_target'], $deepl_api_key);
$source = 'DeepL';

// Si DeepL falla, intentar con Google Translate
if ($translation === false) {
    $translation = translateWithGoogle($text, $lang_info['source'], $lang_info['google_target']);
    $source = 'Google Translate';
}

// Si ambos fallan, devolver error
if ($translation === false) {
    echo json_encode(['error' => 'No se pudo traducir el texto']);
    exit();
}

// Registrar el uso si la traducción fue exitosa y el usuario está logueado
if (isset($user_id)) {
    incrementTranslationUsage($user_id, $text);
}

echo json_encode([
    'translation' => $translation,
    'source' => $source,
    'original' => $text,
    'detected_language' => $lang_info['source']
]);
?>
