<?php

class ContactForm {

    // $error is an array of 
    var $name_file, $subject_file, $message_file, $insult_file;
    var $my_email, $errors;

    public function __construct() {
        $this->my_email = 'jlt256@nau.edu';
        $this->name_file = 'names.txt';
        $this->subject_file = 'excuses.txt';
        $this->message_file = 'deepThoughts.many.txt';
        $this->insult_file = 'insults.txt';
    }

    // Returns a randomly generated insult from the list of Shakspearean insults
    // PHP 5.3 doesn't support direct function dereferencing
    function generateInsult($insults) {
        $insult[0] = explode(' ', $insults[array_rand($insults)]);
        $insult[0] = $insult[0][0];
        $insult[1] = explode(' ', $insults[array_rand($insults)]);
        $insult[1] = $insult[1][1];
        $insult[2] = explode(' ', $insults[array_rand($insults)]);
        $insult[2] = $insult[2][2];
        return 'Thou ' . implode(' ', $insult) . '!';
    }

    // Returns true if the input appears to be valid, although it not guaranteed.
    // Sets the error array prompts with Victorian style insults indicating what
    // is wrong with each field
    function isValid($input, $insult_file, &$error) {
        $isValid = true;

        // Load the list of Shakespearean insults into memory
        // This is not in the constructor to avoid an unecessary load during GET requests
        $insults = file($insult_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (empty($input['name'])) {
            $error['name'] = '<p class="error">Hast thou forgotten thine name? ' . $this->generateInsult($insults) . '</p>';
            $isValid = false;
        }
        if (empty($input['email'])) {
            $error['email'] = '<p class="error">How shalt I answer thee without an email? ' . $this->generateInsult($insults) . '</p>';
            $isValid = false;
        } else if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $error['email'] = '<p class="error">Fie! Methinks thou art false of thine email. ' . $this->generateInsult($insults) . '</p>';
            $isValid = false;
        }
        if (empty($input['subject'])) {
            $error['subject'] = '<p class="error">Prithee, what is the subject? ' . $this->generateInsult($insults) . '</p>';
            $isValid = false;
        }
        if (empty($input['message'])) {
            $error['message'] = '<p class="error">You speak an infinite deal of nothing without a message. ' . $this->generateInsult($insults) . '</p>';
            $isValid = false;
        }
        return $isValid;
    }

    // Display the form
    function display($input, $name_file, $subject_file, $message_file, $errors) {
        // Select a random name
        $names = file($name_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $name = $names[array_rand($names)];

        // Create an email address based on the name
        $email = str_replace(' ', '.', $name) . '@example.com';

        // Select a random excuse
        $excuses = file($subject_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $excuse = $excuses[array_rand($excuses)];
        
        // Select a random quote
        $thoughts = explode("\n\n", file_get_contents($message_file));
        $thought = str_replace(array("\n", "\t"), ' ', $thoughts[array_rand($thoughts)]);
        
        echo '
        <!-- http://listofrandomnames.com/index.cfm -->
        <!-- http://pages.cs.wisc.edu/~ballard/bofh/excuses -->
        <!-- http://www.radford.edu/~ibarland/Public/Humor/deepThoughts.many -->
        <!-- http://www.ariel.com.au/jokes/Shakespearean_Insults.html -->
        <form class="center A4" name="feedback" method="POST" action="' . $_SERVER['PHP_SELF'] . '">
            <fieldset>
                <legend>Send a Message</legend>
                <label for="name">Name<input id="name" type="text" name="name" value="' . (isset($input['name']) ? htmlspecialchars($input['name'], ENT_QUOTES, 'UTF-8') : '') . '" placeholder="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"></label>' .
        (isset($errors['name']) ? $errors['name'] : '') .
        '<label for="email">Email<input id="email" type="email" name="email" value="' . (isset($input['email']) ? htmlspecialchars($input['email'], ENT_QUOTES, 'UTF-8') : '') . '" placeholder="' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '"></label>' .
        (isset($errors['email']) ? $errors['email'] : '') .
        '<label for="subject">Subject<input id="subject" type="text" name="subject" value="' . (isset($input['subject']) ? htmlspecialchars($input['subject'], ENT_QUOTES, 'UTF-8') : '') . '" placeholder="' . htmlspecialchars($excuse, ENT_QUOTES, 'UTF-8') . '"></label>' .
        (isset($errors['subject']) ? $errors['subject'] : '') .
        '<label for="message">Message<textarea id="message" name="message" placeholder="' . htmlspecialchars($thought, ENT_QUOTES, 'UTF-8') . '">' . (isset($input['message']) ? htmlspecialchars($input['message'], ENT_NOQUOTES, 'UTF-8') : '') . '</textarea></label>' .
        (isset($errors['message']) ? $errors['message'] : '') .
        '<select name="type">
            <option value="Unspecified">Unspecified</option>
            <option value="Complaint">Complaint</option>
            <option value="Question">Question</option>
            <option value="Suggestion">Suggestion</option>
            <option value="Praise">Praise</option>
         </select>
         <input type="submit" value="Submit">
         </fieldset>
         </form>';
    }

    // Send message to the email provided by the user, a BCC to $my_email, and
    // display the message with a link back to the form indicating success.
    function sendAndDisplayMessage($input, $my_email) {
        $message = "Hello " . htmlspecialchars($input['name'], ENT_NOQUOTES) . ",\n" .
                'Your message was sent ' . date('c') . "\n\n" .
                "Subject:\n" .
                htmlspecialchars($input['subject'], ENT_NOQUOTES) . "\n" .
                "Type:\n" .
                htmlspecialchars($input['type'], ENT_NOQUOTES) . "\n" .
                "Message:\n" .
                htmlspecialchars($input['message'], ENT_NOQUOTES);

        // To send HTML mail, the Content-type header must be set
        // When these headers are set, newline characters do not display in Gmail
        //$headers = 'MIME-Version: 1.0' . "\r\n";
        //$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        // Additional headers
        $headers .= 'From: DoNotReply@' . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= 'Bcc: ' . $my_email . "\r\n";

        // Mail it
        mail($input['email'], $input['subject'], $message, $headers);
        echo str_replace("\n", '<br>', $message);
        echo '<p><a href="' . $_SERVER['PHP_SELF'] . '">Send another message</a></p>';
    }

}
?>
<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">
    <style>
        body {
            background: #fafafa;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #333;
        }

        input, textarea {
            display:block;
            width:100%;
            padding:6px;
            margin-bottom:1em;
        }

        input[type="submit"] {
            width:auto;
            background-color:#6C4;
            color:white;
            text-shadow: 0.1em 0.1em .2em black;
        }

        input {
            border-radius: 5px;
        }

        fieldset {
            padding:1em;
            padding-right: 2em;
            border-radius: 20px;
            background-image:linear-gradient(135deg, white, orange);
        }

        .center {
            margin:auto;
        }

        /* An A4 sheet of paper is approximately 21cm wide */
        .A4 {
            max-width:21cm;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>
    <?php
    $form = new ContactForm();
    if ($_SERVER['REQUEST_METHOD'] === 'GET' || !$form->isValid($_POST, $form->insult_file, $form->errors)) {
        $form->display($_POST, $form->name_file, $form->subject_file, $form->message_file, $form->errors);
    } else {
        $form->sendAndDisplayMessage($_POST, $form->my_email);
    }
    ?>
</body>
</html>
