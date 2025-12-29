<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo "Debes iniciar sesión.";
    exit();
}

if (!isset($_POST['word']) || !isset($_POST['translation'])) {
    echo "Faltan datos.";
    exit();
}

$user_id = $_SESSION['user_id'];
$word = $_POST['word'];
$translation = $_POST['translation'];

// Puedes guardar contexto, por ahora se deja vacío
$context = '';

$stmt = $conn->prepare("INSERT INTO saved_words (user_id, word, translation, context) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $word, $translation, $context);

if ($stmt->execute()) {
    echo "Palabra guardada correctamente.";
} else {
    echo "Error al guardar la palabra.";
}

$stmt->close();
$conn->close();


// ¿Qué hace?
// Comprueba que el usuario esté autenticado.

// Recibe word y translation desde JavaScript.

// Inserta esos datos en la tabla saved_words.