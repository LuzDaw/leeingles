<?php
// includes/helpers.php
// Utilidades comunes para limpieza y formateo de texto

/**
 * Limpia ejemplos extraídos de Merriam-Webster u otras APIs
 * Conserva el texto legible y elimina marcadores propios de la API.
 */
function limpiarEjemploMerriamWebster(string $texto): string {
    $texto = preg_replace('/\{wi\}(.*?)\{\/wi\}/', '$1', $texto);
    $texto = preg_replace('/\{it\}(.*?)\{\/it\}/', '$1', $texto);
    $texto = preg_replace('/\{b\}(.*?)\{\/b\}/', '$1', $texto);
    $texto = preg_replace('/\{sup\}(.*?)\{\/sup\}/', '$1', $texto);
    $texto = preg_replace('/\{inf\}(.*?)\{\/inf\}/', '$1', $texto);
    $texto = preg_replace('/\{dx\}(.*?)\{\/dx\}/', '$1', $texto);
    $texto = preg_replace('/\{dxt\}(.*?)\{\/dxt\}/', '$1', $texto);
    $texto = preg_replace('/\{ma\}(.*?)\{\/ma\}/', '$1', $texto);
    $texto = trim($texto);
    // Normalizar espacios múltiples
    $texto = preg_replace('/\s+/', ' ', $texto);
    return $texto;
}

/**
 * Normaliza texto: trim y colapsa múltiples espacios, útil antes de cache/keys
 */
function normalizarTexto(string $texto): string {
    $texto = trim($texto);
    $texto = preg_replace('/\s+/', ' ', $texto);
    return $texto;
}

/**
 * Genera una clave de caché consistente.
 * - Normaliza el texto (trim + colapsar espacios)
 * - Convierte a minúsculas
 * - Añade un salt opcional
 * Devuelve: "{prefix}_{md5}".
 */
function make_cache_key(string $prefix, string $texto, string $salt = ''): string {
    $normalized = normalizarTexto($texto);
    $normalized = strtolower($normalized);
    if ($salt !== '') {
        $salt = normalizarTexto($salt);
        $normalized .= '|' . $salt;
    }
    return $prefix . '_' . md5($normalized);
}

/**
 * Genera un token hex seguro de `n` bytes (devuelve hex).
 */
function generate_hex_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

/**
 * Hashea un token con el algoritmo indicado (por defecto sha256).
 */
function hash_token(string $token, string $algo = 'sha256'): string {
    return hash($algo, $token);
}

/**
 * Wrapper seguro para cache_set que ignora excepciones y registra errores.
 */
function safe_cache_set(string $key, $value, int $ttl = 3600): void {
    try {
        cache_set($key, $value, $ttl);
    } catch (Exception $e) {
        error_log("safe_cache_set failed for $key: " . $e->getMessage());
    }
}

// =====================
// FUNCIONES DE TEXTO GENERALES
// =====================

/**
 * Cuenta palabras en un string.
 */
function countWords($text) {
    return str_word_count(strip_tags($text));
}

/**
 * Limpia una cadena de caracteres especiales (solo letras, números y espacios).
 */
function cleanString($text) {
    return preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
}

// =====================
// VALIDACIÓN
// =====================

/**
 * Valida un email.
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// =====================
// ARRAYS Y UTILIDADES
// =====================

/**
 * Devuelve el primer elemento de un array o null.
 */
function array_first($arr) {
    return is_array($arr) && count($arr) > 0 ? reset($arr) : null;
}

// =====================
// CACHÉ SIMPLE (wrapper)
// =====================

/**
 * Guarda un valor en caché (archivo temporal).
 */
function cache_set_simple($key, $value, $ttl = 3600) {
    $path = sys_get_temp_dir() . '/leeingles_cache_' . md5($key);
    file_put_contents($path, serialize(['v'=>$value,'e'=>time()+$ttl]));
}

/**
 * Obtiene un valor de caché.
 */
function cache_get_simple($key) {
    $path = sys_get_temp_dir() . '/leeingles_cache_' . md5($key);
    if (!file_exists($path)) return null;
    $data = unserialize(file_get_contents($path));
    if ($data['e'] < time()) { unlink($path); return null; }
    return $data['v'];
}

/**
 * Elimina un valor de caché.
 */
function cache_delete_simple($key) {
    $path = sys_get_temp_dir() . '/leeingles_cache_' . md5($key);
    if (file_exists($path)) unlink($path);
}

// =====================
// USO: Incluir este archivo donde se necesiten helpers globales
// require_once __DIR__ . '/helpers.php';
