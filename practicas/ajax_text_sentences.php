<?php
session_start();
require_once '../db/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['text_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes']);
    exit();
}

$user_id = $_SESSION['user_id'];
$text_id = intval($_GET['text_id']);

// Verificar que el texto pertenece al usuario
$stmt = $conn->prepare("SELECT content FROM texts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $text_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$text = $result->fetch_assoc();

if (!$text) {
    echo json_encode(['success' => false, 'message' => 'Texto no encontrado']);
    exit();
}

// Dividir el texto en oraciones más simples
$content = $text['content'];
// Primero dividir por párrafos, luego por oraciones
$paragraphs = explode("\n", $content);
$sentences = [];

foreach ($paragraphs as $paragraph) {
    $paragraph = trim($paragraph);
    if (empty($paragraph)) continue;
    
    // Dividir cada párrafo en oraciones simples
    $para_sentences = preg_split('/(?<=[.!?])\s+/', $paragraph, -1, PREG_SPLIT_NO_EMPTY);
    
    foreach ($para_sentences as $sentence) {
        $sentence = trim($sentence);
        if (!empty($sentence)) {
            // Filtrar oraciones que sean demasiado complejas
            if (substr_count($sentence, ':') <= 1 && substr_count($sentence, ';') <= 1) {
                $sentences[] = $sentence;
            }
        }
    }
}

// Limitar número de oraciones para acelerar la carga
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;
if (count($sentences) > $limit) {
    $sentences = array_slice($sentences, 0, $limit);
}

// Traducir las frases al español usando el sistema existente
$translated_sentences = [];
foreach ($sentences as $sentence) {
    $sentence = trim($sentence);
    if (empty($sentence)) continue;
    
    // Filtrar frases con longitud adecuada (entre 8-20 palabras)
    $word_count = str_word_count($sentence);
    if ($word_count < 8 || $word_count > 20) continue;
    
    // Evitar frases con demasiados signos de puntuación complejos
    if (substr_count($sentence, '"') > 4 || substr_count($sentence, '—') > 1) continue;
    
    // Usar el sistema de traducción existente (translate.php)
    $translation = translateUsingExistingSystem($sentence);
    
    // Verificar que la traducción tenga longitud similar (tolerancia de ±5 palabras)
    $translation_word_count = str_word_count($translation ?: $sentence);
    if ($translation_word_count < 6 || abs($translation_word_count - $word_count) > 5) continue;
    
    // Normalizar apóstrofes comunes en el texto inglés 
    $normalizedSentence = $sentence;
    $normalizedSentence = str_replace('´', "'", $normalizedSentence);
    $normalizedSentence = str_replace('`', "'", $normalizedSentence);
    
    $translated_sentences[] = [
        'en' => $normalizedSentence,
        'es' => $translation ?: $sentence
    ];
}

/**
 * Traduce un texto utilizando la API de Google Translate.
 *
 * Esta función realiza una solicitud a la API de Google Translate para obtener
 * la traducción de un texto del inglés al español. Incluye un timeout reducido
 * para la solicitud HTTP. En caso de fallo, devuelve el texto original con un prefijo.
 *
 * @param string $text El texto a traducir.
 * @return string La traducción del texto o un mensaje de fallback si la traducción falla.
 */
function translateUsingExistingSystem($text) {
    // Incluir directamente la lógica de translate.php (más eficiente)
    $from = 'en';
    $to = 'es';
    
    // Configurar contexto con timeout reducido para acelerar
    $context = stream_context_create([
        'http' => [
            'timeout' => 3 // Reducido a 3 segundos para mayor velocidad
        ]
    ]);

    // Traducción directa y rápida
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=$from&tl=$to&dt=t&q=" . urlencode($text);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        // Fallback: devolver texto original si falla la traducción
        return "Traducción de: " . $text;
    }
    
    $data = json_decode($response, true);
    if (isset($data[0][0][0])) {
        return $data[0][0][0];
    }
    
    // Fallback si no se puede parsear
    return "Traducción de: " . $text;
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'sentences' => $translated_sentences]);
?>
