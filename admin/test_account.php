<?php
session_start();
require_once '../db/connection.php';
// Usar el ID de un usuario existente del dump SQL (ej: 74 o 108)
$_SESSION['user_id'] = 74; 
$_SESSION['username'] = 'luz';

// Redirigir a la pestaña de cuenta
header('Location: ../index.php?tab=account');
exit;
?>
