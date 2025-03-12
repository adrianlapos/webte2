<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../../config.php';
require_once 'vendor/autoload.php';

use Google\Client;

$client = new Client();
// Required, call the setAuthConfig function to load authorization credentials from
// client_secret.json file. The file can be downloaded from Google Cloud Console.
$client->setAuthConfig('../../client_secret.json');
$pdo = connectDatabase($hostname, $database, $username, $password);

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);

    // Get the user profile info from Google OAuth 2.0.
    $oauth = new Google\Service\Oauth2($client);
    $account_info = $oauth->userinfo->get();

    $_SESSION['fullname'] = $account_info->name;
    $_SESSION['gid'] = $account_info->id;
    $_SESSION['email'] = $account_info->email;
}

// Handle password change request (example code)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password']) && !isset($_POST['gid'])) {
    // Validate the new password (you can add more validation rules as needed)
    if (empty($_POST['new_password'])) {
        $error = "Zadajte nové heslo!";
    } elseif (strlen($_POST['new_password']) < 6) {
        $error = "Heslo musí obsahovať aspoň 6 znakov";
    } else {
        $newPassword = $_POST['new_password'];

        // Hash the new password before updating it
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password in the database
// Replace with actual password
        $sql = "UPDATE users SET password = :password WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $_SESSION['email']);
        
        if ($stmt->execute()) {
            $success = "Heslo úspešne zmezené!";
        } else {
            $error = "Nepodarila sa zmena hesla! Skúste znova!";
        }
    }
}

?>

<!doctype html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zabezpečená stránka</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
        .filter-form select,
        .filter-form button {
            margin: 10px 0;
        }
        .filter-form label {
            margin-right: 10px;
        }
        .table-container {
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 2.5em;
            color: #007bff;
        }
        .pagination a {
            margin: 0 5px;
            color: #007bff;
        }
        th a {
            color: #007bff;
            text-decoration: none;
        }
        th a:hover {
            text-decoration: underline;
        }
        .pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 90%;
            margin: 0 auto;
        }

        .pagination a {
            margin: 5px;
            padding: 5px 10px;
            border: 1px solid #007bff;
            border-radius: 5px;
            text-decoration: none;
        }

        .pagination a:hover {
            background-color: #007bff;
            color: white;
        }

        .alert {
            margin-top: 20px;
        }

        .form-container {
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

    </style>
</head>

<body>
<?php if (isset($error)) { ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <?php if (isset($success)) { ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php } ?>

<div class="container">
    <header class="text-center mb-4">
        <h1>Zabezpečená stránka</h1>
        <p>Obsah tejto stránky je dostupný len po prihlásení.</p>
    </header>

    <div class="alert alert-info">
        <h3>Vitaj, <?php echo $_SESSION['fullname']; ?></h3>
        <p><strong>Email:</strong> <?php echo $_SESSION['email']; ?></p>

        <?php if (isset($_SESSION['gid'])) : ?>
            <p><strong>Si prihlásený cez Google účet, ID:</strong> <?php echo $_SESSION['gid']; ?></p>
        <?php else : ?>
            <p><strong>Si prihlásený cez lokálne údaje.</strong></p>
            <p><strong>Dátum vytvoria konta:</strong> <?php echo $_SESSION['created_at'] ?></p>
        <?php endif; ?>
    </div>
    <?php if (!isset($_SESSION['gid']) ): ?>
    <div class="form-container">
        <h4>Change Password</h4>
        <form method="POST" action="restricted.php">
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <button type="submit" class="btn btn-primary" name="change_password">Change Password</button>
        </form>
    </div>
<?php endif; ?>

    <p class="mt-3"><a href="logout.php" class="btn btn-danger">Odhlásenie</a> alebo <a href="index.php" class="btn btn-link">Úvodná stránka</a></p>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
