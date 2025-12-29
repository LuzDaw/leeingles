<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Ejecutar la MISMA consulta que en ajax_my_texts_content.php
$stmt = $conn->prepare("SELECT id, title, title_translation, content, is_public FROM texts WHERE user_id = ? AND (is_public = 0 OR (is_public = 1 AND id NOT IN (SELECT COALESCE(text_id, 0) FROM hidden_texts WHERE user_id = ?))) ORDER BY created_at DESC");

if (!$stmt) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $user_id, $user_id);
if (!$stmt->execute()) {
    echo json_encode(['error' => 'Execute failed: ' . $stmt->error]);
    exit();
}

$result = $stmt->get_result();
$texts = [];

while ($row = $result->fetch_assoc()) {
    $texts[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'title_translation' => $row['title_translation'],
        'is_public' => $row['is_public'],
        'content_length' => strlen($row['content'])
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'user_id' => $user_id,
    'total_textos' => count($texts),
    'textos' => $texts
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
