<?php
// consumo.php

// Incluir funciones si deseas registrar actividades (opcional)
// require_once 'functions.php';

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

// Obtener datos de consumo
try {
    // Total Consumption
    $stmt = $pdo->prepare("SELECT SUM(kwh) as total_kwh FROM consumptions");
    $stmt->execute();
    $total_consumption = $stmt->fetch(PDO::FETCH_ASSOC)['total_kwh'] ?? 0;

    // Daily Average
    $stmt = $pdo->prepare("SELECT AVG(daily_sum) as average_kwh FROM (SELECT SUM(kwh) as daily_sum FROM consumptions GROUP BY date) as daily_totals");
    $stmt->execute();
    $daily_average = $stmt->fetch(PDO::FETCH_ASSOC)['average_kwh'] ?? 0;

    // Peak Usage
    $stmt = $pdo->prepare("SELECT MAX(daily_sum) as peak_kwh FROM (SELECT SUM(kwh) as daily_sum FROM consumptions GROUP BY date) as daily_totals");
    $stmt->execute();
    $peak_usage = $stmt->fetch(PDO::FETCH_ASSOC)['peak_kwh'] ?? 0;

    // Monthly Comparison (últimos 6 meses)
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(date, '%Y-%m') as month, SUM(kwh) as total_kwh 
        FROM consumptions 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
        GROUP BY DATE_FORMAT(date, '%Y-%m') 
        ORDER BY DATE_FORMAT(date, '%Y-%m') ASC
    ");
    $stmt->execute();
    $monthly_comparison = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Daily Usage (últimos 30 días)
    $stmt = $pdo->prepare("
        SELECT date, SUM(kwh) as total_kwh 
        FROM consumptions 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        GROUP BY date 
        ORDER BY date ASC
    ");
    $stmt->execute();
    $daily_usage = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Energy Sources
    $stmt = $pdo->prepare("
        SELECT source, SUM(kwh) as total_kwh 
        FROM consumptions 
        GROUP BY source
    ");
    $stmt->execute();
    $energy_sources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Carbon Footprint (asumiendo 0.5 kg CO2 por kWh)
    $carbon_footprint = $total_consumption * 0.5; // Ajusta el factor según tus necesidades

    // Energy Savings (Comparación con el mes anterior)
    $stmt = $pdo->prepare("
        SELECT SUM(kwh) as last_month_kwh 
        FROM consumptions 
        WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')
    ");
    $stmt->execute();
    $last_month = $stmt->fetch(PDO::FETCH_ASSOC)['last_month_kwh'] ?? 0;

    $energy_savings = $last_month > 0 ? (($last_month - $total_consumption) / $last_month) * 100 : 0;

    // Next Bill Estimate (Asumiendo $0.15 por kWh más impuestos)
    $tax_rate = $settings['tax_rate'] / 100;
    $next_bill_estimate = ($total_consumption * 0.15) * (1 + $tax_rate);

    // Energy Efficiency Score (Puntuación arbitraria basada en consumo total)
    // Puedes ajustar la fórmula según tus criterios
    if ($total_consumption <= 1000) {
        $efficiency_score = 'A';
    } elseif ($total_consumption <= 2000) {
        $efficiency_score = 'B';
    } elseif ($total_consumption <= 3000) {
        $efficiency_score = 'C';
    } else {
        $efficiency_score = 'D';
    }

    // (Opcional) Registrar actividad
    // logActivity($pdo, $admin_id, "Accedió a la página de Consumo.");
    
} catch (PDOException $e) {
    die("Error al obtener datos de consumo: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIP ENERGY Navigation - Admin Panel - Consumo</title>
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

        .consumption-item {
            color: #000 !important;
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
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
            <a href="consumo.php" class="nav-item active">
                <i class="fas fa-chart-line"></i>
                <span class="consumption-item">Consumption</span>
            </a>
            <a href="facturas.php" class="nav-item">
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
        <h1>Energy Consumption Dashboard</h1>
        <div class="dashboard">
            <div class="card">
                <h2>Total Consumption</h2>
                <div class="stat"><?php echo number_format($total_consumption, 2); ?> kWh</div>
                <p>This month</p>
            </div>
            <div class="card">
                <h2>Daily Average</h2>
                <div class="stat"><?php echo number_format($daily_average, 2); ?> kWh</div>
                <p>Last 30 days</p>
            </div>
            <div class="card">
                <h2>Peak Usage</h2>
                <div class="stat"><?php echo number_format($peak_usage, 2); ?> kWh</div>
                <p>Highest day this month</p>
            </div>
            <div class="card">
                <h2>Monthly Comparison</h2>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
            <div class="card">
                <h2>Daily Usage</h2>
                <div class="chart-container">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
            <div class="card">
                <h2>Energy Sources</h2>
                <div class="chart-container">
                    <canvas id="sourcesChart"></canvas>
                </div>
            </div>
            <div class="card">
                <h2>Carbon Footprint</h2>
                <div class="stat"><?php echo number_format($carbon_footprint, 2); ?> tons</div>
                <p>CO2 emissions this month</p>
            </div>
            <div class="card">
                <h2>Energy Savings</h2>
                <div class="stat"><?php echo number_format($energy_savings, 2); ?>%</div>
                <p>Compared to last month</p>
            </div>
            <div class="card">
                <h2>Next Bill Estimate</h2>
                <div class="stat"><?php echo $currency_symbol . number_format($next_bill_estimate, 2); ?></div>
                <p>Based on current usage</p>
            </div>
            <div class="card">
                <h2>Energy Efficiency Score</h2>
                <div class="stat"><?php echo $efficiency_score; ?></div>
                <p>Room for improvement</p>
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
            const charts = [monthlyChart, dailyChart, sourcesChart];
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

        // Monthly Comparison Chart
        const monthlyChart = new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($monthly_comparison, 'month')); ?>,
                datasets: [{
                    label: 'Monthly Consumption (kWh)',
                    data: <?php echo json_encode(array_column($monthly_comparison, 'total_kwh')); ?>,
                    backgroundColor: '#f2c517'
                }]
            },
            options: { ...chartOptions }
        });

        // Daily Usage Chart
        const dailyChart = new Chart(document.getElementById('dailyChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_usage, 'date')); ?>,
                datasets: [{
                    label: 'Daily Usage (kWh)',
                    data: <?php echo json_encode(array_column($daily_usage, 'total_kwh')); ?>,
                    borderColor: '#8833ff',
                    tension: 0.1
                }]
            },
            options: { ...chartOptions }
        });

        // Energy Sources Chart
        const sourcesChart = new Chart(document.getElementById('sourcesChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($energy_sources, 'source')); ?>,
                datasets: [{
                    label: 'Energy Sources (kWh)',
                    data: <?php echo json_encode(array_column($energy_sources, 'total_kwh')); ?>,
                    backgroundColor: [
                        '#d4a017', // Solar
                        '#8833ff', // Wind
                        '#00a8e8', // Hydro
                        '#007ea7'  // Natural Gas
                        // Agrega más colores según las fuentes de energía
                    ],
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: { ...chartOptions,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function createPattern(ctx, color, index) {
            const patternCanvas = document.createElement('canvas');
            const patternContext = patternCanvas.getContext('2d');
            patternCanvas.width = 20;
            patternCanvas.height = 20;
            patternContext.fillStyle = color;
            patternContext.fillRect(0, 0, 20, 20);
            patternContext.strokeStyle = '#fff';
            patternContext.lineWidth = 2;

            switch(index) {
                case 0: // Stripes for Solar
                    patternContext.beginPath();
                    patternContext.moveTo(0, 0);
                    patternContext.lineTo(20, 20);
                    patternContext.stroke();
                    break;
                case 1: // Circles for Wind
                    patternContext.beginPath();
                    patternContext.arc(10, 10, 6, 0, Math.PI * 2);
                    patternContext.stroke();
                    break;
                case 2: // Squares for Hydro
                    patternContext.strokeRect(4, 4, 12, 12);
                    break;
                case 3: // Triangles for Natural Gas
                    patternContext.beginPath();
                    patternContext.moveTo(10, 2);
                    patternContext.lineTo(18, 18);
                    patternContext.lineTo(2, 18);
                    patternContext.closePath();
                    patternContext.stroke();
                    break;
            }

            return ctx.createPattern(patternCanvas, 'repeat');
        }
    </script>
</body>
</html>
