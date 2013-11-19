<?php
session_start();

// Must be logged in
if (!isset($_SESSION['uname'])) {
    header('HTTP/1.1 401 Unauthorized', true, 401);
    include __DIR__ . '/account.php';
    exit();
}

include 'dbh.php';

// An ajax request is sent when the user reads a message
// Set the message to read ensuring it belongs to them
if (isset($_POST['read'])) {
    try {
        $sql = "UPDATE messages SET read=True WHERE id = ? AND 'to' = ?";
        $sth = $dbh->prepare($sql);
        $sth->execute(array($_POST['read'], $_SESSION['uname']));
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        echo json_encode($e->getMessage());
    }
    exit();
}

function fetchMessages($dbh) {
    try {
        $sql = "SELECT * FROM messages WHERE 'to'=?";
        $sth = $dbh->prepare($sql);
        $sth->execute(array($_SESSION['uname']));
        return $sth->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Message Reader</title>
        <link rel="stylesheet" type="text/css" href="style.css">
        <link rel="stylesheet" type="text/css" href="message.css">
    </head>
    <body>
        <button id="compose" type="button">Compose</button><button id="displayRead" type="button">Hide Read</button>
        <section class="accordion">
            <?php
            $msgs = fetchMessages($dbh);
            foreach ($msgs as $row) {
                echo '<article id="#' . $row['id'] . '"';
                echo $row['read'] === True ? ' class="read">' : '>';
                echo '<h2>' . $row['subject'] . '</h2>'
                . '<p>' . $row['message'] . '</p>'
                . '</article>';
            }
            ?>
            <article id="acc1">
                <h2><a href="#acc1">Title One</a></h2>
                <p>This content appears on page 1.</p>
            </article>

            <article id="acc2">
                <h2><a href="#acc2">Title Two</a></h2>
                <p>This content appears on page 2.</p>
            </article>

            <article id="acc3" class="read">
                <h2><a href="#acc3">Title Three</a></h2>
                <p>This content appears on page 3.</p>
            </article>

            <article id="acc4">
                <h2><a href="#acc4">Title Four</a></h2>
                <p>This content appears on page 4.</p>
            </article>

            <article id="acc5">
                <h2><a href="#acc5">Title Five</a></h2>
                <p>This content appears on page 5.</p>
            </article>
        </section>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script>
            $(document).ready(function() {
                $('article').click(function() {
                    // Set the message to read and update the database
                    $(this).addClass('read');
                    $.post('url', data).fail(function() {
                        $(this).removeClass('read');
                        console.log(data);
                    });
                });
                $('button').click(function() {
                    switch($(this).attr('id')) {
                        case 'compose':
                            break;
                        case 'displayRead':
                            $(this).text($(this).text() === 'Hide Read' ? 'Display Read' : 'Hide Read');
                            $('.read').toggle();
                            break;
                    }
                });
            });
        </script>
    </body>
</html>
