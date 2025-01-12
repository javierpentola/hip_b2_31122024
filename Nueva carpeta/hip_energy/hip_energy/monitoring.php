<?php
// monitoring.php

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

// Obtener registros de actividades (últimas 100 actividades)
try {
    $stmt = $pdo->prepare("
        SELECT al.id, al.action, al.timestamp, a.username 
        FROM activity_logs al 
        JOIN admins a ON al.admin_id = a.id 
        ORDER BY al.timestamp DESC 
        LIMIT 100
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener registros de actividades: " . $e->getMessage());
}

// Obtener datos para gráficos
try {
    // Ejemplo: Número de actividades por día en los últimos 30 días
    $stmt = $pdo->prepare("
        SELECT DATE(timestamp) as date, COUNT(*) as count 
        FROM activity_logs 
        WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        GROUP BY DATE(timestamp) 
        ORDER BY DATE(timestamp) ASC
    ");
    $stmt->execute();
    $activities_per_day = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ejemplo: Número de actividades por administrador
    $stmt = $pdo->prepare("
        SELECT a.username, COUNT(al.id) as count 
        FROM activity_logs al 
        JOIN admins a ON al.admin_id = a.id 
        GROUP BY al.admin_id 
        ORDER BY count DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $activities_per_admin = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos para gráficos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIP ENERGY Navigation - Admin Panel - Monitoreo</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reutilización de los estilos existentes */
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

        .monitoring-section {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .monitoring-section h2 {
            margin-top: 0;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .activity-log {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .activity-item {
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .system-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f8f8;
            border-radius: 4px;
        }

        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .status-good {
            background-color: #4CAF50;
        }

        .status-warning {
            background-color: #FFC107;
        }

        .status-error {
            background-color: #F44336;
        }

        .uptime-chart {
            width: 100%;
            height: 200px;
            margin-top: 1rem;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chart-container {
            width: 100%;
            height: 400px;
            margin-top: 2rem;
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

        /* Estilos para mensajes de éxito/error */
        .message {
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
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
            <a href="reports.php" class="nav-item">
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
            <a href="monitoring.php" class="nav-item active">
                <i class="fas fa-desktop"></i>
                <span>Monitoring</span>
            </a>
    </div>
    <div class="logo-container">
        <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/Normal-S0ZM46xhJ8Mm0vNUqKXmmqWS9gTvZJ.png" alt="HIP ENERGY Logo" class="logo">
    </div>
</nav>

<main class="main-content">
    <div class="monitoring-section">
        <h2>7. Monitoreo</h2>
        
        <!-- Mensajes de éxito/error -->
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <h3>Registro de Actividades</h3>
        <div class="activity-log" id="activityLog">
            <?php if (count($activities) > 0): ?>
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <strong><?php echo htmlspecialchars($activity['timestamp']); ?></strong> - <strong><?php echo htmlspecialchars($activity['username']); ?>:</strong> <?php echo htmlspecialchars($activity['action']); ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay actividades registradas.</p>
            <?php endif; ?>
        </div>

        <h3>Estado del Sistema</h3>
        <div class="system-status">
            <div>
                <span class="status-indicator status-good"></span>
                <span>Estado: Operativo</span>
            </div>
            <div>
                <strong>Uptime:</strong> <span id="uptime">99.99%</span>
            </div>
        </div>
        <div class="uptime-chart">
            <!-- Aquí se insertaría un gráfico de uptime -->
            <canvas id="uptimeChart" width="400" height="200"></canvas>
        </div>

        <h3>Gráficos de Monitoreo</h3>
        <div class="chart-container">
            <canvas id="activitiesPerDayChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="activitiesPerAdminChart"></canvas>
        </div>
    </div>
</main>

<button class="logout-btn" onclick="logout()">Cerrar Sesión</button>

<!-- Incluir Chart.js desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function logout() {
        // Redirigir a logout.php
        window.location.href = 'logout.php';
    }

    // Simulación de actualización de uptime (puede ser reemplazada por datos reales)
    function updateUptime() {
        // Aquí podrías obtener el uptime real del servidor si tienes una API o método para ello
        // Por simplicidad, simularemos un uptime aleatorio
        const uptime = (99.80 + Math.random() * 0.20).toFixed(2);
        document.getElementById('uptime').textContent = uptime + '%';

        // Actualizar el gráfico de uptime
        const uptimeChart = Chart.getChart("uptimeChart"); // Obtener instancia existente
        if (uptimeChart) {
            uptimeChart.data.datasets[0].data.push(uptime);
            uptimeChart.data.labels.push(new Date().toLocaleTimeString());
            if (uptimeChart.data.labels.length > 20) {
                uptimeChart.data.labels.shift();
                uptimeChart.data.datasets[0].data.shift();
            }
            uptimeChart.update();
        }
    }

    // Inicializar el gráfico de uptime
    const ctxUptime = document.getElementById('uptimeChart').getContext('2d');
    const uptimeChart = new Chart(ctxUptime, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Uptime (%)',
                data: [],
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.2)',
                fill: true,
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: 100
                }
            }
        }
    });

    // Renderizar los gráficos de actividades
    const activitiesPerDayData = <?php 
        $labels = [];
        $data = [];
        foreach ($activities_per_day as $record) {
            $labels[] = $record['date'];
            $data[] = (int)$record['count'];
        }
        echo json_encode(['labels' => $labels, 'data' => $data]);
    ?>;

    const activitiesPerAdminData = <?php 
        $labelsAdmin = [];
        $dataAdmin = [];
        foreach ($activities_per_admin as $record) {
            $labelsAdmin[] = $record['username'];
            $dataAdmin[] = (int)$record['count'];
        }
        echo json_encode(['labels' => $labelsAdmin, 'data' => $dataAdmin]);
    ?>;

    // Gráfico de actividades por día
    const ctxActivitiesPerDay = document.getElementById('activitiesPerDayChart').getContext('2d');
    const activitiesPerDayChart = new Chart(ctxActivitiesPerDay, {
        type: 'bar',
        data: {
            labels: activitiesPerDayData.labels,
            datasets: [{
                label: 'Actividades por Día',
                data: activitiesPerDayData.data,
                backgroundColor: '#f2c517',
                borderColor: '#d4a017',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision:0
                    }
                }
            }
        }
    });

    // Gráfico de actividades por administrador
    const ctxActivitiesPerAdmin = document.getElementById('activitiesPerAdminChart').getContext('2d');
    const activitiesPerAdminChart = new Chart(ctxActivitiesPerAdmin, {
        type: 'pie',
        data: {
            labels: activitiesPerAdminData.labels,
            datasets: [{
                label: 'Actividades por Administrador',
                data: activitiesPerAdminData.data,
                backgroundColor: [
                    '#4CAF50',
                    '#FFC107',
                    '#F44336',
                    '#2196F3',
                    '#9C27B0',
                    '#FF5722',
                    '#00BCD4',
                    '#8BC34A',
                    '#CDDC39',
                    '#FF9800'
                ]
            }]
        },
        options: {
            responsive: true
        }
    });

    // Inicializar la página
    document.addEventListener('DOMContentLoaded', function() {
        updateUptime();
        // Actualizar el uptime cada 5 minutos (300,000 ms)
        setInterval(updateUptime, 300000);
    });
</script>
</body>
</html>
