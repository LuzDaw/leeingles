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
                    $to_hide[] = $row['id'];
                } elseif ($row['user_id'] == $user_id) {
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
            }
            
            // Borrar textos privados
            if (!empty($to_delete)) {
                $params = array_merge($to_delete, [$user_id]);
                $types = str_repeat('i', count($params));
                $stmt = $conn->prepare("DELETE FROM texts WHERE id IN ($placeholders) AND user_id = ?");
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Textos eliminados correctamente.']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Textos ocultados correctamente.']);
            }
            exit();
        }
    }
}

// Obtener textos propios
$stmt = $conn->prepare("SELECT id, title, title_translation, content, is_public, created_at FROM texts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Consulta textos públicos leídos (Ya optimizada con INNER JOIN)
$public_read_stmt = $conn->prepare('
    SELECT t.id, t.title, t.title_translation, t.content, t.user_id, t.is_public, t.created_at, rp.percent, rp.read_count
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
?>

<style>
    .text-meta-container { 
        display: flex; 
        align-items: center; 
        color: #64748b; 
        font-size: 0.92em; 
        white-space: nowrap; 
        flex-shrink: 0;
    }
    .meta-col { padding: 0 15px; border-right: 1px solid #f1f5f9; text-align: center; }
    .meta-col:last-child { border-right: none; padding-right: 0; }
    .meta-words { width: 100px; text-align: right; }
    .meta-status { width: 130px; }
    .meta-date { width: 100px; color: #94a3b8; }
    .meta-public { width: 70px; text-align: right; }
    
    .reading-status-label { color: #3B82F6; font-weight: bold; }
    .progress-percent { color: #2563eb; font-weight: 500; }
    .status-public-tag { 
        font-size: 0.65em; 
        padding: 2px 6px; 
        background: #eff6ff; 
        color: #2563eb; 
        border: 1px solid #dbeafe; 
        border-radius: 4px; 
        text-transform: uppercase; 
        letter-spacing: 0.05em; 
        font-weight: 600;
    }

    @media (max-width: 850px) {
        .text-meta-container { display: none; }
    }
</style>

<div class="tab-content-wrapper">
    <div id="messages-container"></div>

    <div class="bulk-actions-container">
        <div style="color: #64748b; font-weight: 500;">
            <span style="color: #3b82f6; font-weight: 600;"><?php echo ($result->num_rows + $public_read_count) ?></span> textos encontrados
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
            while ($row = $result->fetch_assoc()) {
                $num_words = str_word_count(strip_tags($row['content']));
                $progress = 0;
                $is_read = false;
                $read_count = 0;
                
                $stmt2 = $conn->prepare("SELECT percent, read_count FROM reading_progress WHERE user_id = ? AND text_id = ?");
                $stmt2->bind_param('ii', $user_id, $row['id']);
                $stmt2->execute();
                $stmt2->bind_result($percent, $rc);
                if ($stmt2->fetch()) {
                    $progress = intval($percent);
                    $is_read = ($progress >= 100);
                    $read_count = intval($rc);
                }
                $stmt2->close();
                
                $formatted_date = date('d/m/Y', strtotime($row['created_at']));
                ?>
                <li class="text-item">
                    <input type="checkbox" class="text-checkbox" name="selected_texts[]" value="<?= $row['id'] ?>" onchange="updateBulkActions()">
                    <span class="text-icon">📄</span>
                    
                    <div class="text-main-info">
                        <a href="?text_id=<?= $row['id'] ?>" class="text-title">
                            <span class="title-english"><?= htmlspecialchars($row['title']) ?></span>
                            <?php if (!empty($row['title_translation'])): ?>
                                <span class="title-spanish">• <?= htmlspecialchars($row['title_translation']) ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="text-meta-container">
                        <div class="meta-col meta-words"><?= $num_words ?> palabras</div>
                        <div class="meta-col meta-status">
                            <?php if ($is_read || $read_count > 0): ?>
                                <span class="reading-status-label">Leído <?= $read_count ?> <?= $read_count == 1 ? 'vez' : 'veces' ?></span>
                            <?php elseif ($progress > 0): ?>
                                <span class="progress-percent"><?= $progress ?>%</span>
                            <?php else: ?>
                                <span style="color: #cbd5e1;">-</span>
                            <?php endif; ?>
                        </div>
                        <div class="meta-col meta-date"><?= $formatted_date ?></div>
                        <div class="meta-col meta-public">
                            <?php if ($row['is_public']): ?>
                                <span class="status-public-tag">Público</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php } ?>

            <?php
            // Renderizar textos públicos leídos
            foreach ($public_read_rows as $row) {
                $num_words = str_word_count(strip_tags($row['content']));
                $percent = intval($row['percent']);
                $read_count = intval($row['read_count']);
                $formatted_date = date('d/m/Y', strtotime($row['created_at']));
                ?>
                <li class="text-item">
                    <input type="checkbox" class="text-checkbox" name="selected_texts[]" value="<?= $row['id'] ?>" onchange="updateBulkActions()">
                    <span class="text-icon">📄</span>
                    
                    <div class="text-main-info">
                        <a href="?public_text_id=<?= $row['id'] ?>" class="text-title">
                            <span class="title-english"><?= htmlspecialchars($row['title']) ?></span>
                            <?php if (!empty($row['title_translation'])): ?>
                                <span class="title-spanish">• <?= htmlspecialchars($row['title_translation']) ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <div class="text-meta-container">
                        <div class="meta-col meta-words"><?= $num_words ?> palabras</div>
                        <div class="meta-col meta-status">
                            <?php if ($percent >= 100 || $read_count > 0): ?>
                                <span class="reading-status-label">Leído <?= $read_count ?> <?= $read_count == 1 ? 'vez' : 'veces' ?></span>
                            <?php elseif ($percent > 0): ?>
                                <span class="progress-percent"><?= $percent ?>%</span>
                            <?php else: ?>
                                <span style="color: #cbd5e1;">-</span>
                            <?php endif; ?>
                        </div>
                        <div class="meta-col meta-date"><?= $formatted_date ?></div>
                        <div class="meta-col meta-public">
                            <span class="status-public-tag">Público</span>
                        </div>
                    </div>
                </li>
            <?php } ?>
        </ul>

        <?php if ($result->num_rows == 0 && $public_read_count == 0): ?>
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
<?php
$stmt->close();
$public_read_stmt->close();
$conn->close();
?>
