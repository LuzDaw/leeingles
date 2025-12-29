<?php
require_once __DIR__ . '/../db/connection.php';

// Obtener categorías
$result = $conn->query("SELECT id, name FROM categories ORDER BY name");

// Si es petición AJAX, devolver JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $categories = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        $result->close();
    }
    header('Content-Type: application/json');
    echo json_encode($categories);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Categorías Públicas</title>
</head>
<body>
    <h1>📚 Categorías de Textos Públicos</h1>
    <ul>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php
            // Separar el nombre en inglés y español
            $parts = explode(' - ', $row['name']);
            $english = $parts[0] ?? '';
            $spanish = $parts[1] ?? '';
            
            // Si no hay traducción, usar el nombre completo como inglés
            if (empty($spanish)) {
                $english = $row['name'];
                $spanish = '';
            }
            
            // Formatear la opción
            if (!empty($spanish)) {
                $display_name = $english . ' - ' . $spanish;
            } else {
                $display_name = $english;
            }
            ?>
            <li>
                <a href="public_texts.php?category_id=<?= $row['id'] ?>">
                    <?= htmlspecialchars($display_name) ?>
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
    <p><a href="../index.php">Volver al inicio</a></p>
</body>
</html>
