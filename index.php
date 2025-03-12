<?php
session_start();
require_once('../../config.php');
$db = connectDatabase($hostname, $database, $username, $password);

// Check if the user is logged in
$logged_in_status = "Not logged in";  // Default message

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if (isset($_SESSION["email"])) {
        // Google login: Display email
        $logged_in_status = "Prihlásený: " . $_SESSION["email"];
    } else if (isset($_SESSION["fullname"])) {
        // Database login: Display username
        $logged_in_status = "Prihlásený: " . $_SESSION["fullname"];
    }
}


// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if DB is connected
if (!$db) {
    die("Database connection failed.");
}

// Get available years and categories
$years = $db->query("SELECT DISTINCT year FROM prizes ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
$categories = $db->query("SELECT DISTINCT category FROM prizes ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Process filters and sorting
$filterYear = $_GET['year'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$perPage = $_GET['per_page'] ?? 10; // Default to 10 records per page
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $perPage;

// Sorting parameters
$sortColumn = $_GET['sort_column'] ?? 'priezvisko'; // Default to 'priezvisko'
$sortDirection = $_GET['sort_direction'] ?? 'ASC'; // Default to ascending

// Count total records for pagination
$countQuery = "SELECT COUNT(*) FROM laureates l
    JOIN laureates_prizes lp ON l.id = lp.laureate_id
    JOIN prizes p ON lp.prize_id = p.id
    LEFT JOIN laureates_countries lc ON l.id = lc.laureate_id
    LEFT JOIN countries c ON lc.country_id = c.id
    WHERE 1=1";
$params = [];
if (!empty($filterYear)) {
    $countQuery .= " AND p.year = :year";
    $params[':year'] = $filterYear;
}
if (!empty($filterCategory)) {
    $countQuery .= " AND p.category = :category";
    $params[':category'] = $filterCategory;
}
$totalRecordsStmt = $db->prepare($countQuery);
$totalRecordsStmt->execute($params);
$totalRecords = $totalRecordsStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Fetch laureates with pagination and sorting
$query = "
    SELECT l.id, COALESCE(NULLIF(l.fullname, ''), NULLIF(l.organisation, '')) AS priezvisko,
           p.year, p.category, c.country_name
    FROM laureates l
    JOIN laureates_prizes lp ON l.id = lp.laureate_id
    JOIN prizes p ON lp.prize_id = p.id
    LEFT JOIN laureates_countries lc ON l.id = lc.laureate_id
    LEFT JOIN countries c ON lc.country_id = c.id
    WHERE 1=1";
if (!empty($filterYear)) {
    $query .= " AND p.year = :year";
}
if (!empty($filterCategory)) {
    $query .= " AND p.category = :category";
}

// Dynamic sorting
$query .= " ORDER BY $sortColumn $sortDirection LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
if (!empty($filterYear)) {
    $stmt->bindValue(':year', $filterYear, PDO::PARAM_INT);
}
if (!empty($filterCategory)) {
    $stmt->bindValue(':category', $filterCategory, PDO::PARAM_STR);
}
$stmt->execute();
$laureates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nobelovi laureáti</title>
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
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>

<div class="container">
    <header class="text-center mb-4">
        <h1>Nobelovi laureáti</h1>
        <nav>
            <div>
                <a href="index.php" class="btn btn-link">Domov</a> |
                <a href="restricted.php" class="btn btn-link">Zabezpečená stránka</a>
            </div>
            <div class="text-right">
                <span><?= htmlspecialchars($logged_in_status) ?></span>
            </div>
        </nav>
    </header>

    <form method="GET" class="filter-form">
        <div class="form-row justify-content-center">
            <div class="col-md-3">
                <label for="year">Vyber rok:</label>
                <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                    <option value="">Všetky roky</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>" <?= ($filterYear == $year) ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="category">Vyber kategóriu:</label>
                <select name="category" id="category" class="form-control" onchange="this.form.submit()">
                    <option value="">Všetky kategórie</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category ?>" <?= ($filterCategory == $category) ? 'selected' : '' ?>><?= $category ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="per_page">Počet záznamov:</label>
                <select name="per_page" id="per_page" class="form-control" onchange="this.form.submit()">
                    <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10</option>
                    <option value="20" <?= ($perPage == 20) ? 'selected' : '' ?>>20</option>
                    <option value="<?= $totalRecords ?>" <?= ($perPage == $totalRecords) ? 'selected' : '' ?>>Všetky</option>
                </select>
            </div>
        </div>
    </form>

    <div class="table-container">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>
                        <a href="?page=<?= $page ?>&year=<?= $filterYear ?>&category=<?= $filterCategory ?>&per_page=<?= $perPage ?>&sort_column=priezvisko&sort_direction=<?= ($sortColumn == 'priezvisko' && $sortDirection == 'ASC') ? 'DESC' : 'ASC' ?>">Priezvisko</a>
                    </th>
                    <?php if (!$filterYear): ?>
                        <th>
                            <a href="?page=<?= $page ?>&year=<?= $filterYear ?>&category=<?= $filterCategory ?>&per_page=<?= $perPage ?>&sort_column=year&sort_direction=<?= ($sortColumn == 'year' && $sortDirection == 'ASC') ? 'DESC' : 'ASC' ?>">Rok</a>
                        </th>
                    <?php endif; ?>
                    <?php if (!$filterCategory): ?>
                        <th>
                            <a href="?page=<?= $page ?>&year=<?= $filterYear ?>&category=<?= $filterCategory ?>&per_page=<?= $perPage ?>&sort_column=category&sort_direction=<?= ($sortColumn == 'category' && $sortDirection == 'ASC') ? 'DESC' : 'ASC' ?>">Kategória</a>
                        </th>
                    <?php endif; ?>
                    <th>Krajina</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($laureates as $laureate): ?>
                    <tr>
                        <td><a href="laureate.php?id=<?= $laureate['id'] ?>"><?= htmlspecialchars($laureate['priezvisko'] ?? 'Neznáme') ?></a></td>
                        <?php if (!$filterYear): ?><td><?= htmlspecialchars($laureate['year']) ?></td><?php endif; ?>
                        <?php if (!$filterCategory): ?><td><?= htmlspecialchars($laureate['category']) ?></td><?php endif; ?>
                        <td><?= htmlspecialchars($laureate['country_name'] ?? 'Neznáma') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination text-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&year=<?= $filterYear ?>&category=<?= $filterCategory ?>&per_page=<?= $perPage ?>&sort_column=<?= $sortColumn ?>&sort_direction=<?= $sortDirection ?>"
               class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
