<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';
require_once __DIR__ . '/../db/connection.php';

noCacheHeaders();
// Solo admin puede eliminar categorías
requireUserOrExitJson();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    ajax_error('Acceso denegado', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ajax_error('Método no permitido', 405);
}

$category_id = $_POST['category_id'] ?? null;

if (!$category_id || !is_numeric($category_id)) {
    ajax_error('ID de categoría inválido', 400);
}

$category_id = intval($category_id);

try {
    // Verificar si la categoría existe
    $stmt_check = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
    $stmt_check->bind_param("i", $category_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        ajax_error('Categoría no encontrada', 404);
    }
    
    $category = $result->fetch_assoc();
    
    // Verificar si hay textos usando esta categoría
    $stmt_texts = $conn->prepare("SELECT COUNT(*) as count FROM texts WHERE category_id = ?");
    $stmt_texts->bind_param("i", $category_id);
    $stmt_texts->execute();
    $texts_result = $stmt_texts->get_result();
    $texts_count = $texts_result->fetch_assoc()['count'];
    
    if ($texts_count > 0) {
        ajax_error('No se puede eliminar la categoría porque hay ' . $texts_count . ' texto(s) que la usan', 409, 'Category in use');
    }
    
    // Eliminar la categoría
    $stmt_delete = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt_delete->bind_param("i", $category_id);
    
    if ($stmt_delete->execute()) {
        $stmt_delete->close();
        $conn->close();
        ajax_success(['message' => 'Categoría "' . $category['name'] . '" eliminada correctamente', 'category_id' => $category_id]);
    } else {
        $stmt_delete->close();
        if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
        ajax_error('Error al eliminar la categoría', 500, $stmt_delete->error ?? null);
    }
    
} catch (Exception $e) {
    if (isset($stmt_check) && $stmt_check instanceof mysqli_stmt) { @ $stmt_check->close(); }
    if (isset($stmt_texts) && $stmt_texts instanceof mysqli_stmt) { @ $stmt_texts->close(); }
    if (isset($stmt_delete) && $stmt_delete instanceof mysqli_stmt) { @ $stmt_delete->close(); }
    if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
    ajax_error('Error interno del servidor', 500, $e->getMessage());
}
?>
