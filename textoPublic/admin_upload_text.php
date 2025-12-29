<?php
session_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../logueo_seguridad/auth_functions.php';

// Verificar que el usuario sea admin
if (!isAuthenticated() || !isAdmin()) {
    header('Location: ../logueo_seguridad/admin_login.php');
    exit;
}

// Verificación de conexión
if (!$conn || $conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener categorías
$categorias = $conn->query("SELECT id, name FROM categories");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id']);
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($category_id && $title && $content) {
        $stmt = $conn->prepare("INSERT INTO public_texts (category_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $category_id, $title, $content);

        if ($stmt->execute()) {
            $msg = "✅ Texto público guardado correctamente.";
        } else {
            $msg = "❌ Error al guardar: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $msg = "❌ Todos los campos son obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Texto Público</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>📄 Añadir nuevo texto público</h1>
    <p>Usuario: <strong><?= htmlspecialchars(getCurrentUsername()) ?></strong></p>

    <?php if (isset($msg)) echo "<p>$msg</p>"; ?>

    <form method="post">
        <div class="form-group">
            <label>Categoría:</label>
            <select name="category_id" required>
                <option value="">Seleccione...</option>
                <?php while ($cat = $categorias->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Título:</label>
            <input type="text" name="title" required>
        </div>

        <div class="form-group">
            <label>Contenido:</label>
            <textarea name="content" rows="8" cols="50" required></textarea>
        </div>

        <button type="submit">Guardar texto</button>
    </form>

    <hr>
    <p><a href="../textoPublic/admin_upload_category.php">➜ Añadir categorías</a></p>
    <p><a href="../logueo_seguridad/logout.php">Cerrar sesión</a></p>
    <p><a href="../index.php">⬅ Volver al inicio</a></p>
</body>
</html>
