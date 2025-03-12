<?php
$hostname = 'localhost';
$database = 'nobels';
$username = 'xlapos';
$password = 'mittudomen123';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function connectDatabase($hostname, $database,$username,$password){
    try{
        $conn = new PDO("mysql:host=$hostname;dbname=$database",$username,$password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    }catch(PDOException $e){
        echo "Connection failed: " . $e->getMessage();
        return null;
    }
}
function connectMySqli($hostname, $database,$username,$password){
    $conn = new mysqli($hostname, $username, $password,$database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
echo "Connected successfully";
}
?>