<?php
$host = "db";            
$db   = "mas_deforestacion";
$user = "root";
$pass = "root";           
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $opciones);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error de conexión: " . $e->getMessage();
    exit;
}
?>