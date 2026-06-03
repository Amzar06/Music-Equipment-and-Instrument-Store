<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "music-equipment-and-instrument-store";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>