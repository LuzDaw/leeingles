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

/**
 * Obtiene el número total de textos de un usuario
 */
function getTotalUserTexts($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM texts WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return intval($row['total']);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Renderiza el texto con palabras clickeables y paginación
 */
function render_text_clickable($text, $title = '', $title_translation = '')
{
  $sentences = preg_split('/(?<=[.?!])\s+|\n+/', $text);
  $pages = [];
  $currentPage = [];
  $wordCount = 0;

  foreach ($sentences as $sentence) {
    $sentence = trim($sentence); // Limpiar espacios
    if (empty($sentence)) continue; // Saltar oraciones vacías

    $wordsInSentence = str_word_count($sentence);
    // 50 palabras por página para oraciones más cortas
    if ($wordCount + $wordsInSentence > 120 && count($currentPage) > 0) {
      $pages[] = $currentPage;
      $currentPage = [];
      $wordCount = 0;
    }
    $currentPage[] = $sentence;
    $wordCount += $wordsInSentence;
  }

  if (count($currentPage) > 0) {
    $pages[] = $currentPage;
  }

  // Obtener el text_id del contexto actual
  $text_id = '';
  if (isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
  } elseif (isset($_GET['public_text_id'])) {
    $text_id = intval($_GET['public_text_id']);
  }

  $output = '<div class="encabezado-lectura">
            <button class="btn-volver" onclick="window.cambiarPestana(\'textos\')" aria-label="Volver a Mis Textos">←</button>
            <div class="titulos-lectura-contenedor">
                <h1 class="titulo-lectura">' . htmlspecialchars($title) . '</h1>
                <h1 class="titulo-lectura-traduccion">' . htmlspecialchars($title_translation) . '</h1>
            </div>
            <div class="progreso-lectura" aria-label="Progreso">
                <div class="barra-progreso"><span class="progreso" style="width:0%"></span></div>
                <span class="porcentaje" aria-live="polite">0%</span>
            </div>
</div>';

  $output .= '<div id="pages-container" data-total-pages="' . count($pages) . '" data-total-words="' . str_word_count(strip_tags($text)) . '" data-text-id="' . $text_id . '">';
  
  foreach ($pages as $index => $page) {
    $output .= '<div class="page' . ($index === 0 ? ' active' : '') . '">';
    foreach ($page as $sentence) {
      $words = preg_split('/(\s+)/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE);
      $output .= '<p class="paragraph">';
      foreach ($words as $word) {
        if (trim($word) === '') {
          $output .= $word;
        } else {
          $output .= '<span class="clickable-word">' . htmlspecialchars($word) . '</span>';
        }
      }
      $output .= '</p>';
      $output .= '<p class="translation"></p>';
    }
    $output .= '</div>';
  }
  
  // Solo mostrar paginación si hay más de una página
  if (count($pages) > 1) {
    $output .= '<div id="pagination-controls">
            <button id="prev-page" class="pagination-btn" disabled>◀ Anterior</button>
            <span class="page-info"><span id="page-number">1</span> / <span id="total-pages">' . count($pages) . '</span></span>
            <button id="next-page" class="pagination-btn">Siguiente ▶</button>
    </div>';
  }

  $output .= '</div>';

  // Sin área de traducción fija - usaremos tooltips

  // Pestaña del menú - siempre visible
  $output .= '<button onclick="window.toggleFloatingMenu(); event.stopPropagation();" id="menu-btn">☰</button>';
  
  // Menú desplegable - siempre visible pero oculto por CSS
  $output .= '<div id="submenu">
        <div class="submenu-item">
            <button onclick="showAllTranslations()" id="show-all-translations-btn" class="submenu-button">📖 Mostrar todas las traducciones</button>
        </div>
        <div class="submenu-item">
            <button onclick="toggleTranslations()" id="toggle-translations-btn" class="submenu-button translations">👁️ Ocultar Traducciones</button>
        </div>
        <div class="submenu-item">
            <button onclick="printFullTextWithTranslations()" class="submenu-button print">🖨️ Imprimir</button>
        </div>
        <div class="submenu-item">
            <button onclick="readCurrentParagraphTwice(); event.stopPropagation();" class="submenu-button double-read">🔊 Leer dos veces</button>
        </div>
        <div class="speed-control">
            <label>Velocidad:</label>
            <input type="range" id="rate" min="0.5" max="0.9" value="0.9" step="0.1" />
            <span id="rate-value">100%</span>
        </div>
    </div>';

  // Nuevo contenedor para el botón de play flotante, fuera de floating-menu
  $output .= '<div id="floating-play" style="display: block;">
        <button onclick="window.toggleFloatingPlayPause()" id="floating-btn" title="Iniciar lectura">▶️</button>
    </div>';

  return $output;
}
?>
