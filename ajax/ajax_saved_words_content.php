<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Liberar bloqueo de sesión para permitir otras peticiones paralelas
session_write_close();

// Endpoint para obtener el número de palabras de un texto específico
if (isset($_GET['get_word_count']) && isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as word_count FROM saved_words WHERE user_id = ? AND text_id = ?");
    $stmt->bind_param("ii", $user_id, $text_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    echo json_encode(['word_count' => intval($data['word_count'])]);
    $stmt->close();
    $conn->close();
    exit();
}

// Endpoint para obtener las palabras guardadas de un texto específico
if (isset($_GET['get_words_by_text']) && isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
    
    $stmt = $conn->prepare("SELECT sw.word, sw.translation, sw.context, sw.text_id, t.title as text_title, t.title_translation FROM saved_words sw LEFT JOIN texts t ON sw.text_id = t.id WHERE sw.user_id = ? AND sw.text_id = ? ORDER BY sw.created_at DESC");
    $stmt->bind_param("ii", $user_id, $text_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $words = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'words' => $words]);
    $stmt->close();
    $conn->close();
    exit();
}

// Procesar eliminación de palabra individual
if (isset($_POST['delete_word'])) {
    $word_to_delete = $_POST['word_to_delete'];
    $stmt = $conn->prepare("DELETE FROM saved_words WHERE user_id = ? AND word = ?");
    $stmt->bind_param("is", $user_id, $word_to_delete);
    if ($stmt->execute()) {
        $success_message = "Palabra eliminada correctamente.";
    } else {
        $error_message = "Error al eliminar la palabra.";
    }
    $stmt->close();
}

// Procesar acción en lote para eliminar palabras seleccionadas via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['selected_words'])) {
    $deleted_count = 0;
    $errors = [];
    
    foreach ($_POST['selected_words'] as $word_info) {
        list($word, $text_id) = explode('|', $word_info);
        $stmt = $conn->prepare("DELETE FROM saved_words WHERE user_id = ? AND word = ? AND (text_id = ? OR (text_id IS NULL AND ? = 0))");
        $stmt->bind_param("isii", $user_id, $word, $text_id, $text_id);
        if ($stmt->execute()) {
            $deleted_count++;
        } else {
            $errors[] = "Error eliminando: $word";
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        echo json_encode(['success' => true, 'message' => "$deleted_count palabra(s) eliminada(s) correctamente."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar algunas palabras.']);
    }
    exit();
}

// Obtener palabras guardadas del usuario, con título del texto
$stmt = $conn->prepare("SELECT sw.word, sw.translation, sw.context, sw.created_at, sw.text_id, t.title as text_title, t.title_translation FROM saved_words sw LEFT JOIN texts t ON sw.text_id = t.id WHERE sw.user_id = ? ORDER BY t.title, sw.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$words = $result->fetch_all(MYSQLI_ASSOC);

// Agrupar palabras por texto
$words_by_text = [];
foreach ($words as $word) {
    $title = $word['text_title'] ?? 'Sin texto asociado';
    $words_by_text[$title][] = $word;
}
?>

<div class="tab-content-wrapper">
    <div class="bulk-actions-container">
        <div style="color: #64748b; font-weight: 500;">
            <span style="color: #3b82f6; font-weight: 600;">
                <?php echo isset($words) ? count($words) : 0; ?>
            </span> palabras guardadas
        </div>
        <div class="bulk-actions">
            <div class="dropdown">
                <button class="nav-btn" id="dropdownBtn" onclick="toggleDropdown()">
                    Acciones en lote ▼
                </button>
                <div class="dropdown-content" id="dropdownContent">
                    <button type="button" onclick="selectAllWords()">✓ Marcar todos</button>
                    <button type="button" onclick="unselectAllWords()">✗ Desmarcar todos</button>
                    <button type="button" onclick="performBulkActionWords('delete')" style="color: #ff8a00;">🗑️ Eliminar seleccionadas</button>
                </div>
            </div>
        </div>
    </div>

<?php if (isset($success_message)): ?>
    <div class="message" style="background: #d1fae5; color: #161818ff; border: 1px solid #ff8a0087; text-align:center; font-size:16px;">
        <?= htmlspecialchars($success_message) ?>
    </div>
<?php elseif (isset($error_message)): ?>
    <div class="message" style="background: #fee2e2; color: #2a2323ff; border: 1px solid #ef8b07ff; text-align:center; font-size:16px;">
        <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<?php if (empty($words_by_text)): ?>
    <div style="text-align:center; color:#888; font-size:18px; margin:40px 0;">No tienes palabras guardadas aún.</div>
<?php else: ?>
    <form method="post" id="words-list-form">
        <?php foreach ($words_by_text as $text_title => $words): ?>
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header" style="display:flex;align-items:center;gap:10px;">
                    <input type="checkbox" class="text-checkbox" onclick="toggleGroup(this, 'group-<?= md5($text_title) ?>')">
                    <span class="text-title" style="font-size:1.2rem; font-weight:600; color:#1B263B;">
                        <?= htmlspecialchars($text_title) ?>
                        <span style="font-size:0.9em; color:#64748b; font-weight:400; margin-left:8px;">(<?= count($words) ?>)</span>
                    </span>
                </div>
                <ul class="text-list" id="group-<?= md5($text_title) ?>">
                    <?php foreach ($words as $word): ?>
                        <li class="text-item">
                            <input type="checkbox" name="selected_words[]" value="<?= htmlspecialchars($word['word']) . '|' . (int)($word['text_id'] ?? 0) ?>" class="text-checkbox" onchange="updateBulkActionsWords()">
                            <span class="text-title" style="font-size:1rem;">
                                <?= htmlspecialchars($word['word']) ?>
                                <span class="word-translation">(<?= htmlspecialchars($word['translation']) ?>)</span>
                            </span>
                            <?php if (!empty($word['context'])): ?>
                                <span class="word-context" data-context="<?= htmlspecialchars($word['context']) ?>">"<?= htmlspecialchars($word['context']) ?>"</span>
                                <div class="context-translation" style="color:#ca7c20d6; font-size:0.95em; margin-top:2px;"></div>
                            <?php endif; ?>
                            <span class="word-date"><?= date('d/m/Y', strtotime($word['created_at'])) ?></span>
                            
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </form>
<?php endif; ?>

<style>
.word-translation {
    color: #3b82f6;
    font-style: italic;
    margin-left: 10px;
}

.word-context {
    background: #f3f4f6;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    color: #6b7280;
    margin-top: 8px;
}

.word-date {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 8px;
}
</style>

<?php
$stmt->close();
$conn->close();
?>
