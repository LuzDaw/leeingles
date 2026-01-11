<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/subscription_functions.php';

echo "<h1>Checking User 110</h1>";
$uid = 110;

$res = $conn->query("SELECT * FROM users WHERE id = $uid");
$user = $res->fetch_assoc();
echo "<h2>User Data:</h2><pre>"; print_r($user); echo "</pre>";

$status = getUserSubscriptionStatus($uid);
echo "<h2>Status from Function:</h2><pre>"; print_r($status); echo "</pre>";

$res = $conn->query("SELECT * FROM user_subscriptions WHERE user_id = $uid");
echo "<h2>Subscriptions in DB:</h2>";
while ($row = $res->fetch_assoc()) {
    echo "<pre>"; print_r($row); echo "</pre>";
}
?>
