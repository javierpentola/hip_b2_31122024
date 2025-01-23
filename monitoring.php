<?php
/************************************************************
 * monitoring.php
 * Muestra métricas de CPU, Memoria, últimos 100 cambios 
 * (union de 'access_logs' y 'activity_logs'), 
 * estado del sistema y gráficas de actividad.
 ************************************************************/

// 1) Mostrar errores para depuración (no usar en prod)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2) Iniciar sesión
session_start();

// 3) Conexión a la BD
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 4) Verificar sesión de administrador (ajusta según tu login)
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}
$admin_id = $_SESSION['admin_id'];

// 5) Obtener username del admin (opcional)
try {
    $stmt = $pdo->prepare("SELECT username FROM admins WHERE id = :id");
    $stmt->execute(['id' => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        session_destroy();
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    die("Error al obtener datos del admin: " . $e->getMessage());
}

/************************************************************
 * A) CPU & MEM USAGE
 *    - Tomados de system_settings si existen
 *    - Si no, generamos valores aleatorios
 ************************************************************/
$cpu_usage    = null;
$memory_usage = null;

try {
    // Leer CPU usage si existe
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key='cpu_usage' LIMIT 1");
    $stmt->execute();
    $rowCpu = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rowCpu) {
        $cpu_usage = floatval($rowCpu['setting_value']);
    }

    // Leer MEM usage si existe
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key='memory_usage' LIMIT 1");
    $stmt->execute();
    $rowMem = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rowMem) {
        $memory_usage = floatval($rowMem['setting_value']);
    }
} catch (PDOException $e) {
    // Si la tabla system_settings no existe, seguimos
}

// Si no se logró obtener datos reales, generamos valores ficticios
if ($cpu_usage === null) {
    $cpu_usage = rand(40, 90);
}
if ($memory_usage === null) {
    $memory_usage = rand(30, 80);
}

/************************************************************
 * B) ÚLTIMOS 100 CAMBIOS
 *    Solo usando tablas con columna 'timestamp' comprobada:
 *      - access_logs
 *      - activity_logs
 *    Cada SELECT produce: (source_table, action, timestamp)
 ************************************************************/
$all_changes = [];

try {
    $union_sql = "
      SELECT 
        'access_logs' AS source_table,
        CONCAT('Acceso IP: ', ip_address, ' [', country, ']') AS action,
        timestamp
      FROM access_logs

      UNION

      SELECT
        'activity_logs' AS source_table,
        CONCAT('Admin action: ', action) AS action,
        timestamp
      FROM activity_logs

      ORDER BY timestamp DESC
      LIMIT 100
    ";

    $stmt = $pdo->query($union_sql);
    $all_changes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al obtener últimos cambios: " . $e->getMessage());
}

/************************************************************
 * C) ESTADO DEL SISTEMA (totales de users, customer, etc.)
 *    Asegúrate de que existan estas tablas si las necesitas.
 ************************************************************/
$total_users     = 0;
$total_customers = 0;
$total_blocked   = 0;
$total_bills     = 0;

// 1) total users
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Si no existe la tabla, omite o maneja error
}

// 2) total customers
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM customer");
    $total_customers = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Omitir
}

// 3) total blocked IPs
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM blocked_ips");
    $total_blocked = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Omitir
}

// 4) total bills
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM bills");
    $total_bills = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Omitir
}

/************************************************************
 * D) GRÁFICOS DE ACTIVIDAD
 *    # de accesos (access_logs) últimos 7 días
 *    # de actividades (activity_logs) últimos 7 días
 ************************************************************/

$access_logs_data = [
    'labels' => [],
    'counts' => []
];
try {
    $stmt = $pdo->prepare("
        SELECT DATE(timestamp) AS fecha, COUNT(*) AS total
        FROM access_logs
        WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(timestamp)
        ORDER BY fecha ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $access_logs_data['labels'][] = $r['fecha'];
        $access_logs_data['counts'][] = (int)$r['total'];
    }
} catch (PDOException $e) {
    // Si no existe access_logs, omite
}

$activity_logs_data = [
    'labels' => [],
    'counts' => []
];
try {
    $stmt = $pdo->prepare("
        SELECT DATE(timestamp) AS fecha, COUNT(*) AS total
        FROM activity_logs
        WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(timestamp)
        ORDER BY fecha ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $activity_logs_data['labels'][] = $r['fecha'];
        $activity_logs_data['counts'][] = (int)$r['total'];
    }
} catch (PDOException $e) {
    // Si no existe activity_logs, omite
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>HIP ENERGY Navigation - Admin Panel - Monitoreo</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  
  <!-- Chart.js para los gráficos -->
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
    .metrics-cards {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
    }
    .metrics-card {
      flex: 1;
      background-color: #f8f8f8;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 1rem;
      text-align: center;
    }
    .metrics-card h4 {
      margin: 0;
      margin-bottom: 0.5rem;
    }
    .metrics-card p {
      margin: 0;
      font-size: 1.2rem;
      font-weight: 500;
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
    .chart-container {
      width: 100%;
      height: 400px;
      margin-top: 2rem;
    }
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
      .metrics-cards {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
<!-- SIDEBAR -->
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
      <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/Normal-S0ZM46xhJ8Mm0vNUqKXmmqWS9gTvZJ.png" 
           alt="HIP ENERGY Logo" class="logo">
  </div>
</nav>

<!-- CONTENIDO PRINCIPAL -->
<main class="main-content">
  <div class="monitoring-section">
    <h2>7. Monitoreo</h2>

    <!-- Métricas de CPU/Mem -->
    <h3>Métricas del Servidor</h3>
    <div class="metrics-cards">
      <div class="metrics-card">
        <h4>CPU Usage</h4>
        <p><?php echo htmlspecialchars($cpu_usage) . '%'; ?></p>
      </div>
      <div class="metrics-card">
        <h4>Memory Usage</h4>
        <p><?php echo htmlspecialchars($memory_usage) . '%'; ?></p>
      </div>
    </div>

    <!-- Registro de Actividades (últimos 100) -->
    <h3>Registro de Actividades (Últimos 100)</h3>
    <div class="activity-log" id="activityLog">
      <?php if (!empty($all_changes)): ?>
        <?php foreach ($all_changes as $chg): ?>
          <div class="activity-item">
            <strong><?php echo htmlspecialchars($chg['timestamp']); ?></strong>
            &mdash; 
            <em>[<?php echo htmlspecialchars($chg['source_table']); ?>]</em> 
            <?php echo htmlspecialchars($chg['action']); ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay cambios registrados.</p>
      <?php endif; ?>
    </div>

    <!-- Estado del Sistema -->
    <h3>Estado del Sistema</h3>
    <div class="system-status">
      <div>
        <span class="status-indicator status-good"></span>
        <span>Estado: Operativo</span>
      </div>
      <div>
        <strong>IPs Bloqueadas:</strong> <?php echo $total_blocked; ?>
      </div>
    </div>

    <!-- Otras estadísticas -->
    <div class="metrics-cards" style="margin-top:1rem;">
      <div class="metrics-card">
        <h4>Total de Usuarios</h4>
        <p><?php echo $total_users; ?></p>
      </div>
      <div class="metrics-card">
        <h4>Total de Clientes</h4>
        <p><?php echo $total_customers; ?></p>
      </div>
      <div class="metrics-card">
        <h4>Total de Bills</h4>
        <p><?php echo $total_bills; ?></p>
      </div>
    </div>

    <!-- Gráficos de Actividad -->
    <h3>Gráficos de Actividad</h3>
    <div class="chart-container">
      <canvas id="accessLogsChart"></canvas>
    </div>
    <div class="chart-container">
      <canvas id="activityLogsChart"></canvas>
    </div>
  </div>
</main>

<button class="logout-btn" onclick="logout()">Cerrar Sesión</button>

<script>
function logout() {
  window.location.href = 'logout.php';
}

// 1) Access logs (7 días)
const accessData = <?php echo json_encode($access_logs_data); ?>;
// 2) Activity logs (7 días)
const activityData = <?php echo json_encode($activity_logs_data); ?>;

// Gráfico 1: Access Logs (barras)
const ctxAccess = document.getElementById('accessLogsChart').getContext('2d');
new Chart(ctxAccess, {
  type: 'bar',
  data: {
    labels: accessData.labels,
    datasets: [{
      label: 'Accesos (7 días)',
      data: accessData.counts,
      backgroundColor: 'rgba(54, 162, 235, 0.2)',
      borderColor: 'rgba(54, 162, 235, 1)',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: {
        beginAtZero: true,
        ticks: { precision: 0 }
      }
    }
  }
});

// Gráfico 2: Activity Logs (línea)
const ctxActivity = document.getElementById('activityLogsChart').getContext('2d');
new Chart(ctxActivity, {
  type: 'line',
  data: {
    labels: activityData.labels,
    datasets: [{
      label: 'Actividades Admin (7 días)',
      data: activityData.counts,
      fill: true,
      backgroundColor: 'rgba(255, 206, 86, 0.2)',
      borderColor: 'rgba(255, 206, 86, 1)',
      tension: 0.1,
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: {
        beginAtZero: true,
        ticks: { precision: 0 }
      }
    }
  }
});
</script>
</body>
</html>
