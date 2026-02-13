<?php
require '../db/connection.php';
require_once '../includes/user_functions.php';
$email = get_first_user_email($conn);
echo $email !== null ? $email : '';
?>
