<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/word_functions.php';

requireUserOrExitJson();

if (!isset($_POST['word']) || !isset($_POST['translation'])) {
    echo json_encode(['error' => 'Faltan datos.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$word = $_POST['word'];
$translation = $_POST['translation'];
$context = '';

$res = saveTranslatedWord($user_id, $word, $translation, $context, null);

if ($res['success']) {
    echo json_encode(['success' => true, 'message' => $res['message']]);
} else {
    echo json_encode(['success' => false, 'error' => $res['error']]);
}

$conn->close();
