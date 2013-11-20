<?php

/* 
 * Include dbh.php to create a PDO database handle in $dbh
 */

$host = 'tund.cefns.nau.edu';
$database = 'jlt256';
$username = 'jlt256script';
$password = 'web212';

try {
    $dbh = new PDO('mysql:host=' . $host . ';dbname=' . $database, $username, $password);
    // Throw exceptions
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error', true, 500);
    echo 'ERROR: ' . $e->getMessage();
}
?>