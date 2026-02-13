<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/word_functions.php';

header('Content-Type: application/json; charset=utf-8');
requireUserOrExitJson();

if (!isset($_POST['word']) || !isset($_POST['translation'])) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$user_id = $_SESSION['user_id'];
$word = trim($_POST['word']);
$translation = trim($_POST['translation']);
$context = isset($_POST['context']) ? trim($_POST['context']) : '';

// Obtener text_id de forma robusta
$text_id = null;
if (isset($_POST['text_id']) && is_numeric($_POST['text_id'])) {
    $text_id = intval($_POST['text_id']);
} elseif (isset($_GET['text_id']) && is_numeric($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
} elseif (isset($_SERVER['HTTP_REFERER'])) {
    // Intentar extraer text_id o public_text_id de la URL referer
    if (preg_match('/[?&](?:text_id|public_text_id)=(\d+)/', $_SERVER['HTTP_REFERER'], $matches)) {
        $text_id = intval($matches[1]);
    }
}

// El text_id ya no es estrictamente obligatorio para permitir guardar palabras sueltas
// pero lo usaremos si está disponible.

if (empty($word) || empty($translation)) {
    echo json_encode(['error' => 'Palabra y traducción son requeridas']);
    exit();
}

// Reuse centralized helper
$res = saveTranslatedWord($user_id, $word, $translation, $context, $text_id);

if ($res['success']) {
    echo json_encode(['success' => true, 'message' => $res['message']]);
} else {
    echo json_encode(['error' => $res['error']]);
}

$conn->close();
?>
