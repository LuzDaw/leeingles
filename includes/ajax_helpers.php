<?php
/**
 * Helpers para respuestas AJAX estandarizadas.
 */
function ajax_error($message = 'Error del servidor', $code = 500, $details = null) {
    if ($details) {
        error_log('[leeingles][ajax_error] ' . $details);
    }
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function ajax_success($data = [], $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    $payload = array_merge(['success' => true], $data ?: []);
    echo json_encode($payload);
    exit;
}

?>
<?php
// includes/ajax_helpers.php
// Helpers reutilizables para endpoints AJAX

/**
 * Renderiza un elemento de la lista de textos (propio o público).
 * Imprime directamente el HTML necesario.
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
                    <span class="title-spanish"> - <?= htmlspecialchars($row['title_translation']) ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="text-meta-container">
            <div class="meta-col meta-words"><?= $num_words ?> palabras</div>
            <div class="meta-col1 meta-words1"><?= $num_words ?> Pal.</div>
            <div class="meta-col meta-status">
                <?php if ($read_count > 0): ?>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <span class="reading-status-label">Leído <?= $read_count ?> <?= $read_count == 1 ? 'vez' : 'veces' ?></span>
                        <?php if ($last_read_date): ?>
                            <span id="fecha" style="font-size: 1em; color: #94a3b8; margin-top: 2px;"><?= date('d/m/Y', strtotime($last_read_date)) ?></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <span style="color: #cbd5e1;">-</span>
                <?php endif; ?>
            </div>
             <div class="meta-col meta-status1">
            <span class="reading-status-label">Subido</span>
            <div class="meta-col meta-date"><?= $formatted_date ?></div>
            <div class="meta-col meta-public">
                <?php if (isset($row['is_public']) && $row['is_public']): ?>
                    <span class="status-public-tag">Público</span>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </li>
    <?php
}
