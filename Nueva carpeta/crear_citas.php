<?php
// crear_citas.php

// Datos de conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE citas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                date DATE NOT NULL,
                time TIME NOT NULL,
                location VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Tabla 'citas' creada exitosamente.";
} catch (PDOException $e) {
    die("Error al crear la tabla: " . $e->getMessage());
}
?>
