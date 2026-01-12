<?php
require_once __DIR__ . '/connection.php';

echo "Iniciando corrección de tabla practice_time...\n";

// 1. Asegurar que la tabla existe
$conn->query("
    CREATE TABLE IF NOT EXISTS practice_time (
        id INT NOT NULL,
        user_id INT NOT NULL,
        mode VARCHAR(32) NOT NULL,
        duration_seconds INT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// 2. Verificar si ya tiene PRIMARY KEY
$result = $conn->query("SHOW KEYS FROM practice_time WHERE Key_name = 'PRIMARY'");
if ($result->num_rows == 0) {
    echo "Añadiendo PRIMARY KEY...\n";
    $conn->query("ALTER TABLE practice_time ADD PRIMARY KEY (id)");
}

// 3. Asegurar AUTO_INCREMENT
echo "Configurando AUTO_INCREMENT...\n";
$conn->query("ALTER TABLE practice_time MODIFY id INT AUTO_INCREMENT");

// 4. Asegurar Foreign Key
$conn->query("ALTER TABLE practice_time ADD CONSTRAINT fk_practice_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");

echo "Proceso finalizado. Verifica si hay errores arriba.\n";
?>
