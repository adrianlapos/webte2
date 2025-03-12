<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Check if the user is already logged in, if yes then redirect him to welcome page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

require_once 'config.php';
require_once 'vendor/autoload.php';
require_once 'utilities.php';
$pdo = connectDatabase($hostname, $database, $username, $password);
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = "";

    // Validate email
    if (isEmpty($_POST['email']) === true) {
        $errors .= "Nevyplnený e-mail.\n";
    }

    // TODO: validate if user entered correct e-mail format

    // Validate user existence
    if (userExist($pdo, $_POST['email']) === true) {
        $errors .= "Používateľ s týmto e-mailom už existuje.\n";
        die();
    }

    // Vaidate name and surname
    if (isEmpty($_POST['firstname']) === true) {
        $errors .= "Nevyplnené meno.\n";
    } elseif (isEmpty($_POST['lastname']) === true) {
        $errors .= "Nevyplnené priezvisko.\n";
    }

    // TODO: Implement name and surname length validation based on the database column length.
    // TODO: Implement name and surname allowed characters validation.

    // Validate password
    if (isEmpty($_POST['password']) === true) {
        $errors .= "Nevyplnené heslo.\n";
    }

    // TODO: Implement repeat password validation.
    // TODO: Sanitize and validate all user inputs.

    if (empty($errors)) {
        $sql = "INSERT INTO users (fullname, email, password, 2fa_code) VALUES (:fullname, :email, :password, :2fa_code)";

        $fullname = $_POST['firstname'] . ' ' . $_POST['lastname'];
        $email = $_POST['email'];
        $pw_hash = password_hash($_POST['password'], PASSWORD_ARGON2ID);

        // $tfa = new TwoFactorAuth(new BaconQrCodeProvider(4, '#ffffff', '#000000', 'svg'));
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
        $user_secret = $tfa->createSecret();
        $qr_code = $tfa->getQRCodeImageAsDataUri('Nobel Prizes', $user_secret);

        // Bind parameters to SQL
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":password", $pw_hash, PDO::PARAM_STR);
        $stmt->bindParam(":2fa_code", $user_secret, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $reg_status = "Registracia prebehla uspesne.";
        } else {
            $reg_status = "Ups. Nieco sa pokazilo...";
        }

        unset($stmt);
    }
    unset($pdo);
}

?>
<!doctype html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Registrácia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Ensure the body and html fill the full viewport height */
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 100%;
            max-width: 600px;
            padding: 2em;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            text-align: center;
        }

        .form-label {
            color: #007bff;
        }

        .form-control {
            border-radius: 5px;
            border: 2px solid #007bff;
            box-shadow: inset 0 1px 3px rgba(0, 123, 255, 0.1);
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #0056b3;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            padding: 0.75rem 1.25rem;
            font-size: 1.2em;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 5px;
            padding: 1em;
            margin-top: 1em;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-radius: 5px;
            padding: 1em;
            margin-top: 1em;
        }

        .text-center {
            text-align: center;
        }

        .mt-3 {
            margin-top: 1em;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Registrácia</h1>
            <h2>Vytvorenie nového používateľského konta</h2>
        </header>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <?php if (isset($reg_status)) {
                echo "<div class='alert alert-success'>$reg_status</div>";
            } ?>

            <div class="mb-3">
                <label for="firstname" class="form-label">Meno:</label>
                <input type="text" name="firstname" value="" id="firstname" placeholder="napr. John" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="lastname" class="form-label">Priezvisko:</label>
                <input type="text" name="lastname" value="" id="lastname" placeholder="napr. Doe" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail:</label>
                <input type="email" name="email" value="" id="email" placeholder="napr. johndoe@example.com" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Heslo:</label>
                <input type="password" name="password" value="" id="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Vytvoriť konto</button>

            <?php
            if (!empty($errors)) {
                echo "<div class='mt-3 alert alert-danger'>";
                echo nl2br($errors);
                echo "</div>";
            }
            if (isset($qr_code)) {
                // If a QR code was generated after successful registration, display it.
                $message = '<p>Zadajte kód: ' . $user_secret . ' do aplikácie pre 2FA</p>';
                $message .= '<p>alebo naskenujte QR kód:<br><img src="' . $qr_code . '" alt="qr kod pre aplikaciu authenticator"></p>';
                echo $message;
                echo '<p>Teraz sa môžete prihlásiť: <a href="login.php">Login stránka</a></p>';
            }
            ?>

        </form>
        <p class="text-center">Už máte vytvorené konto? <a href="login.php">Prihláste sa tu.</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

