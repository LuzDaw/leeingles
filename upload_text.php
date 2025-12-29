<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: logueo_seguridad/login.php");
    exit();
}

$errors = [];
$success = "";

// Obtener categorías para el select
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    // Si no hay título, generar uno con las 3 primeras palabras del contenido
    if (empty($title) && !empty($content)) {
        $words = preg_split('/\s+/', $content, 4);
        $title = implode(' ', array_slice($words, 0, 3));
        // Limpiar caracteres especiales y limitar longitud
        $title = preg_replace('/[^\w\s-]/', '', $title);
        $title = substr($title, 0, 50);
        if (empty($title)) {
            $title = "Texto sin título";
        }
    }

    if (empty($content)) {
        $errors[] = "Debes incluir contenido para el texto.";
    } elseif ($is_public && $category_id === 0) {
        $errors[] = "Debes seleccionar una categoría para el texto público.";
    } else {
        // Si texto privado, category_id debe ser null
        if (!$is_public) {
            $category_id = null;
        }

        $stmt = $conn->prepare("INSERT INTO texts (user_id, title, content, category_id, is_public) VALUES (?, ?, ?, ?, ?)");
        if ($category_id === null) {
            // Para pasar NULL en mysqli bind_param usamos variable con NULL y tipo "s" para string, pero category_id es INT, así que tipo "i" y pasamos NULL con referencia
            $null = null;
            $stmt->bind_param("isssi", $_SESSION['user_id'], $title, $content, $null, $is_public);
        } else {
            $stmt->bind_param("issii", $_SESSION['user_id'], $title, $content, $category_id, $is_public);
        }

        if ($stmt->execute()) {
            $text_id = $conn->insert_id;
            
            $success = "Texto subido correctamente.";
            // Redirigir inmediatamente a mis textos, la traducción se hará en background con JS
            header("Location: index.php?auto_translate=" . $text_id);
            exit();
        } else {
            $errors[] = "Error al subir el texto.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir texto</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h2>Subir nuevo texto</h2>
    <p><a href="index.php">← Volver</a></p>

    <?php if (!empty($success)): ?>
        <p class="msg-success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="msg-error">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <label>Título:<br>
            <input type="text" name="title" value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
        </label><br><br>

        <label>Contenido:<br>
            <textarea name="content" rows="10" cols="50" required><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
        </label><br><br>

        <label>
            <input type="checkbox" name="is_public" id="is_public" <?= (isset($_POST['is_public']) ? 'checked' : '') ?>>
            Texto público
        </label><br><br>

        <label id="category_label" style="display: <?= (isset($_POST['is_public']) ? 'inline' : 'none') ?>;">
            Categoría:
            <select name="category_id">
                <option value="0">-- Selecciona categoría --</option>
                <?php while ($cat = $categories_result->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label><br><br>

        <button type="submit">Subir texto</button>
    </form>

    <script>
        const checkbox = document.getElementById('is_public');
        const categoryLabel = document.getElementById('category_label');
        checkbox.addEventListener('change', () => {
            categoryLabel.style.display = checkbox.checked ? 'inline' : 'none';
        });
    </script>
</body>
</html>
