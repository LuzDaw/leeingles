
<?php
// includes/db_helpers.php
// Helpers reutilizables para consultas comunes a la base de datos

/**
 * Verifica si existe una categoría por nombre.
 */
function category_exists_by_name($conn, string $name) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    if ($stmt === false) return false;
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Inserta una nueva categoría por nombre. Devuelve true en éxito, false en fallo.
 */
function insert_category($conn, string $name) {
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    if ($stmt === false) return false;
    $stmt->bind_param("s", $name);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Obtiene todas las categorías con conteo de textos.
 */
function get_categories_with_text_count($conn) {
    $sql = "SELECT c.id, c.name, COUNT(t.id) as texts_count FROM categories c LEFT JOIN texts t ON c.id = t.category_id GROUP BY c.id, c.name ORDER BY c.name";
    $result = $conn->query($sql);
    $categories = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

/**
 * Obtiene un texto si pertenece al usuario o es público. Devuelve array asociativo o false.
 */
function get_text_if_allowed($conn, int $text_id, int $user_id) {
    $sql = "SELECT id, title, title_translation FROM texts WHERE id = ? AND (user_id = ? OR is_public = 1)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) return false;
    $stmt->bind_param('ii', $text_id, $user_id);
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $res = $stmt->get_result();
    if ($res->num_rows === 0) { $stmt->close(); return false; }
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Actualiza la traducción del título para un texto dado.
 * Devuelve true en éxito, false en fallo.
 */
function update_text_title_translation($conn, int $text_id, string $title_translation) {
    $stmt = $conn->prepare("UPDATE texts SET title_translation = ? WHERE id = ?");
    if ($stmt === false) return false;
    $stmt->bind_param('si', $title_translation, $text_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}


/**
 * Verifica si una categoría existe y devuelve sus datos (id, name) o false.
 */
function get_category_by_id($conn, int $category_id) {
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
    if ($stmt === false) return false;
    $stmt->bind_param("i", $category_id);
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $result = $stmt->get_result();
    if ($result->num_rows === 0) { $stmt->close(); return false; }
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Devuelve el número de textos que usan una categoría dada.
 */
function count_texts_in_category($conn, int $category_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM texts WHERE category_id = ?");
    if ($stmt === false) return false;
    $stmt->bind_param("i", $category_id);
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    return $count;
}

/**
 * Elimina una categoría por ID. Devuelve true en éxito, false en fallo.
 */
function delete_category_by_id($conn, int $category_id) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt === false) return false;
    $stmt->bind_param("i", $category_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}


?>
