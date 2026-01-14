<?php
session_start();
require_once 'db/connection.php';

// Solo admin puede entrar
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = "";

// Insertar nueva categoría 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if (empty($name)) {
        $errors[] = "El nombre de la categoría es obligatorio.";
    } else {
        // Traducir automáticamente el nombre de la categoría 
        $translated_name = $name;
        
        // Solo traducir si no contiene ya el formato "Inglés - Español"
        if (strpos($name, ' - ') === false) {
            // Usar la API de traducción
            $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=es&dt=t&q=" . urlencode($name);
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $response = @file_get_contents($url, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response);
                if (is_array($data) && isset($data[0][0][0])) {
                    $translation = $data[0][0][0];
                    $translated_name = $name . ' - ' . $translation;
                }
            }
        }
        
        // Verificar si ya existe
        $stmt_check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt_check->bind_param("s", $translated_name);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors[] = "La categoría ya existe.";
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt_insert->bind_param("s", $translated_name);
            if ($stmt_insert->execute()) {
                $success = "Categoría creada correctamente.";
            } else {
                $errors[] = "Error al crear la categoría.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}

// Obtener categorías actuales con conteo de textos
$result = $conn->query("
    SELECT c.id, c.name, COUNT(t.id) as texts_count 
    FROM categories c 
    LEFT JOIN texts t ON c.id = t.category_id 
    GROUP BY c.id, c.name 
    ORDER BY c.name
");

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Categorías - LeerEntender</title>
    <link rel="stylesheet" href="css/common-styles.css">
    <link rel="stylesheet" href="css/modern-styles.css">
    <link rel="stylesheet" href="css/color-theme.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 20px;
        }
        .admin-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .admin-header {
            background: linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .admin-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .admin-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .admin-content {
            padding: 30px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3B82F6;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .back-link:hover {
            background: #f0f9ff;
        }
        .form-section {
            background: #f8fafc;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }
        .form-section h3 {
            margin: 0 0 20px 0;
            color: #374151;
            font-size: 18px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #3B82F6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563EB;
            transform: translateY(-1px);
        }
        .btn-danger {
            background: #ff8a00;
            color: white;
            padding: 8px 16px;
            font-size: 14px;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }
        .categories-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            background: white;
            transition: all 0.2s;
        }
        .category-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .category-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .category-name {
            font-weight: 600;
            color: #374151;
            font-size: 16px;
        }
        .category-count {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .category-actions {
            display: flex;
            gap: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-warning {
            background: #ff6f0074;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .delete-confirmation {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .confirmation-modal {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .confirmation-modal h3 {
            margin: 0 0 15px 0;
            color: #374151;
        }
        .confirmation-modal p {
            margin: 0 0 25px 0;
            color: #6b7280;
        }
        .confirmation-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>⚙️ Administrar Categorías</h1>
            <p>Gestiona las categorías para organizar los textos</p>
        </div>
        
        <div class="admin-content">
            <a href="index.php" class="back-link">
                ← Volver al inicio
            </a>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulario para crear categoría -->
            <div class="form-section">
                <h3>➕ Crear Nueva Categoría</h3>
                <form method="post" id="create-category-form">
                    <div class="form-group">
                        <label class="form-label">Nombre de la categoría:</label>
                        <input type="text" name="name" class="form-input" required 
                               placeholder="Ej: Tecnología, Literatura, Ciencia...">
                    </div>
                    <button type="submit" class="btn btn-primary">Crear Categoría</button>
                </form>
            </div>

            <!-- Lista de categorías existentes -->
            <div class="form-section">
                <h3>📋 Categorías Existentes</h3>
                <div id="categories-container">
                    <?php if ($result->num_rows > 0): ?>
                        <ul class="categories-list">
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
                                <li class="category-item" data-category-id="<?= $row['id'] ?>">
                                    <div class="category-info">
                                        <span class="category-name"><?= htmlspecialchars($display_name) ?></span>
                                        <span class="category-count">
                                            <?= $row['texts_count'] ?> texto<?= $row['texts_count'] != 1 ? 's' : '' ?>
                                        </span>
                                    </div>
                                    <div class="category-actions">
                                        <button class="btn btn-danger delete-category-btn" 
                                                data-category-id="<?= $row['id'] ?>"
                                                data-category-name="<?= htmlspecialchars($display_name) ?>"
                                                data-texts-count="<?= $row['texts_count'] ?>">
                                            🗑️ Eliminar
                                        </button>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p style="text-align: center; color: #6b7280; padding: 20px;">
                            No hay categorías creadas aún.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div id="delete-confirmation" class="delete-confirmation">
        <div class="confirmation-modal">
            <h3>🗑️ Confirmar Eliminación</h3>
            <p id="confirmation-message">¿Estás seguro de que quieres eliminar esta categoría?</p>
            <div class="confirmation-buttons">
                <button id="confirm-delete" class="btn btn-danger">Sí, Eliminar</button>
                <button id="cancel-delete" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-category-btn');
            const confirmationModal = document.getElementById('delete-confirmation');
            const confirmationMessage = document.getElementById('confirmation-message');
            const confirmDeleteBtn = document.getElementById('confirm-delete');
            const cancelDeleteBtn = document.getElementById('cancel-delete');
            
            let currentCategoryId = null;
            let currentCategoryName = null;

            // Mostrar modal de confirmación
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentCategoryId = this.dataset.categoryId;
                    currentCategoryName = this.dataset.categoryName;
                    const textsCount = parseInt(this.dataset.textsCount);
                    
                    if (textsCount > 0) {
                        confirmationMessage.textContent = `No se puede eliminar la categoría "${currentCategoryName}" porque tiene ${textsCount} texto(s) asociado(s). Primero debes cambiar la categoría de esos textos.`;
                        confirmDeleteBtn.style.display = 'none';
                    } else {
                        confirmationMessage.textContent = `¿Estás seguro de que quieres eliminar la categoría "${currentCategoryName}"? Esta acción no se puede deshacer.`;
                        confirmDeleteBtn.style.display = 'inline-block';
                    }
                    
                    confirmationModal.style.display = 'flex';
                });
            });

            // Cancelar eliminación
            cancelDeleteBtn.addEventListener('click', function() {
                confirmationModal.style.display = 'none';
                currentCategoryId = null;
                currentCategoryName = null;
            });

            // Confirmar eliminación
            confirmDeleteBtn.addEventListener('click', function() {
                if (!currentCategoryId) return;
                
                deleteCategory(currentCategoryId);
            });

            // Cerrar modal al hacer clic fuera
            confirmationModal.addEventListener('click', function(e) {
                if (e.target === confirmationModal) {
                    confirmationModal.style.display = 'none';
                }
            });

            // Función para eliminar categoría
            function deleteCategory(categoryId) {
                const formData = new FormData();
                formData.append('category_id', categoryId);

                // Mostrar loading
                const categoryItem = document.querySelector(`[data-category-id="${categoryId}"]`);
                if (categoryItem) {
                    categoryItem.classList.add('loading');
                }

                fetch('ajax_delete_category.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Eliminar el elemento de la lista
                        if (categoryItem) {
                            categoryItem.remove();
                        }
                        
                        // Mostrar mensaje de éxito
                        showAlert('success', data.message);
                        
                        // Verificar si no quedan categorías
                        const remainingCategories = document.querySelectorAll('.category-item');
                        if (remainingCategories.length === 0) {
                            const container = document.getElementById('categories-container');
                            container.innerHTML = '<p style="text-align: center; color: #6b7280; padding: 20px;">No hay categorías creadas aún.</p>';
                        }
                    } else {
                        showAlert('error', data.error);
                    }
                })
                .catch(error => {
                    showAlert('error', 'Error al eliminar la categoría');
                })
                .finally(() => {
                    // Ocultar modal y loading
                    confirmationModal.style.display = 'none';
                    if (categoryItem) {
                        categoryItem.classList.remove('loading');
                    }
                    currentCategoryId = null;
                    currentCategoryName = null;
                });
            }

            // Función para mostrar alertas
            function showAlert(type, message) {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type}`;
                alertDiv.textContent = message;
                
                const content = document.querySelector('.admin-content');
                content.insertBefore(alertDiv, content.firstChild);
                
                // Auto-remover después de 5 segundos
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html>
