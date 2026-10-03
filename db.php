<?php
error_reporting(0);
date_default_timezone_set('Asia/Kolkata');

$servername = "localhost";
$username = "root";
$password = "usbw";  
$dbname = "voting_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>