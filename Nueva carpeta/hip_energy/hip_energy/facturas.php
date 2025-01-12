<?php
// facturas.php

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

// Obtener configuraciones del sistema
try {
    $stmt = $pdo->prepare("SELECT * FROM system_settings LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) {
        // Insertar configuración por defecto si no existe
        $stmt = $pdo->prepare("INSERT INTO system_settings (currency, tax_rate) VALUES ('USD', 0.00)");
        $stmt->execute();
        $settings = [
            'currency' => 'USD',
            'tax_rate' => '0.00',
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
} catch (PDOException $e) {
    die("Error al obtener configuraciones: " . $e->getMessage());
}

// Función para obtener el símbolo de la moneda
function getCurrencySymbol($currency) {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'CAD' => 'C$',
        'AUD' => 'A$'
        // Agrega más monedas según tus necesidades
    ];
    return isset($symbols[$currency]) ? $symbols[$currency] : $currency . ' ';
}

// Obtener el símbolo de la moneda actual
$currency_symbol = getCurrencySymbol($settings['currency']);

// Procesar búsqueda de facturas
$search_params = [];
$search_conditions = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Validar y asignar parámetros de búsqueda
    if (!empty($_GET['startDate'])) {
        $search_conditions[] = "due_date >= :startDate";
        $search_params['startDate'] = $_GET['startDate'];
    }
    if (!empty($_GET['endDate'])) {
        $search_conditions[] = "due_date <= :endDate";
        $search_params['endDate'] = $_GET['endDate'];
    }
    if (!empty($_GET['minAmount'])) {
        $search_conditions[] = "amount >= :minAmount";
        $search_params['minAmount'] = $_GET['minAmount'];
    }
    if (!empty($_GET['maxAmount'])) {
        $search_conditions[] = "amount <= :maxAmount";
        $search_params['maxAmount'] = $_GET['maxAmount'];
    }
}

// Construir consulta SQL para buscar facturas
$sql = "SELECT b.*, c.name as customer_name FROM bills b 
        JOIN customers c ON b.customer_id = c.id";
if (!empty($search_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $search_conditions);
}
$sql .= " ORDER BY b.due_date DESC";

// Preparar y ejecutar la consulta
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($search_params);
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al buscar facturas: " . $e->getMessage());
}

// (Opcional) Registrar actividad de búsqueda
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($search_conditions)) {
    $action_desc = "Buscó facturas con los siguientes criterios: " . implode(", ", array_keys($search_params));
    logActivity($pdo, $admin_id, $action_desc);
}

// Obtener los últimos 5 pagos para la tabla de "Recent Bills"
try {
    $stmt = $pdo->prepare("SELECT b.*, c.name as customer_name FROM bills b 
                           JOIN customers c ON b.customer_id = c.id 
                           ORDER BY b.due_date DESC LIMIT 5");
    $stmt->execute();
    $recent_bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener facturas recientes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIP ENERGY Navigation - Admin Panel - Facturas</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .dashboard {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 2rem;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            grid-column: span 1;
        }

        .wide-card {
            grid-column: span 2;
        }

        /* Eliminar o comentar la regla siguiente si existe */
        /* .dashboard > .card:nth-last-child(-n+3) {
            grid-column: span 1;
        } */

        @media (max-width: 1200px) {
            .dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
            .card, .wide-card {
                grid-column: span 1;
            }
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
            .dashboard {
                grid-template-columns: 1fr;
            }

            .card, .wide-card {
                grid-column: span 1;
            }
        }
        .card h2 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .stat {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .chart-container {
            width: 100%;
            height: 300px;
        }

        .accessible-mode .card {
            background-color: #ffffff;
            color: #000000;
        }

        .accessible-mode .stat {
            color: #000000;
        }

        .accessible-mode canvas {
            filter: grayscale(100%) contrast(120%);
        }

        .accessible-mode .nav-item {
            color: #ffffff;
        }

        .accessible-mode .brand {
            color: #ffffff;
        }


        .accessible-mode .main-content h1,
        .accessible-mode .card {
            color: #000000;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #ccc;
        }

        .btn-download, .btn-edit {
            color: var(--accent-color);
            text-decoration: none;
        }

        .btn-download:hover, .btn-edit:hover {
            text-decoration: underline;
        }

        .btn-add {
            display: block;
            margin-top: 1rem;
            color: var(--accent-color);
            text-decoration: none;
        }

        .btn-add:hover {
            text-decoration: underline;
        }

        .payment-method {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .payment-method i {
            margin-right: 0.5rem;
        }

        .payment-method .btn-edit {
            margin-left: auto;
        }

        /* Tabla responsive */
        @media (max-width: 600px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr {
                margin-bottom: 1rem;
            }
            td {
                border: none;
                position: relative;
                padding-left: 50%;
            }
            td::before {
                position: absolute;
                top: 0;
                left: 0;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
            }
            td:nth-of-type(1)::before { content: "Date"; }
            td:nth-of-type(2)::before { content: "Amount"; }
            td:nth-of-type(3)::before { content: "Status"; }
            td:nth-of-type(4)::before { content: "Action"; }
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
            <a href="facturas.php" class="nav-item active">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Bills</span>
            </a>
            <a href="notificaciones.php" class="nav-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <div class="notification-badge">4</div>
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
            <img src="images/hiplogo.jpg" alt="HIP ENERGY Logo" class="logo">
        </div>
    </nav>

    <main class="main-content">
        <h1>Bill Dashboard</h1>
        <div class="dashboard">
            <div class="card">
                <h2>Current Bill</h2>
                <div class="stat"><?php echo $currency_symbol . number_format($bills[0]['amount'], 2); ?> </div>
                <p>Due on <?php echo date('M d, Y', strtotime($bills[0]['due_date'])); ?></p>
            </div>
            <div class="card">
                <h2>Average Monthly Bill</h2>
                <div class="stat">
                    <?php
                        // Calcular el promedio mensual
                        try {
                            $stmt = $pdo->prepare("SELECT AVG(monthly_total) as avg_monthly_bill FROM (
                                SELECT SUM(amount) as monthly_total 
                                FROM bills 
                                WHERE due_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                GROUP BY DATE_FORMAT(due_date, '%Y-%m')
                            ) as monthly_totals");
                            $stmt->execute();
                            $avg_monthly_bill = $stmt->fetch(PDO::FETCH_ASSOC)['avg_monthly_bill'] ?? 0;
                            echo $currency_symbol . number_format($avg_monthly_bill, 2);
                        } catch (PDOException $e) {
                            echo "Error";
                        }
                    ?>
                </div>
                <p>Last 6 months</p>
            </div>
            <div class="card">
                <h2>Payment Streak</h2>
                <div class="stat">
                    <?php
                        // Calcular la racha de pagos consecutivos
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as streak FROM bills 
                                                   WHERE status = 'Paid' 
                                                   AND due_date <= CURDATE()
                                                   ORDER BY due_date DESC");
                            $stmt->execute();
                            $streak = $stmt->fetch(PDO::FETCH_ASSOC)['streak'] ?? 0;
                            echo number_format($streak) . " months";
                        } catch (PDOException $e) {
                            echo "Error";
                        }
                    ?>
                </div>
                <p>Consecutive on-time payments</p>
            </div>
            <div class="card">
                <h2>Next Bill Estimate</h2>
                <div class="stat">
                    <?php
                        // Estimación de la próxima factura
                        try {
                            $stmt = $pdo->prepare("SELECT SUM(amount) as next_bill FROM bills 
                                                   WHERE due_date = (SELECT MIN(due_date) FROM bills WHERE due_date > CURDATE())");
                            $stmt->execute();
                            $next_bill = $stmt->fetch(PDO::FETCH_ASSOC)['next_bill'] ?? 0;
                            echo $currency_symbol . number_format($next_bill, 2);
                        } catch (PDOException $e) {
                            echo "Error";
                        }
                    ?>
                </div>
                <p>Based on current usage</p>
            </div>
            <div class="card wide-card">
                <h2>Search Bills</h2>
                <form id="searchForm" method="GET" action="facturas.php">
                    <input type="date" id="startDate" name="startDate" class="form-input" value="<?php echo htmlspecialchars($_GET['startDate'] ?? ''); ?>">
                    <input type="date" id="endDate" name="endDate" class="form-input" value="<?php echo htmlspecialchars($_GET['endDate'] ?? ''); ?>">
                    <input type="number" id="minAmount" name="minAmount" placeholder="Min Amount" class="form-input" step="0.01" value="<?php echo htmlspecialchars($_GET['minAmount'] ?? ''); ?>">
                    <input type="number" id="maxAmount" name="maxAmount" placeholder="Max Amount" class="form-input" step="0.01" value="<?php echo htmlspecialchars($_GET['maxAmount'] ?? ''); ?>">
                    <button type="submit" class="btn">Search</button>
                </form>
            </div>
            <div class="card wide-card">
                <h2>Recent Bills</h2>
                <table id="recentBills">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_bills) > 0): ?>
                            <?php foreach ($recent_bills as $bill): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($bill['due_date'])); ?></td>
                                    <td><?php echo $currency_symbol . number_format($bill['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($bill['status']); ?></td>
                                    <td>
                                        <a href="download_bill.php?id=<?php echo $bill['id']; ?>" class="btn-download">Download</a>
                                        <a href="edit_bill.php?id=<?php echo $bill['id']; ?>" class="btn-edit">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No bills found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h2>Payment Methods</h2>
                <div id="paymentMethods">
                    <?php
                        // Obtener métodos de pago del cliente
                        // Asumiendo que hay una tabla 'payment_methods' relacionada con 'customers'
                        // Necesitarás adaptar esto según tu esquema de base de datos
                        try {
                            // Obtener el ID del cliente (esto depende de cómo esté estructurada tu aplicación)
                            // Aquí asumimos que tienes el 'customer_id' en la sesión
                            $customer_id = $_SESSION['customer_id'] ?? 1; // Por defecto 1 si no está definido
                            
                            $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE customer_id = :customer_id");
                            $stmt->execute(['customer_id' => $customer_id]);
                            $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            $payment_methods = [];
                        }
                    ?>
                    <?php if (count($payment_methods) > 0): ?>
                        <?php foreach ($payment_methods as $method): ?>
                            <div class="payment-method">
                                <?php
                                    // Determinar el ícono basado en el tipo de método de pago
                                    switch($method['type']) {
                                        case 'Credit Card':
                                            echo '<i class="fas fa-credit-card"></i>';
                                            break;
                                        case 'Bank Account':
                                            echo '<i class="fas fa-university"></i>';
                                            break;
                                        default:
                                            echo '<i class="fas fa-wallet"></i>';
                                    }
                                ?>
                                <span><?php echo htmlspecialchars($method['details']); ?></span>
                                <a href="edit_payment_method.php?id=<?php echo $method['id']; ?>" class="btn-edit">Edit</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No payment methods found.</p>
                    <?php endif; ?>
                    <a href="add_payment_method.php" class="btn-add">Add Payment Method</a>
                </div>
            </div>
        </div>
    </main>

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

        function updateChartsForAccessibleMode() {
            const isAccessible = document.body.classList.contains('accessible-mode');
            const charts = [billHistoryChart]; // Añade otros gráficos si es necesario
            charts.forEach(chart => {
                if (isAccessible) {
                    chart.options.plugins.legend.labels.color = '#000000';
                    chart.options.scales.x.ticks.color = '#000000';
                    chart.options.scales.y.ticks.color = '#000000';
                } else {
                    chart.options.plugins.legend.labels.color = '#000000';
                    chart.options.scales.x.ticks.color = '#000000';
                    chart.options.scales.y.ticks.color = '#000000';
                }
                chart.update();
            });
        }

        // Chart Options
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#000000'
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: '#000000'
                    }
                },
                y: {
                    ticks: {
                        color: '#000000'
                    }
                }
            }
        };

        // Bill History Chart
        const billHistoryChart = new Chart(document.getElementById('billHistoryChart'), {
            type: 'line',
            data: {
                labels: <?php 
                    // Obtener fechas y montos para el gráfico de historial de facturas
                    $bill_history_dates = array_map(function($bill) {
                        return date('M d, Y', strtotime($bill['due_date']));
                    }, $recent_bills);
                    $bill_history_amounts = array_map(function($bill) use ($currency_symbol) {
                        return floatval($bill['amount']);
                    }, $recent_bills);
                    echo json_encode($bill_history_dates); 
                ?>,
                datasets: [{
                    label: 'Bill Amount (<?php echo $currency_symbol; ?>)',
                    data: <?php echo json_encode($bill_history_amounts); ?>,
                    borderColor: '#8833ff',
                    tension: 0.1
                }]
            },
            options: {
                ...chartOptions
            }
        });

        // Opcional: Implementar otros gráficos si es necesario
        // Por ejemplo, un gráfico de pagos pendientes, etc.
    </script>
</body>
</html>
