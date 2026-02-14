<?php
// sistemaTraducion/test_endpoint.php
// MODO DIAGNÓSTICO: Muestra los resultados de cada capa de caché.

// --- MODO DE DEPURACIÓN ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- FIN DE DEPURACIÓN ---

header('Content-Type: application/json');

// 1. INCLUIR FICHEROS
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 74; // Asegúrate de que el usuario 74 exista.
}
$user_id = $_SESSION['user_id'];

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_helpers.php';
require_once __DIR__ . '/../includes/cache.php';
require_once __DIR__ . '/../db/connection.php';

require_once __DIR__ . '/../includes/word_functions.php';

$word = isset($_POST['word']) ? trim($_POST['word']) : '';
if (empty($word)) {
    echo json_encode(['error' => 'No se proporcionó ninguna palabra.']);
    exit;
}

// 2. OBTENER TRADUCCIÓN USANDO LA FUNCIÓN CENTRAL
$result = get_or_translate_word($conn, $user_id, $word);

// 3. CONSTRUIR Y DEVOLVER LA RESPUESTA
echo json_encode([
    'word' => $word,
    'translation' => $result['translation'],
    'source' => $result['source']
]);


?>
