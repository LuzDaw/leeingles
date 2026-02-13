<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/db_helpers.php';

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
    $category = get_category_by_id($conn, $category_id);
    if (!$category) {
        ajax_error('Categoría no encontrada', 404);
    }

    // Verificar si hay textos usando esta categoría
    $texts_count = count_texts_in_category($conn, $category_id);
    if ($texts_count > 0) {
        ajax_error('No se puede eliminar la categoría porque hay ' . $texts_count . ' texto(s) que la usan', 409, 'Category in use');
    }

    // Eliminar la categoría
    if (delete_category_by_id($conn, $category_id)) {
        $conn->close();
        ajax_success(['message' => 'Categoría "' . $category['name'] . '" eliminada correctamente', 'category_id' => $category_id]);
    } else {
        if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
        ajax_error('Error al eliminar la categoría', 500);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) { @ $conn->close(); }
    ajax_error('Error interno del servidor', 500, $e->getMessage());
}
?>
