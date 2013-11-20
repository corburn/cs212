<?php
/*
 * account.php is a login, register, and user info update form all-in-one.
 * If the user is authenticated it displays a user info form with the fields
 * filled with the current user info, otherwise it displays a login form that
 * can toggle to a register form.
 */

$authenticated = isset($_SESSION['uname']);
$title = $authenticated ? "Userinfo" : "Login";

if ($authenticated) {
    // The user is logged in, fetch their information to autofill the form
    include __DIR__ . '/dbh.php';
    try {
        $sql = "SELECT * FROM users WHERE uname=? and active=1";
        $sth = $dbh->prepare($sql);
        $sth->execute(array($_SESSION['uname']));
        $user = $sth->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        echo $e->getMessage();
    }
}

if (isset($_POST['action'], $_POST['uname'], $_POST['password'])) {
    // The form was submitted
    include_once __DIR__ . '/dbh.php';
    switch ($_POST['action']) {
        case 'Login':
            $error = login($dbh, $_POST['uname'], $_POST['password']);
            break;
        case 'Register':
            $error = register($dbh, $_POST);
            break;
        case 'Update':
            $error = update($dbh, $_POST);
            break;
    }
    // Return from whence you came
    if (empty($error))
        header('Location: ' . $_SERVER['PHP_SELF']);
}

/**
 * hashPassword returns a Blowfish hash of the given password
 * @param string $password password to hash
 * @return string Blowfish hashed password
 */
function hashPassword($password) {
    return crypt($password, '$2a$');
}

/**
 * verifyPassword checks if a password matches a hashed password
 * @param string $password password
 * @param string $passHash hashed pasword
 * @return boolean true if the given password matches the hashed password
 */
function verifyPassword($password, $passHash) {
    return crypt($password, $passHash) === $passHash;
}

/**
 * login sets the $_SESSION keys if the credentials are valid and the account is
 * active
 * @param object $dbh database handle
 * @param string $uname username
 * @param string $password password
 * @return string error message
 */
function login($dbh, $uname, $password) {
    try {
        $sql = "SELECT phash, admin FROM users WHERE uname=? and active=1";
        $sth = $dbh->prepare($sql);
        $sth->execute(array($uname));
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        if (!result)
        // No such user
            return 'There was a problem with the username or password';
        if (verifyPassword($password, $result['phash'])) {
            // Set username key
            $_SESSION['uname'] = $uname;
            if ($result['admin'] === True)
            // Set admin key
                $_SESSION['admin'] = True;
        } else {
            // Invalid password
            return 'There was a problem with the username or password';
        }
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * register inserts a new user in the database
 * The uname field is a unique key and the query will fail if a duplicate is inserted
 * @param object $dbh database handle
 * @param array $user an associative array for the users table
 * @return boolean
 */
function register($dbh, array $user) {
    if (!isset($user['fname'], $user['lname'], $user['uname'], $user['password'], $user['confirmPassword']))
        return 'Missing fields';
    if ($user['password'] !== $user['confirmPassword'])
        return 'Passwords do not match';
    try {
        $sql = "INSERT INTO users (fname, lname, email, uname, phash) VALUES (:fname, :lname, :email, :uname, :phash)";
        $sth = $dbh->prepare($sql);
        $sth->bindValue(':fname', $user['fname']);
        $sth->bindValue(':lname', $user['lname']);
        $sth->bindValue(':email', $user['email']);
        $sth->bindValue(':uname', $user['uname']);
        $sth->bindValue(':phash', hashPassword($user['password']));
        $sth->execute();
        $_SESSION['uname'] = $user['uname'];
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        return 'ERROR: ' . $e->getMessage();
    }
}

function update($dbh, array $user) {
    // Fill form with existing user information
    // Verify new password
    if ($user['password'] !== $user['confirmPassword']) {
        return 'Passwords do not match';
    }
    // Get old password hash
    try {
        $sql = 'SELECT phash FROM users WHERE uname = ?';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($user['uname']));
        $phash = $sth->fetchColumn();
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        return 'ERROR: ' . $e->getMessage();
    }
    // Verify old password
    if (!verifyPassword($user['oldPassword'], $phash)) {
        return 'Incorrect password';
    }
    // Update database with new values
    try {
        $sql = 'UPDATE users SET fname=:fname, lname=:lname, email=:email, phash=:phash WHERE uname = :uname';
        $sth = $dbh->prepare($sql);
        $sth->bindValue(':fname', $user['fname']);
        $sth->bindValue(':lname', $user['lname']);
        $sth->bindValue(':email', $user['email']);
        $sth->bindValue(':phash', hashPassword($user['newpass']));
        $sth->bindValue(':uname', $uname);
        $sth->execute();
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        return 'ERROR: ' . $e->getMessage();
    }
    // Update session username in case it was changed
    $_SESSION['uname'] = $user['uname'];
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $title; ?></title>
        <link rel="stylesheet" type="text/css" href="style.css">
        <link rel="stylesheet" type="text/css" href="account.css">
        <?php if ($authenticated) echo '<style type="text/css">input.hide { display: block; }</style>'; ?>
    </head>

    <body>
        <form method="POST">
            <h1><?php echo $title; ?></h1>
            <fieldset id="inputs">
                <input name="uname" class="user" type="text" placeholder="Username" <?php echo $authenticated ? 'value="' . $user['uname'] . '" disabled' : ''; ?> autofocus required>
                <input name="fname" class="user hide" type="text" placeholder="First Name" value="<?php echo $authenticated ? $user['fname'] : ''; ?>">
                <input name="lname" class="user hide" type="text" placeholder="Last Name" value="<?php echo $authenticated ? $user['lname'] : ''; ?>">
                <input name="email" class="user hide" type="text" placeholder="Email" value="<?php echo $authenticated ? $user['email'] : ''; ?>">
                <?php if ($authenticated) echo '<input name="oldPassword" class="key" type="password" placeholder="Old Password" required'; ?>
                <input name="password" class="key" type="password" placeholder="Password" required>
                <input name="confirmPassword" class="key hide" type="password" placeholder="Confirm Password">
            </fieldset>
            <fieldset id="actions">
                <?php
                // If authenticated display a 'Update' button, otherwise display
                // a 'Login' button with register and recover password links 
                echo $authenticated ? '<input name="action" type="submit" id="submit" value="Update">' : '<input name="action" type="submit" id="submit" value="Login"><a href="#">Forgot your password?</a><a name="change" href="#">Register</a>';
                ?>
            </fieldset>
            <a href="http://www.red-team-design.com/slick-login-form-with-html5-css3" id="back">Back to article...</a>
            <?php echo isset($error) ? $error : ''; ?>
        </form>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script>
            'use strict';
            $(document).ready(function() {
<?php if ($authenticated): ?>
                    $(input.hide).attr(\'required\', true);'
<?php else: ?>
                    // Toggle between Login and Register form
                    $('a[name=change]').click(function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var login = 'Login', register = 'Register';
                        // Registration requires confirmPassword input and shows
                        // additional fields
                        if ($(this).text() === register) {
                            $(this).text(login);
                            $('h1').text(register);
                            $('input[type=submit]').val(register);
                            $('input.hide').attr('required', true).show();
                        } else {
                            $(this).text(register);
                            $('h1').text(login);
                            $('input[type=submit]').val(login);
                            $('input.hide').attr('required', false).hide();
                        }
                    });
<?php endif; ?>
            });
        </script>
    </body>
</html>

