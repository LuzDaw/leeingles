<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
$mode = isset($_POST['mode']) ? $_POST['mode'] : '';

// Validaciones mejoradas para evitar datos incorrectos
if ($duration <= 0 || $duration > 3600 || !$mode) { // Máximo 1 hora por sesión
    echo json_encode(['success' => false, 'error' => 'Datos inválidos: duración debe estar entre 1 y 3600 segundos']);
    exit;
}

// Validar que el modo sea correcto
$valid_modes = ['selection', 'writing', 'sentences'];
if (!in_array($mode, $valid_modes)) {
    echo json_encode(['success' => false, 'error' => 'Modo de práctica inválido']);
    exit;
}

require_once '../db/connection.php';

// Asegurar que la tabla existe y tiene AUTO_INCREMENT
$conn->query("
    CREATE TABLE IF NOT EXISTS practice_time (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        mode VARCHAR(32) NOT NULL,
        duration_seconds INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// Por si la tabla ya existía pero sin AUTO_INCREMENT (caso reportado)
$conn->query("ALTER TABLE practice_time MODIFY id INT AUTO_INCREMENT");

// Optimización: Buscar si ya existe un registro para este usuario, modo y día actual
$stmt_check = $conn->prepare("SELECT id FROM practice_time WHERE user_id = ? AND mode = ? AND DATE(created_at) = CURRENT_DATE() LIMIT 1");
$stmt_check->bind_param('is', $user_id, $mode);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
$existing_row = $res_check->fetch_assoc();
$stmt_check->close();

if ($existing_row) {
    // Si existe, acumular el tiempo
    $stmt = $conn->prepare("UPDATE practice_time SET duration_seconds = duration_seconds + ? WHERE id = ?");
    $stmt->bind_param('ii', $duration, $existing_row['id']);
} else {
    // Si no existe, crear nuevo registro para hoy
    $stmt = $conn->prepare("INSERT INTO practice_time (user_id, mode, duration_seconds) VALUES (?, ?, ?)");
    $stmt->bind_param('isi', $user_id, $mode, $duration);
}

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error preparando consulta: ' . $conn->error]);
    exit;
}

$ok = $stmt->execute();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar en BD: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
