<?php

session_start();

if (!isset($_SESSION['uname'])) {
    include __DIR__ . '/account.php';
    exit();
}
echo 'Hello';
?>
