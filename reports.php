<?php
// (1) Mostrar errores (no usar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// (2) Iniciar sesión
session_start();

// (3) Datos de conexión
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

// (4) FPDF para PDF (asegúrate de que la ruta es correcta)
require('fpdf/fpdf.php');

// (5) Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/* ------------------------------------------------------------------------
   A. EXPORTACIONES: USERS
   ------------------------------------------------------------------------ */
/**
 *  A1. Exportación a CSV para USERS
 */
if (isset($_GET['export']) && $_GET['export'] === 'csv_users') {
    // Obtener filas de la tabla users
    $stmt = $pdo->prepare("SELECT id, username, user_type, email, visits FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cabeceras para forzar la descarga CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=report_users.csv');

    // Abrir el 'output' en modo escritura
    $output = fopen('php://output', 'w');

    // Cabecera de columnas
    fputcsv($output, ['ID', 'Username', 'User Type', 'Email', 'Visits']);

    // Datos
    foreach ($users as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

/**
 *  A2. Exportación a PDF para USERS
 */
if (isset($_GET['export']) && $_GET['export'] === 'pdf_users') {
    // Obtener datos de users
    $stmt = $pdo->prepare("SELECT id, username, user_type, email, visits FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear PDF
    $pdf = new FPDF();
    $pdf->AddPage();

    // Título
    $pdf->SetFont('Arial', '', 16);
    $pdf->Cell(0, 10, 'Reporte de Usuarios', 0, 1, 'C');
    $pdf->Ln(10);

    // Cabecera tabla
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(15, 10, 'ID', 1);
    $pdf->Cell(40, 10, 'Username', 1);
    $pdf->Cell(40, 10, 'Tipo', 1);
    $pdf->Cell(60, 10, 'Email', 1);
    $pdf->Cell(25, 10, 'Visitas', 1);
    $pdf->Ln();

    // Filas
    foreach ($users as $row) {
        $pdf->Cell(15, 10, $row['id'], 1);
        $pdf->Cell(40, 10, $row['username'], 1);
        $pdf->Cell(40, 10, $row['user_type'], 1);
        $pdf->Cell(60, 10, $row['email'], 1);
        $pdf->Cell(25, 10, $row['visits'], 1);
        $pdf->Ln();
    }

    // Salida PDF
    $pdf->Output('D', 'report_users.pdf');
    exit();
}

/* ------------------------------------------------------------------------
   B. EXPORTACIONES: CUSTOMER
   ------------------------------------------------------------------------ */
/**
 *  B1. Exportación a CSV para CUSTOMER
 */
if (isset($_GET['export']) && $_GET['export'] === 'csv_customers') {
    $stmt = $pdo->prepare("SELECT Account_ID, First_Name, Last_Name, Consumption, Balance, Email, username FROM customer");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cabeceras para CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=report_customers.csv');

    $output = fopen('php://output', 'w');

    // Encabezados de columnas
    fputcsv($output, ['Account_ID', 'First_Name', 'Last_Name', 'Consumption', 'Balance', 'Email', 'Username']);

    // Filas
    foreach ($customers as $c) {
        fputcsv($output, $c);
    }

    fclose($output);
    exit();
}

/**
 *  B2. Exportación a PDF para CUSTOMER
 */
if (isset($_GET['export']) && $_GET['export'] === 'pdf_customers') {
    // Obtener datos de customer
    $stmt = $pdo->prepare("SELECT Account_ID, First_Name, Last_Name, Consumption, Balance, Email, username FROM customer");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear PDF
    $pdf = new FPDF();
    $pdf->AddPage();

    // Título
    $pdf->SetFont('Arial', '', 16);
    $pdf->Cell(0, 10, 'Reporte de Customer', 0, 1, 'C');
    $pdf->Ln(10);

    // Cabecera
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(30, 10, 'AccID', 1);
    $pdf->Cell(30, 10, 'Nombre', 1);
    $pdf->Cell(30, 10, 'Apellido', 1);
    $pdf->Cell(25, 10, 'Consumo', 1);
    $pdf->Cell(25, 10, 'Balance', 1);
    $pdf->Cell(50, 10, 'Email', 1);
    $pdf->Cell(0, 10, '', 0); // Ajuste
    $pdf->Ln();

    // Filas
    foreach ($customers as $c) {
        $pdf->Cell(30, 10, $c['Account_ID'], 1);
        $pdf->Cell(30, 10, $c['First_Name'], 1);
        $pdf->Cell(30, 10, $c['Last_Name'], 1);
        $pdf->Cell(25, 10, $c['Consumption'], 1);
        $pdf->Cell(25, 10, $c['Balance'], 1);
        $pdf->Cell(50, 10, $c['Email'], 1);
        $pdf->Ln();
    }

    // Salida PDF
    $pdf->Output('D', 'report_customers.pdf');
    exit();
}

/* ------------------------------------------------------------------------
   C. Consultas para gráficas: USERS
   ------------------------------------------------------------------------ */
try {
    // Totales (usuarios, visitas)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_users, 
            SUM(visits) AS total_visits 
        FROM users
    ");
    $stmt->execute();
    $resUsers = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalUsers  = $resUsers['total_users']  ?? 0;
    $totalVisits = $resUsers['total_visits'] ?? 0;
} catch (PDOException $e) {
    die("Error al obtener datos (usuarios/visitas): " . $e->getMessage());
}

// Distribución por tipo de usuario
try {
    $stmt = $pdo->prepare("
        SELECT user_type, COUNT(*) AS count
        FROM users
        GROUP BY user_type
    ");
    $stmt->execute();
    $userTypeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos de tipos de usuario: " . $e->getMessage());
}

/* ------------------------------------------------------------------------
   D. Consultas para gráficas: CUSTOMER
   ------------------------------------------------------------------------ */
// Sumar Consumption y Balance totales
try {
    $stmt = $pdo->prepare("
        SELECT 
            SUM(Consumption) AS total_consumption,
            SUM(Balance)     AS total_balance
        FROM customer
    ");
    $stmt->execute();
    $resCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalConsumption = $resCustomer['total_consumption'] ?? 0;
    $totalBalance     = $resCustomer['total_balance']     ?? 0;
} catch (PDOException $e) {
    die("Error al obtener datos (customer): " . $e->getMessage());
}

/* ------------------------------------------------------------------------
   E. Preparar datos para Chart.js
   ------------------------------------------------------------------------ */

/** 
 * E1. Gráfico 1 (USERS): Barras con total de usuarios y total de visitas 
 */
$usersVisitsChart = [
    'labels' => ['Total Usuarios', 'Total Visitas'],
    'datasets' => [
        [
            'label' => 'Estadísticas de Users',
            'data' => [$totalUsers, $totalVisits],
            'backgroundColor' => [
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)'
            ],
            'borderColor' => [
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)'
            ],
            'borderWidth' => 1
        ]
    ]
];

/**
 * E2. Gráfico 2 (USERS): Pie con la distribución por tipo de usuario
 */
$userTypeLabels = [];
$userTypeCounts = [];
$userTypeColors = [];
$palette        = [
    'rgba(75, 192, 192, 0.2)',
    'rgba(255, 99, 132, 0.2)',
    'rgba(255, 159, 64, 0.2)',
    'rgba(153, 102, 255, 0.2)',
    'rgba(201, 203, 207, 0.2)'
];
$borderColors   = [
    'rgba(75, 192, 192, 1)',
    'rgba(255, 99, 132, 1)',
    'rgba(255, 159, 64, 1)',
    'rgba(153, 102, 255, 1)',
    'rgba(201, 203, 207, 1)'
];

$i = 0;
foreach ($userTypeData as $ut) {
    $userTypeLabels[] = $ut['user_type'];
    $userTypeCounts[] = (int)$ut['count'];
    // Elegir color
    $userTypeColors[]      = $palette[$i % count($palette)];
    $thisBorder            = $borderColors[$i % count($borderColors)];
    $i++;
}

// Armar dataset
$userTypeChart = [
    'labels' => $userTypeLabels,
    'datasets' => [
        [
            'label' => 'Distribución Tipo de Usuario',
            'data' => $userTypeCounts,
            'backgroundColor' => $userTypeColors,
            'borderColor' => array_slice($borderColors, 0, count($userTypeCounts)),
            'borderWidth' => 1
        ]
    ]
];

/**
 * E3. Gráfico 3 (CUSTOMER): Barras con total consumption y total balance
 */
$customerTotalsChart = [
    'labels' => ['Total Consumption', 'Total Balance'],
    'datasets' => [
        [
            'label' => 'Resumen Customer',
            'data' => [$totalConsumption, $totalBalance],
            'backgroundColor' => [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
            ],
            'borderColor' => [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
            ],
            'borderWidth' => 1
        ]
    ]
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - User & Customer Stats</title>

    <!-- Fuentes / Estilos -->
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #f2c517;
            --primary-dark: #d4a017;
            --accent-color: #f2c517;
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
            transition: margin-left var(--transition-speed) ease;
            min-height: 100vh;
            max-width: 1200px;
            margin-right: auto;
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

        .reports-section {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .reports-section h2 {
            margin-top: 0;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        .charts-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }
        .chart-container {
            flex: 1;
            min-width: 300px;
            height: 300px;
            border: 1px solid #ddd;
            padding: 10px;
            box-sizing: border-box;
        }
        .export-btn {
            margin-top: 1rem;
            margin-right: 1rem;
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
            .charts-wrapper {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<!-- SIDEBAR -->
<nav class="sidebar">
    <div class="brand">USER & CUSTOMER</div>
    <div class="nav-items">
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="user_management.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>User Management</span>
        </a>
        <a href="reports.php" class="nav-item active">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <a href="security.php" class="nav-item">
            <i class="fas fa-shield-alt"></i>
            <span>Security</span>
        </a>
        <a href="system_settings.php" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>System Settings</span>
        </a>
        <a href="monitoring.php" class="nav-item">
            <i class="fas fa-desktop"></i>
            <span>Monitoring</span>
        </a>
    </div>
    <div class="logo-container">
        <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/Normal-S0ZM46xhJ8Mm0vNUqKXmmqWS9gTvZJ.png" alt="Some Logo" class="logo">
    </div>
</nav>

<!-- CONTENIDO PRINCIPAL -->
<main class="main-content">
    <div class="reports-section">
        <h2>Reportes de Usuarios y Clientes</h2>

        <!-- Botones de Exportación -->
        <h3>Exportación de Datos (Users)</h3>
        <button class="action-btn export-btn" onclick="window.location='reports.php?export=csv_users'">
            Exportar Users CSV
        </button>
        <button class="action-btn export-btn" onclick="window.location='reports.php?export=pdf_users'">
            Exportar Users PDF
        </button>

        <h3>Exportación de Datos (Customer)</h3>
        <button class="action-btn export-btn" onclick="window.location='reports.php?export=csv_customers'">
            Exportar Customer CSV
        </button>
        <button class="action-btn export-btn" onclick="window.location='reports.php?export=pdf_customers'">
            Exportar Customer PDF
        </button>

        <h3>Visualización de Reportes</h3>
        <div class="charts-wrapper">
            <!-- Gráfico 1: Barras (Users) Totales -->
            <div class="chart-container">
                <canvas id="usersVisitsChart"></canvas>
            </div>

            <!-- Gráfico 2: Pie (Users) Distribución por Tipo -->
            <div class="chart-container">
                <canvas id="userTypeChart"></canvas>
            </div>
            
            <!-- Gráfico 3: Barras (Customer) Consumo/Balance -->
            <div class="chart-container">
                <canvas id="customerTotalsChart"></canvas>
            </div>
        </div>
    </div>
</main>

<!-- BOTÓN CERRAR SESIÓN -->
<button class="logout-btn" onclick="logout()">Cerrar Sesión</button>

<script>
function logout() {
    alert("Sesión cerrada");
    window.location.href = 'index.php';
}
</script>

<script>
/** DATOS GRÁFICAS (inyectados desde PHP) **/
const usersVisitsChartData   = <?php echo json_encode($usersVisitsChart); ?>;
const userTypeChartData      = <?php echo json_encode($userTypeChart); ?>;
const customerTotalsChartData= <?php echo json_encode($customerTotalsChart); ?>;

function renderCharts() {
    // 1) Gráfico de barras (Users: Totales)
    new Chart(document.getElementById('usersVisitsChart'), {
        type: 'bar',
        data: usersVisitsChartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 2) Gráfico de pie (Users: Tipo)
    new Chart(document.getElementById('userTypeChart'), {
        type: 'pie',
        data: userTypeChartData,
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // 3) Gráfico de barras (Customer: Consumo vs Balance)
    new Chart(document.getElementById('customerTotalsChart'), {
        type: 'bar',
        data: customerTotalsChartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

// Cuando cargue el DOM, renderizar
document.addEventListener('DOMContentLoaded', renderCharts);
</script>
</body>
</html>
