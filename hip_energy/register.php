<?php
session_start();

// Datos de conexión a la base de datos
$host = 'sql108.infinityfree.com';
$user = 'if0_37852817';
$pass = 'BkgzaebxbJ';
$db   = 'if0_37852817_hipgeneraldb';

$error = '';
$success_message = '';

// Función para generar un Account_ID único
function generateUniqueAccountID($pdo) {
    do {
        // Generar un número aleatorio de 9 dígitos
        $accountID = mt_rand(100000000, 999999999);

        $stmt = $pdo->prepare("SELECT Account_ID FROM Customer WHERE Account_ID = :accountID");
        $stmt->execute(['accountID' => $accountID]);

        $exists = ($stmt->rowCount() > 0);
    } while ($exists);

    return $accountID;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);

    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "Por favor, completa todos los campos.";
    } elseif ($password !== $confirmPassword) {
        $error = "Las contraseñas no coinciden.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Verificar si el email ya está registrado
            $stmt = $pdo->prepare("SELECT * FROM Customer WHERE Email = :email");
            $stmt->execute(['email' => $email]);

            if ($stmt->rowCount() > 0) {
                $error = "The email is already registered.";
            } else {
                // Verificar si el nombre de usuario ya existe
                $stmt = $pdo->prepare("SELECT * FROM Customer WHERE username = :username");
                $stmt->execute(['username' => $username]);

                if ($stmt->rowCount() > 0) {
                    $error = "El nombre de usuario ya está en uso.";
                } else {
                    // Generar Account_ID único
                    $accountID = generateUniqueAccountID($pdo);

                    // Generar Consumption y Balance aleatorios
                    $consumption = rand(0, 1000) + rand() / getrandmax();
                    $balance = rand(0, 10000) + rand() / getrandmax();

                    // Hash de la contraseña
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Insertar el nuevo usuario en la base de datos
                    $insertStmt = $pdo->prepare("INSERT INTO Customer (Account_ID, First_Name, Last_Name, Consumption, Balance, Email, Password, username)
                                                VALUES (:accountID, :firstName, :lastName, :consumption, :balance, :email, :password, :username)");
                    $insertStmt->execute([
                        'accountID'   => $accountID,
                        'firstName'   => $firstName,
                        'lastName'    => $lastName,
                        'consumption' => $consumption,
                        'balance'     => $balance,
                        'email'       => $email,
                        'password'    => $hashedPassword,
                        'username'    => $username
                    ]);

                    // Mensaje de éxito (más comercial)
                    $success_message = "¡Felicitaciones! Tu cuenta ha sido creada exitosamente. 
                                        Ahora puedes <a href='index.html'>iniciar sesión</a> y disfrutar de nuestros servicios.";
                }
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

        .chat-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }


        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 400px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1rem;
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
            background-color: var(--accent-color);
            color: white;
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
		
		/* Estilos para mensajes de error */
.error-message {
    background-color: #ffdddd;
    border-left: 6px solid #f44336;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
}

    .error-message {
        background-color: #ffdddd;
        border-left: 6px solid #f44336;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
        font-weight: 500;
    }

    /* Estilos para mensajes de éxito */
    .success-message {
        background-color: #ddffdd;
        border-left: 6px solid #4CAF50;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
        font-weight: 500;
        text-align: center;
    }

    /* Asegurar que el formulario esté correctamente centrado y no afecte otros elementos */
    .form-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    /* Ajustar el título para que sea visible */
    .main-content h1 {
        text-align: center;
        margin-bottom: 20px;
    }

    /* Opcional: Estilos adicionales para mejorar la apariencia del enlace en el mensaje de éxito */
    .success-message a {
        color: var(--primary-dark);
        text-decoration: none;
        font-weight: bold;
    }

    .success-message a:hover {
        text-decoration: underline;

    </style>
</head>
<body>
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
<?php 
    // Make sure that $accountID is defined and safe to display
    echo "Congratulations! Your account has been successfully created. Your account number is: <strong>" . htmlspecialchars($accountID) . "</strong>. 
          You can now <a href='index.php'>log in</a> and enjoy our services."; 
?>

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





<style>
    .submit-btn {
        background-color: #f3c517;  /* Color de fondo amarillo */
        color: white;  /* Texto en blanco */
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .submit-btn:hover {
        background-color: #d4a017;  /* Cambio de color al pasar el ratón por encima */
    }
</style>


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
        const accessibleModeToggle = document.getElementById('accessibleModeToggle');
        const protanopiaToggle = document.getElementById('protanopiaToggle');
        const deuteranopiaToggle = document.getElementById('deuteranopiaToggle');
        const tritanopiaToggle = document.getElementById('tritanopiaToggle');
        const normalModeToggle = document.getElementById('normalModeToggle');
        
        accessibleModeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('accessible-mode');
        });

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

        // Chat functionality (commented out)
        /*const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');

        function addMessage(message, isUser = true) {
            const messageElement = document.createElement('div');
            messageElement.textContent = message;
            messageElement.style.marginBottom = '10px';
            messageElement.style.padding = '5px';
            messageElement.style.borderRadius = '5px';
            messageElement.style.maxWidth = '70%';
            
            if (isUser) {
                messageElement.style.backgroundColor = '#e6f2ff';
                messageElement.style.marginLeft = 'auto';
            } else {
                messageElement.classList.add('bot-message');
                const botIcon = document.createElement('div');
                botIcon.classList.add('bot-icon');
                botIcon.innerHTML = '🦁'; // Cambiado de '🤖' a '🦁'
                messageElement.insertBefore(botIcon, messageElement.firstChild);
            }
            
            chatMessages.appendChild(messageElement);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function handleSendMessage() {
            const message = messageInput.value.trim();
            if (message) {
                addMessage(message);
                messageInput.value = '';
                
                // Simulate a response (you can replace this with actual backend logic)
                setTimeout(() => {
                    addMessage("Thank you for reporting the issue. Our team will look into it and get back to you soon.", false);
                }, 1000);
            }
        }

        sendButton.addEventListener('click', handleSendMessage);
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                handleSendMessage();
            }
        });

        // Add initial message
        addMessage("Welcome to Fault Reporting. How can we assist you today?", false);*/


    </script>
</body>
</html>