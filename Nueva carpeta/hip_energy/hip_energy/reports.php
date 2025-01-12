<?php
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

// Incluir la biblioteca FPDF para la generación de PDF
require('fpdf/fpdf.php'); // Asegúrate de que la ruta es correcta

// Conectar a la base de datos usando PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Manejar la exportación a CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Obtener todos los clientes
    $stmt = $pdo->prepare("SELECT Account_ID, First_Name, Last_Name, Email, username FROM Customer");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Definir las cabeceras para la descarga del archivo CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=report.csv');

    // Crear un puntero al flujo de salida
    $output = fopen('php://output', 'w');

    // Escribir la fila de cabecera
    fputcsv($output, ['Account ID', 'First Name', 'Last Name', 'Email', 'Username']);

    // Escribir las filas de datos
    foreach ($customers as $customer) {
        fputcsv($output, $customer);
    }

    fclose($output);
    exit();
}

// Manejar la exportación a PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Obtener todos los clientes
    $stmt = $pdo->prepare("SELECT Account_ID, First_Name, Last_Name, Email, username FROM Customer");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear una instancia de FPDF
    $pdf = new FPDF();
    $pdf->AddPage();

    // Establecer la fuente sin estilo (Evita usar 'B' para evitar requerir archivos de fuentes adicionales)
    $pdf->SetFont('Arial', '', 16);
    $pdf->Cell(0, 10, 'Reporte de Clientes', 0, 1, 'C');

    // Espacio
    $pdf->Ln(10);

    // Establecer la fuente para la tabla sin estilo
    $pdf->SetFont('Arial', '', 12);

    // Cabecera de la tabla
    $pdf->Cell(40, 10, 'Account ID', 1);
    $pdf->Cell(40, 10, 'First Name', 1);
    $pdf->Cell(40, 10, 'Last Name', 1);
    $pdf->Cell(50, 10, 'Email', 1);
    $pdf->Cell(30, 10, 'Username', 1);
    $pdf->Ln();

    // Datos de la tabla
    foreach ($customers as $customer) {
        $pdf->Cell(40, 10, $customer['Account_ID'], 1);
        $pdf->Cell(40, 10, $customer['First_Name'], 1);
        $pdf->Cell(40, 10, $customer['Last_Name'], 1);
        $pdf->Cell(50, 10, $customer['Email'], 1);
        $pdf->Cell(30, 10, $customer['username'], 1);
        $pdf->Ln();
    }

    // Salida del PDF
    $pdf->Output('D', 'report.pdf');
    exit();
}

// Obtener datos para los gráficos
// Ejemplo: Total de clientes, total consumo y total balance
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total_customers, SUM(Consumption) AS total_consumption, SUM(Balance) AS total_balance FROM Customer");
    $stmt->execute();
    $reportData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los datos del reporte: " . $e->getMessage());
}

// Obtener datos para el gráfico de estado de facturas (ejemplo)
try {
    // Supongamos que hay una tabla 'Invoices' con un campo 'status'
    // Si no existe, usar datos simulados
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS count FROM Invoices GROUP BY status");
    $stmt->execute();
    $invoiceStatusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($invoiceStatusData)) {
        // Datos simulados si no hay facturas
        $invoiceStatusData = [
            ['status' => 'Pagadas', 'count' => 300],
            ['status' => 'Pendientes', 'count' => 50]
        ];
    }
} catch (PDOException $e) {
    // En caso de error o tabla inexistente, usar datos simulados
    $invoiceStatusData = [
        ['status' => 'Pagadas', 'count' => 300],
        ['status' => 'Pendientes', 'count' => 50]
    ];
}

// Convertir datos a formato JSON para Chart.js
$incomeExpenseData = [
    'labels' => ['Total Clientes', 'Total Consumo', 'Total Balance'],
    'datasets' => [
        [
            'label' => 'Datos Totales',
            'data' => [
                $reportData['total_customers'] ?? 0,
                $reportData['total_consumption'] ?? 0,
                $reportData['total_balance'] ?? 0
            ],
            'backgroundColor' => [
                'rgba(75, 192, 192, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(153, 102, 255, 0.2)'
            ],
            'borderColor' => [
                'rgba(75, 192, 192, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            'borderWidth' => 1
        ]
    ]
];

$invoiceStatusLabels = [];
$invoiceStatusCounts = [];
$invoiceStatusColors = [];

foreach ($invoiceStatusData as $status) {
    $invoiceStatusLabels[] = $status['status'];
    $invoiceStatusCounts[] = $status['count'];
    // Asignar colores diferentes según el estado
    if (strtolower($status['status']) === 'pagadas') {
        $invoiceStatusColors[] = 'rgba(75, 192, 192, 0.2)';
    } else {
        $invoiceStatusColors[] = 'rgba(255, 99, 132, 0.2)';
    }
}

$invoiceStatusChartData = [
    'labels' => $invoiceStatusLabels,
    'datasets' => [
        [
            'data' => $invoiceStatusCounts,
            'backgroundColor' => $invoiceStatusColors,
            'borderColor' => [
                'rgba(75, 192, 192, 1)',
                'rgba(255, 99, 132, 1)'
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
    <title>HIP ENERGY Navigation - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        }

        .export-btn {
            margin-top: 1rem;
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
        }
    </style>
</head>
<body>
<nav class="sidebar">
    <div class="brand">HIP ENERGY</div>
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
        <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/Normal-S0ZM46xhJ8Mm0vNUqKXmmqWS9gTvZJ.png" alt="HIP ENERGY Logo" class="logo">
    </div>
</nav>

<main class="main-content">
    <div class="reports-section">
        <h2>4. Reportes Básicos</h2>
        <h3>Visualización de Reportes</h3>
        
        <div class="charts-wrapper">
            <div class="chart-container">
                <canvas id="incomeExpenseChart"></canvas>
            </div>
            
            <div class="chart-container">
                <canvas id="invoiceStatusChart"></canvas>
            </div>
        </div>
        
        <h3>Exportación de Datos</h3>
        <button class="action-btn export-btn" onclick="exportCSV()">Exportar a CSV</button>
        <button class="action-btn export-btn" onclick="exportPDF()">Exportar a PDF</button>
    </div>
</main>

<button class="logout-btn" onclick="logout()">Cerrar Sesión</button>

<script>
    function logout() {
        // Aquí se implementaría la lógica para cerrar sesión
        alert("Sesión cerrada");

        // Redirigir al index.php
        window.location.href = 'index.php';
    }
</script>

<script>
    // Datos para los gráficos en formato JSON
    const incomeExpenseData = <?php echo json_encode($incomeExpenseData); ?>;
    const invoiceStatusChartData = <?php echo json_encode($invoiceStatusChartData); ?>;

    function renderCharts() {
        new Chart(document.getElementById('incomeExpenseChart'), {
            type: 'bar',
            data: incomeExpenseData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        new Chart(document.getElementById('invoiceStatusChart'), {
            type: 'pie',
            data: invoiceStatusChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    function exportCSV() {
        // Redirigir a la misma página con el parámetro de exportación
        window.location.href = 'reports.php?export=csv';
    }

    function exportPDF() {
        // Redirigir a la misma página con el parámetro de exportación
        window.location.href = 'reports.php?export=pdf';
    }

    // Inicializar los gráficos
    document.addEventListener('DOMContentLoaded', () => {
        renderCharts();
    });
</script>
</body>
</html>
