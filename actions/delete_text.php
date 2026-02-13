<?php
session_start();
require_once '../db/connection.php';
require_once __DIR__ . '/../includes/word_functions.php';
require_once __DIR__ . '/../includes/practice_functions.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: logueo_seguridad/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
    
    // Iniciar transacción para asegurar limpieza total
    $conn->begin_transaction();

    try {
        // 1. Borrar palabras guardadas asociadas a este texto
        if (!deleteSavedWordsByTextIds($user_id, [$text_id])) {
            throw new Exception('Error borrando palabras guardadas');
        }

        // 2. Borrar progreso de lectura
        if (!deleteReadingProgressByTextIds($user_id, [$text_id])) {
            throw new Exception('Error borrando progreso de lectura');
        }

        // 3. Borrar de textos ocultos
        $stmt5 = $conn->prepare("DELETE FROM hidden_texts WHERE text_id = ? AND user_id = ?");
        $stmt5->bind_param("ii", $text_id, $user_id);
        $stmt5->execute();
        $stmt5->close();

        // 4. Finalmente, borrar el texto
        $stmt6 = $conn->prepare("DELETE FROM texts WHERE id = ? AND user_id = ?");
        $stmt6->bind_param("ii", $text_id, $user_id);
        
        if ($stmt6->execute()) {
            $conn->commit();
            $message = "Texto y todos sus datos asociados eliminados correctamente.";
        } else {
            throw new Exception("Error al eliminar el registro del texto.");
        }
        $stmt6->close();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error al eliminar el texto: " . $e->getMessage();
    }
}

$conn->close();

// Redirigir de vuelta a la lista de textos
header("Location: ../?show_my_texts=1");
exit();
?>
