<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hall"; // change this to your database name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
