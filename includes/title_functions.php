<?php
/**
 * Funciones comunes para el manejo de traducciones de títulos
 * Sigue el mismo patrón que word_functions.php para saved_words
 */

// Función para guardar traducción de título
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

// Función para obtener traducción de título
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

// Función para obtener todos los textos con sus traducciones
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

// Función para verificar si un título necesita traducción
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

// Función para obtener estadísticas de traducciones de títulos
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