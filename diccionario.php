<?php
// =============================
// API DE DICCIONARIO MERRIAM-WEBSTER
// =============================
// Obtiene información real: definición, categoría, ejemplos, sinónimos, antónimos, pronunciación

// Claves API de Merriam-Webster
$claveDiccionario = '7ef8642e-2065-40f6-b130-cdd73f703124';
$claveTesauro = '54c025db-bc08-45cc-b120-c388644d418c';

function obtenerInfoPalabra($palabra) {
    global $claveDiccionario, $claveTesauro;
    
    // Función para limpiar ejemplos de Merriam-Webster
    function limpiarEjemploMerriamWebster($texto) {
        // Eliminar etiquetas específicas de Merriam-Webster
        $texto = preg_replace('/\{wi\}(.*?)\{\/wi\}/', '$1', $texto); // Palabras importantes
        $texto = preg_replace('/\{it\}(.*?)\{\/it\}/', '$1', $texto); // Texto en cursiva
        $texto = preg_replace('/\{b\}(.*?)\{\/b\}/', '$1', $texto);   // Texto en negrita
        $texto = preg_replace('/\{sup\}(.*?)\{\/sup\}/', '$1', $texto); // Superíndice
        $texto = preg_replace('/\{inf\}(.*?)\{\/inf\}/', '$1', $texto); // Subíndice
        $texto = preg_replace('/\{dx\}(.*?)\{\/dx\}/', '$1', $texto);   // Definición extendida
        $texto = preg_replace('/\{dxt\}(.*?)\{\/dxt\}/', '$1', $texto); // Texto de definición
        $texto = preg_replace('/\{ma\}(.*?)\{\/ma\}/', '$1', $texto);   // Meta información
        
        // Limpiar espacios extra y caracteres especiales
        $texto = trim($texto);
        $texto = preg_replace('/\s+/', ' ', $texto); // Múltiples espacios a uno solo
        
        return $texto;
    }
    

    
    $resultado = [
        'categoria'     => '',
        'definicion'    => '',
        'ejemplos'      => [],
        'ejemplos_es'   => [],
        'sinonimos'     => [],
        'antonimos'     => [],
        'pronunciacion' => '',
        'audio'         => '',
        'source'        => 'Merriam-Webster Dictionary',
        'fallback'      => false
    ];

    // ----- Diccionario -----
    $urlDic = "https://www.dictionaryapi.com/api/v3/references/collegiate/json/" . urlencode($palabra) . "?key=" . $claveDiccionario;
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $resDic = @file_get_contents($urlDic, false, $context);
    
    if ($resDic) {
        $data = json_decode($resDic, true);
        
        if (!empty($data) && is_array($data) && isset($data[0]) && is_array($data[0])) {
            $entry = $data[0];
            
            // Categoría gramatical
            if (!empty($entry['fl'])) {
                $resultado['categoria'] = $entry['fl'];
            }
            
            // Definición
            if (!empty($entry['shortdef'][0])) {
                $resultado['definicion'] = $entry['shortdef'][0];
            }
            
            // Pronunciación
            if (!empty($entry['hwi']['prs'][0]['mw'])) {
                $resultado['pronunciacion'] = $entry['hwi']['prs'][0]['mw'];
            }
            
            // Audio
            if (!empty($entry['hwi']['prs'][0]['sound']['audio'])) {
                $audioFile = $entry['hwi']['prs'][0]['sound']['audio'];
                if (preg_match('/^[0-9]/', $audioFile)) {
                    $sub = 'number';
                } elseif (str_starts_with($audioFile, 'bix')) {
                    $sub = 'bix';
                } elseif (str_starts_with($audioFile, 'gg')) {
                    $sub = 'gg';
                } else {
                    $sub = $audioFile[0];
                }
                $resultado['audio'] = "https://media.merriam-webster.com/audio/prons/en/us/mp3/$sub/$audioFile.mp3";
            }
            
            // Ejemplos
            if (!empty($entry['def'][0]['sseq'])) {
                foreach ($entry['def'][0]['sseq'] as $entry_seq) {
                    foreach ($entry_seq as $e) {
                        if (isset($e[1]['dt'])) {
                            foreach ($e[1]['dt'] as $d) {
                                if ($d[0] === 'vis') {
                                    foreach ($d[1] as $ej) {
                                        $ejemplo_limpio = limpiarEjemploMerriamWebster($ej['t']);
                                        if (!empty($ejemplo_limpio)) {
                                            $resultado['ejemplos'][] = $ejemplo_limpio;
                                            $resultado['ejemplos_es'][] = ''; // Se traducirá en el frontend
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // ----- Tesauro -----
    $urlTes = "https://www.dictionaryapi.com/api/v3/references/thesaurus/json/" . urlencode($palabra) . "?key=" . $claveTesauro;
    
    $resTes = @file_get_contents($urlTes, false, $context);
    
    if ($resTes) {
        $data = json_decode($resTes, true);
        
        if (!empty($data) && is_array($data) && isset($data[0]) && is_array($data[0])) {
            $entry = $data[0];
            
            // Sinónimos
            if (!empty($entry['meta']['syns'][0])) {
                $resultado['sinonimos'] = array_slice($entry['meta']['syns'][0], 0, 5); // Máximo 5
            }
            
            // Antónimos
            if (!empty($entry['meta']['ants'][0])) {
                $resultado['antonimos'] = array_slice($entry['meta']['ants'][0], 0, 5); // Máximo 5
            }
        }
    }
    
    // Si no se obtuvo información, usar fallback básico
    if (empty($resultado['definicion'])) {
        $resultado['fallback'] = true;
        $resultado['definicion'] = "Información no disponible para '$palabra'";
        $resultado['categoria'] = 'N/A';
    }
    
    return $resultado;
}

header('Content-Type: application/json; charset=utf-8');
$palabra = $_GET['palabra'] ?? '';

if (!$palabra) {
    echo json_encode(['error' => 'No se proporcionó ninguna palabra']);
    exit();
}

$palabra = trim($palabra);
if ($palabra === '') {
    echo json_encode(['error' => 'Palabra vacía']);
    exit();
}

// Debug: ver qué palabra se está procesando
error_log("Procesando palabra: '$palabra'");

echo json_encode(obtenerInfoPalabra($palabra));
?>
