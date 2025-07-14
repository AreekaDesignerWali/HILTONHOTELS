<?php
$host = 'localhost';
$dbname = 'dbcbyemccrvbpy';
$username = 'uc7ggok7oyoza';
$password = 'gqypavorhbbc';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
