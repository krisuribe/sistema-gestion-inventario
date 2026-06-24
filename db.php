<?php
// db.php

$host = '127.0.0.1'; // o 'localhost'
$db_name = 'inventario_db';
$username = 'root'; // Usuario por defecto en XAMPP
$password = ''; // Contraseña por defecto en XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // En un entorno de producción, no muestres el error detallado
    // solo registra el error y muestra un mensaje genérico.
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit; // Termina el script si la conexión falla
}

// Establecer la cabecera para que la respuesta sea siempre JSON
header('Content-Type: application/json');
?>