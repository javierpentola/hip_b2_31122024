<?php
// create_admin.php

// Datos de conexión a la base de datos en InfinityFree
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

// Conectar a la base de datos usando PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Datos del nuevo admin
$username = 'admin'; // Cambia esto según prefieras
$password = 'admin123'; // Cambia esto a una contraseña segura

// Hash de la contraseña
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insertar en la base de datos
try {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (:username, :password)");
    $stmt->execute([
        'username' => $username,
        'password' => $hashed_password
    ]);
    echo "Administrador creado exitosamente.";
} catch (PDOException $e) {
    die("Error al crear administrador: " . $e->getMessage());
}
?>
