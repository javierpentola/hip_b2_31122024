<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Datos de conexión a tu base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

try {
    // Conexión con PDO
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// ID de administrador por defecto (o como manejes tus sesiones)
$admin_id = 0;

/**
 * Obtiene la IP real del cliente
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$user_ip = getUserIP();

/**
 * Obtiene datos de geolocalización usando ip-api.com
 */
function getGeolocation($ip) {
    $url = "http://ip-api.com/json/{$ip}";
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if ($data['status'] === 'success') {
            return [
                'country'      => $data['country'],
                'country_code' => $data['countryCode']
            ];
        }
    }
    return [
        'country'      => 'Unknown',
        'country_code' => 'XX'
    ];
}

$geo = getGeolocation($user_ip);

/**
 * Verifica si la IP está bloqueada
 */
try {
    $stmt = $pdo->prepare("SELECT * FROM blocked_ips WHERE ip_address = :ip");
    $stmt->execute(['ip' => $user_ip]);
    $blocked = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($blocked) {
        die("Acceso denegado desde esta dirección IP. Motivo: " . htmlspecialchars($blocked['reason']));
    }
} catch (PDOException $e) {
    die("Error al verificar IP bloqueada: " . $e->getMessage());
}

/**
 * Registra el acceso en la tabla access_logs
 */
try {
    $stmt = $pdo->prepare("INSERT INTO access_logs (admin_id, ip_address, country, timestamp)
                           VALUES (:admin_id, :ip, :country, NOW())");
    $stmt->execute([
        'admin_id' => $admin_id,
        'ip'       => $user_ip,
        'country'  => $geo['country']
    ]);
} catch (PDOException $e) {
    die("Error al registrar acceso: " . $e->getMessage());
}

/**
 * SI SE SOLICITA DESCARGAR LOS LOGS EN .TXT
 */
if (isset($_GET['download_logs'])) {
    // 1) Primero, añade la IP de quien descarga en la parte superior
    $logs_text = "La IP de este usuario: " . $user_ip . "\n";
    $logs_text .= str_repeat("-", 50) . "\n\n";

    // 2) Obtenemos todos los registros de access_logs
    try {
        $stmt = $pdo->prepare("SELECT * FROM access_logs ORDER BY timestamp DESC");
        $stmt->execute();
        $access_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3) Generamos el texto
        foreach ($access_logs as $log) {
            $logs_text .= "IP: " . $log['ip_address']
                       . " | País: " . $log['country']
                       . " | Fecha: " . $log['timestamp']
                       . "\n";
        }
    } catch (PDOException $e) {
        // Si hay un error al obtener registros
        $logs_text .= "Error al obtener registros: " . $e->getMessage();
    }

    // 4) Forzamos la descarga como archivo .txt
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="access_logs.txt"');
    echo $logs_text;
    exit; // Finalizamos la ejecución para no enviar el resto del HTML
}

/**
 * Manejo de formularios POST (bloquear IP, asignar rol, etc.)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // BLOQUEAR IP
    if (isset($_POST['block_ip'])) {
        $ip_to_block = $_POST['ip_to_block'];
        $reason = $_POST['reason'] ?? 'Sin motivo';
        if (!filter_var($ip_to_block, FILTER_VALIDATE_IP)) {
            $error_message = "Formato de IP inválido.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM blocked_ips WHERE ip_address = :ip");
                $stmt->execute(['ip' => $ip_to_block]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    $error_message = "La IP ya está bloqueada.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip_address, reason)
                                           VALUES (:ip, :reason)");
                    $stmt->execute(['ip' => $ip_to_block, 'reason' => $reason]);
                    $success_message = "IP $ip_to_block ha sido bloqueada exitosamente.";
                }
            } catch (PDOException $e) {
                $error_message = "Error al bloquear la IP: " . $e->getMessage();
            }
        }
    }
    // DESBLOQUEAR IP
    if (isset($_POST['unblock_ip'])) {
        $ip_to_unblock = $_POST['ip_to_unblock'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM blocked_ips WHERE ip_address = :ip");
            $stmt->execute(['ip' => $ip_to_unblock]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                $error_message = "La IP no está bloqueada.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = :ip");
                $stmt->execute(['ip' => $ip_to_unblock]);
                $success_message = "IP $ip_to_unblock ha sido desbloqueada exitosamente.";
            }
        } catch (PDOException $e) {
            $error_message = "Error al desbloquear la IP: " . $e->getMessage();
        }
    }
    // ASIGNAR ROL A ADMIN
    if (isset($_POST['assign_role'])) {
        $user_id = $_POST['user_id'];
        $role_id = $_POST['role_id'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :role_id");
            $stmt->execute(['role_id' => $role_id]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$role) {
                $error_message = "El rol seleccionado no existe.";
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET role_id = :role_id WHERE id = :user_id");
                $stmt->execute(['role_id' => $role_id, 'user_id' => $user_id]);
                $success_message = "Rol asignado exitosamente.";
            }
        } catch (PDOException $e) {
            $error_message = "Error al asignar el rol: " . $e->getMessage();
        }
    }
    // BLOQUEAR A UN ADMIN (IS_LOGGED_IN = 0)
    if (isset($_POST['block_admin'])) {
        $admin_to_block = $_POST['admin_id'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = :id");
            $stmt->execute(['id' => $admin_to_block]);
            $admin_block = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$admin_block) {
                $error_message = "El administrador no existe.";
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET is_logged_in = 0 WHERE id = :id");
                $stmt->execute(['id' => $admin_to_block]);
                $success_message = "Administrador bloqueado exitosamente.";
            }
        } catch (PDOException $e) {
            $error_message = "Error al bloquear al administrador: " . $e->getMessage();
        }
    }
}

/**
 * Obtener lista de admins
 */
try {
    $stmt = $pdo->prepare("SELECT a.*, r.name AS role_name
                           FROM admins a
                           LEFT JOIN roles r ON a.role_id = r.id");
    $stmt->execute();
    $admin_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener la lista de admins: " . $e->getMessage());
}

/**
 * Obtener lista de roles
 */
try {
    $stmt = $pdo->prepare("SELECT * FROM roles");
    $stmt->execute();
    $role_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener la lista de roles: " . $e->getMessage());
}

/**
 * Obtener IPs bloqueadas
 */
try {
    $stmt = $pdo->prepare("SELECT * FROM blocked_ips");
    $stmt->execute();
    $blocked_ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener la lista de IPs bloqueadas: " . $e->getMessage());
}

/**
 * Obtener estadísticas de accesos por país (últimos 30 días)
 */
try {
    $stmt = $pdo->prepare("SELECT country, COUNT(*) as count
                           FROM access_logs
                           WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                           GROUP BY country");
    $stmt->execute();
    $access_countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos para el mapa: " . $e->getMessage());
}

// Preparar datos para el mapa
$map_data = [];
foreach ($access_countries as $country) {
    $map_data[$country['country']] = (int)$country['count'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIP ENERGY Navigation - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.5/jquery-jvectormap.min.css" crossorigin="anonymous" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.5/jquery-jvectormap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.5/jquery-jvectormap-world-mill.min.js"></script>
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
        .security-section {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .security-section h2 {
            margin-top: 0;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .user-table th, .user-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .user-table th {
            background-color: var(--primary-color);
            color: white;
        }
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
        .map-container {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        #world-map {
            width: 60%;
            height: 400px;
        }
        .access-list {
            width: 35%;
            max-height: 400px;
            overflow-y: auto;
        }
        .access-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            border-bottom: 1px solid #ddd;
        }
        .roles-menu {
            margin-top: 2rem;
        }
        .role-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            border-bottom: 1px solid #ddd;
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
            .map-container {
                flex-direction: column;
            }
            #world-map, .access-list {
                width: 100%;
            }
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
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
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
        <a href="security.php" class="nav-item active">
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
    <div class="security-section">
        <h2>5. Seguridad y Control de Acceso</h2>
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <h3>Autenticación Segura</h3>
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre de Usuario</th>
                    <th>Última Conexión</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="adminTableBody">
                <?php foreach ($admin_list as $admin_item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin_item['id']); ?></td>
                        <td><?php echo htmlspecialchars($admin_item['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin_item['last_login']); ?></td>
                        <td><?php echo $admin_item['is_logged_in'] ? 'Activo' : 'Inactivo'; ?></td>
                        <td>
                            <?php if ($admin_item['is_logged_in'] && $admin_item['id'] != $admin_id): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($admin_item['id']); ?>">
                                    <button type="submit" name="block_admin" class="action-btn">Bloquear</button>
                                </form>
                            <?php else: ?>
                                <span>No requiere acción</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Gestión de Roles</h3>
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre de Usuario</th>
                    <th>Rol Actual</th>
                    <th>Asignar Nuevo Rol</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admin_list as $admin_item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin_item['id']); ?></td>
                        <td><?php echo htmlspecialchars($admin_item['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin_item['role_name'] ?? 'Sin Rol'); ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($admin_item['id']); ?>">
                                <select name="role_id" required>
                                    <option value="">Seleccionar Rol</option>
                                    <?php foreach ($role_list as $role_item): ?>
                                        <option value="<?php echo htmlspecialchars($role_item['id']); ?>"
                                            <?php if ($role_item['id'] == $admin_item['role_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($role_item['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="assign_role" class="action-btn">Asignar Rol</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Control de IPs No Autorizadas</h3>
        <form method="POST" style="margin-bottom: 1rem;">
            <input type="text" name="ip_to_block" placeholder="Dirección IP" required>
            <input type="text" name="reason" placeholder="Motivo" required>
            <button type="submit" name="block_ip" class="action-btn">Bloquear IP</button>
        </form>

        <h4>IPs Bloqueadas</h4>
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>IP</th>
                    <th>Motivo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blocked_ips as $blocked_ip): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($blocked_ip['id']); ?></td>
                        <td><?php echo htmlspecialchars($blocked_ip['ip_address']); ?></td>
                        <td><?php echo htmlspecialchars($blocked_ip['reason']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="ip_to_unblock" value="<?php echo htmlspecialchars($blocked_ip['ip_address']); ?>">
                                <button type="submit" name="unblock_ip" class="action-btn">Desbloquear</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Accesos Registrados</h3>

        <!-- BOTÓN PARA DESCARGAR LOS ACCESOS EN .TXT -->
        <form method="GET" action="security.php" style="margin-bottom: 1rem;">
            <button type="submit" name="download_logs" class="action-btn">
                Descargar accesos
            </button>
        </form>

        <div class="map-container">
            <div id="world-map"></div>
            <div class="access-list" id="accessList">
                <?php foreach ($access_countries as $country): ?>
                    <div class="access-item">
                        <span>
                            <?php echo htmlspecialchars($country['country']); ?>:
                            <?php echo htmlspecialchars($country['count']); ?> acceso(s)
                        </span>
                        <button class="action-btn" onclick="blockCountry('<?php echo htmlspecialchars($country['country']); ?>')">
                            Bloquear País
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<button class="logout-btn" onclick="logout()">Cerrar Sesión</button>

<script>
function logout() {
    window.location.href = 'logout.php';
}

function blockIP(ip) {
    if (confirm(`¿Estás seguro de que deseas bloquear la IP ${ip}?`)) {
        var form = document.createElement('form');
        form.method = 'POST';
        var ipInput = document.createElement('input');
        ipInput.type = 'hidden';
        ipInput.name = 'ip_to_block';
        ipInput.value = ip;
        form.appendChild(ipInput);

        var reasonInput = document.createElement('input');
        reasonInput.type = 'text';
        reasonInput.name = 'reason';
        reasonInput.placeholder = 'Motivo';
        reasonInput.required = true;
        form.appendChild(reasonInput);

        var submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.name = 'block_ip';
        submitBtn.className = 'action-btn';
        submitBtn.textContent = 'Bloquear';
        form.appendChild(submitBtn);

        document.body.appendChild(form);
        form.submit();
    }
}

function unblockIP(ip) {
    if (confirm(`¿Estás seguro de que deseas desbloquear la IP ${ip}?`)) {
        var form = document.createElement('form');
        form.method = 'POST';
        var ipInput = document.createElement('input');
        ipInput.type = 'hidden';
        ipInput.name = 'ip_to_unblock';
        ipInput.value = ip;
        form.appendChild(ipInput);

        var submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.name = 'unblock_ip';
        submitBtn.className = 'action-btn';
        submitBtn.textContent = 'Desbloquear';
        form.appendChild(submitBtn);

        document.body.appendChild(form);
        form.submit();
    }
}

function blockCountry(countryName) {
    if (confirm(`¿Estás seguro de que deseas bloquear el país con nombre ${countryName}?`)) {
        document.getElementById('country_code').value = countryName;
        document.getElementById('countryModal').style.display = "block";
    }
}

var modal = document.getElementById("countryModal");
var span = document.getElementsByClassName("close")[0];

// Al hacer clic en la 'X' del modal
span.onclick = function() {
    modal.style.display = "none";
}

// Al hacer clic fuera del modal
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Iniciamos el mapa vectorial con las estadísticas
function initMap() {
    const accessData = <?php echo json_encode($map_data); ?>;
    $('#world-map').vectorMap({
        map: 'world_mill',
        backgroundColor: '#fff',
        zoomOnScroll: false,
        series: {
            regions: [{
                values: accessData,
                scale: ['#C8EEFF', '#0071A4'],
                normalizeFunction: 'polynomial'
            }]
        },
        onRegionTipShow: function(e, el, code) {
            if (accessData[code] !== undefined) {
                el.html(el.html() + ': ' + accessData[code] + ' acceso(s)');
            }
        }
    });
}

$(document).ready(function() {
    initMap();
});
</script>

<!-- Modal para bloquear país -->
<div id="countryModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Bloquear País</h3>
        <form method="POST">
            <input type="hidden" name="country_code" id="country_code">
            <input type="text" name="country_reason" placeholder="Motivo" required>
            <button type="submit" name="block_country" class="action-btn">Bloquear País</button>
        </form>
    </div>
</div>
</body>
</html>
