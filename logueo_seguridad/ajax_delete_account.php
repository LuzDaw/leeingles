<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'No has iniciado sesión.']);
    exit;
}

$user_id = getCurrentUserId();

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario no encontrado.']);
    exit;
}

try {
    // Iniciar transacción para asegurar limpieza total y atómica
    $conn->begin_transaction();

    // 1. Borrar palabras guardadas
    $stmt1 = $conn->prepare("DELETE FROM saved_words WHERE user_id = ?");
    $stmt1->bind_param("i", $user_id);
    $stmt1->execute();
    $stmt1->close();

    // 2. Borrar progreso de práctica
    $stmt2 = $conn->prepare("DELETE FROM practice_progress WHERE user_id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $stmt2->close();

    // 3. Borrar tiempos de práctica
    $stmt3 = $conn->prepare("DELETE FROM practice_time WHERE user_id = ?");
    $stmt3->bind_param("i", $user_id);
    $stmt3->execute();
    $stmt3->close();

    // 4. Borrar progreso de lectura
    $stmt4 = $conn->prepare("DELETE FROM reading_progress WHERE user_id = ?");
    $stmt4->bind_param("i", $user_id);
    $stmt4->execute();
    $stmt4->close();

    // 5. Borrar tiempos de lectura
    $stmt5 = $conn->prepare("DELETE FROM reading_time WHERE user_id = ?");
    $stmt5->bind_param("i", $user_id);
    $stmt5->execute();
    $stmt5->close();

    // 6. Borrar registros de textos ocultos
    $stmt6 = $conn->prepare("DELETE FROM hidden_texts WHERE user_id = ?");
    $stmt6->bind_param("i", $user_id);
    $stmt6->execute();
    $stmt6->close();

    // 7. Borrar uso de traducciones
    $stmt7 = $conn->prepare("DELETE FROM uso_traducciones WHERE user_id = ?");
    $stmt7->bind_param("i", $user_id);
    $stmt7->execute();
    $stmt7->close();

    // 8. Borrar suscripciones
    $stmt8 = $conn->prepare("DELETE FROM user_subscriptions WHERE user_id = ?");
    $stmt8->bind_param("i", $user_id);
    $stmt8->execute();
    $stmt8->close();

    // 9. Borrar verificaciones de email
    $stmt9 = $conn->prepare("DELETE FROM verificaciones_email WHERE id_usuario = ?");
    $stmt9->bind_param("i", $user_id);
    $stmt9->execute();
    $stmt9->close();

    // 10. Borrar los textos del usuario
    $stmt10 = $conn->prepare("DELETE FROM texts WHERE user_id = ?");
    $stmt10->bind_param("i", $user_id);
    $stmt10->execute();
    $stmt10->close();

    // 11. Finalmente, borrar el registro del usuario
    $stmt11 = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt11->bind_param("i", $user_id);
    
    if ($stmt11->execute()) {
        $conn->commit();
        
        // Cerrar la sesión después de borrar la cuenta
        logoutUser();
        
        echo json_encode(['success' => true, 'message' => 'Cuenta y todos sus datos asociados eliminados correctamente.']);
    } else {
        throw new Exception("Error al eliminar el registro principal del usuario.");
    }
    
    $stmt11->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Error al eliminar la cuenta: ' . $e->getMessage()]);
}

$conn->close();
