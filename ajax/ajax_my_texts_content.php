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

// Liberar bloqueo de sesión para permitir otras peticiones paralelas
session_write_close();

// Procesar acciones masivas via AJAX
if ($_POST && isset($_POST['action']) && isset($_POST['selected_texts'])) {
    $selected_texts = $_POST['selected_texts'];
    $action = $_POST['action'];

    if (!empty($selected_texts) && in_array($action, ['delete', 'make_public'])) {
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
                    $to_hide[] = $row['id'];
                } elseif ($row['user_id'] == $user_id) {
                    $to_delete[] = $row['id'];
                }
            }
            $stmt_info->close();
            
            $conn->begin_transaction();
            try {
                $params = array_merge($selected_texts, [$user_id]);
                $types = str_repeat('i', count($selected_texts)) . 'i';
                $placeholders_all = str_repeat('?,', count($selected_texts) - 1) . '?';

                // 1. Borrar palabras guardadas
                $stmt1 = $conn->prepare("DELETE FROM saved_words WHERE text_id IN ($placeholders_all) AND user_id = ?");
                $stmt1->bind_param($types, ...$params);
                $stmt1->execute();
                $stmt1->close();

                // 2. Borrar progreso de lectura
                $stmt2 = $conn->prepare("DELETE FROM reading_progress WHERE text_id IN ($placeholders_all) AND user_id = ?");
                $stmt2->bind_param($types, ...$params);
                $stmt2->execute();
                $stmt2->close();

                // Ocultar textos públicos
                foreach ($to_hide as $tid) {
                    $stmt_hide = $conn->prepare("INSERT IGNORE INTO hidden_texts (user_id, text_id) VALUES (?, ?)");
                    $stmt_hide->bind_param("ii", $user_id, $tid);
                    $stmt_hide->execute();
                    $stmt_hide->close();
                }
                
                // Borrar textos privados
                if (!empty($to_delete)) {
                    $params_del = array_merge($to_delete, [$user_id]);
                    $types_del = str_repeat('i', count($to_delete)) . 'i';
                    $placeholders_del = str_repeat('?,', count($to_delete) - 1) . '?';
                    
                    $stmt = $conn->prepare("DELETE FROM texts WHERE id IN ($placeholders_del) AND user_id = ?");
                    $stmt->bind_param($types_del, ...$params_del);
                    $stmt->execute();
                    $stmt->close();
                }

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Acción completada correctamente.']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Error al procesar: ' . $e->getMessage()]);
            }
            exit();
        }
    }
}

/**
 * Función para renderizar un item de la lista de textos
 */
function renderTextItem($row, $user_id, $is_public_list = false) {
    $num_words = str_word_count(strip_tags($row['content'] ?? ''));
    $read_count = isset($row['read_count']) ? intval($row['read_count']) : 0;
    $last_read_date = isset($row['updated_at']) ? $row['updated_at'] : null;

    $formatted_date = isset($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : '-';
    $link_param = $is_public_list ? "public_text_id" : "text_id";
    ?>
    <li class="text-item">
        <input type="checkbox" class="text-checkbox" name="selected_texts[]" value="<?= $row['id'] ?>" onchange="updateBulkActions()">
        <span class="text-icon">📄</span>
        
        <div class="text-main-info">
            <a href="?<?= $link_param ?>=<?= $row['id'] ?>" class="text-title">
                <span class="title-english"><?= htmlspecialchars($row['title'] ?? 'Sin título') ?></span>
                <?php if (!empty($row['title_translation'])): ?>
                    <span class="title-spanish">• <?= htmlspecialchars($row['title_translation']) ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="text-meta-container">
            <div class="meta-col meta-words"><?= $num_words ?> palabras</div>
            <div class="meta-col meta-status">
                <?php if ($read_count > 0): ?>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <span class="reading-status-label">Leído <?= $read_count ?> <?= $read_count == 1 ? 'vez' : 'veces' ?></span>
                        <?php if ($last_read_date): ?>
                            <span style="font-size: 0.75em; color: #94a3b8; margin-top: 2px;"><?= date('d/m/Y', strtotime($last_read_date)) ?></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <span style="color: #cbd5e1;">-</span>
                <?php endif; ?>
            </div>
            <div class="meta-col meta-date"><?= $formatted_date ?></div>
            <div class="meta-col meta-public">
                <?php if (isset($row['is_public']) && $row['is_public']): ?>
                    <span class="status-public-tag">Público</span>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php
}

// 1. Obtener textos propios con su progreso (si existe)
$stmt = $conn->prepare("
    SELECT t.id, t.title, t.title_translation, t.content, t.is_public, t.created_at, 
           rp.read_count, rp.updated_at, rp.percent
    FROM texts t
    LEFT JOIN reading_progress rp ON rp.text_id = t.id AND rp.user_id = ?
    WHERE t.user_id = ? 
    ORDER BY t.created_at DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$own_texts = [];
while ($row = $result->fetch_assoc()) { $own_texts[] = $row; }
$stmt->close();

// 2. Obtener textos públicos leídos (que no sean del usuario)
// Eliminamos GROUP BY para evitar errores en modo estricto de SQL
$public_read_stmt = $conn->prepare('
    SELECT t.id, t.title, t.title_translation, t.content, t.user_id, t.is_public, t.created_at, 
           rp.read_count, rp.updated_at, rp.percent
    FROM texts t
    INNER JOIN reading_progress rp ON rp.text_id = t.id
    WHERE rp.user_id = ? AND t.is_public = 1 AND t.user_id != ? AND (rp.percent > 0 OR rp.read_count > 0)
    AND t.id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?)
    ORDER BY rp.updated_at DESC, t.title ASC
');
$public_read_stmt->bind_param('iii', $user_id, $user_id, $user_id);
$public_read_stmt->execute();
$public_read_result = $public_read_stmt->get_result();
$public_read_rows = [];
while ($row = $public_read_result->fetch_assoc()) { $public_read_rows[] = $row; }
$public_read_stmt->close();

$total_found = count($own_texts) + count($public_read_rows);
?>

<style>
    .text-meta-container { display: flex; align-items: center; color: #64748b; font-size: 0.92em; white-space: nowrap; flex-shrink: 0; }
    .meta-col { padding: 0 15px; border-right: 1px solid #f1f5f9; text-align: center; }
    .meta-col:last-child { border-right: none; padding-right: 0; }
    .meta-words { width: 100px; text-align: right; }
    .meta-status { width: 130px; }
    .meta-date { width: 100px; color: #94a3b8; }
    .meta-public { width: 70px; text-align: right; }
    .reading-status-label { color: #3B82F6; font-weight: bold; }
    .status-public-tag { font-size: 0.65em; padding: 2px 6px; background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
    @media (max-width: 850px) { .text-meta-container { display: none; } }
</style>

<div class="tab-content-wrapper">
    <div id="messages-container"></div>

    <div class="bulk-actions-container">
        <div style="color: #64748b; font-weight: 500;">
            <span style="color: #3b82f6; font-weight: 600;"><?php echo $total_found ?></span> textos encontrados
        </div>
        <div class="bulk-actions" style="display: flex; gap: 12px; align-items: center;">
            <div class="dropdown">
                <button class="nav-btn" id="dropdownBtn" onclick="toggleDropdown()">Acciones en lote ▼</button>
                <div class="dropdown-content" id="dropdownContent">
                    <button type="button" onclick="selectAllTexts()">✓ Marcar todos</button>
                    <button type="button" onclick="unselectAllTexts()">✗ Desmarcar todos</button>
                    <button type="button" onclick="performBulkAction('delete')" style="color: #ff8a00;">🗑️ Eliminar seleccionados</button>
                </div>
            </div>
        </div>
        <div class="dropdown" id="publicTextsDropdown" style="position: relative; margin-top: 16px; background:#ff8a00; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px;">
            <button class="nav-btn" id="publicTextsBtn" onclick="togglePublicTextsDropdown(event)">Textos públicos ▼</button>
            <div class="dropdown-content" id="publicCategoriesContent">
                <div style="padding: 10px; color: #6b7280;">Cargando categorías...</div>
            </div>
        </div>
    </div>

    <form id="bulkForm">
        <ul class="text-list">
            <?php 
            // Renderizar textos propios
            foreach ($own_texts as $row) { renderTextItem($row, $user_id, false); } 
            
            // Renderizar textos públicos leídos
            foreach ($public_read_rows as $row) { renderTextItem($row, $user_id, true); }
            ?>
        </ul>

        <?php if ($total_found == 0): ?>
            <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">📚</div>
                <h3 style="margin-bottom: 10px; color: #374151;">No hay textos en tu lista</h3>
                <p style="margin-bottom: 30px;">¡Comienza subiendo un texto o explora los públicos!</p>
                <button type="button" onclick="loadTabContent('upload')" class="nav-btn primary" style="padding: 15px 40px;">⬆ Subir mi primer texto</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<link rel="stylesheet" href="css/tab-system.css">
<?php $conn->close(); ?>
