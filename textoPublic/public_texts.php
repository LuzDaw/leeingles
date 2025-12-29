<?php
require_once __DIR__ . '/../db/connection.php';

$has_category = isset($_GET['category_id']) && intval($_GET['category_id']) > 0;
$category_id = $has_category ? intval($_GET['category_id']) : 0;

if ($has_category) {
    // Cargar nombre de la categoría
    $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $stmt->bind_result($category_name);
    $stmt->fetch();
    $stmt->close();
} else {
    $category_name = 'Todos';
}

// Cargar textos públicos
if ($has_category) {
    $stmt = $conn->prepare("SELECT id, title, title_translation FROM texts WHERE is_public = 1 AND category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT id, title, title_translation FROM texts WHERE is_public = 1");
    $stmt->execute();
    $result = $stmt->get_result();
}

// Si es petición AJAX, devolver JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $texts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $texts[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'title_translation' => $row['title_translation']
            ];
        }
        $result->close();
    }
    header('Content-Type: application/json');
    echo json_encode([
        'category' => $category_name,
        'texts' => $texts
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Textos Públicos</title>
</head>
<body>
    <h1>📚 Textos de la categoría: <?= htmlspecialchars($category_name) ?></h1>
    <ul>
        <?php while ($row = $result->fetch_assoc()): ?>
            <li>
                <a href="ver_texto.php?text_id=<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['title']) ?>
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
    <p><a href="../index.php">Volver al inicio</a></p>
</body>
</html>
