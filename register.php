<?php
session_start();

// Datos de conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

$error = '';
$success_message = '';

// Función para generar un Account_ID único
function generateUniqueAccountID($pdo) {
    do {
        // Generar un número aleatorio de 9 dígitos
        $accountID = mt_rand(100000000, 999999999);

        $stmt = $pdo->prepare("SELECT Account_ID FROM customer WHERE Account_ID = :accountID");
        $stmt->execute(['accountID' => $accountID]);

        $exists = ($stmt->rowCount() > 0);
    } while ($exists);

    return $accountID;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName       = trim($_POST['firstName']);
    $lastName        = trim($_POST['lastName']);
    $username        = trim($_POST['username']);
    $email           = trim($_POST['email']);
    $password        = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);

    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "Por favor, completa todos los campos.";
    } elseif ($password !== $confirmPassword) {
        $error = "Las contraseñas no coinciden.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Iniciar transacción
            $pdo->beginTransaction();

            // Verificar si el email ya está registrado en customer
            $stmt = $pdo->prepare("SELECT * FROM customer WHERE Email = :email");
            $stmt->execute(['email' => $email]);

            if ($stmt->rowCount() > 0) {
                $error = "El email ya está registrado.";
                $pdo->rollBack();
            } else {
                // Verificar si el nombre de usuario ya existe en customer
                $stmt = $pdo->prepare("SELECT * FROM customer WHERE username = :username");
                $stmt->execute(['username' => $username]);

                if ($stmt->rowCount() > 0) {
                    $error = "El nombre de usuario ya está en uso.";
                    $pdo->rollBack();
                } else {
                    // Verificar si el nombre de usuario ya existe en admins
                    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
                    $stmt->execute(['username' => $username]);

                    if ($stmt->rowCount() > 0) {
                        $error = "El nombre de usuario ya está en uso en el panel de administración.";
                        $pdo->rollBack();
                    } else {
                        // Generar Account_ID único
                        $accountID = generateUniqueAccountID($pdo);

                        // Generar Consumption y Balance aleatorios
                        $consumption = rand(0, 1000) + rand() / getrandmax();
                        $balance     = rand(0, 10000) + rand() / getrandmax();

                        // Hash de la contraseña
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        // Insertar el nuevo usuario en la tabla customer
                        $insertCustomer = $pdo->prepare("INSERT INTO customer (Account_ID, First_Name, Last_Name, Consumption, Balance, Email, Password, username)
                                                         VALUES (:accountID, :firstName, :lastName, :consumption, :balance, :email, :password, :username)");
                        $insertCustomer->execute([
                            'accountID'   => $accountID,
                            'firstName'   => $firstName,
                            'lastName'    => $lastName,
                            'consumption' => $consumption,
                            'balance'     => $balance,
                            'email'       => $email,
                            'password'    => $hashedPassword,
                            'username'    => $username
                        ]);

                        // Insertar el nuevo usuario en la tabla admins
                        $insertAdmin = $pdo->prepare("INSERT INTO admins (username, password, role_id, is_logged_in, last_login)
                                                      VALUES (:username, :password, :role_id, 0, NULL)");
                        $insertAdmin->execute([
                            'username' => $username,
                            'password' => $hashedPassword,
                            'role_id'  => 1 // Asumiendo que 1 es el ID para el rol de admin
                        ]);

                        // Confirmar transacción
                        $pdo->commit();

                        // Mensaje de éxito
                        $success_message = "¡Felicitaciones! Tu cuenta ha sido creada exitosamente. 
                                            Tu número de cuenta es: <strong>" . htmlspecialchars($accountID) . "</strong>. 
                                            Ahora puedes <a href='admin_login.php'>iniciar sesión</a> y disfrutar de nuestros servicios.";
                    }
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
    <title>HIP ENERGY Navigation</title>
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
            <a href="register.php" class="nav-item active">
                <i class="fas fa-user-plus"></i>
                <span>Register</span>
            </a>
            <a href="recover_password.php" class="nav-item">
                <i class="fas fa-key"></i>
                <span>Recover Password</span>
            </a>
            <a href="admin_login.php" class="nav-item">
                <i class="fas fa-home"></i>
               <span>Admin dashboard</span>
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
        <h1>Sign Up</h1>
        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php else: ?>
                <form id="signupForm" method="POST" action="register.php">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" required value="<?php echo isset($firstName) ? htmlspecialchars($firstName) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" required value="<?php echo isset($lastName) ? htmlspecialchars($lastName) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <button type="submit" class="submit-btn">Sign Up</button>
                </form>
            <?php endif; ?>
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
