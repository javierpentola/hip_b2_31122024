<?php
// create_appointments_table.php

// Datos de conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

// Crear conexión
$mysqli = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

// SQL para crear la tabla
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    location VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Ejecutar la consulta
if ($mysqli->query($sql) === TRUE) {
    echo "Tabla 'appointments' creada exitosamente.";
} else {
    echo "Error al crear la tabla: " . $mysqli->error;
}

// Cerrar conexión
$mysqli->close();
?>
