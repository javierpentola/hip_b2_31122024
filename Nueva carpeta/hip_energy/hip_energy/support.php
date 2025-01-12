<?php
// Support.php

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
    // Gestión de Citas
    if (isset($_POST['action']) && $_POST['action'] === 'add_appointment') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $location = trim($_POST['location']);

        // Validaciones básicas
        if (empty($title) || empty($date) || empty($time) || empty($location)) {
            $error_appointment = "Título, fecha, hora y ubicación son obligatorios.";
        } elseif (!validateDate($date)) {
            $error_appointment = "Fecha inválida.";
        } elseif (!validateTime($time)) {
            $error_appointment = "Hora inválida.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO appointments (title, description, date, time, location) VALUES (:title, :description, :date, :time, :location)");
                $stmt->execute([
                    'title' => $title,
                    'description' => $description,
                    'date' => $date,
                    'time' => $time,
                    'location' => $location
                ]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Añadió una nueva cita: '$title' el $date a las $time.");

                $success_appointment = "Cita añadida exitosamente.";
            } catch (PDOException $e) {
                $error_appointment = "Error al añadir la cita: " . $e->getMessage();
            }
        }
    }

    // Edición de Citas
    if (isset($_POST['action']) && $_POST['action'] === 'edit_appointment' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $location = trim($_POST['location']);

        // Validaciones básicas
        if (empty($title) || empty($date) || empty($time) || empty($location)) {
            $error_edit_appointment = "Título, fecha, hora y ubicación son obligatorios.";
        } elseif (!validateDate($date)) {
            $error_edit_appointment = "Fecha inválida.";
        } elseif (!validateTime($time)) {
            $error_edit_appointment = "Hora inválida.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE appointments SET title = :title, description = :description, date = :date, time = :time, location = :location WHERE id = :id");
                $stmt->execute([
                    'title' => $title,
                    'description' => $description,
                    'date' => $date,
                    'time' => $time,
                    'location' => $location,
                    'id' => $id
                ]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Editó la cita ID: $id.");

                $success_edit_appointment = "Cita actualizada exitosamente.";
            } catch (PDOException $e) {
                $error_edit_appointment = "Error al actualizar la cita: " . $e->getMessage();
            }
        }
    }

    // Eliminación de Citas
    if (isset($_POST['delete_appointment_id'])) {
        $delete_id = $_POST['delete_appointment_id'];
        try {
            // Obtener detalles de la cita para el logging
            $stmt = $pdo->prepare("SELECT title FROM appointments WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($appointment) {
                $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = :id");
                $stmt->execute(['id' => $delete_id]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Eliminó la cita ID: $delete_id, Título: '{$appointment['title']}'.");

                $success_delete_appointment = "Cita eliminada exitosamente.";
            } else {
                $error_delete_appointment = "Cita no encontrada.";
            }
        } catch (PDOException $e) {
            $error_delete_appointment = "Error al eliminar la cita: " . $e->getMessage();
        }
    }

    // Gestión de Notificaciones
    if (isset($_POST['action']) && $_POST['action'] === 'add_notification') {
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);

        // Validaciones básicas
        if (empty($title) || empty($message)) {
            $error_notification = "Título y mensaje son obligatorios.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO notifications (title, message) VALUES (:title, :message)");
                $stmt->execute([
                    'title' => $title,
                    'message' => $message
                ]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Añadió una nueva notificación: '$title'.");

                $success_notification = "Notificación añadida exitosamente.";
            } catch (PDOException $e) {
                $error_notification = "Error al añadir la notificación: " . $e->getMessage();
            }
        }
    }

    // Edición de Notificaciones
    if (isset($_POST['action']) && $_POST['action'] === 'edit_notification' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);

        // Validaciones básicas
        if (empty($title) || empty($message)) {
            $error_edit_notification = "Título y mensaje son obligatorios.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE notifications SET title = :title, message = :message WHERE id = :id");
                $stmt->execute([
                    'title' => $title,
                    'message' => $message,
                    'id' => $id
                ]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Editó la notificación ID: $id.");

                $success_edit_notification = "Notificación actualizada exitosamente.";
            } catch (PDOException $e) {
                $error_edit_notification = "Error al actualizar la notificación: " . $e->getMessage();
            }
        }
    }

    // Eliminación de Notificaciones
    if (isset($_POST['delete_notification_id'])) {
        $delete_id = $_POST['delete_notification_id'];
        try {
            // Obtener detalles de la notificación para el logging
            $stmt = $pdo->prepare("SELECT title FROM notifications WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($notification) {
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = :id");
                $stmt->execute(['id' => $delete_id]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Eliminó la notificación ID: $delete_id, Título: '{$notification['title']}'.");

                $success_delete_notification = "Notificación eliminada exitosamente.";
            } else {
                $error_delete_notification = "Notificación no encontrada.";
            }
        } catch (PDOException $e) {
            $error_delete_notification = "Error al eliminar la notificación: " . $e->getMessage();
        }
    }

    // Obtener todas las citas
    try {
        $stmt = $pdo->prepare("SELECT * FROM appointments ORDER BY date ASC, time ASC");
        $stmt->execute();
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener las citas: " . $e->getMessage());
    }

    // Obtener todas las notificaciones
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications ORDER BY created_at DESC");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener las notificaciones: " . $e->getMessage());
    }

    // Funciones de Validación
    function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    function validateTime($time, $format = 'H:i') {
        $t = DateTime::createFromFormat($format, $time);
        return $t && $t->format($format) === $time;
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>HIP ENERGY Navigation - Admin Panel - Support</title>
        <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.5/jquery-jvectormap.min.css" crossorigin="anonymous" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.5/jquery-jvectormap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.5/jquery-jvectormap-world-mill.min.js"></script>
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
                background-color: white;
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

            .main-content {
                margin-left: var(--sidebar-width);
                padding: 2rem;
                transition: margin-left var(--transition-speed) ease, background-color var(--transition-speed) ease, color var(--transition-speed) ease;
                min-height: 100vh;
                max-width: 1200px;
                margin-right: auto;
                background-color: var(--primary-color);
                color: var(--text-color);
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

            .logout-btn {
                position: fixed;
                bottom: 1rem;
                right: 1rem;
                padding: 0.3rem 0.6rem;
                background-color: red;
                color: white;
                border: 2px solid black;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.8rem;
                font-weight: 500;
                transition: background-color 0.3s ease;
            }

            .logout-btn:hover {
                background-color: darkred;
            }

            /* Secciones de Gestión */
            .management-section {
                display: flex;
                flex-direction: column;
                gap: 2rem;
            }

            .management-card {
                background-color: rgba(255, 255, 255, 0.9);
                border-radius: 8px;
                padding: 1rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .management-card h2 {
                margin-top: 0;
                border-bottom: 2px solid var(--primary-color);
                padding-bottom: 0.5rem;
            }

            /* Formularios */
            .form-group {
                margin-bottom: 1rem;
            }

            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
            }

            .form-group input,
            .form-group textarea,
            .form-group select {
                width: 100%;
                padding: 0.5rem;
                border: 1px solid #ccc;
                border-radius: 4px;
            }

            /* Botones */
            .btn {
                background-color: var(--accent-color);
                color: white;
                padding: 0.5rem 1rem;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 1rem;
                font-weight: 500;
                transition: background-color 0.3s ease;
            }

            .btn:hover {
                background-color: var(--primary-dark);
            }

            .btn-edit, .btn-delete {
                background-color: #007bff;
                color: white;
                padding: 0.3rem 0.6rem;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.8rem;
                margin-right: 0.3rem;
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

            /* Tablas */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1rem;
            }

            th, td {
                border: 1px solid #ddd;
                padding: 0.75rem;
                text-align: left;
            }

            th {
                background-color: var(--primary-color);
                color: white;
            }

            tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            /* Modales */
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
                padding: 1.5rem;
                border: 1px solid #888;
                width: 80%;
                max-width: 500px;
                border-radius: 8px;
            }

            .close-modal {
                color: #aaa;
                float: right;
                font-size: 1.5rem;
                font-weight: bold;
                cursor: pointer;
            }

            .close-modal:hover,
            .close-modal:focus {
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
                <a href="notificaciones.php" class="nav-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <div class="notification-badge"><?php echo count($notifications); ?></div>
                </a>
                <a href="citas.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                    <div class="notification-badge"><?php echo count($appointments); ?></div>
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
            <h1>Support Panel</h1>
            <div class="management-section">
                <!-- Gestión de Citas -->
                <div class="management-card" id="appointmentsManagement">
                    <h2>Gestión de Citas</h2>

                    <!-- Mensajes de Error/Éxito para Citas -->
                    <?php if (isset($error_appointment)): ?>
                        <div class="error-message">
                            <p style="color: red;"><?php echo htmlspecialchars($error_appointment); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success_appointment)): ?>
                        <div class="success-message">
                            <p style="color: green;"><?php echo htmlspecialchars($success_appointment); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_edit_appointment)): ?>
                        <div class="error-message">
                            <p style="color: red;"><?php echo htmlspecialchars($error_edit_appointment); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success_edit_appointment)): ?>
                        <div class="success-message">
                            <p style="color: green;"><?php echo htmlspecialchars($success_edit_appointment); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success_delete_appointment)): ?>
                        <div class="success-message">
                            <p style="color: green;"><?php echo htmlspecialchars($success_delete_appointment); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_delete_appointment)): ?>
                        <div class="error-message">
                            <p style="color: red;"><?php echo htmlspecialchars($error_delete_appointment); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario para Añadir Nueva Cita -->
                    <form method="POST" action="Support.php">
                        <input type="hidden" name="action" value="add_appointment">
                        <div class="form-group">
                            <label for="title">Título:</label>
                            <input type="text" id="title" name="title" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Descripción:</label>
                            <textarea id="description" name="description" class="form-input" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="date">Fecha:</label>
                            <input type="date" id="date" name="date" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="time">Hora:</label>
                            <input type="time" id="time" name="time" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="location">Ubicación:</label>
                            <input type="text" id="location" name="location" class="form-input" required>
                        </div>
                        <button type="submit" class="btn">Añadir Cita</button>
                    </form>

                    <!-- Tabla de Citas Existentes -->
                    <h3>Lista de Citas</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Descripción</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Ubicación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['title']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['description']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['date']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['time']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['location']); ?></td>
                                    <td>
                                        <button class="btn-edit" data-id="<?php echo $appointment['id']; ?>">Editar</button>
                                        <form method="POST" action="Support.php" style="display:inline;">
                                            <input type="hidden" name="delete_appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <button type="submit" class="btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar esta cita?');">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($appointments) === 0): ?>
                                <tr>
                                    <td colspan="7">No hay citas disponibles.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Gestión de Notificaciones -->
                <div class="management-card" id="notificationsManagement">
                    <h2>Gestión de Notificaciones</h2>

                    <!-- Mensajes de Error/Éxito para Notificaciones -->
                    <?php if (isset($error_notification)): ?>
                        <div class="error-message">
                            <p style="color: red;"><?php echo htmlspecialchars($error_notification); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success_notification)): ?>
                        <div class="success-message">
                            <p style="color: green;"><?php echo htmlspecialchars($success_notification); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_edit_notification)): ?>
                        <div class="error-message">
                            <p style="color: red;"><?php echo htmlspecialchars($error_edit_notification); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success_edit_notification)): ?>
                        <div class="success-message">
                            <p style="color: green;"><?php echo htmlspecialchars($success_edit_notification); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success_delete_notification)): ?>
                        <div class="success-message">
                            <p style="color: green;"><?php echo htmlspecialchars($success_delete_notification); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_delete_notification)): ?>
                        <div class="error-message">
                            <p style="color: red;"><?php echo htmlspecialchars($error_delete_notification); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario para Añadir Nueva Notificación -->
                    <form method="POST" action="Support.php">
                        <input type="hidden" name="action" value="add_notification">
                        <div class="form-group">
                            <label for="notif_title">Título:</label>
                            <input type="text" id="notif_title" name="title" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="notif_message">Mensaje:</label>
                            <textarea id="notif_message" name="message" class="form-input" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn">Añadir Notificación</button>
                    </form>

                    <!-- Tabla de Notificaciones Existentes -->
                    <h3>Lista de Notificaciones</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Mensaje</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($notification['id']); ?></td>
                                    <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                    <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                    <td><?php echo htmlspecialchars($notification['created_at']); ?></td>
                                    <td>
                                        <button class="btn-edit" data-id="<?php echo $notification['id']; ?>">Editar</button>
                                        <form method="POST" action="Support.php" style="display:inline;">
                                            <input type="hidden" name="delete_notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar esta notificación?');">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($notifications) === 0): ?>
                                <tr>
                                    <td colspan="5">No hay notificaciones disponibles.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Modales para Editar Citas y Notificaciones -->
        <!-- Modal para Editar Cita -->
        <div id="editAppointmentModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Editar Cita</h2>
                <form method="POST" action="Support.php">
                    <input type="hidden" name="action" value="edit_appointment">
                    <input type="hidden" name="id" id="edit_appointment_id">
                    <div class="form-group">
                        <label for="edit_title">Título:</label>
                        <input type="text" id="edit_title" name="title" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Descripción:</label>
                        <textarea id="edit_description" name="description" class="form-input" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_date">Fecha:</label>
                        <input type="date" id="edit_date" name="date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_time">Hora:</label>
                        <input type="time" id="edit_time" name="time" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_location">Ubicación:</label>
                        <input type="text" id="edit_location" name="location" class="form-input" required>
                    </div>
                    <button type="submit" class="btn">Actualizar Cita</button>
                </form>
            </div>
        </div>

        <!-- Modal para Editar Notificación -->
        <div id="editNotificationModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Editar Notificación</h2>
                <form method="POST" action="Support.php">
                    <input type="hidden" name="action" value="edit_notification">
                    <input type="hidden" name="id" id="edit_notification_id">
                    <div class="form-group">
                        <label for="edit_notif_title">Título:</label>
                        <input type="text" id="edit_notif_title" name="title" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_notif_message">Mensaje:</label>
                        <textarea id="edit_notif_message" name="message" class="form-input" rows="3" required></textarea>
                    </div>
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

            // Manejo de Modales
            const editAppointmentModal = document.getElementById('editAppointmentModal');
            const editNotificationModal = document.getElementById('editNotificationModal');
            const closeModalButtons = document.getElementsByClassName('close-modal');

            // Cerrar modales al hacer clic en la "X"
            Array.from(closeModalButtons).forEach(button => {
                button.onclick = function() {
                    this.parentElement.parentElement.style.display = "none";
                }
            });

            // Cerrar modales al hacer clic fuera del contenido
            window.onclick = function(event) {
                if (event.target == editAppointmentModal) {
                    editAppointmentModal.style.display = "none";
                }
                if (event.target == editNotificationModal) {
                    editNotificationModal.style.display = "none";
                }
            }

            // Funciones para abrir modales de edición
            const editAppointmentButtons = document.getElementsByClassName('btn-edit');
            Array.from(editAppointmentButtons).forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.parentElement.parentElement.parentElement;
                    const id = this.getAttribute('data-id');
                    const title = row.cells[1].innerText;
                    const description = row.cells[2].innerText;
                    const date = row.cells[3].innerText;
                    const time = row.cells[4].innerText;
                    const location = row.cells[5].innerText;

                    document.getElementById('edit_appointment_id').value = id;
                    document.getElementById('edit_title').value = title;
                    document.getElementById('edit_description').value = description;
                    document.getElementById('edit_date').value = date;
                    document.getElementById('edit_time').value = time;
                    document.getElementById('edit_location').value = location;

                    editAppointmentModal.style.display = "block";
                });
            });

            const editNotificationButtons = document.querySelectorAll('.btn-edit-notif');
            Array.from(editNotificationButtons).forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.parentElement.parentElement.parentElement;
                    const id = this.getAttribute('data-id');
                    const title = row.cells[1].innerText;
                    const message = row.cells[2].innerText;

                    document.getElementById('edit_notification_id').value = id;
                    document.getElementById('edit_notif_title').value = title;
                    document.getElementById('edit_notif_message').value = message;

                    editNotificationModal.style.display = "block";
                });
            });

            // Función de Cierre de Sesión
            function logout() {
                // Implementar lógica de cierre de sesión aquí
                alert("Admin ha cerrado sesión");
                window.location.href = 'login.php';
            }
        </script>
    </body>
    </html>
