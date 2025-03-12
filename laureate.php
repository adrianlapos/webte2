<?php
session_start();
require_once('../../config.php');
$db = connectDatabase($hostname, $database, $username, $password);

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if DB is connected
if (!$db) {
    die("Database connection failed.");
}

// Get laureate ID from the URL
$laureateId = $_GET['id'] ?? null;
if ($laureateId === null) {
    die("Laureate ID is required.");
}

// Fetch laureate details
$query = "
    SELECT l.id, l.fullname, l.organisation, l.birth_year, l.death_year, p.year, p.category, c.country_name, p.details_id
    FROM laureates l
    JOIN laureates_prizes lp ON l.id = lp.laureate_id
    JOIN prizes p ON lp.prize_id = p.id
    LEFT JOIN laureates_countries lc ON l.id = lc.laureate_id
    LEFT JOIN countries c ON lc.country_id = c.id
    WHERE l.id = :id
";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $laureateId, PDO::PARAM_INT);
$stmt->execute();
$laureate = $stmt->fetch(PDO::FETCH_ASSOC);
if ($laureate['death_year'] == "0" and $laureate['fullname'] == "")
   $laureate['death_year'] = "Ešte existuje";
elseif ($laureate['death_year'] == "0" and $laureate['fullname'] != "")
   $laureate['death_year'] = "Ešte žije";
if (!$laureate) {
    die("Laureate not found.");
}


$prizeDetails = [];
if ($laureate['category'] === 'Literature') {
    $queryDetails = "
        SELECT pd.language_sk, pd.language_en, pd.genre_sk, pd.genre_en
        FROM prize_details pd
        JOIN prizes p ON pd.id = p.details_id
        JOIN laureates_prizes lp ON lp.prize_id = p.id
        WHERE lp.laureate_id = :id AND p.category = 'Literature'
    ";
    $stmtDetails = $db->prepare($queryDetails);
    $stmtDetails->bindValue(':id', $laureateId, PDO::PARAM_INT);
    $stmtDetails->execute();
    $prizeDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laureát - <?= htmlspecialchars($laureate['fullname'] ?? $laureate['organisation']) ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
        h1 {
            font-size: 2.5em;
            color: #007bff;
        }
        .laureate-details {
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .laureate-details p {
            margin: 5px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <header class="text-center mb-4">
        <h1>Detail Nobelovho Laureáta</h1>
        <nav>
            <a href="index.php" class="btn btn-link">Domov(Nobelove ceny)</a> |
            <a href="restricted.php" class="btn btn-link">Zabezpečená stránka</a>
        </nav>
    </header>

    <div class="laureate-details">
        <h3><?= htmlspecialchars($laureate['fullname'] ?? $laureate['organisation']) ?></h3>
        <p><strong>Rok:</strong> <?= htmlspecialchars($laureate['year']) ?></p>
        <p><strong>Kategória:</strong> <?= htmlspecialchars($laureate['category']) ?></p>
        <p><strong>Krajina:</strong> <?= htmlspecialchars($laureate['country_name'] ?? 'Neznáma') ?></p>
        <p><strong>Rok narodenia:</strong> <?= htmlspecialchars($laureate['birth_year']) ?></p>
        <p><strong>Rok úmrtia:</strong> <?= htmlspecialchars($laureate['death_year'] ?? 'Žije') ?></p>

        
        <?php if ($laureate['category'] === 'Literature' && !empty($prizeDetails)):
          ?>
            
            <h4>Podrobnosti o ocenení:</h4>
            <?php foreach ($prizeDetails as $detail): ?>
                <p><strong>Žáner (SK):</strong> <?= htmlspecialchars($detail['genre_sk'] ?? 'Neznámy') ?></p>
                <p><strong>Žáner (EN):</strong> <?= htmlspecialchars($detail['genre_en'] ?? 'Unknown') ?></p>
                <p><strong>Jazyk (SK):</strong> <?= htmlspecialchars($detail['language_sk'] ?? 'Neznámy') ?></p>
                <p><strong>Jazyk (EN):</strong> <?= htmlspecialchars($detail['language_en'] ?? 'Unknown') ?></p>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
