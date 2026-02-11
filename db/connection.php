<?php
$host = "localhost";
$user = "root";        // Cambia si tienes otro usuario
$password = "";        // Cambia si tienes contraseña
$database = "traductor_app";
$conn = @new mysqli($host, $user, $password, $database);

$GLOBALS['db_connection_error'] = $conn->connect_error ?? null;
if ($GLOBALS['db_connection_error']) {
    error_log('[leeingles] DB connection error: ' . $GLOBALS['db_connection_error']);
} else {
    $conn->set_charset('utf8mb4');
}
