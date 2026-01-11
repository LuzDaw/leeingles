<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/subscription_functions.php';

session_start();

echo "<h1>Debug de Base de Datos</h1>";
echo "Base de datos conectada: " . (isset($conn) ? "SÍ" : "NO") . "<br>";

if (isset($conn)) {
    $res = $conn->query("SELECT DATABASE()");
    $row = $res->fetch_row();
    echo "Nombre de la BD: " . $row[0] . "<br>";

    echo "<h2>Tablas existentes:</h2>";
    $res = $conn->query("SHOW TABLES");
    while ($row = $res->fetch_array()) {
        echo "- " . $row[0] . "<br>";
    }

    echo "<h2>Estructura de user_subscriptions:</h2>";
    $res = $conn->query("DESCRIBE user_subscriptions");
    if ($res) {
        echo "<table border='1'><tr><th>Campo</th><th>Tipo</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "ERROR: No se pudo describir la tabla user_subscriptions. ¿Existe?<br>";
    }

    echo "<h2>Datos del usuario actual (ID: " . ($_SESSION['user_id'] ?? 'N/A') . "):</h2>";
    if (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $res = $conn->query("SELECT * FROM users WHERE id = $uid");
        $user = $res->fetch_assoc();
        echo "<pre>"; print_r($user); echo "</pre>";

        echo "<h3>Estado de suscripción (getUserSubscriptionStatus):</h3>";
        $status = getUserSubscriptionStatus($uid);
        echo "<pre>"; print_r($status); echo "</pre>";

        echo "<h3>Registros en user_subscriptions para este usuario:</h3>";
        $res = $conn->query("SELECT * FROM user_subscriptions WHERE user_id = $uid");
        while ($row = $res->fetch_assoc()) {
            echo "<pre>"; print_r($row); echo "</pre>";
        }
    }
}
?>
