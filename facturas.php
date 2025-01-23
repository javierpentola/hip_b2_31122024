<?php
// facturas.php

// (Opcional) require_once 'functions.php'; // Solo si tuvieras funciones extra

// 1. Mostrar errores para depuración (no usar en producción real)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Eliminamos todo lo relacionado con sesión
//    (Se supone que el sistema de sesiones está “bugeado”)
// session_start(); // Completamente removido

// 3. Datos de conexión a la BD
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

// 4. Conexión con PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 5. Obtener configuraciones del sistema
try {
    $stmt = $pdo->prepare("SELECT * FROM system_settings LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        // Insertar configuración por defecto si no existe
        $stmt = $pdo->prepare("INSERT INTO system_settings (currency, tax_rate) VALUES ('USD', 0.00)");
        $stmt->execute();
        $settings = [
            'currency'     => 'USD',
            'tax_rate'     => '0.00',
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
} catch (PDOException $e) {
    die("Error al obtener configuraciones: " . $e->getMessage());
}

/**
 * 6. Función para obtener el símbolo de la moneda
 */
function getCurrencySymbol($currency) {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'CAD' => 'C$',
        'AUD' => 'A$'
        // Agrega más monedas si deseas
    ];
    return isset($symbols[$currency]) ? $symbols[$currency] : $currency . ' ';
}

// 7. Obtener símbolo de la moneda
$currency_symbol = getCurrencySymbol($settings['currency'] ?? 'USD');

/**
 * 8. Procesar búsqueda de facturas
 */
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

/**
 * 9. Construir consulta SQL para buscar facturas
 *    Usamos las tablas 'bills' y 'customer' (Account_ID).
 */
$sql = "
    SELECT 
        b.*,
        CONCAT(c.First_Name, ' ', c.Last_Name) AS customer_name
    FROM bills b
    JOIN customer c ON b.customer_id = c.Account_ID
";
if (!empty($search_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $search_conditions);
}
$sql .= " ORDER BY b.due_date DESC";

// 10. Ejecutar la consulta de facturas
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($search_params);
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al buscar facturas: " . $e->getMessage());
}

/**
 * 11. (Opcional) Registrar actividad de búsqueda (si tuvieras logs)
 *     if (!empty($search_conditions)) { ... }
 */

// 12. Obtener los últimos 5 pagos para la tabla "Recent Bills"
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            CONCAT(c.First_Name, ' ', c.Last_Name) AS customer_name
        FROM bills b
        JOIN customer c ON b.customer_id = c.Account_ID
        ORDER BY b.due_date DESC
        LIMIT 5
    ");
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
    <title>HIP ENERGY - Facturas</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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
            background-color: var(--primary-color);
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

        h1 {
            margin-top: 0;
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

        .form-input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        /* BOTÓN DE BÚSQUEDA EN COLOR AMARILLO */
        .btn-search {
            background-color: #f1c40f; /* Amarillo */
            color: #000;
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-search:hover {
            background-color: #d4a017; 
            color: #fff;
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
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .btn-download:hover, .btn-edit:hover {
            text-decoration: underline;
        }
        .btn-add {
            display: block;
            margin-top: 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
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
        <div class="logo-container">
            <img src="images/hiplogo.jpg" alt="HIP ENERGY Logo" class="logo">
        </div>
    </nav>

    <main class="main-content">
        <h1>Bill Dashboard</h1>

        <div class="dashboard">
            <!-- Tarjeta 1: Current Bill -->
            <div class="card">
                <h2>Current Bill</h2>
                <?php if (!empty($bills)): ?>
                    <div class="stat">
                        <?php echo $currency_symbol . number_format($bills[0]['amount'], 2); ?>
                    </div>
                    <p>Due on <?php echo date('M d, Y', strtotime($bills[0]['due_date'])); ?></p>
                <?php else: ?>
                    <p>No bills available.</p>
                <?php endif; ?>
            </div>

            <!-- Tarjeta 2: Average Monthly Bill -->
            <div class="card">
                <h2>Average Monthly Bill</h2>
                <div class="stat">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT AVG(monthly_total) AS avg_monthly_bill
                            FROM (
                                SELECT SUM(amount) AS monthly_total
                                FROM bills
                                WHERE due_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                GROUP BY DATE_FORMAT(due_date, '%Y-%m')
                            ) AS monthly_totals
                        ");
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

            <!-- Tarjeta 3: Payment Streak -->
            <div class="card">
                <h2>Payment Streak</h2>
                <div class="stat">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as total_paid
                            FROM bills
                            WHERE status = 'Paid'
                        ");
                        $stmt->execute();
                        $streak = $stmt->fetch(PDO::FETCH_ASSOC)['total_paid'] ?? 0;
                        echo number_format($streak) . " bills paid";
                    } catch (PDOException $e) {
                        echo "Error";
                    }
                    ?>
                </div>
                <p>Consecutive on-time payments</p>
            </div>

            <!-- Tarjeta 4: Next Bill Estimate -->
            <div class="card">
                <h2>Next Bill Estimate</h2>
                <div class="stat">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT SUM(amount) AS next_bill
                            FROM bills
                            WHERE due_date = (
                                SELECT MIN(due_date)
                                FROM bills
                                WHERE due_date > CURDATE()
                            )
                        ");
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

            <!-- Tarjeta 5: Search Bills (wide-card) -->
            <div class="card wide-card">
                <h2>Search Bills</h2>
                <form id="searchForm" method="GET" action="facturas.php">
                    <input type="date" id="startDate" name="startDate"
                           class="form-input"
                           value="<?php echo htmlspecialchars($_GET['startDate'] ?? ''); ?>">
                    <input type="date" id="endDate" name="endDate"
                           class="form-input"
                           value="<?php echo htmlspecialchars($_GET['endDate'] ?? ''); ?>">
                    <input type="number" id="minAmount" name="minAmount"
                           placeholder="Min Amount" class="form-input"
                           step="0.01"
                           value="<?php echo htmlspecialchars($_GET['minAmount'] ?? ''); ?>">
                    <input type="number" id="maxAmount" name="maxAmount"
                           placeholder="Max Amount" class="form-input"
                           step="0.01"
                           value="<?php echo htmlspecialchars($_GET['maxAmount'] ?? ''); ?>">
                    <!-- BOTÓN DE BÚSQUEDA AMARILLO -->
                    <button type="submit" class="btn-search">Search</button>
                </form>
            </div>

            <!-- Tarjeta 6: Recent Bills (wide-card) -->
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
                        <?php if (!empty($recent_bills)): ?>
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

            <!-- Tarjeta 7: Payment Methods -->
            <div class="card">
                <h2>Payment Methods</h2>
                <div id="paymentMethods">
                    <?php
                    // 13. Obtener métodos de pago del “cliente” con Account_ID fijo (ej. 1)
                    $customer_id = 1; // Valor fijo
                    try {
                        $stmt = $pdo->prepare("
                            SELECT *
                            FROM payment_methods
                            WHERE customer_id = :cust_id
                        ");
                        $stmt->execute(['cust_id' => $customer_id]);
                        $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $payment_methods = [];
                    }
                    ?>
                    <?php if (!empty($payment_methods)): ?>
                        <?php foreach ($payment_methods as $method): ?>
                            <div class="payment-method">
                                <?php
                                // Determinar ícono
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
</body>
</html>
