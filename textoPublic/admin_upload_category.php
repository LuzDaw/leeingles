<?php
session_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../logueo_seguridad/auth_functions.php';

// Verificar que el usuario sea admin
if (!isAuthenticated() || !isAdmin()) {
    header('Location: ../logueo_seguridad/admin_login.php');
    exit;
}

// Procesar envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
        $stmt->close();
        $msg = "✅ Categoría añadida con éxito.";
    } else {
        $msg = "❌ El nombre es obligatorio.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Categoría Pública</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>📂 Añadir nueva categoría pública</h1>
    <p>Usuario: <strong><?= htmlspecialchars(getCurrentUsername()) ?></strong></p>
    
    <?php if (isset($msg)) echo "<p>$msg</p>"; ?>
    
    <form method="post">
        <div class="form-group">
            <label>Nombre:</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Descripción:</label>
            <textarea name="description" rows="4" cols="50"></textarea>
        </div>

        <button type="submit">Guardar categoría</button>
    </form>

    <hr>
    <p><a href="../textoPublic/admin_upload_text.php">➜ Añadir textos públicos</a></p>
    <p><a href="../logueo_seguridad/logout.php">Cerrar sesión</a></p>
    <p><a href="../">Volver al inicio</a></p>
</body>
</html>
