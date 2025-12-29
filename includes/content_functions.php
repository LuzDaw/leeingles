<?php
/**
 * Funciones comunes para el manejo de traducciones de contenido
 * Sigue el mismo patrón que title_functions.php para saved_words
 */

// Función para guardar traducción de contenido
function saveContentTranslation($text_id, $content, $translation) {
    global $conn;
    
    try {
        // Verificar si ya existe una traducción para este texto
        $stmt = $conn->prepare("SELECT content_translation FROM texts WHERE id = ?");
        $stmt->bind_param("i", $text_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $existingTranslation = $row['content_translation'];
            
            // Si ya existe una traducción, agregar la nueva al final
            if (!empty($existingTranslation)) {
                // Separar las traducciones existentes y agregar la nueva
                $translations = json_decode($existingTranslation, true) ?: [];
                $translations[] = [
                    'content' => $content,
                    'translation' => $translation,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $newTranslation = json_encode($translations);
            } else {
                // Primera traducción
                $newTranslation = json_encode([
                    [
                        'content' => $content,
                        'translation' => $translation,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
        } else {
            // Texto no encontrado
            return ['success' => false, 'error' => 'Texto no encontrado'];
        }
        
        // Actualizar la traducción
        $stmt = $conn->prepare("UPDATE texts SET content_translation = ? WHERE id = ?");
        $stmt->bind_param("si", $newTranslation, $text_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Traducción de contenido guardada correctamente'];
        } else {
            $stmt->close();
            return ['success' => false, 'error' => 'Error al guardar la traducción del contenido'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()];
    }
}

// Función para obtener traducción de contenido
function getContentTranslation($text_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT content_translation FROM texts WHERE id = ?");
        $stmt->bind_param("i", $text_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            
            $contentTranslation = $row['content_translation'];
            if (!empty($contentTranslation)) {
                // Intentar decodificar como JSON
                $translations = json_decode($contentTranslation, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($translations)) {
                    // Es el nuevo formato JSON
                    return $translations;
                } else {
                    // Es el formato antiguo (texto plano)
                    return $contentTranslation;
                }
            }
            return null;
        } else {
            $stmt->close();
            return null;
        }
        
    } catch (Exception $e) {
        return null;
    }
}

// Función para obtener todos los textos con sus traducciones de contenido
function getTextsWithContentTranslations($user_id = null, $limit = null) {
    global $conn;
    
    try {
        $sql = "SELECT id, title, content, content_translation, user_id, is_public FROM texts";
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

// Función para verificar si un contenido necesita traducción
function needsContentTranslation($text_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT content_translation FROM texts WHERE id = ?");
        $stmt->bind_param("i", $text_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            // Retorna true si no hay traducción o está vacía
            return empty($row['content_translation']);
        } else {
            $stmt->close();
            return false; // El texto no existe
        }
        
    } catch (Exception $e) {
        return false;
    }
}

// Función para obtener estadísticas de traducciones de contenido
function getContentTranslationStats($user_id = null) {
    global $conn;
    
    try {
        $sql = "SELECT 
                    COUNT(*) as total_texts,
                    COUNT(content_translation) as translated_contents,
                    (COUNT(content_translation) * 100.0 / COUNT(*)) as translation_percentage
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
        return ['total_texts' => 0, 'translated_contents' => 0, 'translation_percentage' => 0];
    }
}
?>
