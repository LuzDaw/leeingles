<?php
// Configuración central para rutas y entorno
// Detecta entorno por HOST o variable APP_ENV. No contiene credenciales sensibles.

if (getenv('APP_ENV')) {
    $env = getenv('APP_ENV');
} else {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $env = 'local';
    } else {
        $env = 'production';
    }
}

if ($env === 'production') {
    // Configuración de errores para producción
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../log/errors.log');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

    // Producción: servido en la raíz de dominio
    if (getenv('BASE_URL')) {
        define('BASE_URL', rtrim(getenv('BASE_URL'), '/'));
    } else {
        define('BASE_URL', 'https://leeingles.com');
    }
    define('BASE_PATH', '');
} else {
    // Configuración de errores para desarrollo
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../log/dev_errors.log');
    error_reporting(E_ALL);

    // Local: asumimos XAMPP en /leeingles
    if (getenv('BASE_URL')) {
        define('BASE_URL', rtrim(getenv('BASE_URL'), '/'));
    } else {
        define('BASE_URL', 'http://localhost/leeingles');
    }
    define('BASE_PATH', '/leeingles');
}

// Devuelve una URL absoluta para rutas internas (ej: url('/logueo_seguridad/login.php'))
function url($path = '') {
    $p = ltrim($path, '/');
    return BASE_URL . '/' . $p;
}

// Devuelve ruta de assets (css/js/img) relativa al BASE_URL
function asset($path = '') {
    $p = ltrim($path, '/');
    return BASE_URL . '/' . $p;
}

// Helper para inyectar configuración al cliente JS
function inject_app_config_js() {
    $base = defined('BASE_URL') ? BASE_URL : '';
    echo "<script>window.APP = { BASE_URL: '" . $base . "', BASE_PATH: '" . BASE_PATH . "' };</script>\n";
}

// --- Conexión a Redis (solo si la extensión está disponible) ---
$redis = null;
if (class_exists('Redis')) {
    try {
        $redis_host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $redis_port = getenv('REDIS_PORT') ?: 6379;

        $redis = new Redis();
        // Usar pconnect para una conexión persistente
        $redis->pconnect($redis_host, $redis_port); 
    } catch (Exception $e) {
        // Si la conexión falla, $redis permanecerá null y la app usará la caché de archivos.
        // Opcional: registrar el error en un log del servidor.
        error_log('Error al conectar con Redis: ' . $e->getMessage());
        $redis = null;
    }
}
?>
