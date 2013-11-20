<?php

session_start();

if (isset($_GET['logout'])) {
    $_SESSION = array();
}

if (!isset($_SESSION['uname'])) {
    include __DIR__ . '/account.php';
    exit();
}
?>
<a href="message.php">Message Reader</a>
<br>
<a href="<?php echo $_SERVER['PHP_SELF']; ?>?logout">Logout</a>