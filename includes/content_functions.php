<?php
/**
 * Funciones comunes para el manejo de traducciones de contenido
 * Sigue el mismo patrón que title_functions.php para saved_words
 */

/**
 * Guarda una traducción de contenido para un texto específico.
 *
 * Si ya existen traducciones para el texto, la nueva traducción se añade
 * al final de un array JSON. Si es la primera traducción, se inicializa
 * el campo `content_translation` con la nueva traducción.
 *
 * @param int $text_id El ID del texto al que se asocia la traducción.
 * @param string $content El fragmento de contenido original que se está traduciendo.
 * @param string $translation La traducción del fragmento de contenido.
 * @return array Un array asociativo con 'success' (booleano) y 'message' o 'error'.
 */
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

/**
 * Obtiene las traducciones de contenido para un texto específico.
 *
 * Intenta decodificar el campo `content_translation` como JSON. Si es un JSON válido,
 * devuelve un array de traducciones. Si no es JSON (formato antiguo), devuelve el texto plano.
 *
 * @param int $text_id El ID del texto del que se quieren obtener las traducciones.
 * @return array|string|null Un array de traducciones, el texto plano de la traducción antigua, o null si no hay traducción o el texto no existe.
 */
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

/**
 * Obtiene una lista de textos con sus traducciones de contenido.
 *
 * Permite filtrar por ID de usuario y limitar el número de resultados.
 *
 * @param int|null $user_id (Opcional) El ID del usuario para filtrar los textos. Si es null, se obtienen todos los textos.
 * @param int|null $limit (Opcional) El número máximo de textos a devolver.
 * @return array Un array de objetos de texto, cada uno incluyendo su contenido y traducción.
 */
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

/**
 * Verifica si un texto específico necesita una traducción de contenido.
 *
 * Retorna `true` si el campo `content_translation` del texto está vacío o es null.
 *
 * @param int $text_id El ID del texto a verificar.
 * @return bool `true` si el texto necesita traducción, `false` en caso contrario o si el texto no existe.
 */
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

/**
 * Obtiene estadísticas sobre las traducciones de contenido.
 *
 * Incluye el número total de textos, el número de contenidos traducidos
 * y el porcentaje de contenidos traducidos. Puede filtrar por ID de usuario.
 *
 * @param int|null $user_id (Opcional) El ID del usuario para filtrar las estadísticas.
 * @return array Un array asociativo con 'total_texts', 'translated_contents' y 'translation_percentage'.
 */
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
 * Renderiza el texto con palabras clickeables y estructura fluida para paginación dinámica
 */
function render_text_clickable($text, $title = '', $title_translation = '')
{
  // Dividir por párrafos o oraciones largas para mantener estructura
  $paragraphs = preg_split('/\n+/', $text);
  
  // Obtener el text_id del contexto actual
  $text_id = '';
  if (isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
  } elseif (isset($_GET['public_text_id'])) {
    $text_id = intval($_GET['public_text_id']);
  }

  $output = '<div class="encabezado-lectura">
            <button class="btn-volver" onclick="window.cambiarPestana(\'textos\')" aria-label="Volver a Mis Textos">◀</button>
            <div class="titulos-lectura-contenedor">
                <h1 class="titulo-lectura">' . htmlspecialchars($title) . '</h1>
                <h1 class="titulo-lectura-traduccion">' . htmlspecialchars($title_translation) . '</h1>
            </div>
            <div class="progreso-lectura" aria-label="Progreso">
                <div class="barra-progreso"><span class="progreso" style="width:0%"></span></div>
                <span class="porcentaje" aria-live="polite">0%</span>
            </div>
            <div class="menu-herramientas-contenedor">
                <button onclick="window.toggleFloatingMenu(); event.stopPropagation();" id="menu-btn" title="Herramientas de lectura">🛠️</button>
                <div id="submenu">
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
                </div>
            </div>
</div>';

  // Contenedor principal con estructura fluida (sin páginas estáticas de PHP)
  $output .= '<div id="pages-container" class="dynamic-pagination" data-total-words="' . str_word_count(strip_tags($text)) . '" data-text-id="' . $text_id . '">';
  
  // Envolvemos todo en un contenedor de scroll que JS gestionará
  $output .= '<div id="dynamic-content-viewport">';
  
  foreach ($paragraphs as $p_text) {
    $p_text = trim($p_text);
    if (empty($p_text)) continue;

    $words = preg_split('/(\s+)/', $p_text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $output .= '<div class="paragraph-wrapper" >';
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
    $output .= '</div>';
  }
  
  $output .= '</div>'; // Fin dynamic-content-viewport

  // Controles de paginación (JS los activará y actualizará el total de páginas)
  $output .= '<div id="pagination-controls" style="display: none;">
  <button id="prev-page" class="pagination-btn" disabled>◀</button>
  <div class="center-controls">
      <div class="font-size-container">
          <button id="font-size-btn" class="pagination-btn" title="Tamaño de fuente" data-listener-toggle="true" style="
    font-size: medium;
">🅰️</button>
          <div id="font-size-selector" class="speed-selector-popup" style="display: none;">
              <div class="speed-selector-inner">
                  <span id="font-size-value">100%</span>
                  <input type="range" id="font-size" min="0.8" max="1.5" value="1" step="0.1" />
                  <label>Tamaño</label>
              </div>
          </div>
      </div>
      
          <button id="speed-btn" class="pagination-btn" title="Velocidad" data-listener-toggle="true" style="
    padding-bottom: 4px !important;
">🐢</button>
         
      <div class="speed-container">
         <div id="speed-selector" class="speed-selector-popup" style="display: none; margin-bottom: 35px;">
              <div class="speed-selector-inner">
                  <span id="rate-value">100%</span>
                  <input type="range" id="rate" min="0.5" max="0.9" value="0.9" step="0.1" />
                  <label>Velocidad</label>
              </div>
          </div>
      </div>
  </div>
   <button onclick="window.toggleFloatingPlayPause()" id="floating-btn" class="play-btn" title="Iniciar lectura">▶️</button>
  <span class="page-info"><span id="page-number">1</span>/<span id="total-pages">1</span></span>
  <button id="next-page" class="pagination-btn">▶</button>
  </div>';

  $output .= '</div>';

  // Sin área de traducción fija - usaremos tooltips

  return $output;
}

/**
 * Prepara los datos necesarios para la página de inicio (index.php).
 *
 * Centraliza la lógica para obtener datos de textos públicos, textos de usuario,
 * estadísticas de progreso y categorías, dependiendo del estado de la sesión (invitado o logueado)
 * y los parámetros GET.
 *
 * @param mysqli $conn Objeto de conexión a la db.
 * @return array Un array asociativo con todos los datos necesarios para renderizar la página de inicio.
 */
function get_index_page_data($conn) {
    $is_guest = !isset($_SESSION['user_id']);
    $user_id = $is_guest ? null : $_SESSION['user_id'];
    
    // Definir variables para bind_result
    $res_content = ""; 
    $res_title = ""; 
    $res_title_translation = "";

    $data = [
        'is_guest' => $is_guest,
        'user_id' => $user_id,
        'public_titles' => [],
        'user_titles' => [],
        'text' => "",
        'current_text_title' => "",
        'current_text_translation' => "",
        'progress_data' => [],
        'categories' => []
    ];

    // VISITANTE: Mostrar títulos públicos más recientes 
    if ($is_guest) {
        $result = $conn->query("SELECT t.id, t.title, t.title_translation, u.username FROM texts t JOIN users u ON t.user_id = u.id WHERE t.is_public = 1 ORDER BY t.created_at DESC LIMIT 6");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data['public_titles'][] = $row;
            }
            $result->close();
        }
    } else {
        // USUARIO LOGUEADO: Mostrar sus textos recientes si no hay texto seleccionado
        if (!isset($_GET['text_id']) && !isset($_GET['public_text_id'])) {
            $stmt = $conn->prepare("SELECT id, title, title_translation FROM texts WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $data['user_titles'][] = $row;
            }
            $stmt->close();
        }

        // Mostrar texto privado
        if (isset($_GET['text_id'])) {
            $text_id = intval($_GET['text_id']);
            $stmt = $conn->prepare("SELECT content, title, title_translation FROM texts WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $text_id, $user_id);
            $stmt->execute();
            $stmt->bind_result($res_content, $res_title, $res_title_translation);
            if ($stmt->fetch()) {
                $data['text'] = $res_content;
                $data['current_text_title'] = $res_title;
                $data['current_text_translation'] = $res_title_translation;
            }
            $stmt->close();
        }

        // Mostrar texto público
        if (isset($_GET['public_text_id'])) {
            $public_id = intval($_GET['public_text_id']);
            $stmt = $conn->prepare("SELECT content, title, title_translation FROM texts WHERE id = ? AND is_public = 1");
            $stmt->bind_param("i", $public_id);
            $stmt->execute();
            $stmt->bind_result($res_content, $res_title, $res_title_translation);
            if ($stmt->fetch()) {
                $data['text'] = $res_content;
                $data['current_text_title'] = $res_title;
                $data['current_text_translation'] = $res_title_translation;
            }
            $stmt->close();
        }
    }

    // Mostrar texto público (disponible para todos, incluidos invitados)
    if (isset($_GET['public_text_id']) && empty($data['text'])) {
        $public_id = intval($_GET['public_text_id']);
        $stmt = $conn->prepare("SELECT content, title, title_translation FROM texts WHERE id = ? AND is_public = 1");
        $stmt->bind_param("i", $public_id);
        $stmt->execute();
        $stmt->bind_result($res_content, $res_title, $res_title_translation);
        if ($stmt->fetch()) {
            $data['text'] = $res_content;
            $data['current_text_title'] = $res_title;
            $data['current_text_translation'] = $res_title_translation;
        }
        $stmt->close();
    }

    // Mostrar todos los textos públicos cuando se solicite
    if (isset($_GET['show_public_texts'])) {
        $result = $conn->query("SELECT t.id, t.title, u.username FROM texts t JOIN users u ON t.user_id = u.id WHERE t.is_public = 1 ORDER BY t.created_at DESC");
        if ($result) {
            $data['public_titles'] = [];
            while ($row = $result->fetch_assoc()) {
                $data['public_titles'][] = $row;
            }
            $result->close();
        }
    }

    // Estadísticas de progreso
    if (isset($_GET['show_progress']) && isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_words FROM saved_words WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $data['progress_data']['total_words'] = $stmt->get_result()->fetch_assoc()['total_words'];
        $stmt->close();

        $stmt = $conn->prepare("SELECT word, translation, created_at FROM saved_words WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data['progress_data']['recent_words'] = [];
        while ($row = $res->fetch_assoc()) { $data['progress_data']['recent_words'][] = $row; }
        $stmt->close();

        $data['progress_data']['total_texts'] = getTotalUserTexts($user_id);

        $stmt = $conn->prepare("SELECT title, created_at FROM texts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data['progress_data']['recent_texts'] = [];
        while ($row = $res->fetch_assoc()) { $data['progress_data']['recent_texts'][] = $row; }
        $stmt->close();

        $data['progress_data']['practice'] = ['selection' => ['count' => 0, 'accuracy' => 0], 'writing' => ['count' => 0, 'accuracy' => 0], 'sentences' => ['count' => 0, 'accuracy' => 0], 'total_exercises' => 0];
        $stmt = $conn->prepare("SELECT mode, COUNT(*) as cnt, SUM(total_words) as words, AVG(accuracy) as avg_accuracy FROM practice_progress WHERE user_id = ? GROUP BY mode");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $total_exercises = 0;
        while ($row = $res->fetch_assoc()) {
            $mode = $row['mode'];
            $data['progress_data']['practice'][$mode] = ['count' => intval($row['words']), 'accuracy' => round(floatval($row['avg_accuracy']), 1)];
            $total_exercises += intval($row['cnt']);
        }
        $data['progress_data']['practice']['total_exercises'] = $total_exercises;
        $stmt->close();
    }

    // Categorías
    $categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name");
    if ($categories_result) {
        while ($cat = $categories_result->fetch_assoc()) { $data['categories'][] = $cat; }
        $categories_result->close();
    }

    $data['text'] = preg_replace('/(?<=[.?!])\s+/', "\n", $data['text']);
    
    return $data;
}
?>
