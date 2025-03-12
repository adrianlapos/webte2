<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
require_once('../../config.php');
function parserCSV($filename){
    $handle = fopen($filename,'r');
    $data = array();
    while (($row = fgetcsv($handle,0,';')) !== false){
      $data[] = array_filter($row);
    }
    fclose($handle);
    unset($data[0]);
    return $data;
}

$laureates = parserCSV('nobel_v5.2_FYZ.csv');
echo "<pre>";
print_r($laureates);
echo "</pre>";
?>