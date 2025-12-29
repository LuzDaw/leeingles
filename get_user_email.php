<?php
require 'db/connection.php';
$r = $conn->query('SELECT email FROM users LIMIT 1');
$u = $r->fetch_assoc();
echo $u['email'];
?>
