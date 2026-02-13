/**
 * Obtiene el email de un usuario (el primero encontrado).
 * @param mysqli $conn
 * @return string|null
 */
function get_first_user_email($conn) {
    $r = $conn->query('SELECT email FROM users LIMIT 1');
    if ($r && $u = $r->fetch_assoc()) {
        return $u['email'];
    }
    return null;
}
<?php
// includes/user_functions.php
// Funciones relacionadas con operaciones de usuario (borrado, suspensión, etc.)

/**
 * Elimina de forma atómica todos los datos relacionados con un usuario.
 * Devuelve ['success'=>true] o ['success'=>false,'error'=>...]
 */
function delete_user_account($conn, $user_id) {
    try {
        $conn->begin_transaction();

        $tables = [
            'saved_words' => "DELETE FROM saved_words WHERE user_id = ?",
            'practice_progress' => "DELETE FROM practice_progress WHERE user_id = ?",
            'practice_time' => "DELETE FROM practice_time WHERE user_id = ?",
            'reading_progress' => "DELETE FROM reading_progress WHERE user_id = ?",
            'reading_time' => "DELETE FROM reading_time WHERE user_id = ?",
            'hidden_texts' => "DELETE FROM hidden_texts WHERE user_id = ?",
            'uso_traducciones' => "DELETE FROM uso_traducciones WHERE user_id = ?",
            'user_subscriptions' => "DELETE FROM user_subscriptions WHERE user_id = ?",
            'verificaciones_email' => "DELETE FROM verificaciones_email WHERE id_usuario = ?",
            'texts' => "DELETE FROM texts WHERE user_id = ?",
        ];

        foreach ($tables as $name => $sql) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Borrar el registro principal del usuario
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if (!$stmt) throw new Exception('Error preparando delete user: ' . $conn->error);
        $stmt->bind_param('i', $user_id);
        if (!$stmt->execute()) throw new Exception('Error eliminando usuario: ' . $stmt->error);
        $stmt->close();

        $conn->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

?>
