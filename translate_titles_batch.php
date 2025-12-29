<?php
session_start();
require_once 'db/connection.php';
require_once 'includes/title_functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Modo 1: Traducir todos los títulos sin traducción del usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'translate_all') {
    
    // Obtener textos sin traducción
    $stmt = $conn->prepare("
        SELECT id, title 
        FROM texts 
        WHERE user_id = ? AND (title_translation IS NULL OR title_translation = '')
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $translated = 0;
    $failed = 0;
    $errors = [];
    
    while ($row = $result->fetch_assoc()) {
        $text_id = $row['id'];
        $title = $row['title'];
        
        // Intentar traducir usando la API
        $translation = translateTitleViaAPI($title);
        
        if ($translation !== false) {
            // Guardar la traducción
            $save_result = saveTitleTranslation($text_id, $title, $translation);
            if ($save_result['success']) {
                $translated++;
                error_log("[BATCH] Título traducido ID $text_id: '$title' -> '$translation'");
            } else {
                $failed++;
                $errors[] = "Error guardando ID $text_id: " . $save_result['error'];
            }
        } else {
            $failed++;
            $errors[] = "No se pudo traducir: '$title'";
        }
        
        // Pequeña pausa para no sobrecargar la API
        usleep(200000);
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'translated' => $translated,
        'failed' => $failed,
        'errors' => count($errors) > 0 ? array_slice($errors, 0, 5) : [],
        'message' => "Se tradujeron $translated títulos, $failed fallaron"
    ]);
    exit();
}

// Modo 2: Traducir un título específico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'translate_single') {
    
    if (!isset($_POST['text_id'])) {
        echo json_encode(['success' => false, 'error' => 'text_id requerido']);
        exit();
    }
    
    $text_id = intval($_POST['text_id']);
    
    // Verificar que el texto pertenece al usuario
    $stmt = $conn->prepare("SELECT title FROM texts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $text_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Texto no encontrado o no autorizado']);
        exit();
    }
    
    $row = $result->fetch_assoc();
    $title = $row['title'];
    $stmt->close();
    
    // Intentar traducir
    $translation = translateTitleViaAPI($title);
    
    if ($translation !== false) {
        $save_result = saveTitleTranslation($text_id, $title, $translation);
        
        if ($save_result['success']) {
            $conn->close();
            echo json_encode([
                'success' => true,
                'text_id' => $text_id,
                'original' => $title,
                'translation' => $translation,
                'message' => 'Título traducido correctamente'
            ]);
        } else {
            $conn->close();
            echo json_encode([
                'success' => false,
                'error' => 'Error guardando la traducción: ' . $save_result['error']
            ]);
        }
    } else {
        $conn->close();
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo traducir el título'
        ]);
    }
    exit();
}

// Función auxiliar para traducir vía API
function translateTitleViaAPI($title) {
    try {
        // Construir URL dinámica
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $translate_url = $protocol . '://' . $host . '/traductor/translate.php';
        
        // Llamar a la API de traducción
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $translate_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'word=' . urlencode($title));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($response && empty($curl_error)) {
            $translation_data = json_decode($response, true);
            if (isset($translation_data['translation']) && !empty($translation_data['translation'])) {
                return $translation_data['translation'];
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error traduciendo título '$title': " . $e->getMessage());
        return false;
    }
}

// Si no hay action especificado, mostrar error
echo json_encode(['success' => false, 'error' => 'Acción no especificada']);
$conn->close();
?>
