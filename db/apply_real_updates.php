<?php
require_once __DIR__ . '/connection.php';

echo "<h2>Actualizando Base de Datos Real: $database</h2>";

// 1. Asegurar que la tabla user_subscriptions existe
$create_table = "CREATE TABLE IF NOT EXISTS `user_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_name` enum('Inicio','Ahorro','Pro') NOT NULL,
  `fecha_inicio` datetime DEFAULT current_timestamp(),
  `fecha_fin` datetime NOT NULL,
  `paypal_subscription_id` varchar(100) DEFAULT NULL,
  `status` enum('active','expired','cancelled','pending') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($create_table)) {
    echo "✅ Tabla 'user_subscriptions' verificada.<br>";
} else {
    echo "❌ Error al verificar tabla: " . $conn->error . "<br>";
}

// 2. Añadir columna payment_method si no existe
$check_col = $conn->query("SHOW COLUMNS FROM `user_subscriptions` LIKE 'payment_method'");
if ($check_col->num_rows == 0) {
    $add_col = "ALTER TABLE user_subscriptions ADD COLUMN payment_method ENUM('paypal', 'transferencia') DEFAULT 'paypal' AFTER paypal_subscription_id";
    if ($conn->query($add_col)) {
        echo "✅ Columna 'payment_method' añadida con éxito.<br>";
    } else {
        echo "❌ Error al añadir columna: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ La columna 'payment_method' ya existe.<br>";
}

// 3. Asegurar que el estado 'pending' está en el ENUM
$mod_status = "ALTER TABLE user_subscriptions MODIFY COLUMN status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'active'";
if ($conn->query($mod_status)) {
    echo "✅ Columna 'status' actualizada correctamente.<br>";
}

echo "<br><strong>Proceso completado.</strong> Ya puedes volver a <a href='../dePago/webhook_handler.php'>webhook_handler.php</a>";
?>
