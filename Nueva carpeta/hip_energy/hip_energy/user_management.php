<?php
// Iniciar sesión
session_start();

// Datos de conexión a la base de datos en InfinityFree
$host = 'sql108.infinityfree.com'; // mysql host name
$user = 'if0_37852817';           // mysql username
$pass = 'BkgzaebxbJ';             // mysql password
$db   = 'if0_37852817_hipgeneraldb';// mysql database

// Inicializar variables para mensajes
$error = '';
$success_message = '';

// Conectar a la base de datos usando PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Manejar la adición de un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addUser'])) {
    // Recibir y sanitizar los datos del formulario
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $consumption = trim($_POST['consumption']);
    $balance = trim($_POST['balance']);

    // Validar los campos
    if (empty($firstName) || empty($lastName) || empty($email) || empty($username) || empty($password) || empty($consumption) || empty($balance)) {
        $error = "Por favor, completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato de email inválido.";
    } else {
        // Verificar si el email o username ya existen
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Customer WHERE Email = :email OR username = :username");
        $stmt->execute(['email' => $email, 'username' => $username]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = "El email o username ya están registrados.";
        } else {
            // Hash de la contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insertar el nuevo usuario
            $stmt = $pdo->prepare("INSERT INTO Customer (First_Name, Last_Name, Email, username, Password, Consumption, Balance) VALUES (:firstName, :lastName, :email, :username, :password, :consumption, :balance)");
            try {
                $stmt->execute([
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'username' => $username,
                    'password' => $hashedPassword,
                    'consumption' => $consumption,
                    'balance' => $balance
                ]);
                $success_message = "Usuario añadido exitosamente.";
            } catch (PDOException $e) {
                $error = "Error al añadir el usuario: " . $e->getMessage();
            }
        }
    }
}

// Manejar la eliminación de un usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteUser'])) {
    $deleteId = $_POST['deleteId'];

    // Verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT * FROM Customer WHERE Account_ID = :id");
    $stmt->execute(['id' => $deleteId]);
    if ($stmt->rowCount() > 0) {
        // Eliminar el usuario
        $stmt = $pdo->prepare("DELETE FROM Customer WHERE Account_ID = :id");
        try {
            $stmt->execute(['id' => $deleteId]);
            $success_message = "Usuario eliminado exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al eliminar el usuario: " . $e->getMessage();
        }
    } else {
        $error = "Usuario no encontrado.";
    }
}

// Manejar el restablecimiento de la contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resetPassword'])) {
    $resetId = $_POST['resetId'];
    $newPassword = 'password';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT * FROM Customer WHERE Account_ID = :id");
    $stmt->execute(['id' => $resetId]);
    if ($stmt->rowCount() > 0) {
        // Actualizar la contraseña
        $stmt = $pdo->prepare("UPDATE Customer SET Password = :password WHERE Account_ID = :id");
        try {
            $stmt->execute(['password' => $hashedPassword, 'id' => $resetId]);
            $success_message = "Contraseña restablecida a 'password' exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al restablecer la contraseña: " . $e->getMessage();
        }
    } else {
        $error = "Usuario no encontrado.";
    }
}

// Manejar la edición de un usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editUser'])) {
    $editId = $_POST['editId'];
    $firstName = trim($_POST['editFirstName']);
    $lastName = trim($_POST['editLastName']);
    $email = trim($_POST['editEmail']);
    $username = trim($_POST['editUsername']);
    // $consumption and $balance are ignored for editing

    // Validar los campos
    if (empty($firstName) || empty($lastName) || empty($email) || empty($username)) {
        $error = "Por favor, completa todos los campos de edición.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato de email inválido.";
    } else {
        // Verificar si el email o username ya existen para otro usuario
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Customer WHERE (Email = :email OR username = :username) AND Account_ID != :id");
        $stmt->execute(['email' => $email, 'username' => $username, 'id' => $editId]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = "El email o username ya están registrados por otro usuario.";
        } else {
            // Actualizar el usuario sin modificar 'Consumption' y 'Balance'
            $stmt = $pdo->prepare("UPDATE Customer SET First_Name = :firstName, Last_Name = :lastName, Email = :email, username = :username WHERE Account_ID = :id");
            try {
                $stmt->execute([
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'username' => $username,
                    'id' => $editId
                ]);
                $success_message = "Usuario editado exitosamente.";
            } catch (PDOException $e) {
                $error = "Error al editar el usuario: " . $e->getMessage();
            }
        }
    }
}

// Manejar los filtros de búsqueda
$search = '';
$searchParam = [];
$searchQuery = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $searchQuery = " WHERE First_Name LIKE :search OR Last_Name LIKE :search OR Email LIKE :search OR username LIKE :search";
    $searchParam = ['search' => "%$search%"];
}

// Obtener los usuarios
try {
    $stmt = $pdo->prepare("SELECT * FROM Customer" . $searchQuery);
    $stmt->execute($searchParam);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los usuarios: " . $e->getMessage());
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
    <style>
        /* Estilos originales */
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

        .user-management {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .user-management h2 {
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

        .add-user-form, .edit-user-form {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .add-user-form input, .edit-user-form input {
            flex: 1;
            min-width: 200px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-form {
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-form input {
            flex: 1;
            min-width: 200px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-form button {
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-form button:hover {
            background-color: var(--primary-dark);
        }

        .message {
            margin-top: 1rem;
            padding: 0.5rem;
            border-radius: 4px;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        /* Modal Styles */
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
            <a href="user_management.php" class="nav-item active">
                <i class="fas fa-users"></i>
                <span>Gestión de Usuarios</span>
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
        <div class="user-management">
            <h2>3. Gestión de Usuarios</h2>
            
            <h3>Visualización de Usuarios</h3>
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Buscar por nombre, email o username" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Buscar</button>
            </form>

            <?php if (!empty($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <table class="user-table">
                <thead>
                    <tr>
                        <th>Account ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Consumption</th>
                        <th>Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['Account_ID']); ?></td>
                                <td><?php echo htmlspecialchars($user['First_Name']); ?></td>
                                <td><?php echo htmlspecialchars($user['Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['Consumption']); ?></td>
                                <td><?php echo htmlspecialchars($user['Balance']); ?></td>
                                <td>
                                    <button class="action-btn edit-btn" data-id="<?php echo htmlspecialchars($user['Account_ID']); ?>">Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="deleteId" value="<?php echo htmlspecialchars($user['Account_ID']); ?>">
                                        <button type="submit" name="deleteUser" class="action-btn" onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario?')">Delete</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="resetId" value="<?php echo htmlspecialchars($user['Account_ID']); ?>">
                                        <button type="submit" name="resetPassword" class="action-btn" onclick="return confirm('¿Estás seguro de que deseas restablecer la contraseña de este usuario?')">Reset Password</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No se encontraron usuarios.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modal para Editar Usuario -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3>Editar Usuario</h3>
                    <form method="POST" class="edit-user-form">
                        <input type="hidden" name="editId" id="editId">
                        <input type="text" name="editFirstName" id="editFirstName" placeholder="First Name" required>
                        <input type="text" name="editLastName" id="editLastName" placeholder="Last Name" required>
                        <input type="email" name="editEmail" id="editEmail" placeholder="Email" required>
                        <input type="text" name="editUsername" id="editUsername" placeholder="Username" required>
                        <button type="submit" name="editUser" class="action-btn">Guardar Cambios</button>
                    </form>
                </div>
            </div>

            <h3>Añadir Nuevo Usuario</h3>
            <form method="POST" id="addUserForm" class="add-user-form">
                <input type="text" name="firstName" placeholder="First Name" required>
                <input type="text" name="lastName" placeholder="Last Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="number" step="0.01" name="consumption" placeholder="Consumption" required>
                <input type="number" step="0.01" name="balance" placeholder="Balance" required>
                <button type="submit" name="addUser" class="action-btn">Añadir Usuario</button>
            </form>
        </div>
    </main>

    <button class="logout-btn" onclick="logout()">Cerrar Sesión</button>

    <!-- Modal para Editar Usuario -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Editar Usuario</h3>
            <form method="POST" class="edit-user-form">
                <input type="hidden" name="editId" id="editId">
                <input type="text" name="editFirstName" id="editFirstName" placeholder="First Name" required>
                <input type="text" name="editLastName" id="editLastName" placeholder="Last Name" required>
                <input type="email" name="editEmail" id="editEmail" placeholder="Email" required>
                <input type="text" name="editUsername" id="editUsername" placeholder="Username" required>
                <button type="submit" name="editUser" class="action-btn">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <script>
        // Obtener el modal
        var modal = document.getElementById("editModal");

        // Obtener el elemento <span> que cierra el modal
        var span = document.getElementsByClassName("close")[0];

        // Cuando el usuario hace clic en <span> (x), cierra el modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // Cuando el usuario hace clic fuera del modal, lo cierra
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Obtener todos los botones de edición
        var editButtons = document.getElementsByClassName("edit-btn");

        Array.from(editButtons).forEach(function(button) {
            button.addEventListener("click", function() {
                var userId = this.getAttribute("data-id");

                // Obtener los datos de la fila correspondiente
                var row = this.parentElement.parentElement;
                var cells = row.getElementsByTagName("td");

                var firstName = cells[1].innerText;
                var lastName = cells[2].innerText;
                var email = cells[3].innerText;
                var username = cells[4].innerText;

                // Llenar el formulario del modal con los datos del usuario
                document.getElementById("editId").value = userId;
                document.getElementById("editFirstName").value = firstName;
                document.getElementById("editLastName").value = lastName;
                document.getElementById("editEmail").value = email;
                document.getElementById("editUsername").value = username;

                // Mostrar el modal
                modal.style.display = "block";
            });
        });

        function logout() {
            // Aquí se implementaría la lógica para cerrar sesión
            alert("Sesión cerrada");

            // Redirigir al index.php
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>
