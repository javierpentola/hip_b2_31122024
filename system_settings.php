<?php
// (1) Mostrar errores para depuración (no usar en producción)
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

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// (4) Verificar si el admin está logueado
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}
$admin_id = $_SESSION['admin_id'];

/*
// (5) (Opcional) Obtener datos del admin
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
*/

// (6) Buscar configuración en la tabla system_settings (limit 1)
try {
    $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        // Insertar configuración por defecto si no existe
        $pdo->exec("
            INSERT INTO system_settings 
            (currency, tax_rate, maintenance_mode, site_title, last_updated)
            VALUES ('USD', 0.00, 0, 'Mi Sitio', NOW())
        ");
        // Leer la recién insertada
        $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error al obtener o crear configuración: " . $e->getMessage());
}

// Variables para feedback al usuario
$success_message = '';
$error_message   = '';

// (7) Procesar POST (formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // (A) Actualizar Moneda
    if (isset($_POST['update_currency'])) {
        $selected_currency = strtoupper(trim($_POST['currency']));
        $valid_currencies  = ['USD','EUR','GBP','JPY','CAD','AUD'];

        if (!in_array($selected_currency, $valid_currencies)) {
            $error_message = "Moneda no válida seleccionada.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE system_settings
                    SET currency = :curr, last_updated = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'curr' => $selected_currency,
                    'id'   => $settings['id']
                ]);

                $success_message = "Moneda actualizada a $selected_currency.";
                // Actualizar array local
                $settings['currency']     = $selected_currency;
                $settings['last_updated'] = date('Y-m-d H:i:s');

            } catch (PDOException $e) {
                $error_message = "Error al actualizar la moneda: " . $e->getMessage();
            }
        }
    }

    // (B) Actualizar Impuesto
    if (isset($_POST['update_tax'])) {
        $tax_rate = trim($_POST['tax_rate']);
        if (!is_numeric($tax_rate) || $tax_rate < 0 || $tax_rate > 100) {
            $error_message = "La tasa de impuesto debe ser un número entre 0 y 100.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE system_settings
                    SET tax_rate = :tax, last_updated = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'tax' => $tax_rate,
                    'id'  => $settings['id']
                ]);
                $success_message = "Tasa de impuesto actualizada a $tax_rate%.";
                $settings['tax_rate']     = $tax_rate;
                $settings['last_updated'] = date('Y-m-d H:i:s');
            } catch (PDOException $e) {
                $error_message = "Error al actualizar la tasa de impuesto: " . $e->getMessage();
            }
        }
    }

    // (C) Actualizar Modo Mantenimiento (requiere password supremo)
    if (isset($_POST['update_maintenance'])) {

        // Verificar contraseña suprema
        if (!isset($_POST['admin_super_password']) || $_POST['admin_super_password'] !== 'Nutrialoca123') {
            $error_message = "Contraseña de administrador supremo incorrecta. No se ha modificado el modo mantenimiento.";
        } else {
            $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
            try {
                $stmt = $pdo->prepare("
                    UPDATE system_settings
                    SET maintenance_mode = :mm, last_updated = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'mm' => $maintenance_mode,
                    'id' => $settings['id']
                ]);
                $settings['maintenance_mode'] = $maintenance_mode;
                $settings['last_updated']     = date('Y-m-d H:i:s');
                $success_message = $maintenance_mode
                    ? "Modo Mantenimiento activado."
                    : "Modo Mantenimiento desactivado.";
            } catch (PDOException $e) {
                $error_message = "Error al actualizar modo mantenimiento: " . $e->getMessage();
            }
        }
    }

    // (D) Actualizar Título del Sitio (requiere password supremo)
    if (isset($_POST['update_site_title'])) {

        // Verificar contraseña suprema
        if (!isset($_POST['admin_super_password']) || $_POST['admin_super_password'] !== 'Nutrialoca123') {
            $error_message = "Contraseña de administrador supremo incorrecta. No se ha modificado el título.";
        } else {
            $site_title = trim($_POST['site_title']);
            if (strlen($site_title) < 3) {
                $error_message = "El título del sitio es muy corto.";
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE system_settings
                        SET site_title = :title, last_updated = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'title' => $site_title,
                        'id'    => $settings['id']
                    ]);
                    $settings['site_title']   = $site_title;
                    $settings['last_updated'] = date('Y-m-d H:i:s');
                    $success_message = "Título del sitio actualizado correctamente.";
                } catch (PDOException $e) {
                    $error_message = "Error al actualizar el título del sitio: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HIP ENERGY - Configuración del Sistema</title>

    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        .system-settings-section {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .system-settings-section h2 {
            margin-top: 0;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        .settings-form {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }
        .settings-form label {
            display: flex;
            flex-direction: column;
        }
        .settings-form input,
        .settings-form select {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        <a href="system_settings.php" class="nav-item active">
            <i class="fas fa-cog"></i>
            <span>System Settings</span>
        </a>
        <a href="monitoring.php" class="nav-item">
            <i class="fas fa-desktop"></i>
            <span>Monitoring</span>
        </a>
    </div>
    <div class="logo-container">
        <img 
          src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/Normal-S0ZM46xhJ8Mm0vNUqKXmmqWS9gTvZJ.png" 
          alt="HIP ENERGY Logo" 
          class="logo">
    </div>
</nav>

<!-- CONTENIDO -->
<main class="main-content">
    <div class="system-settings-section">
        <h2>6. Configuración del Sistema</h2>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <p><strong>Última actualización:</strong> 
            <?php echo htmlspecialchars($settings['last_updated'] ?? 'N/A'); ?></p>

        <!-- (A) Actualizar Moneda -->
        <h3>Cambio de Moneda</h3>
        <form class="settings-form" method="POST">
            <label>
                Moneda:
                <select name="currency" required>
                    <option value="">Seleccionar Moneda</option>
                    <option value="USD" <?php if (($settings['currency'] ?? '') === 'USD') echo 'selected'; ?>>USD - Dólar Estadounidense</option>
                    <option value="EUR" <?php if (($settings['currency'] ?? '') === 'EUR') echo 'selected'; ?>>EUR - Euro</option>
                    <option value="GBP" <?php if (($settings['currency'] ?? '') === 'GBP') echo 'selected'; ?>>GBP - Libra Esterlina</option>
                    <option value="JPY" <?php if (($settings['currency'] ?? '') === 'JPY') echo 'selected'; ?>>JPY - Yen Japonés</option>
                    <option value="CAD" <?php if (($settings['currency'] ?? '') === 'CAD') echo 'selected'; ?>>CAD - Dólar Canadiense</option>
                    <option value="AUD" <?php if (($settings['currency'] ?? '') === 'AUD') echo 'selected'; ?>>AUD - Dólar Australiano</option>
                </select>
            </label>
            <button type="submit" name="update_currency" class="action-btn">Actualizar Moneda</button>
        </form>

        <!-- (B) Configuración de Impuestos -->
        <h3>Configuración de Impuestos</h3>
        <form class="settings-form" method="POST">
            <label>
                Impuesto aplicable (%):
                <input 
                  type="number" 
                  name="tax_rate" 
                  min="0" 
                  max="100" 
                  step="0.01"
                  value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '0.00'); ?>" 
                  required>
            </label>
            <button type="submit" name="update_tax" class="action-btn">Actualizar Impuesto</button>
        </form>

        <!-- (C) Modo Mantenimiento (requiere password supremo) -->
        <h3>Modo Mantenimiento</h3>
        <form class="settings-form" method="POST">
            <label style="flex-direction: row; align-items: center;">
                <input type="checkbox" name="maintenance_mode"
                       <?php if (!empty($settings['maintenance_mode'])) echo 'checked'; ?>>
                Activar Modo Mantenimiento
            </label>

            <!-- Campo para contraseña de super admin -->
            <label>
                Contraseña Admin Supremo:
                <input type="password" name="admin_super_password" required>
            </label>

            <button type="submit" name="update_maintenance" class="action-btn">Guardar Cambios</button>
        </form>

        <!-- (D) Título del Sitio (requiere password supremo) -->
        <h3>Título del Sitio</h3>
        <form class="settings-form" method="POST">
            <label>
                Nuevo Título:
                <input 
                  type="text" 
                  name="site_title" 
                  value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>" 
                  required>
            </label>

            <!-- Campo para contraseña de super admin -->
            <label>
                Contraseña Admin Supremo:
                <input type="password" name="admin_super_password" required>
            </label>

            <button type="submit" name="update_site_title" class="action-btn">Actualizar Título</button>
        </form>
    </div>
</main>

<button class="logout-btn" onclick="logout()">Cerrar Sesión</button>

<script>
function logout() {
    window.location.href = 'logout.php';
}
</script>
</body>
</html>
