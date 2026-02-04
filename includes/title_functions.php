<?php
/**
 * Funciones comunes para el manejo de traducciones de títulos
 * Sigue el mismo patrón que word_functions.php para saved_words
 */

/**
 * Guarda o actualiza la traducción de un título para un texto específico.
 *
 * Si ya existe una traducción para el título, se actualiza. De lo contrario, se inserta.
 *
 * @param int $text_id El ID del texto al que se asocia la traducción del título.
 * @param string $title El título original del texto (no se usa directamente en la actualización, pero es parte de la firma).
 * @param string $translation La traducción del título.
 * @return array Un array asociativo con 'success' (booleano) y 'message' o 'error'.
 */
function saveTitleTranslation($text_id, $title, $translation) {
    global $conn;
    
    try {
        // Verificar si la traducción ya existe para este texto
        $stmt = $conn->prepare("SELECT id FROM texts WHERE id = ? AND title_translation IS NOT NULL");
        $stmt->bind_param("i", $text_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Actualizar traducción existente
            $stmt = $conn->prepare("UPDATE texts SET title_translation = ? WHERE id = ?");
            $stmt->bind_param("si", $translation, $text_id);
        } else {
            // Insertar nueva traducción
            $stmt = $conn->prepare("UPDATE texts SET title_translation = ? WHERE id = ?");
            $stmt->bind_param("si", $translation, $text_id);
        }
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Traducción de título guardada correctamente'];
        } else {
            $stmt->close();
            return ['success' => false, 'error' => 'Error al guardar la traducción del título'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()];
    }
}

/**
 * Obtiene la traducción del título para un texto específico.
 *
 * @param int $text_id El ID del texto del que se quiere obtener la traducción del título.
 * @return string|null La traducción del título si existe, o null en caso contrario.
 */
function getTitleTranslation($text_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT title_translation FROM texts WHERE id = ?");
        $stmt->bind_param("i", $text_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['title_translation'];
        } else {
            $stmt->close();
            return null;
        }
        
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Obtiene una lista de textos con sus títulos y traducciones de títulos.
 *
 * Permite filtrar por ID de usuario y limitar el número de resultados.
 *
 * @param int|null $user_id (Opcional) El ID del usuario para filtrar los textos. Si es null, se obtienen todos los textos.
 * @param int|null $limit (Opcional) El número máximo de textos a devolver.
 * @return array Un array de objetos de texto, cada uno incluyendo su título y traducción de título.
 */
function getTextsWithTranslations($user_id = null, $limit = null) {
    global $conn;
    
    try {
        $sql = "SELECT id, title, title_translation, user_id, is_public FROM texts";
        $params = [];
        $types = "";
        
        if ($user_id) {
            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;
            $types .= "i";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            $types .= "i";
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $texts = [];
        
        while ($row = $result->fetch_assoc()) {
            $texts[] = $row;
        }
        
        $stmt->close();
        return $texts;
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Verifica si el título de un texto específico necesita una traducción.
 *
 * Retorna `true` si el campo `title_translation` del texto está vacío o es null.
 *
 * @param int $text_id El ID del texto a verificar.
 * @return bool `true` si el título necesita traducción, `false` en caso contrario o si el texto no existe.
 */
function needsTitleTranslation($text_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT title_translation FROM texts WHERE id = ?");
        $stmt->bind_param("i", $text_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            // Retorna true si no hay traducción o está vacía
            return empty($row['title_translation']);
        } else {
            $stmt->close();
            return false; // El texto no existe
        }
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obtiene estadísticas sobre las traducciones de títulos.
 *
 * Incluye el número total de textos, el número de títulos traducidos
 * y el porcentaje de títulos traducidos. Puede filtrar por ID de usuario.
 *
 * @param int|null $user_id (Opcional) El ID del usuario para filtrar las estadísticas.
 * @return array Un array asociativo con 'total_texts', 'translated_titles' y 'translation_percentage'.
 */
function getTitleTranslationStats($user_id = null) {
    global $conn;
    
    try {
        $sql = "SELECT 
                    COUNT(*) as total_texts,
                    COUNT(title_translation) as translated_titles,
                    (COUNT(title_translation) * 100.0 / COUNT(*)) as translation_percentage
                FROM texts";
        
        $params = [];
        $types = "";
        
        if ($user_id) {
            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;
            $types .= "i";
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return $stats;
        
    } catch (Exception $e) {
        return ['total_texts' => 0, 'translated_titles' => 0, 'translation_percentage' => 0];
    }
}
?>
