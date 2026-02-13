<?php
/**
 * Funciones comunes para el manejo de palabras
 * Elimina duplicación entre save_word.php y save_translated_word.php
 */

/**
 * Guarda una palabra traducida para un usuario.
 *
 * Si la palabra ya existe para el usuario y el texto (o sin texto),
 * se actualiza la traducción, el contexto y la fecha de creación.
 * De lo contrario, se inserta como una nueva palabra guardada.
 *
 * @param int $user_id El ID del usuario.
 * @param string $word La palabra original en inglés.
 * @param string $translation La traducción de la palabra.
 * @param string $context (Opcional) El contexto en el que se guardó la palabra.
 * @param int|null $text_id (Opcional) El ID del texto del que proviene la palabra.
 * @return array Un array asociativo con 'success' (booleano) y 'message' o 'error'.
 */
function saveTranslatedWord($user_id, $word, $translation, $context = '', $text_id = null) {
    global $conn;
    
    try {
        // Verificar si la palabra ya existe para este usuario y texto
        $stmt = $conn->prepare("SELECT id FROM saved_words WHERE user_id = ? AND word = ? AND (text_id = ? OR (? IS NULL AND text_id IS NULL))");
        $stmt->bind_param("isis", $user_id, $word, $text_id, $text_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Actualizar traducción existente
            $stmt = $conn->prepare("UPDATE saved_words SET translation = ?, context = ?, text_id = ?, created_at = NOW() WHERE user_id = ? AND word = ? AND (text_id = ? OR (? IS NULL AND text_id IS NULL))");
            $stmt->bind_param("sssisis", $translation, $context, $text_id, $user_id, $word, $text_id, $text_id);
        } else {
            // Insertar nueva palabra
            $stmt = $conn->prepare("INSERT INTO saved_words (user_id, word, translation, context, text_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $user_id, $word, $translation, $context, $text_id);
        }
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Palabra guardada correctamente'];
        } else {
            $stmt->close();
            return ['success' => false, 'error' => 'Error al guardar la palabra'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()];
    }
}

/**
 * Obtiene las palabras guardadas por un usuario.
 *
 * Permite filtrar las palabras por un texto específico y limitar el número de resultados.
 * Incluye el título del texto asociado si existe.
 *
 * @param int $user_id El ID del usuario.
 * @param int|null $text_id (Opcional) El ID del texto para filtrar las palabras.
 * @param int|null $limit (Opcional) El número máximo de palabras a devolver.
 * @return array Un array de objetos de palabras guardadas.
 */
function getSavedWords($user_id, $text_id = null, $limit = null) {
    global $conn;
    
    if ($text_id) {
        $stmt = $conn->prepare("SELECT sw.word, sw.translation, sw.context, sw.text_id, t.title as text_title FROM saved_words sw LEFT JOIN texts t ON sw.text_id = t.id WHERE sw.user_id = ? AND sw.text_id = ? ORDER BY sw.created_at DESC");
        $stmt->bind_param("ii", $user_id, $text_id);
    } else {
        $sql = "SELECT sw.word, sw.translation, sw.context, sw.created_at, sw.text_id, t.title as text_title FROM saved_words sw LEFT JOIN texts t ON sw.text_id = t.id WHERE sw.user_id = ? ORDER BY t.title, sw.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $limit);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
        }
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $words = [];
    
    while ($row = $result->fetch_assoc()) {
        $words[] = $row;
    }
    
    $stmt->close();
    return $words;
}

/**
 * Cuenta el número de palabras guardadas por un usuario.
 *
 * Puede contar todas las palabras guardadas o solo las asociadas a un texto específico.
 *
 * @param int $user_id El ID del usuario.
 * @param int|null $text_id (Opcional) El ID del texto para contar palabras específicas.
 * @return int El número total de palabras guardadas o el número de palabras para un texto específico.
 */
function countSavedWords($user_id, $text_id = null) {
    global $conn;
    
    if ($text_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as word_count FROM saved_words WHERE user_id = ? AND text_id = ?");
        $stmt->bind_param("ii", $user_id, $text_id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_words FROM saved_words WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc();
    $stmt->close();
    
    return $text_id ? $count['word_count'] : $count['total_words'];
}

/**
 * Obtiene estadísticas de palabras guardadas por fecha para un usuario.
 *
 * Devuelve el número de palabras guardadas por día durante un período especificado.
 *
 * @param int $user_id El ID del usuario.
 * @param int $days (Opcional) El número de días hacia atrás para obtener las estadísticas. Por defecto es 7.
 * @return array Un array de objetos, cada uno con la fecha y el recuento de palabras guardadas.
 */
function getWordStatsByDate($user_id, $days = 7) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM saved_words WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY date DESC");
    $stmt->bind_param("ii", $user_id, $days);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = [];
    
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
    
    $stmt->close();
    return $stats;
}

/**
 * Obtiene un conjunto de palabras aleatorias guardadas por un usuario para fines de práctica.
 *
 * @param int $user_id El ID del usuario.
 * @param int $limit (Opcional) El número máximo de palabras aleatorias a devolver. Por defecto es 10.
 * @return array Un array de objetos de palabras, cada uno con la palabra, su traducción y contexto.
 */
function getRandomWordsForPractice($user_id, $limit = 10) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT word, translation, context FROM saved_words WHERE user_id = ? ORDER BY RAND() LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $words = [];
    
    while ($row = $result->fetch_assoc()) {
        $words[] = $row;
    }
    
    $stmt->close();
    return $words;
}

/**
 * Elimina una palabra guardada para un usuario. Si $text_id es null, eliminará entradas sin text_id también.
 * @param int $user_id
 * @param string $word
 * @param int|null $text_id
 * @return bool
 */
function deleteSavedWord($user_id, $word, $text_id = null) {
    global $conn;
    if ($text_id === null) {
        $stmt = $conn->prepare("DELETE FROM saved_words WHERE user_id = ? AND word = ?");
        $stmt->bind_param("is", $user_id, $word);
    } else {
        $stmt = $conn->prepare("DELETE FROM saved_words WHERE user_id = ? AND word = ? AND (text_id = ? OR (text_id IS NULL AND ? = 0))");
        $stmt->bind_param("isii", $user_id, $word, $text_id, $text_id);
    }
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}

/**
 * Elimina varias palabras en lote. $items es array de ['word'=>string,'text_id'=>int]
 * Devuelve array con 'deleted' y 'errors'.
 */
function deleteSavedWordsBulk($user_id, $items) {
    $deleted = 0;
    $errors = [];
    foreach ($items as $it) {
        $word = $it['word'] ?? '';
        $text_id = isset($it['text_id']) ? intval($it['text_id']) : null;
        if ($word === '') { $errors[] = 'Palabra vacía'; continue; }
        if (deleteSavedWord($user_id, $word, $text_id)) {
            $deleted++;
        } else {
            $errors[] = "Error eliminando: $word";
        }
    }
    return ['deleted' => $deleted, 'errors' => $errors];
}

/**
 * Elimina palabras asociadas a una lista de text_id para un usuario.
 * @param int $user_id
 * @param array $text_ids
 * @return bool
 */
function deleteSavedWordsByTextIds($user_id, $text_ids) {
    global $conn;
    if (empty($text_ids)) return true;
    $placeholders = implode(',', array_fill(0, count($text_ids), '?'));
    $types = str_repeat('i', count($text_ids)) . 'i';
    $params = array_merge($text_ids, [$user_id]);
    $stmt = $conn->prepare("DELETE FROM saved_words WHERE text_id IN ($placeholders) AND user_id = ?");
    if (!$stmt) return false;
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}
?>
