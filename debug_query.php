<?php
session_start();
require_once 'db/connection.php';

// Debug: Mostrar información de la petición
echo "<h2>Debug de Consulta SQL</h2>";
echo "<p><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'NO SET') . "</p>";
echo "<p><strong>Referer:</strong> " . ($_SERVER['HTTP_REFERER'] ?? 'NO SET') . "</p>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>User ID en sesión:</strong> " . ($_SESSION['user_id'] ?? 'NO SET') . "</p>";

// Simular user_id para la prueba (cambiar por el correcto)
$user_id = 12; // Cambiar por el user_id correcto

echo "<p><strong>User ID para consulta:</strong> $user_id</p>";

// Ejecutar la misma consulta que usa ajax_my_texts_content.php
$stmt = $conn->prepare("SELECT id, title, title_translation, content, is_public FROM texts WHERE user_id = ? AND (is_public = 0 OR id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?)) ORDER BY created_at DESC");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Resultados de la consulta:</h3>";
echo "<p>Número de filas: " . $result->num_rows . "</p>";

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
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
