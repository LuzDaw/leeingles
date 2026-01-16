<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../logueo_seguridad/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$text_ids = [];

if (isset($_GET['ids'])) {
    $ids = explode(',', $_GET['ids']);
    foreach ($ids as $id) {
        if (is_numeric($id)) {
            $text_ids[] = intval($id);
        }
    }
}

if (empty($text_ids)) {
    echo "No se proporcionaron IDs de textos válidos.";
    exit();
}

// Obtener textos seleccionados
$placeholders = str_repeat('?,', count($text_ids) - 1) . '?';
$stmt = $conn->prepare("SELECT title, content FROM texts WHERE id IN ($placeholders) AND user_id = ? ORDER BY created_at DESC");
$params = array_merge($text_ids, [$user_id]);
$stmt->bind_param(str_repeat('i', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

$texts = [];
while ($row = $result->fetch_assoc()) {
    $texts[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>LeeInglés - Textos para Imprimir</title>
    <link rel="stylesheet" href="../css/print.css">
</head>
<body>
    <h1 style="text-align: center; margin-bottom: 40px; font-size: 24px;">LeeInglés</h1>

    <?php foreach ($texts as $text): ?>
        <div class="text-section">
            <h2 class="text-title"><?= htmlspecialchars($text['title']) ?></h2>
            
            <?php
            // Dividir el texto en párrafos
            $content = $text['content'];
            $paragraphs = preg_split('/(?<=[.?!])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
            
            foreach ($paragraphs as $paragraph):
                $paragraph = trim($paragraph);
                if (!empty($paragraph)):
            ?>
                <p class="paragraph"><?= htmlspecialchars($paragraph) ?></p>
                <p class="translation">[Traducción se generará al leer el texto]</p>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    <?php endforeach; ?>

    <script>
        // Auto-imprimir cuando la página carga
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
