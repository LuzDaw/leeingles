<?php
// includes/cache.php
// Sistema de caché dual: Redis (si está disponible) o archivos.

require_once __DIR__ . '/config.php';

function cache_set($key, $value, $ttl = 3600) {
    global $redis;

    if ($redis && $redis->isConnected()) {
        // Usar Redis si está conectado
        $redis->set($key, json_encode($value), (int)$ttl);
    } else {
        // Fallback a caché de archivos
        $path = cache_key_to_path($key);
        $payload = [
            'expires' => time() + (int)$ttl,
            'data' => $value
        ];
        @file_put_contents($path, json_encode($payload), LOCK_EX);
    }
}

function cache_get($key) {
    global $redis;

    if ($redis && $redis->isConnected()) {
        // Intentar obtener de Redis
        $cached = $redis->get($key);
        if ($cached !== false) {
            return json_decode($cached, true);
        }
        return null;
    } else {
        // Fallback a caché de archivos
        $path = cache_key_to_path($key);
        if (!is_file($path)) return null;
        $content = @file_get_contents($path);
        if ($content === false) return null;
        $payload = json_decode($content, true);
        if (!is_array($payload) || !isset($payload['expires']) || !array_key_exists('data', $payload)) {
            @unlink($path);
            return null;
        }
        if ($payload['expires'] < time()) {
            @unlink($path);
            return null;
        }
        return $payload['data'];
    }
}

function cache_delete($key) {
    global $redis;

    if ($redis && $redis->isConnected()) {
        $redis->del($key);
    } else {
        $path = cache_key_to_path($key);
        if (is_file($path)) @unlink($path);
    }
}

// --- Funciones de caché de archivos (usadas como fallback) ---

function cache_dir_path() {
    $dir = __DIR__ . '/../tmp_cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function cache_key_to_path($key) {
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    return rtrim(cache_dir_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safe . '.cache';
}
