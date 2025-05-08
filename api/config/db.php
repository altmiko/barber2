<?php

$servername = "barberbookdb-barberbook.j.aivencloud.com";
$username = "avnadmin";
$password = "AVNS_EHS3bUWR3_7dcdFu9Ow";
$dbname = "barberbookdb";
$port = "14282";


$conn = new mysqli($servername, $username, $password, $dbname, $port);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");
?>