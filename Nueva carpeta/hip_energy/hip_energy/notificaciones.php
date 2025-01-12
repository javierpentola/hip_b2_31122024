<?php
// notificaciones.php

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
    die("Error de conexión: " . $e->getMessage());
}

// Verificar si el admin está logueado
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); // redirigir al login si no está logueado
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Obtener datos del admin (opcional, para mostrar en la interfaz)
try {
    $stmt = $pdo->prepare("SELECT username FROM admins WHERE id = :id");
    $stmt->execute(['id' => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        // Admin no encontrado, cerrar sesión
        session_destroy();
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    die("Error al obtener datos del admin: " . $e->getMessage());
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de CSRF (opcional pero recomendado)
    // Implementa tokens CSRF para proteger los formularios contra ataques

    // Gestión de Notificaciones
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $title = trim($_POST['title']);
            $message = trim($_POST['message']);
            $category = $_POST['category'];
            $priority = $_POST['priority'];
            $scheduled_time = !empty($_POST['scheduled_time']) ? $_POST['scheduled_time'] : NULL;
            $user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? $_POST['user_id'] : NULL;
            $read_status = 'No leído';

            // Manejo de adjuntos
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['attachment']['tmp_name'];
                $fileName = $_FILES['attachment']['name'];
                $fileSize = $_FILES['attachment']['size'];
                $fileType = $_FILES['attachment']['type'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                // Sanitizar el nombre del archivo
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

                // Directorio donde se guardarán los archivos adjuntos
                $uploadFileDir = 'uploads/notifications/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                $dest_path = $uploadFileDir . $newFileName;

                // Verificar la extensión del archivo
                $allowedfileExtensions = array('jpg', 'gif', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx');
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        $attachment = $dest_path;
                    } else {
                        $attachment = NULL;
                        $error = "Error al subir el archivo adjunto.";
                    }
                } else {
                    $attachment = NULL;
                    $error = "Tipo de archivo no permitido para el adjunto.";
                }
            } else {
                $attachment = NULL;
            }

            // Validaciones básicas
            if (empty($title) || empty($message) || empty($category) || empty($priority)) {
                $error = "Título, mensaje, categoría y prioridad son obligatorios.";
            } elseif (!in_array($category, ['Urgente', 'Info', 'Recordatorio'])) {
                $error = "Categoría inválida.";
            } elseif (!in_array($priority, ['Alta', 'Media', 'Baja'])) {
                $error = "Prioridad inválida.";
            } elseif (!empty($scheduled_time) && !validateDateTime($scheduled_time)) {
                $error = "Fecha y hora programadas inválidas.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO notifications (title, message, category, priority, scheduled_time, user_id, read_status, attachment) VALUES (:title, :message, :category, :priority, :scheduled_time, :user_id, :read_status, :attachment)");
                    $stmt->execute([
                        'title' => $title,
                        'message' => $message,
                        'category' => $category,
                        'priority' => $priority,
                        'scheduled_time' => $scheduled_time,
                        'user_id' => $user_id,
                        'read_status' => $read_status,
                        'attachment' => $attachment
                    ]);

                    // (Opcional) Registrar actividad
                    logActivity($pdo, $admin_id, "Añadió una nueva notificación: '$title'");

                    $success = "Notificación añadida exitosamente.";
                } catch (PDOException $e) {
                    $error = "Error al añadir la notificación: " . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = $_POST['id'];
            $title = trim($_POST['title']);
            $message = trim($_POST['message']);
            $category = $_POST['category'];
            $priority = $_POST['priority'];
            $scheduled_time = !empty($_POST['scheduled_time']) ? $_POST['scheduled_time'] : NULL;
            $user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? $_POST['user_id'] : NULL;

            // Manejo de adjuntos
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['attachment']['tmp_name'];
                $fileName = $_FILES['attachment']['name'];
                $fileSize = $_FILES['attachment']['size'];
                $fileType = $_FILES['attachment']['type'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                // Sanitizar el nombre del archivo
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

                // Directorio donde se guardarán los archivos adjuntos
                $uploadFileDir = 'uploads/notifications/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                $dest_path = $uploadFileDir . $newFileName;

                // Verificar la extensión del archivo
                $allowedfileExtensions = array('jpg', 'gif', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx');
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        $attachment = $dest_path;
                    } else {
                        $attachment = NULL;
                        $error = "Error al subir el archivo adjunto.";
                    }
                } else {
                    $attachment = NULL;
                    $error = "Tipo de archivo no permitido para el adjunto.";
                }
            } else {
                // Si no se sube un nuevo adjunto, mantener el existente
                $stmt = $pdo->prepare("SELECT attachment FROM notifications WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                $attachment = $existing['attachment'];
            }

            // Validaciones básicas
            if (empty($title) || empty($message) || empty($category) || empty($priority)) {
                $error = "Título, mensaje, categoría y prioridad son obligatorios.";
            } elseif (!in_array($category, ['Urgente', 'Info', 'Recordatorio'])) {
                $error = "Categoría inválida.";
            } elseif (!in_array($priority, ['Alta', 'Media', 'Baja'])) {
                $error = "Prioridad inválida.";
            } elseif (!empty($scheduled_time) && !validateDateTime($scheduled_time)) {
                $error = "Fecha y hora programadas inválidas.";
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE notifications SET title = :title, message = :message, category = :category, priority = :priority, scheduled_time = :scheduled_time, user_id = :user_id, attachment = :attachment WHERE id = :id");
                    $stmt->execute([
                        'title' => $title,
                        'message' => $message,
                        'category' => $category,
                        'priority' => $priority,
                        'scheduled_time' => $scheduled_time,
                        'user_id' => $user_id,
                        'attachment' => $attachment,
                        'id' => $id
                    ]);

                    // (Opcional) Registrar actividad
                    logActivity($pdo, $admin_id, "Editó la notificación ID: $id");

                    $success = "Notificación actualizada exitosamente.";
                } catch (PDOException $e) {
                    $error = "Error al actualizar la notificación: " . $e->getMessage();
                }
            }
        }
    }

    // Procesar eliminación de notificación
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        try {
            // Obtener detalles de la notificación para el logging y eliminación de adjuntos
            $stmt = $pdo->prepare("SELECT title, attachment FROM notifications WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($notification) {
                // Eliminar el archivo adjunto si existe
                if (!empty($notification['attachment']) && file_exists($notification['attachment'])) {
                    unlink($notification['attachment']);
                }

                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = :id");
                $stmt->execute(['id' => $delete_id]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Eliminó la notificación ID: $delete_id, Título: '{$notification['title']}'");

                $success = "Notificación eliminada exitosamente.";
            } else {
                $error = "Notificación no encontrada.";
            }
        } catch (PDOException $e) {
            $error = "Error al eliminar la notificación: " . $e->getMessage();
        }
    }

    // Obtener todas las notificaciones, filtrando por programación si es necesario
    try {
        $current_time = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE (scheduled_time IS NULL OR scheduled_time <= :current_time) ORDER BY priority DESC, created_at DESC");
        $stmt->execute(['current_time' => $current_time]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener las notificaciones: " . $e->getMessage());
    }

    // Funciones de Validación
    function validateDateTime($datetime, $format = 'Y-m-d\TH:i') {
        $d = DateTime::createFromFormat($format, $datetime);
        return $d && $d->format($format) === $datetime;
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIP ENERGY Navigation - Admin Panel - Notificaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reutilización de los estilos existentes */
        :root {
            --primary-color: #f2c517;
            --primary-dark: #d4a017;
            --accent-color: #ffffff;
            --text-color: #000;
            --transition-speed: 0.3s;
            --divider-width: 6px;
            --sidebar-width: 80px;
            --sidebar-expanded-width: 250px;
        }

        body {
            margin: 0;
            font-family: 'Rubik', sans-serif;
            overflow-x: hidden;
            background-color: var(--primary-color);
        }

        .accessible-mode {
            --primary-color: #000000;
            --primary-dark: #ffffff;
            --accent-color: #ffffff;
            --text-color: #000000;
        }

        .accessible-mode .logo {
            content: url('https://hebbkx1anhila5yf.public.blob.vercel-storage.com/negra-breHQ41WqrzgIYL6eWCIeGlva5Wk1f.png');
        }

        .sidebar {
            height: 100vh;
            background-color: var(--primary-color);
            width: var(--sidebar-width);
            position: fixed;
            left: 0;
            top: 0;
            transition: width var(--transition-speed) ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .sidebar:hover {
            width: var(--sidebar-expanded-width);
        }

        .brand {
            padding: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            text-align: center;
            white-space: nowrap;
            opacity: 0;
            transition: opacity var(--transition-speed);
        }

        .sidebar:hover .brand {
            opacity: 1;
        }

        .nav-items {
            flex: 1;
            padding: 1rem 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            transition: all var(--transition-speed);
            cursor: pointer;
            white-space: nowrap;
            border-radius: 0 25px 25px 0;
            margin: 0.25rem 0;
        }

        .nav-item:hover {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .nav-item.active {
            background-color: var(--primary-dark);
            color: var(--primary-color);
        }

        .nav-item i {
            width: 24px;
            margin-right: 1rem;
            text-align: center;
        }

        .nav-item span {
            opacity: 0;
            transition: opacity var(--transition-speed);
        }

        .sidebar:hover .nav-item span {
            opacity: 1;
        }

        .notification-badge {
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            margin-left: auto;
        }

        .accessible-mode .notification-badge {
            background-color: #ffffff;
            color: #000000;
        }

        .logo-container {
            padding: 1rem;
            margin: 1rem;
            text-align: center;
            opacity: 0;
            transition: opacity var(--transition-speed);
        }

        .sidebar:hover .logo-container {
            opacity: 1;
        }

        .logo {
            width: 150px;
            height: auto;
        }

        .vision-modes {
            padding: 1rem 0;
            border-top: 1px solid var(--primary-dark);
            margin-top: auto;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: margin-left var(--transition-speed) ease, background-color var(--transition-speed) ease, color var(--transition-speed) ease;
            min-height: 100vh;
            background-color: var(--primary-color);
        }

        .accessible-mode .main-content {
            background-color: var(--primary-color);
            color: var(--text-color);
        }

        .main-content::before {
            content: '';
            position: fixed;
            left: var(--sidebar-width);
            top: 0;
            width: var(--divider-width);
            height: 100%;
            background-color: var(--primary-dark);
            transition: left var(--transition-speed) ease;
        }

        .sidebar:hover + .main-content::before {
            left: var(--sidebar-expanded-width);
        }

        /* Notificaciones Dashboard */
        .notifications-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notification-card {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            display: flex;
            align-items: flex-start;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .notification-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--accent-color);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .notification-message {
            color: #666;
            font-size: 0.9rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.5rem;
        }

        .notification-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 0.5rem;
        }

        .notification-action {
            background: none;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 1rem;
            padding: 0;
        }

        .notification-action:hover {
            text-decoration: underline;
        }

        /* Formulario de Añadir/Editar Notificación */
        .notification-form {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .notification-form h2 {
            margin-top: 0;
        }

        .form-input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .btn {
            background-color: var(--accent-color);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .btn-edit, .btn-delete {
            background-color: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 0.5rem;
        }

        .btn-edit:hover {
            background-color: #0056b3;
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        /* Modal para Editar Notificación */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 2000; /* Sit on top */
            padding-top: 100px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 1rem;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: var(--sidebar-width);
            }
            .sidebar:hover {
                width: var(--sidebar-expanded-width);
            }
            .main-content {
                margin-left: var(--sidebar-width);
            }
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="brand">HIP ENERGY</div>
        <div class="nav-items">
            <a href="home.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="consumo.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Consumption</span>
            </a>
            <a href="facturas.php" class="nav-item">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Bills</span>
            </a>
            <a href="notificaciones.php" class="nav-item active">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <div class="notification-badge"><?php echo count($notifications); ?></div>
            </a>
            <a href="citas.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Appointments</span>
            </a>
            <a href="modoaccesible.php" class="nav-item">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Fault Reporting</span>
            </a>
        </div>
        <div class="vision-modes">
            <a href="#" class="nav-item" id="protanopiaToggle">
                <i class="fas fa-eye"></i>
                <span>Protanopia</span>
            </a>
            <a href="#" class="nav-item" id="deuteranopiaToggle">
                <i class="fas fa-eye"></i>
                <span>Deuteranopia</span>
            </a>
            <a href="#" class="nav-item" id="tritanopiaToggle">
                <i class="fas fa-eye"></i>
                <span>Tritanopia</span>
            </a>
            <a href="#" class="nav-item" id="normalModeToggle">
                <i class="fas fa-eye-slash"></i>
                <span>Normal Mode</span>
            </a>
        </div>
        <div class="logo-container">
            <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/Normal-S0ZM46xhJ8Mm0vNUqKXmmqWS9gTvZJ.png" alt="HIP ENERGY Logo" class="logo">
        </div>
    </nav>

    <main class="main-content">
        <h1>Notifications Dashboard</h1>

        <!-- Formulario para Añadir Nueva Notificación -->
        <div class="notification-form">
            <h2>Añadir Nueva Notificación</h2>
            <?php if (isset($error) && $_POST['action'] !== 'edit'): ?>
                <div class="error-message">
                    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            <?php if (isset($success) && $_POST['action'] !== 'edit'): ?>
                <div class="success-message">
                    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>
            <form method="POST" action="notificaciones.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <label for="title">Título:</label>
                <input type="text" id="title" name="title" class="form-input" required>

                <label for="message">Mensaje:</label>
                <textarea id="message" name="message" class="form-input" rows="4" required></textarea>

                <label for="category">Categoría:</label>
                <select id="category" name="category" class="form-input" required>
                    <option value="">Seleccionar Categoría</option>
                    <option value="Urgente">Urgente</option>
                    <option value="Info">Información</option>
                    <option value="Recordatorio">Recordatorio</option>
                </select>

                <label for="priority">Prioridad:</label>
                <select id="priority" name="priority" class="form-input" required>
                    <option value="">Seleccionar Prioridad</option>
                    <option value="Alta">Alta</option>
                    <option value="Media">Media</option>
                    <option value="Baja">Baja</option>
                </select>

                <label for="scheduled_time">Hora Programada (Opcional):</label>
                <input type="datetime-local" id="scheduled_time" name="scheduled_time" class="form-input">

                <label for="user_id">Usuario Específico (Opcional):</label>
                <select id="user_id" name="user_id" class="form-input">
                    <option value="">Todos los Usuarios</option>
                    <?php
                        // Obtener todos los usuarios para seleccionarlos
                        try {
                            $stmt = $pdo->prepare("SELECT id, username FROM users ORDER BY username ASC");
                            $stmt->execute();
                            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users as $user) {
                                echo '<option value="'.htmlspecialchars($user['id']).'">'.htmlspecialchars($user['username']).'</option>';
                            }
                        } catch (PDOException $e) {
                            echo '<option value="">Error al cargar usuarios</option>';
                        }
                    ?>
                </select>

                <label for="attachment">Adjuntar Archivo (Opcional):</label>
                <input type="file" id="attachment" name="attachment" class="form-input" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">

                <button type="submit" class="btn">Añadir Notificación</button>
            </form>
        </div>

        <!-- Lista de Notificaciones Existentes -->
        <div class="notifications-dashboard">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card <?php echo ($notification['read_status'] === 'No leído') ? 'unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                    <div class="notification-icon">
                        <?php
                            // Seleccionar el icono basado en la categoría
                            switch ($notification['category']) {
                                case 'Urgente':
                                    echo '<i class="fas fa-exclamation-circle" style="color: red;"></i>';
                                    break;
                                case 'Info':
                                    echo '<i class="fas fa-info-circle" style="color: blue;"></i>';
                                    break;
                                case 'Recordatorio':
                                    echo '<i class="fas fa-calendar-check" style="color: green;"></i>';
                                    break;
                                default:
                                    echo '<i class="fas fa-info-circle"></i>';
                            }
                        ?>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-message"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></div>
                        <?php if (!empty($notification['attachment'])): ?>
                            <div class="notification-attachment">
                                <a href="<?php echo htmlspecialchars($notification['attachment']); ?>" target="_blank">Ver Adjuntos</a>
                            </div>
                        <?php endif; ?>
                        <div class="notification-time"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></div>
                        <div class="notification-actions">
                            <button class="notification-action btn-edit" data-id="<?php echo $notification['id']; ?>">Editar</button>
                            <form method="POST" action="notificaciones.php" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" class="notification-action btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar esta notificación?');">Eliminar</button>
                            </form>
                            <button class="notification-action btn-mark-read" data-id="<?php echo $notification['id']; ?>">
                                <?php echo ($notification['read_status'] === 'No leído') ? 'Marcar como Leído' : 'Marcar como No Leído'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($notifications) === 0): ?>
                <p>No hay notificaciones disponibles.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal para Editar Notificación -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Editar Notificación</h2>
            <?php if (isset($error) && $_POST['action'] === 'edit'): ?>
                <div class="error-message">
                    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            <?php if (isset($success) && $_POST['action'] === 'edit'): ?>
                <div class="success-message">
                    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>
            <form method="POST" action="notificaciones.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <label for="edit_title">Título:</label>
                <input type="text" id="edit_title" name="title" class="form-input" required>

                <label for="edit_message">Mensaje:</label>
                <textarea id="edit_message" name="message" class="form-input" rows="4" required></textarea>

                <label for="edit_category">Categoría:</label>
                <select id="edit_category" name="category" class="form-input" required>
                    <option value="">Seleccionar Categoría</option>
                    <option value="Urgente">Urgente</option>
                    <option value="Info">Información</option>
                    <option value="Recordatorio">Recordatorio</option>
                </select>

                <label for="edit_priority">Prioridad:</label>
                <select id="edit_priority" name="priority" class="form-input" required>
                    <option value="">Seleccionar Prioridad</option>
                    <option value="Alta">Alta</option>
                    <option value="Media">Media</option>
                    <option value="Baja">Baja</option>
                </select>

                <label for="edit_scheduled_time">Hora Programada (Opcional):</label>
                <input type="datetime-local" id="edit_scheduled_time" name="scheduled_time" class="form-input">

                <label for="edit_user_id">Usuario Específico (Opcional):</label>
                <select id="edit_user_id" name="user_id" class="form-input">
                    <option value="">Todos los Usuarios</option>
                    <?php
                        // Obtener todos los usuarios para seleccionarlos
                        try {
                            $stmt = $pdo->prepare("SELECT id, username FROM users ORDER BY username ASC");
                            $stmt->execute();
                            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users as $user) {
                                echo '<option value="'.htmlspecialchars($user['id']).'">'.htmlspecialchars($user['username']).'</option>';
                            }
                        } catch (PDOException $e) {
                            echo '<option value="">Error al cargar usuarios</option>';
                        }
                    ?>
                </select>

                <label for="edit_attachment">Adjuntar Archivo (Opcional):</label>
                <input type="file" id="edit_attachment" name="attachment" class="form-input" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">

                <button type="submit" class="btn">Actualizar Notificación</button>
            </form>
        </div>
    </div>

    <button class="logout-btn" onclick="logout()">Admin log out</button>

    <svg style="display: none;">
        <defs>
            <filter id="protanopia-filter">
                <feColorMatrix type="matrix" values="0.567, 0.433, 0,     0, 0
                                     0.558, 0.442, 0,     0, 0
                                     0,     0.242, 0.758, 0, 0
                                     0,     0,     0,     1, 0"/>
            </filter>
            <filter id="deuteranopia-filter">
                <feColorMatrix type="matrix" values="0.625, 0.375, 0,   0, 0
                                     0.7,   0.3,   0,   0, 0
                                     0,     0.3,   0.7, 0, 0
                                     0,     0,     0,   1, 0"/>
            </filter>
            <filter id="tritanopia-filter">
                <feColorMatrix type="matrix" values="0.95, 0.05,  0,     0, 0
                                     0,    0.433, 0.567, 0, 0
                                     0,    0.475, 0.525, 0, 0
                                     0,    0,     0,     1, 0"/>
            </filter>
        </defs>
    </svg>

    <script>
        // Manejo de Modos de Visión
        const protanopiaToggle = document.getElementById('protanopiaToggle');
        const deuteranopiaToggle = document.getElementById('deuteranopiaToggle');
        const tritanopiaToggle = document.getElementById('tritanopiaToggle');
        const normalModeToggle = document.getElementById('normalModeToggle');

        function toggleColorBlindMode(mode) {
            document.documentElement.classList.remove('protanopia', 'deuteranopia', 'tritanopia');
            if (mode) {
                document.documentElement.classList.add(mode);
            }
        }

        protanopiaToggle.addEventListener('click', (e) => {
            e.preventDefault();
            toggleColorBlindMode('protanopia');
        });
        deuteranopiaToggle.addEventListener('click', (e) => {
            e.preventDefault();
            toggleColorBlindMode('deuteranopia');
        });
        tritanopiaToggle.addEventListener('click', (e) => {
            e.preventDefault();
            toggleColorBlindMode('tritanopia');
        });
        normalModeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            toggleColorBlindMode(null);
        });

        // Manejo del Modal de Edición
        const modal = document.getElementById('editModal');
        const closeModalBtn = document.getElementsByClassName('close')[0];
        const editButtons = document.getElementsByClassName('btn-edit');
        const markReadButtons = document.getElementsByClassName('btn-mark-read');

        // Abrir el modal al hacer clic en Editar
        Array.from(editButtons).forEach(button => {
            button.addEventListener('click', function() {
                const notificationCard = this.closest('.notification-card');
                const id = notificationCard.getAttribute('data-id');
                const title = notificationCard.querySelector('.notification-title').innerText;
                const message = notificationCard.querySelector('.notification-message').innerText;
                const category = notificationCard.querySelector('.notification-icon i').classList.contains('fa-exclamation-circle') ? 'Urgente' :
                                  (notificationCard.querySelector('.notification-icon i').classList.contains('fa-info-circle') ? 'Info' : 'Recordatorio');
                const priority = "<?php echo ''; ?>"; // Puedes implementar la lógica para mostrar la prioridad
                const user_id = "<?php echo ''; ?>"; // Implementar lógica para mostrar usuario específico
                const attachment = "<?php echo ''; ?>"; // Implementar lógica para manejar adjuntos

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_title').value = title;
                document.getElementById('edit_message').value = message;
                document.getElementById('edit_category').value = category;
                // Implementa la asignación de prioridad y usuario específico si está disponible

                modal.style.display = "block";
            });
        });

        // Cerrar el modal al hacer clic en (x)
        closeModalBtn.onclick = function() {
            modal.style.display = "none";
        }

        // Cerrar el modal al hacer clic fuera del contenido
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Función para cerrar sesión
        function logout() {
            // Implementar lógica de cierre de sesión aquí
            alert("Admin ha cerrado sesión");
            window.location.href = 'login.php';
        }

        // Manejo de marcar como leído/no leído
        Array.from(markReadButtons).forEach(button => {
            button.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                const currentStatus = this.innerText;

                // Realizar una petición AJAX para actualizar el estado
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "update_read_status.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            // Actualizar el texto del botón
                            button.innerText = (currentStatus === 'Marcar como Leído') ? 'Marcar como No Leído' : 'Marcar como Leído';
                            // Opcional: cambiar la apariencia de la notificación
                            const notificationCard = button.closest('.notification-card');
                            if (currentStatus === 'Marcar como Leído') {
                                notificationCard.classList.remove('unread');
                            } else {
                                notificationCard.classList.add('unread');
                            }
                        } else {
                            alert("Error al actualizar el estado de lectura.");
                        }
                    }
                };
                xhr.send("id=" + encodeURIComponent(notificationId));
            });
        });
    </script>
</body>
</html>
