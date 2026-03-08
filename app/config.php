<?php
// config.php - conexiune PDO la SQL Server
$serverName = "localhost\\SQLEXPRESS"; //modifica dupa configuratia locala
$database   = "SpatiiCoworking"; 
$username   = "user"; // configureaza credentialele pentru baza ta de date
$password   = "password"; // configureaza credentialele pentru baza ta de date

$dsn = "sqlsrv:Server=$serverName;Database=$database;Encrypt=no;TrustServerCertificate=yes";

try {
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Conexiune esuata: " . $e->getMessage());
}
