<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
$text_id = isset($_POST['text_id']) ? intval($_POST['text_id']) : 0;

// Validaciones
if ($duration <= 0 || $duration > 3600 || $text_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos: duración debe estar entre 1 y 3600 segundos']);
    exit;
}

require_once 'db/connection.php';

// Verificar que la tabla existe, si no, crearla
$conn->query("
    CREATE TABLE IF NOT EXISTS reading_time (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        text_id INT NOT NULL,
        duration_seconds INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (text_id) REFERENCES texts(id) ON DELETE CASCADE
    )
");

$stmt = $conn->prepare("INSERT INTO reading_time (user_id, text_id, duration_seconds) VALUES (?, ?, ?)");
$stmt->bind_param('iii', $user_id, $text_id, $duration);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar en BD']);
}
?> 