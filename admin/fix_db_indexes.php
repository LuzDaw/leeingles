<?php
/**
 * Script de reparación de índices para el sistema de suscripciones
 */
require_once '../db/connection.php';

echo "<h2>Reparando índices de base de datos...</h2>";

// 1. Reparar tabla uso_traducciones
echo "Paso 1: Actualizando tabla uso_traducciones...<br>";
$res = $conn->query("SHOW INDEX FROM uso_traducciones WHERE Key_name = 'unique_user_month'");
if ($res && $res->num_rows > 0) {
    if ($conn->query("ALTER TABLE uso_traducciones DROP INDEX unique_user_month")) {
        echo "✅ Índice antiguo 'unique_user_month' eliminado.<br>";
    } else {
        echo "❌ Error al eliminar índice: " . $conn->error . "<br>";
    }
}

$res = $conn->query("SHOW INDEX FROM uso_traducciones WHERE Key_name = 'unique_user_week'");
if ($res && $res->num_rows === 0) {
    if ($conn->query("ALTER TABLE uso_traducciones ADD UNIQUE KEY unique_user_week (user_id, semana, anio)")) {
        echo "✅ Nuevo índice semanal 'unique_user_week' creado.<br>";
    } else {
        echo "❌ Error al crear índice: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ El índice semanal ya existe.<br>";
}

// 2. Asegurar columna payment_method en user_subscriptions
echo "<br>Paso 2: Verificando tabla user_subscriptions...<br>";
$res = $conn->query("SHOW COLUMNS FROM user_subscriptions LIKE 'payment_method'");
if ($res && $res->num_rows === 0) {
    if ($conn->query("ALTER TABLE user_subscriptions ADD COLUMN payment_method ENUM('paypal', 'transferencia') DEFAULT 'paypal' AFTER paypal_subscription_id")) {
        echo "✅ Columna 'payment_method' añadida.<br>";
    } else {
        echo "❌ Error al añadir columna: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ La columna 'payment_method' ya existe.<br>";
}

echo "<br><strong>Proceso finalizado. Por favor, intenta cargar la pestaña de cuenta ahora.</strong>";
?>
