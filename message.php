<?php
session_start();

// Must be logged in
if (!isset($_SESSION['uname'])) {
    header('HTTP/1.1 401 Unauthorized', true, 401);
    include __DIR__ . '/account.php';
    exit();
}

include 'dbh.php';

if (isset($_POST['id'], $_POST['action'])) {
    header('Content-type: application/json');
    $error = '';
    switch ($_POST['action']) {
        // An ajax request is sent when the user reads a message
        // Set the message to read ensuring it belongs to them
        case 'read':
            try {
                $sql = "UPDATE `messages` SET `read`=TRUE WHERE `id` = ? AND `to` = ?";
                $sth = $dbh->prepare($sql);
                $sth->execute(array($_POST['id'], $_SESSION['uname']));
            } catch (Exception $e) {
                header('HTTP/1.1 500 Internal Server Error', true, 500);
                $error = $e->getMessage();
            }
            break;
        // An ajax request is sent when the user clicks a message delete button
        case 'delete':
            try {
                $sql = "DELETE FROM `messages` WHERE `id` = ? AND `to` = ?";
                $sth = $dbh->prepare($sql);
                $sth->execute(array($_POST['id'], $_SESSION['uname']));
            } catch (Exception $e) {
                header('HTTP/1.1 500 Internal Server Error', true, 500);
                $error = $e->getMessage();
            }
            break;
    }
    echo json_encode(array('id' => $_POST['id'], 'error' => $error));
    exit();
}

// A message was sent
if (isset($_POST['send'], $_POST['to'], $_POST['subject'], $_POST['message'])) {
    try {
        $sql = "INSERT INTO `messages`(`to`, `from`, `subject`, `message`, `date`) VALUES (:to,'" . $_SESSION['uname'] . "',:subject,:message,NOW())";
        $sth = $dbh->prepare($sql);
        $sth->bindParam(':to', $_POST['to']);
        $sth->bindParam(':subject', $_POST['subject']);
        $sth->bindParam(':message', $_POST['message']);
        $sth->execute();
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        echo $e->getMessage();
    }
}

/**
 * fetchMessages returns an array of the users messages
 * @param type $dbh database handle
 * @return array user's messages
 */
function fetchMessages($dbh) {
    try {
        $sql = "SELECT * FROM messages WHERE `to`=? ORDER BY `date` DESC";
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
            if (!$msgs)
                echo '<span style="color:white">You have no messages</span>';
            // Display messages
            foreach ($msgs as $row) {
                echo '<article id="' . $row['id'] . '"';
                // == cast to boolean
                echo $row['read'] == True ? ' class="read">' : '>';
                echo '<h2><a href=#' . $row['id'] . '>From ' . $row['from'] . ': ' . $row['subject'] . '</a></h2>'
                . '<p>' . $row['message'] . '</p>'
                . '<button class="reply" type="button">Reply</button>'
                . '<button class="delete" type="button">Delete</button>'
                . '</article>';
            }
            ?>
        </section>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script>
            'use strict';
            $(document).ready(function() {
                var $prevMessage;
                var hideRead = <?php echo isset($_POST['hideRead']) ? $_POST['hideRead'] : 'false'; ?>;
                if (hideRead)
                    $('.read').hide();

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
                    $.post(window.location.pathname, {
                        'id': $(this).attr('id'),
                        'action': 'read'
                    })
                            .fail(function(jqXHR, textStatus, errorThrown) {
                                $(this).removeClass('read');
                                $prevMessage = null;
                                console.log(textStatus);
                                console.log(errorThrown);
                                console.log(jqXHR.responseText);
                            });
                });
                $('button').click(function(e) {
                    e.stopPropagation();
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
                            ject
                    }
                    switch ($(this).attr('class')) {
                        case 'reply':
                            var title = $(this).siblings('h2').text();
                            var from = title.substring('From '.length, title.indexOf(':'));
                            var subject = title.substring(title.indexOf(':') + 1, title.length);
                            $('form').show();
                            $('input[name=to]').val(from);
                            $('input[name=subject]').val('Re: ' + subject);
                            $('textarea').focus();
                            break;
                        case 'delete':
                            $.post(window.location.pathname, {
                                'id': $(this).parent().attr('id'),
                                'action': 'delete'
                            })
                                    .done(function(data) {
                                        $('#' + data['id']).remove();
                                        // Display no more messages
                                        if ($('section.accordion').children().length < 1)
                                            $('section.accordion').html('<span style="color:white">You have no messages</span>');
                                    })
                                    .fail(function(jqXHR, textStatus, errorThrown) {
                                        console.log(textStatus);
                                        console.log(errorThrown);
                                        console.log(jqXHR.responseText);
                                    });
                            break;
                    }
                });
            });
        </script>
    </body>
</html>
