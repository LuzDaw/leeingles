<?php
// =============================
// SISTEMA HÍBRIDO DE TRADUCCIÓN
// =============================
// 1. Intenta DeepL API
// 2. Si falla, usa Google Translate API
// 3. Si ambos fallan, devuelve error

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

// Función simplificada para detectar idioma
function detectLanguage($text) {
    // Si contiene caracteres especiales del español, asumir español
    if (preg_match('/[áéíóúñÁÉÍÓÚÑüÜ]/u', $text)) {
        return ['source' => 'es', 'target' => 'en', 'deepl_target' => 'EN', 'google_target' => 'en'];
    }
    
    // Por defecto, asumir inglés
    return ['source' => 'en', 'target' => 'es', 'deepl_target' => 'ES', 'google_target' => 'es'];
}

// Función para traducir con DeepL (optimizada)
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

echo json_encode([
    'translation' => $translation,
    'source' => $source,
    'original' => $text,
    'detected_language' => $lang_info['source']
]);
?>