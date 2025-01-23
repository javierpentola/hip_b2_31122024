<?php
session_start();

// Datos de conexión a la base de datos en InfinityFree
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

// Inicializar variables para mensajes
$error = '';

// Si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibir los datos del formulario
    $username = trim($_POST['username']);         // Limpiar posibles espacios
    $userPassword = trim($_POST['password']); // Limpiar posibles espacios

    if (empty($username) || empty($userPassword)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        try {
            // Establecer conexión con la base de datos usando PDO
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verificar si el usuario existe en la base de datos
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
            $stmt->execute(['username' => $username]);
            
            // Si se encuentra el usuario
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar la contraseña usando password_verify
                if (password_verify($userPassword, $admin['password'])) {
                    // Actualizar el estado de inicio de sesión y la última vez que inició sesión
                    $updateStmt = $pdo->prepare("UPDATE admins SET is_logged_in = 1, last_login = NOW() WHERE id = :id");
                    $updateStmt->execute(['id' => $admin['id']]);
                    
                    // Configurar variables de sesión
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['role_id'] = $admin['role_id'];
                    
                    // Redirigir al dashboard.php después del inicio de sesión exitoso
                    header('Location: dashboard.php');
                    exit();  // Asegúrate de que después de la redirección, el script no continúe ejecutándose
                } else {
                    $error = "Contraseña incorrecta.";
                }
            } else {
                $error = "El nombre de usuario no está registrado.";
            }
        } catch (PDOException $e) {
            $error = "Error de conexión: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIP ENERGY Navigation - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .form-group {
            margin-bottom: 1rem;
            width: 100%;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background-color: #f3c517;  /* Color de fondo amarillo */
            color: #000;  /* Texto en negro */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
        }

        .recover-password {
            text-align: center;
            margin-top: 1rem;
            width: 100%;
        }

        .recover-password a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }

        .recover-password a:hover {
            text-decoration: underline;
        }

        .error-message {
            background-color: #ffdddd;
            border-left: 6px solid #f44336;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-weight: 500;
            width: 100%;
            text-align: center;
        }

        .success-message {
            background-color: #ddffdd;
            border-left: 6px solid #4CAF50;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-weight: 500;
            text-align: center;
            width: 100%;
        }

        .main-content h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .success-message a {
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: bold;
        }

        .success-message a:hover {
            text-decoration: underline;
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

    <nav class="sidebar">
        <div class="brand">HIP ENERGY</div>
        <div class="nav-items">
            <a href="index.php" class="nav-item">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login</span>
            </a>
            <a href="register.php" class="nav-item">
                <i class="fas fa-user-plus"></i>
                <span>Register</span>
            </a>
            <a href="recover_password.php" class="nav-item">
                <i class="fas fa-key"></i>
                <span>Recover Password</span>
            </a>
            <a href="admin_login.php" class="nav-item active">
                <i class="fas fa-home"></i>
               <span>Admin login</span>
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
        <h1>Login</h1>
        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form action="admin_login.php" method="POST">
                <div class="form-group">
                    <label for="username">Nombre de Usuario</label>
                    <input type="text" id="username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="submit-btn">Login</button>
            </form>
            <div class="recover-password">
                <a href="recover_password.php">Recover password</a>
            </div>
        </div>
    </main>

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

        (function() {
            let timeout;
            const redirectUrl = 'admin_login.php';
            const timeoutDuration = 5 * 60 * 1000; // 5 minutos en milisegundos

            // Reiniciar el temporizador
            function resetTimer() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    window.location.href = redirectUrl;
                }, timeoutDuration);
            }

            // Eventos que reinician el temporizador
            window.onload = resetTimer;
            document.onmousemove = resetTimer;
            document.onkeydown = resetTimer;
            document.onclick = resetTimer;
            document.onscroll = resetTimer;
        })();
    </script>
</body>
</html>
