<?php
// citas.php

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

// Procesar formulario de añadir cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $location = trim($_POST['location']);

        // Validaciones básicas
        if (empty($title) || empty($date) || empty($time) || empty($location)) {
            $error = "Título, fecha, hora y ubicación son obligatorios.";
        } elseif (!validateDate($date)) {
            $error = "Fecha inválida.";
        } elseif (!validateTime($time)) {
            $error = "Hora inválida.";
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

                $success = "Cita añadida exitosamente.";
            } catch (PDOException $e) {
                $error = "Error al añadir la cita: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $location = trim($_POST['location']);

        // Validaciones básicas
        if (empty($title) || empty($date) || empty($time) || empty($location)) {
            $error = "Título, fecha, hora y ubicación son obligatorios.";
        } elseif (!validateDate($date)) {
            $error = "Fecha inválida.";
        } elseif (!validateTime($time)) {
            $error = "Hora inválida.";
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

                $success = "Cita actualizada exitosamente.";
            } catch (PDOException $e) {
                $error = "Error al actualizar la cita: " . $e->getMessage();
            }
        }
    }
}

// Procesar eliminación de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
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

            $success = "Cita eliminada exitosamente.";
        } else {
            $error = "Cita no encontrada.";
        }
    } catch (PDOException $e) {
        $error = "Error al eliminar la cita: " . $e->getMessage();
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
    <title>HIP ENERGY Navigation - Admin Panel - Citas</title>
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

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: margin-left var(--transition-speed) ease, background-color var(--transition-speed) ease, color: var(--transition-speed) ease;
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

        .protanopia {
            filter: url('#protanopia-filter');
        }

        .deuteranopia {
            filter: url('#deuteranopia-filter');
        }

        .tritanopia {
            filter: url('#tritanopia-filter');
        }

        .vision-modes {
            padding: 1rem 0;
            border-top: 1px solid var(--primary-dark);
            margin-top: auto;
        }

        .appointments-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .calendar-header {
            background-color: var(--primary-dark);
            color: var(--text-color);
            padding: 1rem;
            border-radius: 8px 8px 0 0;
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .calendar-nav {
            display: flex;
            gap: 1rem;
        }

        .calendar-nav-btn {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: var(--primary-dark);
            border: 1px solid var(--primary-dark);
            border-radius: 0 0 8px 8px;
        }

        .calendar-day {
            background-color: var(--primary-color);
            padding: 1rem;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .calendar-date {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .appointment {
            background-color: var(--accent-color);
            color: var(--primary-color);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
            cursor: pointer;
        }

        .appointment:hover {
            background-color: var(--primary-dark);
            color: var(--accent-color);
        }

        .appointment-list {
            margin-top: 2rem;
        }

        .appointment-item {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .appointment-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .appointment-details {
            font-size: 0.9rem;
            color: var(--text-color);
        }

        /* Formulario de Añadir/Editar Cita */
        .appointment-form {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .appointment-form h2 {
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

        /* Modal para Editar Cita */
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
            .calendar-grid {
                grid-template-columns: repeat(1, 1fr);
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
                <div class="notification-badge"><?php echo count($appointments); ?></div>
            </a>
            <a href="citas.php" class="nav-item active">
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
            <img src="images/hiplogo.jpg" alt="HIP ENERGY Logo" class="logo">
        </div>
    </nav>

    <main class="main-content">
        <h1>Appointments Dashboard</h1>

        <!-- Formulario para Añadir Nueva Cita -->
        <div class="appointment-form">
            <h2>Add New Appointment</h2>
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
            <form method="POST" action="citas.php">
                <input type="hidden" name="action" value="add">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" class="form-input" required>

                <label for="description">Description:</label>
                <textarea id="description" name="description" class="form-input" rows="4"></textarea>

                <label for="date">Date:</label>
                <input type="date" id="date" name="date" class="form-input" required>

                <label for="time">Time:</label>
                <input type="time" id="time" name="time" class="form-input" required>

                <label for="location">Location:</label>
                <input type="text" id="location" name="location" class="form-input" required>

                <button type="submit" class="btn">Add Appointment</button>
            </form>
        </div>

        <!-- Lista de Citas Existentes -->
        <div class="appointments-dashboard">
            <h2>Upcoming Appointments</h2>
            <?php foreach ($appointments as $appointment): ?>
                <div class="appointment-item" data-id="<?php echo $appointment['id']; ?>">
                    <div class="appointment-title"><?php echo htmlspecialchars($appointment['title']); ?></div>
                    <div class="appointment-details">
                        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($appointment['date'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($appointment['time'])); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($appointment['location']); ?></p>
                        <?php if (!empty($appointment['description'])): ?>
                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($appointment['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="appointment-actions">
                        <button class="notification-action btn-edit" data-id="<?php echo $appointment['id']; ?>">Edit</button>
                        <form method="POST" action="citas.php" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $appointment['id']; ?>">
                            <button type="submit" class="notification-action btn-delete" onclick="return confirm('Are you sure you want to delete this appointment?');">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($appointments) === 0): ?>
                <p>No upcoming appointments.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal para Editar Cita -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Appointment</h2>
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
            <form method="POST" action="citas.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <label for="edit_title">Title:</label>
                <input type="text" id="edit_title" name="title" class="form-input" required>

                <label for="edit_description">Description:</label>
                <textarea id="edit_description" name="description" class="form-input" rows="4"></textarea>

                <label for="edit_date">Date:</label>
                <input type="date" id="edit_date" name="date" class="form-input" required>

                <label for="edit_time">Time:</label>
                <input type="time" id="edit_time" name="time" class="form-input" required>

                <label for="edit_location">Location:</label>
                <input type="text" id="edit_location" name="location" class="form-input" required>

                <button type="submit" class="btn">Update Appointment</button>
            </form>
        </div>
    </div>

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
        const closeModal = document.getElementsByClassName('close')[0];
        const editButtons = document.getElementsByClassName('btn-edit');

        // Cuando el usuario hace clic en cualquier botón de editar, abrir el modal con los datos correspondientes
        Array.from(editButtons).forEach(button => {
            button.addEventListener('click', function() {
                const appointmentItem = this.closest('.appointment-item');
                const id = appointmentItem.getAttribute('data-id');
                const title = appointmentItem.querySelector('.appointment-title').innerText;
                const description = appointmentItem.querySelector('.appointment-message') ? appointmentItem.querySelector('.appointment-message').innerText : '';
                const dateText = appointmentItem.querySelector('.appointment-details p:nth-child(1)').innerText;
                const timeText = appointmentItem.querySelector('.appointment-details p:nth-child(2)').innerText;
                const locationText = appointmentItem.querySelector('.appointment-details p:nth-child(3)').innerText;

                const date = dateText.replace('Date: ', '');
                const time = timeText.replace('Time: ', '');
                const location = locationText.replace('Location: ', '');

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_title').value = title;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_date').value = date;
                document.getElementById('edit_time').value = time;
                document.getElementById('edit_location').value = location;

                modal.style.display = "block";
            });
        });

        // Cuando el usuario hace clic en (x), cerrar el modal
        closeModal.onclick = function() {
            modal.style.display = "none";
        }

        // Cuando el usuario hace clic fuera del modal, cerrarlo
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Calendar functionality
        const prevMonthBtn = document.getElementById('prevMonth');
        const nextMonthBtn = document.getElementById('nextMonth');
        const currentMonthSpan = document.getElementById('currentMonth');
        const calendarGrid = document.getElementById('calendarGrid');

        let currentDate = new Date();

        function updateCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            currentMonthSpan.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);
            
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            
            calendarGrid.innerHTML = '';

            // Crear encabezados de los días de la semana
            const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            daysOfWeek.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day';
                dayHeader.style.backgroundColor = var(--primary-dark);
                dayHeader.style.color = var(--text-color);
                dayHeader.style.fontWeight = 'bold';
                dayHeader.style.textAlign = 'center';
                dayHeader.innerText = day;
                calendarGrid.appendChild(dayHeader);
            });

            // Espacios en blanco antes del primer día del mes
            for (let i = 0; i < firstDay.getDay(); i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'calendar-day';
                emptyDay.innerHTML = '';
                calendarGrid.appendChild(emptyDay);
            }

            // Obtener citas para el mes actual
            try {
                $stmt = $pdo->prepare("SELECT * FROM appointments WHERE MONTH(date) = :month AND YEAR(date) = :year");
                $stmt->execute([
                    'month' => $month + 1, // Los meses en SQL son 1-12
                    'year' => $year
                ]);
                $monthlyAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Error al obtener las citas del mes: " . $e->getMessage());
            }

            // Crear un array de citas por día
            $appointmentsByDay = [];
            foreach ($monthlyAppointments as $appointment) {
                $day = date('j', strtotime($appointment['date']));
                if (!isset($appointmentsByDay[$day])) {
                    $appointmentsByDay[$day] = [];
                }
                $appointmentsByDay[$day][] = $appointment;
            }

            // Crear los días del calendario
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                dayElement.innerHTML = `<div class="calendar-date">${day}</div>`;

                if (appointmentsByDay[day]) {
                    appointmentsByDay[day].forEach(appointment => {
                        const appointmentDiv = document.createElement('div');
                        appointmentDiv.className = 'appointment';
                        appointmentDiv.innerText = appointment['title'];
                        appointmentDiv.title = `${appointment['title']} at ${appointment['time']} - ${appointment['location']}`;
                        dayElement.appendChild(appointmentDiv);
                    });
                }

                calendarGrid.appendChild(dayElement);
            }
        }

        prevMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateCalendar();
        });

        nextMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateCalendar();
        });

        updateCalendar();
    </script>
</body>
</html>
