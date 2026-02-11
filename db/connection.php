<?php
// Intentar cargar un archivo .env local (opcional) para facilitar desarrollo en local
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if ($k !== '') {
            putenv($k . '=' . $v);
            $_ENV[$k] = $v;
        }
    }
}

// Leer variables de entorno con valores por defecto para compatibilidad
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'traductor_app';

$conn = @new mysqli($host, $user, $password, $database);

$GLOBALS['db_connection_error'] = $conn->connect_error ?? null;
if ($GLOBALS['db_connection_error']) {
    error_log('[leeingles] DB connection error: ' . $GLOBALS['db_connection_error']);
} else {
    $conn->set_charset('utf8mb4');
}
