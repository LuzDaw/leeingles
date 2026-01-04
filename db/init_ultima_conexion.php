<?php
/**
 * Script para inicializar el campo ultima_conexion en la tabla users.
 * Ejecutar una sola vez después de añadir la columna.
 */
require_once __DIR__ . '/connection.php';

// Ponemos la fecha actual a todos los usuarios para que no sean detectados como inactivos de golpe
$sql = "UPDATE users SET ultima_conexion = NOW() WHERE ultima_conexion IS NULL";

if ($conn->query($sql) === TRUE) {
    $affected = $conn->affected_rows;
    echo "Se han actualizado $affected usuarios con la fecha de conexión actual.";
} else {
    echo "Error al actualizar: " . $conn->error;
}

$conn->close();
?>
