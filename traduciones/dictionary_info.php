<?php
// =============================
// SISTEMA HÍBRIDO DE INFORMACIÓN DE DICCIONARIO
// =============================
// 1. Intenta Free Dictionary API
// 2. Si falla, usa otra API de diccionario gratuita
// 3. Si ambos fallan, devuelve error

header('Content-Type: application/json; charset=utf-8');

// Aceptar solo 'word' como parámetro
$word = $_POST['word'] ?? null;
if (!$word) {
    echo json_encode(['error' => 'No se proporcionó ninguna palabra']);
    exit();
}
$word = trim($word);
if ($word === '') {
    echo json_encode(['error' => 'Palabra vacía']);
    exit();
}

require_once __DIR__ . '/../includes/dictionary_service.php';

$info = getDictionaryInfo($word);
if ($info === false) {
    echo json_encode(['error' => 'No se pudo obtener información de diccionario']);
    exit();
}

echo json_encode($info);
?>
