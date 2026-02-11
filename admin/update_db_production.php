<?php
/**
 * Script de emergencia para actualizar la db en producción (InfinityFree)
 * Añade el plan 'Basico' a los ENUMs de las tablas.
 */

// Credenciales proporcionadas por el usuario para el entorno remoto
$host = "sql206.infinityfree.com";
$user = "if0_39209868";
$password = "xRe9fa3aAy";
$database = "if0_39209868_traductor_app";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("❌ Error de conexión al servidor remoto: " . $conn->connect_error);
}

echo "<h2>Iniciando actualización de db en producción...</h2>";

// 1. Actualizar tabla users (columna tipo_usuario)
$sql1 = "ALTER TABLE users MODIFY COLUMN tipo_usuario ENUM('EnPrueba', 'limitado', 'Inicio', 'Basico', 'Ahorro', 'Pro') DEFAULT 'EnPrueba'";
if ($conn->query($sql1)) {
    echo "<p style='color: green;'>✅ Tabla 'users' (tipo_usuario) actualizada correctamente.</p>";
} else {
    echo "<p style='color: var(--accent-color);'>❌ Error al actualizar tabla 'users': " . $conn->error . "</p>";
}

// 2. Actualizar tabla user_subscriptions (columna plan_name)
$sql2 = "ALTER TABLE user_subscriptions MODIFY COLUMN plan_name ENUM('Inicio', 'Basico', 'Ahorro', 'Pro') NOT NULL";
if ($conn->query($sql2)) {
    echo "<p style='color: green;'>✅ Tabla 'user_subscriptions' (plan_name) actualizada correctamente.</p>";
} else {
    echo "<p style='color: var(--accent-color);'>❌ Error al actualizar tabla 'user_subscriptions': " . $conn->error . "</p>";
}

// 3. Migrar registros antiguos de 'Inicio' a 'Basico' para consistencia
$conn->query("UPDATE users SET tipo_usuario = 'Basico' WHERE tipo_usuario = 'Inicio'");
$conn->query("UPDATE user_subscriptions SET plan_name = 'Basico' WHERE plan_name = 'Inicio'");
echo "<p style='color: blue;'>ℹ️ Registros existentes migrados de 'Inicio' a 'Basico'.</p>";

echo "<h3>Actualización completada. Por favor, elimina este archivo de la raíz por seguridad.</h3>";
$conn->close();
?>
