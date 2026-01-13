<?php
require_once __DIR__ . '/../../db/connection.php';
$res = $conn->query("DESCRIBE user_subscriptions");
echo "Table: user_subscriptions\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
$res = $conn->query("SELECT * FROM user_subscriptions ORDER BY created_at DESC LIMIT 5");
echo "\nLast 5 subscriptions:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
