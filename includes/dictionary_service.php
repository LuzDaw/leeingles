<?php
// includes/dictionary_service.php
// Servicio centralizado para búsquedas de diccionario y procesamiento de resultados

/**
 * Limpia el texto de ejemplos de Merriam-Webster u otras APIs.
 */
function limpiarEjemploMerriamWebster($texto) {
    $texto = preg_replace('/\{wi\}(.*?)\{\/wi\}/', '$1', $texto);
    $texto = preg_replace('/\{it\}(.*?)\{\/it\}/', '$1', $texto);
    $texto = preg_replace('/\{b\}(.*?)\{\/b\}/', '$1', $texto);
    $texto = preg_replace('/\{sup\}(.*?)\{\/sup\}/', '$1', $texto);
    $texto = preg_replace('/\{inf\}(.*?)\{\/inf\}/', '$1', $texto);
    $texto = preg_replace('/\{dx\}(.*?)\{\/dx\}/', '$1', $texto);
    $texto = preg_replace('/\{dxt\}(.*?)\{\/dxt\}/', '$1', $texto);
    $texto = preg_replace('/\{ma\}(.*?)\{\/ma\}/', '$1', $texto);
    $texto = trim($texto);
    $texto = preg_replace('/\s+/', ' ', $texto);
    return $texto;
}

function getFreeDictionaryInfo($word) {
    $url = "https://api.dictionaryapi.dev/api/v2/entries/en/" . urlencode($word);
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'Mozilla/5.0'
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return false;
    $data = json_decode($response, true);
    if (!is_array($data) || empty($data) || !isset($data[0]['meanings'])) return false;
    return $data;
}

function getWordsAPIInfo($word) {
    $url = "https://wordsapiv1.p.rapidapi.com/words/" . urlencode($word);
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'Mozilla/5.0'
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return false;
    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data['word'])) return false;
    return $data;
}

function processFreeDictionaryData($data) {
    $result = [
        'word' => $data[0]['word'] ?? '',
        'definition' => '',
        'grammatical_info' => '',
        'synonyms' => [],
        'antonyms' => [],
        'examples' => []
    ];
    if (isset($data[0]['meanings']) && is_array($data[0]['meanings'])) {
        $meanings = $data[0]['meanings'];
        if (!empty($meanings)) {
            $firstMeaning = $meanings[0];
            if (isset($firstMeaning['definitions'][0]['definition'])) {
                $result['definition'] = $firstMeaning['definitions'][0]['definition'];
            }
            if (isset($firstMeaning['partOfSpeech'])) {
                $result['grammatical_info'] = ucfirst($firstMeaning['partOfSpeech']);
            }
            if (isset($firstMeaning['definitions'][0]['example'])) {
                $result['examples'][] = $firstMeaning['definitions'][0]['example'];
            }
            if (isset($firstMeaning['synonyms'])) {
                $result['synonyms'] = array_slice($firstMeaning['synonyms'], 0, 5);
            }
            if (isset($firstMeaning['antonyms'])) {
                $result['antonyms'] = array_slice($firstMeaning['antonyms'], 0, 5);
            }
        }
    }
    return $result;
}

function processWordsAPIData($data) {
    $result = [
        'word' => $data['word'] ?? '',
        'definition' => '',
        'grammatical_info' => '',
        'synonyms' => [],
        'antonyms' => [],
        'examples' => []
    ];
    if (isset($data['results']) && is_array($data['results'])) {
        $firstResult = $data['results'][0];
        if (isset($firstResult['definition'])) $result['definition'] = $firstResult['definition'];
        if (isset($firstResult['partOfSpeech'])) $result['grammatical_info'] = ucfirst($firstResult['partOfSpeech']);
        if (isset($firstResult['examples'])) $result['examples'] = array_slice($firstResult['examples'], 0, 3);
        if (isset($firstResult['synonyms'])) $result['synonyms'] = array_slice($firstResult['synonyms'], 0, 5);
        if (isset($firstResult['antonyms'])) $result['antonyms'] = array_slice($firstResult['antonyms'], 0, 5);
    }
    return $result;
}
