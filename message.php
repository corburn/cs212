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
        $sql = "UPDATE messages SET read=True WHERE 'id' = ? AND 'to' = ?";
        $sth = $dbh->prepare($sql);
        $sth->execute(array($_POST['read'], $_SESSION['uname']));
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        echo $e->getMessage();
    }
    exit();
}

// A message was sent
if (isset($_POST['send'], $_POST['to'], $_POST['subject'], $_POST['message'])) {
    
}

/**
 * fetchMessages returns an array of the users messages
 * @param type $dbh database handle
 * @return array user's messages
 */
function fetchMessages($dbh) {
    try {
        $sql = "SELECT * FROM messages WHERE 'to'=?";
        $sth = $dbh->prepare($sql);
        $sth->execute(array($_SESSION['uname']));
        return $sth->fetchAll(PDO::FETCH_ASSOC);
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
        <button id="compose" type="button">Compose</button>
        <button id="hideRead" type="button"><?php echo (isset($_POST['hideRead']) and $_POST['hideRead'] === 'true') ? 'Display Read' : 'Hide Read'; ?></button>
        <form method="POST">
            <fieldset>
                <legend>Compose</legend>
                <label for="to">To<input name="to" type="text" required></label>
                <label for="subject">Subject<input name="subject" type="text" required></label>
                <label for="message">Message<textarea name="message" required></textarea></label>
                <input type="hidden" name="hideRead" value="<?php echo isset($_POST['hideRead']) ? $_POST['hideRead'] : 'false'; ?>">
                <button type="submit" name="send">Send</button>
            </fieldset>
        </form>
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
                var $prevMessage;
                var hideRead = <?php echo isset($_POST['hideRead']) ? $_POST['hideRead'] : 'false'; ?>;
                if(hideRead) $('.read').hide();
                
                // Hide the form with javascript so the page will still work if
                // javascript is disabled
                $('form').hide();

                $('article').click(function() {
                    // If read messages are hidden, wait to hide this one until
                    // the next message is selected
                    if ($prevMessage && hideRead)
                        $prevMessage.hide();
                    $prevMessage = $(this);
                    $(this).addClass('read');
                    // Set the message to read and update the database
                    /*$.post(window.location.pathname, {
                     'read': $(this).attr('id')
                     })
                     .fail(function(jqXHR, textStatus, errorThrown) {
                     $(this).removeClass('read');
                     $(this).show();
                     console.log(textStatus);
                     console.log(errorThrown);
                     console.log(jqXHR.responseText);
                     });*/
                });
                $('button').click(function() {
                    switch ($(this).attr('id')) {
                        case 'compose':
                            $('form').toggle('slow');
                            break;
                        case 'hideRead':
                            hideRead = !hideRead;
                            $(this).text(hideRead ? 'Display Read' : 'Hide Read');
                            hideRead ? $('.read').hide() : $('.read').show();
                            $('input[name=hideRead]').val(hideRead);
                            break;
                    }
                });
            });
        </script>
    </body>
</html>
