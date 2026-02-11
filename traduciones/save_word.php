<?php
require_once __DIR__ . '/../includes/ajax_common.php';
require_once __DIR__ . '/../db/connection.php';

requireUserOrExitJson();

if (!isset($_POST['word']) || !isset($_POST['translation'])) {
    echo json_encode(['error' => 'Faltan datos.']);
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
    echo json_encode(['success' => true, 'message' => 'Palabra guardada correctamente.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar la palabra.']);
}

$stmt->close();
$conn->close();


// ¿Qué hace?
// Comprueba que el usuario esté autenticado.

// Recibe word y translation desde JavaScript.

// Inserta esos datos en la tabla saved_words.
