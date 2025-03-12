<?php
session_start();
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Check if the user is already logged in, if yes then redirect him to restricted page.
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: restricted.php");
    exit;
}

require_once "../../config.php";
require_once 'vendor/autoload.php';
require_once 'utilities.php';

use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

// Redirect users to oauth2call.php which redirects users to Google OAuth 2.0
$redirect_uri = "https://node75.webte.fei.stuba.sk/uloha1/oauth2callback.php";
$pdo = connectDatabase($hostname, $database, $username, $password);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // TODO: Implement login credentials verification.
    // TODO: Implement a mechanism to save login information - user_id, login_type, email, fullname - to database.

    $sql = "SELECT id, fullname, email, password, 2fa_code, created_at FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
    $errors = "";

    if ($stmt->execute()) {
        if ($stmt->rowCount() == 1) {
            // User exists, check password.
            $row = $stmt->fetch();
            $hashed_password = $row["password"];

            if (password_verify($_POST['password'], $hashed_password)) {
                // Password is correct.
                $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
                if ($tfa->verifyCode($row["2fa_code"], $_POST['2fa'], 2)) {
                    // Password and code are correct, user authenticated.

                    // Save user data to session.
                    $_SESSION["loggedin"] = true;
                    $_SESSION["fullname"] = $row['fullname'];
                    $_SESSION["email"] = $row['email'];
                    $_SESSION["created_at"] = $row['created_at'];

                    // Redirect user to restricted page.
                    header("location: restricted.php");
                } else {
                    $errors = "Neplatný kód 2FA.";
                }
            } else {
                $errors = "Nesprávne meno alebo heslo.";
            }
        } else {
            $errors = "Nesprávne meno alebo heslo.";
        }
    } else {
        $errors = "Ups. Niečo sa pokazilo... Skúste to neskôr.";
    }

    unset($stmt);
    unset($pdo);
}

?>

<!doctype html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Prihlásenie</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            max-width: 100%;
            width: 90%;
            max-width: 400px;
        }
        .form-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 2em;
            color: #007bff;
        }
        .btn-google {
            background-color: #db4437;
            color: white;
            border-radius: 5px;
        }
        .btn-google:hover {
            background-color: #c1351d;
        }
        .alert {
            margin-top: 15px;
        }
        .guest-button {
            background-color: #6c757d;
            color: white;
            border-radius: 5px;
            text-align: center;
        }
        .guest-button:hover {
            background-color: #5a6268;
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="form-container">
        <h1 class="text-center">Prihlásenie</h1>
        <h3 class="text-center text-muted">Prihlásenie registrovaného používateľa</h3>

        <?php if (isset($errors) && !empty($errors)) { ?>
            <div class="alert alert-danger">
                <?php echo $errors; ?>
            </div>
        <?php } ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="email">E-Mail</label>
                <input type="text" name="email" id="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Heslo</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="2fa">2FA kód</label>
                <input type="number" name="2fa" id="2fa" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Prihlásiť sa</button>
        </form>

        <p class="text-center mt-3">Alebo sa prihláste pomocou 
            <a href="<?php echo filter_var($redirect_uri, FILTER_SANITIZE_URL) ?>" class="btn btn-google btn-block">
                Google konta
            </a>
        </p>

        <p class="text-center mt-2">Nemáte vytvorené konto? <a href="register.php">Zaregistrujte sa tu.</a></p>

        <!-- Continue as Guest button -->
        <form action="index.php" method="get" class="mt-3">
            <button type="submit" class="btn guest-button btn-block">Pokračovať ako hosť</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
