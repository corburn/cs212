<?php
session_start();

if (isset($_GET['logout'])) {
    $_SESSION = array();
}

if (!isset($_SESSION['uname'])) {
    include __DIR__ . '/account.php';
    exit();
}

include 'dbh.php';
// Count unread messages
try {
    $sql = "SELECT COUNT(id) FROM messages WHERE `read`=FALSE AND `to`=?";
    $sth = $dbh->prepare($sql);
    $sth->execute(array($_SESSION['uname']));
    $unread = $sth->fetchColumn();
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
<a href="message.php">Message Reader (<?php echo isset($unread) ? $unread : 0; ?> unread)</a>
<br>
<a href="<?php echo $_SERVER['PHP_SELF']; ?>?logout">Logout</a>