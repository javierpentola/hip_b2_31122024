<?php
// update_read_status.php

// Incluir funciones si deseas registrar actividades (opcional)
require_once 'functions.php';

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
    http_response_code(500);
    echo "Error de conexión: " . $e->getMessage();
    exit();
}

// Verificar si el admin está logueado
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo "No autorizado";
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    // Obtener el estado actual
    try {
        $stmt = $pdo->prepare("SELECT read_status FROM notifications WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($notification) {
            $new_status = ($notification['read_status'] === 'No leído') ? 'Leído' : 'No leído';
            $stmt = $pdo->prepare("UPDATE notifications SET read_status = :new_status WHERE id = :id");
            $stmt->execute([
                'new_status' => $new_status,
                'id' => $id
            ]);

            // (Opcional) Registrar actividad
            logActivity($pdo, $admin_id, "Actualizó el estado de lectura de la notificación ID: $id a '$new_status'");

            echo "Éxito";
        } else {
            http_response_code(404);
            echo "Notificación no encontrada";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Error al actualizar el estado de lectura: " . $e->getMessage();
    }
} else {
    http_response_code(400);
    echo "Solicitud inválida";
}
?>
