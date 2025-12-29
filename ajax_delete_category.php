<?php
session_start();
require_once 'db/connection.php';

// Solo admin puede eliminar categorías
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$category_id = $_POST['category_id'] ?? null;

if (!$category_id || !is_numeric($category_id)) {
    echo json_encode(['error' => 'ID de categoría inválido']);
    exit();
}

$category_id = intval($category_id);

try {
    // Verificar si la categoría existe
    $stmt_check = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
    $stmt_check->bind_param("i", $category_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Categoría no encontrada']);
        exit();
    }
    
    $category = $result->fetch_assoc();
    
    // Verificar si hay textos usando esta categoría
    $stmt_texts = $conn->prepare("SELECT COUNT(*) as count FROM texts WHERE category_id = ?");
    $stmt_texts->bind_param("i", $category_id);
    $stmt_texts->execute();
    $texts_result = $stmt_texts->get_result();
    $texts_count = $texts_result->fetch_assoc()['count'];
    
    if ($texts_count > 0) {
        echo json_encode([
            'error' => 'No se puede eliminar la categoría porque hay ' . $texts_count . ' texto(s) que la usan',
            'texts_count' => $texts_count
        ]);
        exit();
    }
    
    // Eliminar la categoría
    $stmt_delete = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt_delete->bind_param("i", $category_id);
    
    if ($stmt_delete->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Categoría "' . $category['name'] . '" eliminada correctamente',
            'category_id' => $category_id
        ]);
    } else {
        echo json_encode(['error' => 'Error al eliminar la categoría']);
    }
    
    $stmt_delete->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error interno del servidor']);
}

$conn->close();
?> 