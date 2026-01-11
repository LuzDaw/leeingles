<?php
$host = "localhost:3306";
$user = "leeingles";        // Cambia si tienes otro usuario
$password = "Holamundo25__";        // Cambia si tienes contraseña
$database = "leeingles";
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Asegurar codificación correcta para títulos/traducciones
$conn->set_charset('utf8mb4');
