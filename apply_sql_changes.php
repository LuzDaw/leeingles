<?php
require_once 'db/connection.php';

$sql = [
    "ALTER TABLE users MODIFY email varchar(255) NOT NULL",
    "ALTER TABLE users ADD UNIQUE KEY email_unique (email)",
    "ALTER TABLE users DROP INDEX username"
];

echo "<h1>Aplicando cambios SQL...</h1>";

foreach ($sql as $query) {
    echo "Ejecutando: $query ... ";
    if ($conn->query($query)) {
        echo "<span style='color:green'>ÉXITO</span><br>";
    } else {
        echo "<span style='color:red'>ERROR: " . $conn->error . "</span><br>";
    }
}

$conn->close();
echo "<br><a href='index.php'>Volver al inicio</a>";
?>
