<?php
// includes/translation_service.php
// Servicio centralizado de traducción: detecta idioma y usa DeepL o Google como fallback

require_once __DIR__ . '/cache.php';

/** Detecta idioma y devuelve metadatos */
function detectLanguage($text) {
    if (preg_match('/[áéíóúñÁÉÍÓÚÑüÜ]/u', $text)) {
        return ['source' => 'es', 'target' => 'en', 'deepl_target' => 'EN', 'google_target' => 'en'];
    }
    return ['source' => 'en', 'target' => 'es', 'deepl_target' => 'ES', 'google_target' => 'es'];
}

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
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

function translateWithGoogle($text, $source_lang, $target_lang) {
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=$source_lang&tl=$target_lang&dt=t&q=" . urlencode($text);
    $context = stream_context_create([
        'http' => [
            'timeout' => 3,
            'user_agent' => 'Mozilla/5.0'
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) return false;
    $data = json_decode($response, true);
    if (isset($data[0][0][0])) return $data[0][0][0];
    return false;
}

/**
 * Traduce texto usando DeepL primero y Google como fallback. Devuelve array con resultado.
 */
function translateText($text) {
    $deepl_api_key = getenv('DEEPL_API_KEY') ? getenv('DEEPL_API_KEY') : '89bb7c47-40dc-4628-9efb-8882bb6f5fba:fx';
    $lang_info = detectLanguage($text);

    // Try cache first (cache key based on original text + target)
    $cache_key = 'translate_' . md5($text . '|' . $lang_info['deepl_target']);
    $cached = cache_get($cache_key);
    if ($cached !== null) {
        return $cached;
    }

    $translation = translateWithDeepL($text, $lang_info['deepl_target'], $deepl_api_key);
    $source = 'DeepL';

    if ($translation === false) {
        $translation = translateWithGoogle($text, $lang_info['source'], $lang_info['google_target']);
        $source = 'Google Translate';
    }

    if ($translation === false) {
        return ['error' => 'No se pudo traducir el texto'];
    }

    $result = [
        'translation' => $translation,
        'source' => $source,
        'original' => $text,
        'detected_language' => $lang_info['source']
    ];

    // Cache translation for 24 hours
    try {
        cache_set($cache_key, $result, 86400);
    } catch (Exception $e) {
        // ignore cache errors
    }

    return $result;
}

