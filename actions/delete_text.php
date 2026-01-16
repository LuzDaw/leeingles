<?php
session_start();
require_once '../db/connection.php';

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
        $stmt1 = $conn->prepare("DELETE FROM saved_words WHERE text_id = ? AND user_id = ?");
        $stmt1->bind_param("ii", $text_id, $user_id);
        $stmt1->execute();
        $stmt1->close();

        // 2. Borrar progreso de práctica asociado
        $stmt2 = $conn->prepare("DELETE FROM practice_progress WHERE text_id = ? AND user_id = ?");
        $stmt2->bind_param("ii", $text_id, $user_id);
        $stmt2->execute();
        $stmt2->close();

        // 3. Borrar tiempos de lectura asociados
        $stmt3 = $conn->prepare("DELETE FROM reading_time WHERE text_id = ? AND user_id = ?");
        $stmt3->bind_param("ii", $text_id, $user_id);
        $stmt3->execute();
        $stmt3->close();

        // 4. Borrar progreso de lectura
        $stmt4 = $conn->prepare("DELETE FROM reading_progress WHERE text_id = ? AND user_id = ?");
        $stmt4->bind_param("ii", $text_id, $user_id);
        $stmt4->execute();
        $stmt4->close();

        // 5. Borrar de textos ocultos
        $stmt5 = $conn->prepare("DELETE FROM hidden_texts WHERE text_id = ? AND user_id = ?");
        $stmt5->bind_param("ii", $text_id, $user_id);
        $stmt5->execute();
        $stmt5->close();

        // 6. Finalmente, borrar el texto
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
    
    $stmt->close();
}

$conn->close();

// Redirigir de vuelta a la lista de textos
header("Location: ../index.php?show_my_texts=1");
exit();
?>
