
<?php

$host = "localhost";
$dbname = "aitamslack";
$username = "root";
$password = "";

try {

    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
    echo "Connected to the database successfully!";

} catch (PDOException $e) {

    die("Database connection failed.");

}

?>
