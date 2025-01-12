<?php
// edit_bill.php

require_once 'functions.php';

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

// Obtener el ID de la factura
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Factura inválida.");
}

$bill_id = $_GET['id'];

// Obtener datos de la factura
try {
    $stmt = $pdo->prepare("SELECT * FROM bills WHERE id = :id");
    $stmt->execute(['id' => $bill_id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$bill) {
        die("Factura no encontrada.");
    }
} catch (PDOException $e) {
    die("Error al obtener la factura: " . $e->getMessage());
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? $bill['amount'];
    $due_date = $_POST['due_date'] ?? $bill['due_date'];
    $status = $_POST['status'] ?? $bill['status'];

    // Validaciones básicas
    if (!is_numeric($amount) || $amount < 0) {
        $error = "El monto debe ser un número positivo.";
    } elseif (!strtotime($due_date)) {
        $error = "Fecha de vencimiento inválida.";
    } elseif (!in_array($status, ['Paid', 'Unpaid', 'Overdue'])) {
        $error = "Estado de factura inválido.";
    } else {
        // Actualizar factura en la base de datos
        try {
            $stmt = $pdo->prepare("UPDATE bills SET amount = :amount, due_date = :due_date, status = :status WHERE id = :id");
            $stmt->execute([
                'amount' => $amount,
                'due_date' => $due_date,
                'status' => $status,
                'id' => $bill_id
            ]);

            // (Opcional) Registrar actividad
            logActivity($pdo, $admin_id, "Editó la factura ID: " . $bill_id);

            $success = "Factura actualizada exitosamente.";
            // Actualizar los datos de la factura
            $bill['amount'] = $amount;
            $bill['due_date'] = $due_date;
            $bill['status'] = $status;
        } catch (PDOException $e) {
            $error = "Error al actualizar la factura: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Factura - HIP ENERGY</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reutilización de los estilos existentes */
        /* ... Mantén los mismos estilos que en facturas.php ... */
        /* Aquí no se repiten para ahorrar espacio */
    </style>
</head>
<body>
    <nav class="sidebar">
        <!-- ... Mantén la misma estructura de navegación ... -->
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
        <h1>Edit Bill</h1>
        <div class="dashboard">
            <div class="card wide-card">
                <h2>Editar Factura ID: <?php echo htmlspecialchars($bill['id']); ?></h2>
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="success-message">
                        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>
                <form method="POST" action="edit_bill.php?id=<?php echo $bill['id']; ?>">
                    <label for="amount">Amount:</label>
                    <input type="number" id="amount" name="amount" class="form-input" step="0.01" value="<?php echo htmlspecialchars($bill['amount']); ?>" required>

                    <label for="due_date">Due Date:</label>
                    <input type="date" id="due_date" name="due_date" class="form-input" value="<?php echo htmlspecialchars($bill['due_date']); ?>" required>

                    <label for="status">Status:</label>
                    <select id="status" name="status" class="form-input" required>
                        <option value="Paid" <?php echo ($bill['status'] === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                        <option value="Unpaid" <?php echo ($bill['status'] === 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="Overdue" <?php echo ($bill['status'] === 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                    </select>

                    <button type="submit" class="btn">Update Bill</button>
                </form>
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
