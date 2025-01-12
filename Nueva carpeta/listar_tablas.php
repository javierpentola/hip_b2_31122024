<?php
// listar_tablas.php

// Datos de conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h1>Tablas en la Base de Datos '$db'</h1>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
