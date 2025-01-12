<?php
// logout.php

// Habilitar la visualización de errores para depuración (Eliminar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
session_start();

// Datos de conexión a la base de datos en InfinityFree
$host = 'sql108.infinityfree.com'; // nombre del host MySQL
$user = 'if0_37852817';           // nombre de usuario MySQL
$pass = 'BkgzaebxbJ';             // contraseña MySQL
$db   = 'if0_37852817_hipgeneraldb'; // base de datos MySQL

// Conectar a la base de datos usando PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Verificar si el admin está logueado
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];

    // Actualizar is_logged_in a 0
    try {
        $stmt = $pdo->prepare("UPDATE admins SET is_logged_in = 0 WHERE id = :id");
        $stmt->execute(['id' => $admin_id]);
    } catch (PDOException $e) {
        die("Error al actualizar estado de sesión: " . $e->getMessage());
    }

    // Cerrar sesión
    session_destroy();
}

// Redirigir al login
header('Location: login.php');
exit();
?>
