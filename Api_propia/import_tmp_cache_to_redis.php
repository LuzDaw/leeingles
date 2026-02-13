<?php
// Api_propia/import_tmp_cache_to_redis.php
// Importa ficheros de tmp_cache/ a Redis usando las variables de entorno REDIS_*.

// Configuración por entorno (puedes exportarlas o ponerlas en .env)
$redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
$redisPort = getenv('REDIS_PORT') ?: 6379;
$redisDb   = getenv('REDIS_DB') ?: 0;
$redisPass = getenv('REDIS_PASS') ?: null;

echo "Conectando a Redis {$redisHost}:{$redisPort} (db={$redisDb})\n";

// Conectar a Redis - soporta phpredis o Predis (fallback via Composer)
$isPhpRedis = false;
$r = null;
if (class_exists('Redis')) {
    $isPhpRedis = true;
    $r = new Redis();
    try {
        $r->connect($redisHost, (int)$redisPort, 2);
        if ($redisPass) {
            if (!@$r->auth($redisPass)) {
                throw new Exception('Auth fallido');
            }
        }
        $r->select((int)$redisDb);
    } catch (Exception $e) {
        fwrite(STDERR, "Error conectando a Redis (phpredis): " . $e->getMessage() . "\n");
        exit(1);
    }
} else {
    // intentar Predis (composer)
    if (is_file(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        if (class_exists('Predis\Client')) {
            try {
                $r = new Predis\Client([
                    'scheme' => 'tcp',
                    'host' => $redisHost,
                    'port' => (int)$redisPort,
                    'database' => (int)$redisDb,
                    'password' => $redisPass ?: null
                ]);
                // probar conexión
                $r->connect();
            } catch (Exception $e) {
                fwrite(STDERR, "Error conectando a Redis (Predis): " . $e->getMessage() . "\n");
                exit(1);
            }
        } else {
            fwrite(STDERR, "Predis no encontrado en vendor. Ejecuta: composer require predis/predis\n");
            exit(1);
        }
    } else {
        fwrite(STDERR, "Ni phpredis ni Predis disponibles. Instala la extensión phpredis o ejecuta 'composer require predis/predis' y vuelve a intentarlo.\n");
        exit(1);
    }
}

// Wrappers para operaciones usadas (unifican phpredis y Predis)
function r_set($r, $isPhpRedis, $key, $value) {
    if ($isPhpRedis) return $r->set($key, $value);
    return $r->set($key, $value);
}
function r_expire($r, $isPhpRedis, $key, $ttl) {
    if ($isPhpRedis) return $r->expire($key, $ttl);
    return $r->expire($key, $ttl);
}
function r_hmset($r, $isPhpRedis, $key, $array) {
    if ($isPhpRedis) return $r->hMSet($key, $array);
    return $r->hmset($key, $array);
}
function r_zadd($r, $isPhpRedis, $zset, $score, $member) {
    if ($isPhpRedis) return $r->zAdd($zset, $score, $member);
    // Predis expects array of member => score
    return $r->zadd($zset, [$member => (int)$score]);
}

$dir = dirname(__DIR__) . '/tmp_cache';
// Comprobar flag --move
$moveImported = false;
if (isset($argv) && is_array($argv)) {
    $moveImported = in_array('--move', $argv, true);
}

// Directorio destino si movemos importados
$importedDir = $dir . DIRECTORY_SEPARATOR . 'imported';
if ($moveImported && !is_dir($importedDir)) {
    @mkdir($importedDir, 0755, true);
}
if (!is_dir($dir)) {
    fwrite(STDERR, "Directorio tmp_cache no encontrado: {$dir}\n");
    exit(1);
}

$files = glob($dir . DIRECTORY_SEPARATOR . '*.cache');
$count = 0;
$skipped = 0;
$imported = 0;

foreach ($files as $file) {
    $count++;
    $content = @file_get_contents($file);
    if ($content === false) { $skipped++; continue; }
    $payload = json_decode($content, true);
    if (!is_array($payload) || !isset($payload['expires']) || !array_key_exists('data', $payload)) { $skipped++; continue; }
    $expires = (int)$payload['expires'];
    $data = $payload['data'];
    $ttl = $expires - time();
    if ($ttl <= 0) { $skipped++; continue; }

    // Derivar hash desde el nombre si tiene el formato translate_<hash>.cache
    $base = basename($file, '.cache');
    $hash = null;
    if (strpos($base, 'translate_') === 0) {
        $hash = substr($base, strlen('translate_'));
    } else {
        $hash = md5(json_encode($data));
    }

    $key = "translate:" . $hash;
    $value = json_encode($data);

    // Guardar en Redis con TTL
    $ok = $r->set($key, $value);
    if ($ok) {
        $r->expire($key, $ttl);

        // Guardar metadatos en hash: translate_meta:<hash>
        $metaKey = "translate_meta:" . $hash;
        $meta = [
            'expires' => (string)$expires,
            'created_at' => (string)time(),
            'hits' => '0',
            'source' => isset($data['source']) ? $data['source'] : ''
        ];
        @$r->hMSet($metaKey, $meta);
        // Alinear TTL del metaKey con el principal
        @$r->expire($metaKey, $ttl);

        // Añadir al índice (sorted set) por fecha de expiración
        @$r->zAdd('translate_index', $expires, $hash);

        $imported++;
        if ($moveImported) {
            $dest = $importedDir . DIRECTORY_SEPARATOR . basename($file);
            // intentar mover; si falla, seguir y reportar
            if (!@rename($file, $dest)) {
                // intentar copiar y unlink
                if (@copy($file, $dest)) { @unlink($file); }
            }
        }
    } else {
        $skipped++;
    }
}

echo "Ficheros procesados: {$count}\n";
echo "Importados a Redis: {$imported}\n";
echo "Saltados/errores: {$skipped}\n";

echo "Hecho. Comprueba los keys en Redis con 'keys translate:*' o mediante Redis CLI.\n";

?>
