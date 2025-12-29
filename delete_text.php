<?php
session_start();
require_once 'db/connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: logueo_seguridad/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
    
    // Verificar que el texto pertenece al usuario
    $stmt = $conn->prepare("DELETE FROM texts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $text_id, $user_id);
    
    if ($stmt->execute()) {
        $message = "Texto eliminado correctamente.";
    } else {
        $message = "Error al eliminar el texto.";
    }
    
    $stmt->close();
}

$conn->close();

// Redirigir de vuelta a la lista de textos
header("Location: index.php?show_my_texts=1");
exit();
?>
