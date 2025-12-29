<?php
session_start();
require_once 'db/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Validar datos de entrada
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$is_public = isset($_POST['is_public']) ? 1 : 0;

// Si no hay título, generar uno con las 3 primeras palabras del contenido
if (empty($title) && !empty($content)) {
    $words = preg_split('/\s+/', $content, 4);
    $title = implode(' ', array_slice($words, 0, 3));
    // Limpiar caracteres especiales y limitar longitud
    $title = preg_replace('/[^\w\s-]/', '', $title);
    $title = substr($title, 0, 50);
    if (empty($title)) {
        $title = "Texto sin título";
    }
}

// Validaciones
if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Debes incluir contenido para el texto.']);
    exit();
}

if ($is_public && $category_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Debes seleccionar una categoría para el texto público.']);
    exit();
}

// Si texto privado, category_id debe ser null
if (!$is_public) {
    $category_id = null;
}

try {
    $stmt = $conn->prepare("INSERT INTO texts (user_id, title, content, category_id, is_public) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Error preparando la consulta: " . $conn->error);
    }
    
    if ($category_id === null) {
        $null = null;
        $stmt->bind_param("isssi", $_SESSION['user_id'], $title, $content, $null, $is_public);
    } else {
        $stmt->bind_param("issii", $_SESSION['user_id'], $title, $content, $category_id, $is_public);
    }

    if ($stmt->execute()) {
        $text_id = $conn->insert_id;
        
        // Traducir automáticamente el título
        if (!empty($title)) {
            try {
                // Construir URL dinámica basada en el servidor actual
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $translate_url = $protocol . '://' . $host . '/translate.php';
                
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
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($response && empty($curl_error) && $http_code === 200) {
                    $translation_data = json_decode($response, true);
                    if (isset($translation_data['translation']) && !empty($translation_data['translation'])) {
                        // Guardar la traducción en la base de datos
                        $update_stmt = $conn->prepare("UPDATE texts SET title_translation = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $translation_data['translation'], $text_id);
                        if ($update_stmt->execute()) {
                            error_log("[TRANSLATION SUCCESS] Text ID: $text_id, Title: '$title', Translation: '" . $translation_data['translation'] . "'");
                        } else {
                            error_log("[TRANSLATION ERROR] Failed to save translation for text ID: $text_id - " . $update_stmt->error);
                        }
                        $update_stmt->close();
                    } else {
                        error_log("[TRANSLATION ERROR] No translation in response for text ID: $text_id, Response: " . substr($response, 0, 200));
                    }
                } else {
                    error_log("[TRANSLATION ERROR] cURL failed for text ID: $text_id, URL: $translate_url, Error: $curl_error, HTTP Code: $http_code");
                }
            } catch (Exception $e) {
                // Si falla la traducción, no es crítico, solo log
                error_log("[TRANSLATION EXCEPTION] Error traduciendo título para text ID: $text_id - " . $e->getMessage());
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Texto subido correctamente']);
    } else {
        throw new Exception("Error ejecutando la consulta: " . $stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error en ajax_upload_text.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?>
