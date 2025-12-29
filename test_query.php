<?php
session_start();
require_once 'db/connection.php';

// Simular el user_id para la prueba
$user_id = 12; // Cambiar por el user_id correcto

echo "<h2>Prueba de consulta SQL</h2>";
echo "<p>User ID: $user_id</p>";

// Ejecutar la misma consulta que usa ajax_my_texts_content.php
$stmt = $conn->prepare("SELECT id, title, title_translation, content, is_public FROM texts WHERE user_id = ? AND (is_public = 0 OR id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?)) ORDER BY created_at DESC");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Resultados de la consulta:</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Title</th><th>Title Translation</th><th>Is Public</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['title_translation'] ?: 'NULL') . "</td>";
    echo "<td>" . $row['is_public'] . "</td>";
    echo "</tr>";
}

echo "</table>";

$stmt->close();
$conn->close();
?>
