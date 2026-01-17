<?php
session_start();
require_once '../db/connection.php';
require_once '../includes/title_functions.php';

// Headers para evitar caché y asegurar datos frescos
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['is_admin']) ? intval($_SESSION['is_admin']) : 0;
// Procesar acciones masivas via AJAX
if ($_POST && isset($_POST['action']) && isset($_POST['selected_texts'])) {
    $selected_texts = $_POST['selected_texts'];
    $action = $_POST['action'];

    if (!empty($selected_texts) && in_array($action, ['delete', 'make_public'])) {
        $placeholders = str_repeat('?,', count($selected_texts) - 1) . '?';
        if ($action === 'delete') {
            // Obtener info de los textos seleccionados
            $in = implode(',', array_fill(0, count($selected_texts), '?'));
            $types = str_repeat('i', count($selected_texts));
            $stmt_info = $conn->prepare("SELECT id, is_public, user_id FROM texts WHERE id IN ($in)");
            $stmt_info->bind_param($types, ...$selected_texts);
            $stmt_info->execute();
            $result_info = $stmt_info->get_result();
            $to_delete = [];
            $to_hide = [];
            while ($row = $result_info->fetch_assoc()) {
                if ($row['is_public'] == 1) {
                    // Cualquier usuario (incluido admin) puede ocultar textos públicos leídos
                    $to_hide[] = $row['id'];
                } elseif ($row['user_id'] == $user_id) {
                    // Texto privado propio
                    $to_delete[] = $row['id'];
                }
            }
            $stmt_info->close();
            // Ocultar textos públicos
            foreach ($to_hide as $tid) {
                $stmt_hide = $conn->prepare("INSERT IGNORE INTO hidden_texts (user_id, text_id) VALUES (?, ?)");
                $stmt_hide->bind_param("ii", $user_id, $tid);
                $stmt_hide->execute();
                $stmt_hide->close();
                // Eliminar palabras guardadas de ese texto para ese usuario
                $del_words_stmt = $conn->prepare("DELETE FROM saved_words WHERE user_id = ? AND text_id = ?");
                $del_words_stmt->bind_param("ii", $user_id, $tid);
                $del_words_stmt->execute();
                $del_words_stmt->close();
                // Eliminar progreso de lectura de ese texto para ese usuario
                $del_progress_stmt = $conn->prepare("DELETE FROM reading_progress WHERE user_id = ? AND text_id = ?");
                $del_progress_stmt->bind_param("ii", $user_id, $tid);
                $del_progress_stmt->execute();
                $del_progress_stmt->close();
                error_log("[HIDDEN] Usuario $user_id ocultó texto público $tid, eliminó palabras y progreso de lectura");
            }
            // Borrar textos privados permitidos
            if (!empty($to_delete)) {
                $params = array_merge($to_delete, [$user_id]);
                $types = str_repeat('i', count($params));

                // 1. Borrar palabras guardadas
                $del_words_stmt = $conn->prepare("DELETE FROM saved_words WHERE text_id IN ($placeholders) AND user_id = ?");
                $del_words_stmt->bind_param($types, ...$params);
                $del_words_stmt->execute();
                $del_words_stmt->close();

                // 2. Borrar progreso de práctica
                $del_practice_stmt = $conn->prepare("DELETE FROM practice_progress WHERE text_id IN ($placeholders) AND user_id = ?");
                $del_practice_stmt->bind_param($types, ...$params);
                $del_practice_stmt->execute();
                $del_practice_stmt->close();

                // 3. Borrar tiempos de lectura
                $del_reading_time_stmt = $conn->prepare("DELETE FROM reading_time WHERE text_id IN ($placeholders) AND user_id = ?");
                $del_reading_time_stmt->bind_param($types, ...$params);
                $del_reading_time_stmt->execute();
                $del_reading_time_stmt->close();

                // 4. Borrar progreso de lectura
                $del_reading_progress_stmt = $conn->prepare("DELETE FROM reading_progress WHERE text_id IN ($placeholders) AND user_id = ?");
                $del_reading_progress_stmt->bind_param($types, ...$params);
                $del_reading_progress_stmt->execute();
                $del_reading_progress_stmt->close();

                // 5. Borrar de textos ocultos
                $del_hidden_stmt = $conn->prepare("DELETE FROM hidden_texts WHERE text_id IN ($placeholders) AND user_id = ?");
                $del_hidden_stmt->bind_param($types, ...$params);
                $del_hidden_stmt->execute();
                $del_hidden_stmt->close();

                // 6. Borrar los textos
                $stmt = $conn->prepare("DELETE FROM texts WHERE id IN ($placeholders) AND user_id = ?");
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    error_log("[DELETE] Usuario $user_id borró textos: " . implode(',', $to_delete));
                    echo json_encode(['success' => true, 'message' => 'Textos y todos sus datos asociados eliminados correctamente.']);
                } else {
                    error_log("[ERROR DELETE] Usuario $user_id error al borrar textos: " . implode(',', $to_delete));
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar los textos y sus datos asociados.']);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => true, 'message' => 'Textos ocultados correctamente.']);
            }
            exit();
        } elseif ($action === 'make_public') {
            $stmt = $conn->prepare("UPDATE texts SET is_public = 1 WHERE id IN ($placeholders) AND user_id = ?");
            $params = array_merge($selected_texts, [$user_id]);
            $stmt->bind_param(str_repeat('i', count($params)), ...$params);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Textos marcados como públicos correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al hacer públicos los textos.']);
            }
        }
        $stmt->close();
        exit();
    }
}

// Obtener textos propios del usuario (simpler query for reliability)
$stmt = $conn->prepare("SELECT id, title, title_translation, content, is_public FROM texts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
error_log("[DEBUG] Own texts query returned " . $result->num_rows . " rows for user $user_id");


?>

<div class="tab-content-wrapper">
    <div id="messages-container"></div>

    <div class="bulk-actions-container">
        <div style="color: #64748b; font-weight: 500;">
            <span style="color: #3b82f6; font-weight: 600;"><?php echo $result->num_rows ?></span> textos encontrados
        </div>
        <div class="bulk-actions" style="display: flex; gap: 12px; align-items: center;">
            <div class="dropdown">
                <button class="nav-btn" id="dropdownBtn" onclick="toggleDropdown()">
                    Acciones en lote ▼
                </button>
                <div class="dropdown-content" id="dropdownContent">
                    <button type="button" onclick="selectAllTexts()">✓ Marcar todos</button>
                    <button type="button" onclick="unselectAllTexts()">✗ Desmarcar todos</button>
                    <button type="button" onclick="performBulkAction('delete')" style="color: #ff8a00;">🗑️ Eliminar seleccionados</button>
                    <button type="button" onclick="performBulkAction('print')">🖨️ Imprimir seleccionados</button>
                </div>
            </div>
        </div>
        <!-- SIEMPRE mostrar el dropdown de textos públicos -->
        <div class="dropdown" id="publicTextsDropdown" style="position: relative; margin-top: 16px;background:#ff8a00 ;box-shadow: 0 2px 4px rgba(0,0,0,0.1);border-radius: 8px;">
            <button class="nav-btn" id="publicTextsBtn" onclick="togglePublicTextsDropdown(event)">
                Textos públicos ▼
            </button>
            <div class="dropdown-content" id="publicCategoriesContent">
                <div style="padding: 10px; color: #6b7280;">Cargando categorías...</div>
            </div>
        </div>
    </div>
<!-- bulkForm siempre presente -->
<form id="bulkForm">
            <?php
// Consulta textos públicos leídos
$public_read_stmt = $conn->prepare('
    SELECT t.id, t.title, t.title_translation, t.content, t.user_id, t.is_public, rp.percent, rp.read_count
    FROM texts t
    INNER JOIN reading_progress rp ON rp.text_id = t.id
    WHERE rp.user_id = ? AND t.is_public = 1 AND t.user_id != ? AND (rp.percent > 0 OR rp.read_count > 0)
    AND t.id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?)
    GROUP BY t.id
    ORDER BY rp.updated_at DESC, t.title ASC
');
$public_read_stmt->bind_param('iii', $user_id, $user_id, $user_id);
$public_read_stmt->execute();
$public_read_result = $public_read_stmt->get_result();
$public_read_rows = [];
while ($row = $public_read_result->fetch_assoc()) {
    $public_read_rows[] = $row;
}
$public_read_count = count($public_read_rows);
error_log('[DEBUG] Textos públicos leídos encontrados: ' . $public_read_count . ' para user_id: ' . $user_id . ' | IDs: ' . implode(',', array_column($public_read_rows, 'id')));
// Elimino la segunda ejecución y uso solo $public_read_rows para renderizar
// $public_read_stmt->execute();
// $public_read_result = $public_read_stmt->get_result();

if ($result->num_rows > 0) {
    // Mostrar textos propios
    echo '<ul class="text-list">';
    while ($row = $result->fetch_assoc()) {
        $num_words = str_word_count(strip_tags($row['content']));
            $progress = 0;
            $is_read = false;
            $read_count = 0;
            $pages_read = [];
            $stmt2 = $conn->prepare("SELECT percent, pages_read, read_count FROM reading_progress WHERE user_id = ? AND text_id = ?");
            $stmt2->bind_param('ii', $user_id, $row['id']);
            $stmt2->execute();
            $stmt2->bind_result($percent, $pages_read_json, $rc);
            if ($stmt2->fetch()) {
                $progress = intval($percent);
                $is_read = ($progress >= 100);
                $read_count = intval($rc);
                $pages_read = json_decode($pages_read_json, true) ?: [];
            }
            $stmt2->close();
        error_log("[DEBUG] Own Text - ID: " . $row['id'] . ", Title: " . $row['title'] . ", Title Translation (from query): " . $row['title_translation']);
        echo '<li class="text-item">';
        echo '<input type="checkbox" class="text-checkbox" name="selected_texts[]" value="' . $row['id'] . '" onchange="updateBulkActions()">';
        echo '<a href="index.php?text_id=' . $row['id'] . '" class="text-title">';
        echo '<span class="title-english">' . htmlspecialchars($row['title']) . '</span>';
        
        // Usar la traducción que trae la consulta
        if (!empty($row['title_translation'])) {
            echo '<span class="title-spanish" style="color: #eaa827; font-size: 0.9em; margin-left: 8px; font-weight: 500;">• ' . htmlspecialchars($row['title_translation']) . '</span>';
        } else {
            echo '<span class="title-spanish" style="color: #6b7280; font-size: 0.9em; margin-left: 8px;"></span>';
        }
        echo '</a>';
        echo '<span class="text-date">' . $num_words . ' palabras</span>';
        if ($is_read || $read_count > 0) {
            $read_text = ($read_count > 1) ? "Leído $read_count veces" : "Leído $read_count vez";
            if ($read_count == 0 && $is_read) $read_text = "Leído";
            echo '<span class="reading-status" style="color: #ff8a0087; font-weight: bold; margin-left: 10px;">' . $read_text . '</span>';
        } else {
            echo '<span class="reading-progress-bar" style="display: inline-block; width: 80px; height: 10px; background: #e5e7eb; border-radius: 5px; margin-left: 10px; vertical-align: middle; overflow: hidden;">';
            echo '<span style="display: block; height: 100%; width: ' . $progress . '%; background: linear-gradient(90deg, #ff8a0087, #3b82f6); border-radius: 5px; transition: width 0.4s;"></span>';
            echo '</span>';
            if ($progress > 0) {
                echo '<span style="font-size: 11px; color: #2563eb; margin-left: 4px; font-weight: 500;"> ' . $progress . '%</span>';
            }
        }
        echo '<span class="text-status ' . ($row['is_public'] ? 'status-public' : 'status-private') . '">' . ($row['is_public'] ? 'Público' : 'Privado') . '</span>';
        echo '</li>';
    }
    echo '</ul>';
}

// Mostrar textos públicos leídos SIEMPRE que existan
if ($public_read_count > 0) {
    // error_log('[DEBUG] public_read_rows: ' . print_r($public_read_rows, true));
    // echo '<pre style="color:red;background:#fff;">DEBUG: ';
    // print_r($public_read_rows);
    // echo '</pre>';
    echo '<style>
    .text-list { list-style: none; padding: 0; margin: 0; }
    .text-item { display: flex; align-items: center; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 18px; padding: 18px 28px; transition: box-shadow 0.2s; gap: 18px; }
    .text-item:hover { box-shadow: 0 4px 16px rgba(60,60,120,0.10); }
    .text-checkbox { margin-right: 18px; width: 18px; height: 18px; }
    .title-english { font-size: 1.18em; font-weight: 600; color: #22223b; margin-right: 12px; }
    .title-spanish { font-size: 1em; color: #eaa827; margin-left: 8px; font-weight: 500; }
    .text-date { color: #64748b; font-size: 0.98em; margin-right: 18px; min-width: 90px; text-align: right; }
    .reading-progress-bar { width: 90px; height: 10px; background: #e5e7eb; border-radius: 5px; margin: 0 12px; overflow: hidden; display: inline-block; vertical-align: middle; }
    .reading-progress-bar span { display: block; height: 100%; background: linear-gradient(90deg, #ff8a0087, #3b82f6); border-radius: 5px; transition: width 0.4s; }
    .reading-status { color: #ff8a0087; font-weight: bold; margin-left: 12px; font-size: 1.05em; }
    .reading-count { color: #2563eb; font-size: 0.98em; font-weight: 500; margin-left: 12px; }
    .percent-label { color: #2563eb; font-size: 0.98em; margin-left: 8px; font-weight: 500; min-width: 38px; text-align: right; }
    @media (max-width: 700px) {
      .text-item { flex-direction: column; align-items: flex-start; padding: 14px 10px; gap: 8px; }
      .text-date, .reading-progress-bar, .percent-label, .reading-count, .reading-status { margin: 0 0 0 0; min-width: unset; }
    }
    </style>';
    foreach ($public_read_rows as $row) {
        $num_words = str_word_count(strip_tags($row['content']));
        $percent = intval($row['percent']);
        $read_count = isset($row['read_count']) ? intval($row['read_count']) : 0;
        error_log("[DEBUG] Public Read Text - ID: " . $row['id'] . ", Title: " . $row['title'] . ", Title Translation (from query): " . $row['title_translation']);
        echo '<li class="text-item">';
        echo '<input type="checkbox" class="text-checkbox" name="selected_texts[]" value="' . $row['id'] . '" onchange="updateBulkActions()">';
        echo '<a href="index.php?public_text_id=' . $row['id'] . '" class="text-title" style="text-decoration:none;">';
        echo '<span class="title-english">' . htmlspecialchars($row['title']) . '</span>';
        
        // Usar la traducción que trae la consulta
        if (!empty($row['title_translation'])) {
            echo '<span class="title-spanish" style="color: #eaa827; font-size: 0.9em; margin-left: 8px; font-weight: 500;">• ' . htmlspecialchars($row['title_translation']) . '</span>';
        }
        echo '</a>';
        echo '<span class="text-date">' . $num_words . ' palabras</span>';
        
        if ($percent >= 100 || $read_count > 0) {
            $read_text = ($read_count > 1) ? "Leído $read_count veces" : "Leído $read_count vez";
            if ($read_count == 0 && $percent >= 100) $read_text = "Leído";
            echo '<span class="reading-status" style="color: #ff8a0087; font-weight: bold; margin-left: 10px;">' . $read_text . '</span>';
        } else {
            echo '<span class="reading-progress-bar"><span style="width: ' . $percent . '%;"></span></span>';
            echo '<span class="percent-label">' . $percent . '%</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

if ($result->num_rows == 0 && $public_read_count == 0) {
    // Mostrar mensaje solo si no hay textos propios ni públicos leídos
    echo '<div style="text-align: center; padding: 40px; color: #6b7280;">';
    echo '<div style="font-size: 3rem; margin-bottom: 20px;">📚</div>';
    echo '<h3 style="margin-bottom: 10px; color: #374151;">No has subido ningún texto todavía</h3>';
    echo '<p style="margin-bottom: 30px;">¡Comienza tu viaje de aprendizaje subiendo tu primer texto!</p>';
    echo '<button onclick="loadTabContent(\'upload\')" class="nav-btn primary" style="padding: 15px 30px;">⬆ Subir mi primer texto</button>';
    echo '</div>';
}
$public_read_stmt->close();
?>
</form>
</div>

<link rel="stylesheet" href="css/tab-system.css">

<?php
$stmt->close();
$conn->close();
?>
