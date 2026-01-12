<?php
require_once 'connection.php';

echo "Iniciando corrección de índices...\n";

// 1. Eliminar el índice antiguo si existe
$res = $conn->query("SHOW INDEX FROM uso_traducciones WHERE Key_name = 'unique_user_month'");
if ($res && $res->num_rows > 0) {
    if ($conn->query("ALTER TABLE uso_traducciones DROP INDEX unique_user_month")) {
        echo "Índice unique_user_month eliminado.\n";
    } else {
        echo "Error al eliminar índice: " . $conn->error . "\n";
    }
} else {
    echo "El índice unique_user_month no existe.\n";
}

// 2. Crear el nuevo índice semanal si no existe
$res = $conn->query("SHOW INDEX FROM uso_traducciones WHERE Key_name = 'unique_user_week'");
if ($res && $res->num_rows === 0) {
    if ($conn->query("ALTER TABLE uso_traducciones ADD UNIQUE KEY unique_user_week (user_id, semana, anio)")) {
        echo "Índice unique_user_week creado con éxito.\n";
    } else {
        echo "Error al crear índice: " . $conn->error . "\n";
    }
} else {
    echo "El índice unique_user_week ya existe.\n";
}

echo "Proceso finalizado.";
?>
