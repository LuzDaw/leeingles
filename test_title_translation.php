<?php
session_start();
require_once 'db/connection.php';
require_once 'includes/title_functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Obtener textos del usuario
$stmt = $conn->prepare("SELECT id, title, title_translation FROM texts WHERE user_id = ? LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$texts = [];
while ($row = $result->fetch_assoc()) {
    $texts[] = $row;
}
$stmt->close();

// 2. Intentar obtener traducción de cada uno
$debug = [];
foreach ($texts as $text) {
    $translation = getTitleTranslation($text['id']);
    $debug[] = [
        'id' => $text['id'],
        'title' => $text['title'],
        'title_translation_BD' => $text['title_translation'],
        'getTitleTranslation()' => $translation,
        'existe_traduccion' => !empty($translation)
    ];
}

// 3. Verificar API de traducción
$test_translation = null;
try {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $translate_url = $protocol . '://' . $host . '/traductor/translate.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $translate_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'word=Hello%20World');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $test_translation = [
        'url' => $translate_url,
        'http_code' => $http_code,
        'error' => $error ?: 'ninguno',
        'response' => $response ? json_decode($response, true) : null
    ];
} catch (Exception $e) {
    $test_translation = ['error' => $e->getMessage()];
}

echo json_encode([
    'user_id' => $user_id,
    'textos_del_usuario' => $debug,
    'api_test' => $test_translation
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conn->close();
?>
