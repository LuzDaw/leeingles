<?php
require_once __DIR__ . '/../db/connection.php';
header('Content-Type: application/json');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$content = '';
if ($id > 0) {
    $stmt = $conn->prepare('SELECT content FROM texts WHERE id = ? AND is_public = 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($content);
    $stmt->fetch();
    $stmt->close();
}
echo json_encode(['content' => $content]); 