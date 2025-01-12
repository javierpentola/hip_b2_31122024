<?php
// Invoices_expenses.php

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
    // Gestión de Facturas
    if (isset($_POST['action']) && $_POST['action'] === 'add_invoice') {
        $client = trim($_POST['client']);
        $date = $_POST['date'];
        $amount = $_POST['amount'];
        $status = $_POST['status'];

        // Validaciones básicas
        if (empty($client) || empty($date) || empty($amount) || empty($status)) {
            $error_invoice = "Cliente, fecha, monto y estado son obligatorios.";
        } elseif (!validateDate($date)) {
            $error_invoice = "Fecha inválida.";
        } elseif (!is_numeric($amount) || $amount < 0) {
            $error_invoice = "Monto inválido.";
        } elseif (!in_array($status, ['Pendiente', 'Pagada', 'Vencida'])) {
            $error_invoice = "Estado inválido.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO invoices (client, date, amount, status) VALUES (:client, :date, :amount, :status)");
                $stmt->execute([
                    'client' => $client,
                    'date' => $date,
                    'amount' => $amount,
                    'status' => $status
                ]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Añadió una nueva factura para el cliente '$client'.");

                $success_invoice = "Factura añadida exitosamente.";
            } catch (PDOException $e) {
                $error_invoice = "Error al añadir la factura: " . $e->getMessage();
            }
        }
    }

    // Edición de Facturas
    if (isset($_POST['action']) && $_POST['action'] === 'edit_invoice' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $client = trim($_POST['client']);
        $date = $_POST['date'];
        $amount = $_POST['amount'];
        $status = $_POST['status'];

        // Validaciones básicas
        if (empty($client) || empty($date) || empty($amount) || empty($status)) {
            $error_edit_invoice = "Cliente, fecha, monto y estado son obligatorios.";
        } elseif (!validateDate($date)) {
            $error_edit_invoice = "Fecha inválida.";
        } elseif (!is_numeric($amount) || $amount < 0) {
            $error_edit_invoice = "Monto inválido.";
        } elseif (!in_array($status, ['Pendiente', 'Pagada', 'Vencida'])) {
            $error_edit_invoice = "Estado inválido.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE invoices SET client = :client, date = :date, amount = :amount, status = :status WHERE id = :id");
                $stmt->execute([
                    'client' => $client,
                    'date' => $date,
                    'amount' => $amount,
                    'status' => $status,
                    'id' => $id
                ]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Editó la factura ID: $id para el cliente '$client'.");

                $success_edit_invoice = "Factura actualizada exitosamente.";
            } catch (PDOException $e) {
                $error_edit_invoice = "Error al actualizar la factura: " . $e->getMessage();
            }
        }
    }

    // Eliminación de Facturas
    if (isset($_POST['delete_invoice_id'])) {
        $delete_id = $_POST['delete_invoice_id'];
        try {
            // Obtener detalles de la factura para el logging
            $stmt = $pdo->prepare("SELECT client FROM invoices WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($invoice) {
                $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = :id");
                $stmt->execute(['id' => $delete_id]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Eliminó la factura ID: $delete_id para el cliente '{$invoice['client']}'.");

                $success_delete_invoice = "Factura eliminada exitosamente.";
            } else {
                $error_delete_invoice = "Factura no encontrada.";
            }
        } catch (PDOException $e) {
            $error_delete_invoice = "Error al eliminar la factura: " . $e->getMessage();
        }
    }

    // Gestión de Gastos
    if (isset($_POST['action']) && $_POST['action'] === 'add_expense') {
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $amount = $_POST['amount'];
        $category = $_POST['category'];

        // Validaciones básicas
        if (empty($description) || empty($date) || empty($amount) || empty($category)) {
            $error_expense = "Descripción, fecha, monto y categoría son obligatorios.";
        } elseif (!validateDate($date)) {
            $error_expense = "Fecha inválida.";
        } elseif (!is_numeric($amount) || $amount < 0) {
            $error_expense = "Monto inválido.";
        } elseif (!in_array($category, ['Operativo', 'Administrativo', 'Marketing', 'Otro'])) {
            $error_expense = "Categoría inválida.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO expenses (description, date, amount, category) VALUES (:description, :date, :amount, :category)");
                $stmt->execute([
                    'description' => $description,
                    'date' => $date,
                    'amount' => $amount,
                    'category' => $category
                ]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Añadió un nuevo gasto: '$description'.");

                $success_expense = "Gasto añadido exitosamente.";
            } catch (PDOException $e) {
                $error_expense = "Error al añadir el gasto: " . $e->getMessage();
            }
        }
    }

    // Edición de Gastos
    if (isset($_POST['action']) && $_POST['action'] === 'edit_expense' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $description = trim($_POST['description']);
        $date = $_POST['date'];
        $amount = $_POST['amount'];
        $category = $_POST['category'];

        // Validaciones básicas
        if (empty($description) || empty($date) || empty($amount) || empty($category)) {
            $error_edit_expense = "Descripción, fecha, monto y categoría son obligatorios.";
        } elseif (!validateDate($date)) {
            $error_edit_expense = "Fecha inválida.";
        } elseif (!is_numeric($amount) || $amount < 0) {
            $error_edit_expense = "Monto inválido.";
        } elseif (!in_array($category, ['Operativo', 'Administrativo', 'Marketing', 'Otro'])) {
            $error_edit_expense = "Categoría inválida.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE expenses SET description = :description, date = :date, amount = :amount, category = :category WHERE id = :id");
                $stmt->execute([
                    'description' => $description,
                    'date' => $date,
                    'amount' => $amount,
                    'category' => $category,
                    'id' => $id
                ]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Editó el gasto ID: $id, Descripción: '$description'.");

                $success_edit_expense = "Gasto actualizado exitosamente.";
            } catch (PDOException $e) {
                $error_edit_expense = "Error al actualizar el gasto: " . $e->getMessage();
            }
        }
    }

    // Eliminación de Gastos
    if (isset($_POST['delete_expense_id'])) {
        $delete_id = $_POST['delete_expense_id'];
        try {
            // Obtener detalles del gasto para el logging
            $stmt = $pdo->prepare("SELECT description FROM expenses WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($expense) {
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = :id");
                $stmt->execute(['id' => $delete_id]);

                // (Opcional) Registrar actividad
                logActivity($pdo, $admin_id, "Eliminó el gasto ID: $delete_id, Descripción: '{$expense['description']}'.");

                $success_delete_expense = "Gasto eliminado exitosamente.";
            } else {
                $error_delete_expense = "Gasto no encontrado.";
            }
        } catch (PDOException $e) {
            $error_delete_expense = "Error al eliminar el gasto: " . $e->getMessage();
        }
    }

    // Obtener todas las facturas
    try {
        $stmt = $pdo->prepare("SELECT * FROM invoices ORDER BY date DESC, id DESC");
        $stmt->execute();
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener las facturas: " . $e->getMessage());
    }

    // Obtener todos los gastos
    try {
        $stmt = $pdo->prepare("SELECT * FROM expenses ORDER BY date DESC, id DESC");
        $stmt->execute();
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener los gastos: " . $e->getMessage());
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
    <title>HIP ENERGY Navigation - Admin Panel - Invoices & Expenses</title>
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

        /* Tabulaciones */
        .tab-container {
            display: flex;
            margin-bottom: 1rem;
        }

        .tab {
            padding: 0.5rem 1rem;
            background-color: #f1f1f1;
            border: 1px solid #ddd;
            cursor: pointer;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            margin-right: 0.5rem;
        }

        .tab.active {
            background-color: var(--primary-color);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Tablas */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .data-table th {
            background-color: var(--primary-color);
            color: white;
        }

        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Formularios */
        .add-form {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .add-form input, .add-form select {
            flex: 1;
            min-width: 200px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Botones */
        .action-btn {
            padding: 0.3rem 0.6rem;
            margin: 0.2rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .action-btn:hover {
            background-color: var(--primary-dark);
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
                <div class="notification-badge"><?php echo count($invoices); ?></div>
            </a>
            <a href="modoaccesible.php" class="nav-item">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Fault Reporting</span>
            </a>
        </div>
        <div class="logo-container">
            <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/Normal-S0ZM46xhJ8Mm0vNUqKXmmqWS9gTvZJ.png" alt="HIP ENERGY Logo" class="logo">
        </div>
    </nav>

    <main class="main-content">
        <div class="management-section">
            <h2>2. Gestión de Facturas y Gastos</h2>
            <div class="tab-container">
                <div class="tab active" onclick="showTab('invoices')">Facturas</div>
                <div class="tab" onclick="showTab('expenses')">Gastos</div>
            </div>
            <div id="invoices" class="tab-content active">
                <h3>Administración de Facturas</h3>

                <!-- Mensajes de Error/Éxito para Facturas -->
                <?php if (isset($error_invoice)): ?>
                    <div class="error-message">
                        <p style="color: red;"><?php echo htmlspecialchars($error_invoice); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($success_invoice)): ?>
                    <div class="success-message">
                        <p style="color: green;"><?php echo htmlspecialchars($success_invoice); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($error_edit_invoice)): ?>
                    <div class="error-message">
                        <p style="color: red;"><?php echo htmlspecialchars($error_edit_invoice); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($success_edit_invoice)): ?>
                    <div class="success-message">
                        <p style="color: green;"><?php echo htmlspecialchars($success_edit_invoice); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($success_delete_invoice)): ?>
                    <div class="success-message">
                        <p style="color: green;"><?php echo htmlspecialchars($success_delete_invoice); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($error_delete_invoice)): ?>
                    <div class="error-message">
                        <p style="color: red;"><?php echo htmlspecialchars($error_delete_invoice); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Formulario para Añadir Nueva Factura -->
                <form method="POST" action="Invoices_expenses.php" id="addInvoiceForm" class="add-form">
                    <input type="hidden" name="action" value="add_invoice">
                    <input type="text" name="client" id="invoiceClient" placeholder="Cliente" required>
                    <input type="date" name="date" id="invoiceDate" required>
                    <input type="number" name="amount" id="invoiceAmount" placeholder="Monto" step="0.01" required>
                    <select name="status" id="invoiceStatus" required>
                        <option value="">Seleccionar Estado</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Pagada">Pagada</option>
                        <option value="Vencida">Vencida</option>
                    </select>
                    <button type="submit" class="action-btn">Añadir Factura</button>
                </form>

                <!-- Tabla de Facturas Existentes -->
                <table class="data-table" id="invoiceTable">
                    <thead>
                        <tr>
                            <th>ID Factura</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($invoice['id']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['client']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['date']); ?></td>
                                <td>$<?php echo number_format($invoice['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($invoice['status']); ?></td>
                                <td>
                                    <button class="action-btn btn-edit" data-id="<?php echo $invoice['id']; ?>">Editar</button>
                                    <form method="POST" action="Invoices_expenses.php" style="display:inline;">
                                        <input type="hidden" name="delete_invoice_id" value="<?php echo $invoice['id']; ?>">
                                        <button type="submit" class="action-btn btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar esta factura?');">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($invoices) === 0): ?>
                            <tr>
                                <td colspan="6">No hay facturas disponibles.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="expenses" class="tab-content">
                <h3>Administración de Gastos</h3>

                <!-- Mensajes de Error/Éxito para Gastos -->
                <?php if (isset($error_expense)): ?>
                    <div class="error-message">
                        <p style="color: red;"><?php echo htmlspecialchars($error_expense); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($success_expense)): ?>
                    <div class="success-message">
                        <p style="color: green;"><?php echo htmlspecialchars($success_expense); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($error_edit_expense)): ?>
                    <div class="error-message">
                        <p style="color: red;"><?php echo htmlspecialchars($error_edit_expense); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($success_edit_expense)): ?>
                    <div class="success-message">
                        <p style="color: green;"><?php echo htmlspecialchars($success_edit_expense); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($success_delete_expense)): ?>
                    <div class="success-message">
                        <p style="color: green;"><?php echo htmlspecialchars($success_delete_expense); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($error_delete_expense)): ?>
                    <div class="error-message">
                        <p style="color: red;"><?php echo htmlspecialchars($error_delete_expense); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Formulario para Añadir Nuevo Gasto -->
                <form method="POST" action="Invoices_expenses.php" id="addExpenseForm" class="add-form">
                    <input type="hidden" name="action" value="add_expense">
                    <input type="text" name="description" id="expenseDescription" placeholder="Descripción" required>
                    <input type="date" name="date" id="expenseDate" required>
                    <input type="number" name="amount" id="expenseAmount" placeholder="Monto" step="0.01" required>
                    <select name="category" id="expenseCategory" required>
                        <option value="">Seleccionar Categoría</option>
                        <option value="Operativo">Operativo</option>
                        <option value="Administrativo">Administrativo</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Otro">Otro</option>
                    </select>
                    <button type="submit" class="action-btn">Añadir Gasto</button>
                </form>

                <!-- Tabla de Gastos Existentes -->
                <table class="data-table" id="expenseTable">
                    <thead>
                        <tr>
                            <th>ID Gasto</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Categoría</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($expense['id']); ?></td>
                                <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                <td><?php echo htmlspecialchars($expense['date']); ?></td>
                                <td>$<?php echo number_format($expense['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                <td>
                                    <button class="action-btn btn-edit-expense" data-id="<?php echo $expense['id']; ?>">Editar</button>
                                    <form method="POST" action="Invoices_expenses.php" style="display:inline;">
                                        <input type="hidden" name="delete_expense_id" value="<?php echo $expense['id']; ?>">
                                        <button type="submit" class="action-btn btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar este gasto?');">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($expenses) === 0): ?>
                            <tr>
                                <td colspan="6">No hay gastos disponibles.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modales para Editar Facturas y Gastos -->
    <!-- Modal para Editar Factura -->
    <div id="editInvoiceModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Editar Factura</h2>
            <form method="POST" action="Invoices_expenses.php">
                <input type="hidden" name="action" value="edit_invoice">
                <input type="hidden" name="id" id="edit_invoice_id">
                <div class="add-form">
                    <input type="text" name="client" id="edit_invoice_client" placeholder="Cliente" required>
                    <input type="date" name="date" id="edit_invoice_date" required>
                    <input type="number" name="amount" id="edit_invoice_amount" placeholder="Monto" step="0.01" required>
                    <select name="status" id="edit_invoice_status" required>
                        <option value="">Seleccionar Estado</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Pagada">Pagada</option>
                        <option value="Vencida">Vencida</option>
                    </select>
                </div>
                <button type="submit" class="action-btn">Actualizar Factura</button>
            </form>
        </div>
    </div>

    <!-- Modal para Editar Gasto -->
    <div id="editExpenseModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Editar Gasto</h2>
            <form method="POST" action="Invoices_expenses.php">
                <input type="hidden" name="action" value="edit_expense">
                <input type="hidden" name="id" id="edit_expense_id">
                <div class="add-form">
                    <input type="text" name="description" id="edit_expense_description" placeholder="Descripción" required>
                    <input type="date" name="date" id="edit_expense_date" required>
                    <input type="number" name="amount" id="edit_expense_amount" placeholder="Monto" step="0.01" required>
                    <select name="category" id="edit_expense_category" required>
                        <option value="">Seleccionar Categoría</option>
                        <option value="Operativo">Operativo</option>
                        <option value="Administrativo">Administrativo</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <button type="submit" class="action-btn">Actualizar Gasto</button>
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
        const editInvoiceModal = document.getElementById('editInvoiceModal');
        const editExpenseModal = document.getElementById('editExpenseModal');
        const closeModalButtons = document.getElementsByClassName('close-modal');

        // Cerrar modales al hacer clic en la "X"
        Array.from(closeModalButtons).forEach(button => {
            button.onclick = function() {
                this.parentElement.parentElement.style.display = "none";
            }
        });

        // Cerrar modales al hacer clic fuera del contenido
        window.onclick = function(event) {
            if (event.target == editInvoiceModal) {
                editInvoiceModal.style.display = "none";
            }
            if (event.target == editExpenseModal) {
                editExpenseModal.style.display = "none";
            }
        }

        // Funciones para abrir modales de edición
        const editInvoiceButtons = document.getElementsByClassName('btn-edit');
        Array.from(editInvoiceButtons).forEach(button => {
            button.addEventListener('click', function() {
                const row = this.parentElement.parentElement.parentElement;
                const id = this.getAttribute('data-id');
                const client = row.cells[1].innerText;
                const date = row.cells[2].innerText;
                const amount = row.cells[3].innerText.replace('$', '');
                const status = row.cells[4].innerText;

                document.getElementById('edit_invoice_id').value = id;
                document.getElementById('edit_invoice_client').value = client;
                document.getElementById('edit_invoice_date').value = date;
                document.getElementById('edit_invoice_amount').value = amount;
                document.getElementById('edit_invoice_status').value = status;

                editInvoiceModal.style.display = "block";
            });
        });

        const editExpenseButtons = document.getElementsByClassName('btn-edit-expense');
        Array.from(editExpenseButtons).forEach(button => {
            button.addEventListener('click', function() {
                const row = this.parentElement.parentElement.parentElement;
                const id = this.getAttribute('data-id');
                const description = row.cells[1].innerText;
                const date = row.cells[2].innerText;
                const amount = row.cells[3].innerText.replace('$', '');
                const category = row.cells[4].innerText;

                document.getElementById('edit_expense_id').value = id;
                document.getElementById('edit_expense_description').value = description;
                document.getElementById('edit_expense_date').value = date;
                document.getElementById('edit_expense_amount').value = amount;
                document.getElementById('edit_expense_category').value = category;

                editExpenseModal.style.display = "block";
            });
        });

        // Función de Cierre de Sesión
        function logout() {
            // Implementar lógica de cierre de sesión aquí
            alert("Admin ha cerrado sesión");
            window.location.href = 'login.php';
        }

        // Función para mostrar las pestañas
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // Inicializar las tablas y formularios
        document.addEventListener('DOMContentLoaded', () => {
            // Puedes agregar aquí inicializaciones adicionales si es necesario
        });
    </script>
</body>
</html>
