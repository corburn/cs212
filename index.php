<?php

session_start();

if (!isset($_SESSION['uname'])) {
    include __DIR__ . '/account.php';
    exit();
}
?>
<a href="message.php">Message Reader</a>