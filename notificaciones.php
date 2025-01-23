<?php
// notificaciones.php

// 1. (Opcional) require_once 'functions.php'; // si tuvieras funciones extra

// 2. Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 3. SIN sesión
// session_start(); // Eliminado

// 4. Conexión PDO
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

/**
 * Función de validación de fecha/hora
 */
function validateDateTime($datetime, $format = 'Y-m-d\TH:i') {
    $d = DateTime::createFromFormat($format, $datetime);
    return $d && $d->format($format) === $datetime;
}

$error   = '';
$success = '';

// 5. Procesar formularios (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A) Agregar o Editar
    if (isset($_POST['action'])) {
        // A1) Agregar
        if ($_POST['action'] === 'add') {
            $title          = trim($_POST['title']);
            $message        = trim($_POST['message']);
            $category       = $_POST['category'];
            $priority       = $_POST['priority'];
            $scheduled_time = !empty($_POST['scheduled_time']) ? $_POST['scheduled_time'] : null;
            $user_id        = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
            $read_status    = 'No leído';

            // Manejo de adjunto
            $attachment = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath   = $_FILES['attachment']['tmp_name'];
                $fileName      = $_FILES['attachment']['name'];
                $fileNameCmps  = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $newFileName   = md5(time() . $fileName) . '.' . $fileExtension;

                $uploadDir = 'uploads/notices/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $destPath = $uploadDir . $newFileName;

                $allowedExts = ['jpg','gif','png','pdf','doc','docx','xls','xlsx'];
                if (in_array($fileExtension, $allowedExts)) {
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $attachment = $destPath;
                    } else {
                        $attachment = null;
                        $error = "Error al subir adjunto.";
                    }
                } else {
                    $error = "Tipo de archivo no permitido.";
                }
            }

            // Validaciones
            if (empty($title) || empty($message) || empty($category) || empty($priority)) {
                $error = "Título, mensaje, categoría y prioridad son obligatorios.";
            } elseif (!in_array($category, ['Urgente','Info','Recordatorio'])) {
                $error = "Categoría inválida.";
            } elseif (!in_array($priority, ['Alta','Media','Baja'])) {
                $error = "Prioridad inválida.";
            } elseif (!empty($scheduled_time) && !validateDateTime($scheduled_time)) {
                $error = "Fecha/Hora programada inválida.";
            } else {
                // Insertar en la tabla 'notices'
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO notices
                        (title, message, category, priority, scheduled_time, user_id, read_status, attachment)
                        VALUES (:title, :message, :category, :priority, :scheduled_time, :user_id, :read_status, :attachment)
                    ");
                    $stmt->execute([
                        'title'          => $title,
                        'message'        => $message,
                        'category'       => $category,
                        'priority'       => $priority,
                        'scheduled_time' => $scheduled_time,
                        'user_id'        => $user_id,
                        'read_status'    => $read_status,
                        'attachment'     => $attachment
                    ]);
                    $success = "Notificación añadida con éxito.";
                } catch (PDOException $e) {
                    $error = "Error al añadir: " . $e->getMessage();
                }
            }
        }
        // A2) Editar
        elseif ($_POST['action'] === 'edit' && !empty($_POST['id'])) {
            $id             = $_POST['id'];
            $title          = trim($_POST['title']);
            $message        = trim($_POST['message']);
            $category       = $_POST['category'];
            $priority       = $_POST['priority'];
            $scheduled_time = !empty($_POST['scheduled_time']) ? $_POST['scheduled_time'] : null;
            $user_id        = (!empty($_POST['user_id'])) ? $_POST['user_id'] : null;

            // Manejo de adjunto
            $attachment = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath   = $_FILES['attachment']['tmp_name'];
                $fileName      = $_FILES['attachment']['name'];
                $fileNameCmps  = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $newFileName   = md5(time() . $fileName) . '.' . $fileExtension;

                $uploadDir = 'uploads/notices/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $destPath = $uploadDir . $newFileName;

                $allowedExts = ['jpg','gif','png','pdf','doc','docx','xls','xlsx'];
                if (in_array($fileExtension, $allowedExts)) {
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $attachment = $destPath;
                    } else {
                        $error = "Error al subir adjunto.";
                    }
                } else {
                    $error = "Tipo de archivo no permitido.";
                }
            } else {
                // Mantener el existente
                $stmt = $pdo->prepare("SELECT attachment FROM notices WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $existing   = $stmt->fetch(PDO::FETCH_ASSOC);
                $attachment = $existing['attachment'];
            }

            if (empty($title) || empty($message) || empty($category) || empty($priority)) {
                $error = "Título, mensaje, categoría y prioridad son obligatorios.";
            } elseif (!in_array($category, ['Urgente','Info','Recordatorio'])) {
                $error = "Categoría inválida.";
            } elseif (!in_array($priority, ['Alta','Media','Baja'])) {
                $error = "Prioridad inválida.";
            } elseif (!empty($scheduled_time) && !validateDateTime($scheduled_time)) {
                $error = "Fecha/Hora programada inválida.";
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE notices
                        SET
                            title = :title,
                            message = :message,
                            category = :category,
                            priority = :priority,
                            scheduled_time = :scheduled_time,
                            user_id = :user_id,
                            attachment = :attachment
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'title'          => $title,
                        'message'        => $message,
                        'category'       => $category,
                        'priority'       => $priority,
                        'scheduled_time' => $scheduled_time,
                        'user_id'        => $user_id,
                        'attachment'     => $attachment,
                        'id'             => $id
                    ]);
                    $success = "Notificación actualizada con éxito.";
                } catch (PDOException $e) {
                    $error = "Error al actualizar: " . $e->getMessage();
                }
            }
        }
    }

    // B) Eliminar
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        try {
            $stmt = $pdo->prepare("SELECT title, attachment FROM notices WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if (!empty($row['attachment']) && file_exists($row['attachment'])) {
                    unlink($row['attachment']);
                }
                $stmt = $pdo->prepare("DELETE FROM notices WHERE id = :id");
                $stmt->execute(['id' => $delete_id]);
                $success = "Notificación eliminada.";
            } else {
                $error = "No encontrada.";
            }
        } catch (PDOException $e) {
            $error = "Error al eliminar: " . $e->getMessage();
        }
    }
}

// 6. Obtener notificaciones
try {
    $current_time = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT * 
        FROM notices
        WHERE (scheduled_time IS NULL OR scheduled_time <= :now)
        ORDER BY priority DESC, created_at DESC
    ");
    $stmt->execute(['now' => $current_time]);
    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener notificaciones: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HIP ENERGY - Notificaciones</title>
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
        /* Lista de notificaciones */
        .notices-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .notice-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem;
            display: flex;
            align-items: flex-start;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .notice-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .notice-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--primary-dark);
        }
        .notice-content {
            flex: 1;
        }
        .notice-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .notice-message {
            color: #666;
            font-size: 0.9rem;
        }
        .notice-time {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.5rem;
        }
        .notice-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 0.5rem;
            gap: 1rem;
        }
        .notice-btn {
            background: none;
            border: none;
            color: var(--primary-dark);
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0;
        }
        .notice-btn:hover {
            text-decoration: underline;
        }
        .unread {
            border-left: 4px solid red;
        }
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            padding-top: 100px;
            left: 0; top: 0;
            width: 100%; height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 1rem;
            border: 1px solid #888;
            width: 80%; max-width: 500px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
        }
        /* FAB (Floating Action Button) para Añadir */
        .fab-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #009688; /* color de tu preferencia */
            color: white;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            border: none;
            z-index: 3000;
        }
        .fab-button:hover {
            background-color: #00796b;
        }
        /* Modal de Añadir Notificación (ligeramente diferente) */
        #addModal .modal-content {
            background-color: #fff;
            padding: 1.5rem;
        }
        #addModal h2 {
            margin-top: 0;
        }
        .error, .success {
            margin: 0.5rem 0;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        .form-input {
            width: 100%; padding: 0.5rem; margin-bottom: 0.5rem;
            border: 1px solid #ccc; border-radius: 4px;
        }
        .btn-file {
            margin-top: 0.5rem;
        }
        .btn-confirm {
            background-color: #ffab00;
            color: #000;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .btn-confirm:hover {
            background-color: #d4a017;
            color: #fff;
        }
        /* Responsivo */
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
            <a href="home.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="consumo.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Consumption</span>
            </a>
            <a href="facturas.php" class="nav-item">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Bills</span>
            </a>
            <a href="notificaciones.php" class="nav-item active">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <div style="margin-left:auto;">
                    <?php echo count($notices); ?>
                </div>
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
            <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/Normal-S0ZM46xhJ8Mm0vNUqKXmmqWS9gTvZJ.png" 
                 alt="HIP ENERGY Logo" class="logo">
        </div>
    </nav>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        <h1>Notificaciones</h1>

        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <div class="notices-list">
            <?php if (!empty($notices)): ?>
                <?php foreach ($notices as $notice): ?>
                    <div class="notice-card <?php echo ($notice['read_status'] === 'No leído') ? 'unread' : ''; ?>" data-id="<?php echo htmlspecialchars($notice['id']); ?>">
                        <div class="notice-icon">
                            <?php
                            switch ($notice['category']) {
                                case 'Urgente':
                                    echo '<i class="fas fa-exclamation-circle" style="color:red;"></i>';
                                    break;
                                case 'Info':
                                    echo '<i class="fas fa-info-circle" style="color:blue;"></i>';
                                    break;
                                case 'Recordatorio':
                                    echo '<i class="fas fa-calendar-check" style="color:green;"></i>';
                                    break;
                                default:
                                    echo '<i class="fas fa-info-circle"></i>';
                            }
                            ?>
                        </div>
                        <div class="notice-content">
                            <div class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></div>
                            <div class="notice-message"><?php echo nl2br(htmlspecialchars($notice['message'])); ?></div>
                            <?php if (!empty($notice['attachment'])): ?>
                                <div class="notice-attachment">
                                    <a href="<?php echo htmlspecialchars($notice['attachment']); ?>" target="_blank">Ver adjunto</a>
                                </div>
                            <?php endif; ?>
                            <div class="notice-time">
                                <?php
                                if (!empty($notice['created_at'])) {
                                    echo date('M d, Y H:i', strtotime($notice['created_at']));
                                }
                                ?>
                            </div>
                            <div class="notice-actions">
                                <!-- EDIT -->
                                <button class="notice-btn btn-edit" data-id="<?php echo $notice['id']; ?>">Editar</button>
                                <!-- DELETE -->
                                <form method="POST" action="notificaciones.php" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo $notice['id']; ?>">
                                    <button type="submit" class="notice-btn btn-delete" onclick="return confirm('¿Deseas eliminar?');">
                                        Eliminar
                                    </button>
                                </form>
                                <!-- MARCAR LEÍDO / NO LEÍDO -->
                                <button class="notice-btn btn-mark-read" data-id="<?php echo $notice['id']; ?>">
                                    <?php echo ($notice['read_status'] === 'No leído') ? 'Marcar como Leído' : 'Marcar como No Leído'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay notificaciones disponibles.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- BOTÓN FLOTANTE PARA ABRIR MODAL "AÑADIR" -->
    <button class="fab-button" id="fabButton">+</button>

    <!-- MODAL AÑADIR NUEVA NOTIFICACIÓN -->
    <div id="addModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Añadir Nueva Notificación</h2>
        <form method="POST" action="notificaciones.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">

            <label for="title">Título:</label>
            <input type="text" id="title" name="title" class="form-input" required>

            <label for="message">Mensaje:</label>
            <textarea id="message" name="message" class="form-input" rows="4" required></textarea>

            <label for="category">Categoría:</label>
            <select id="category" name="category" class="form-input" required>
                <option value="">Seleccionar Categoría</option>
                <option value="Urgente">Urgente</option>
                <option value="Info">Información</option>
                <option value="Recordatorio">Recordatorio</option>
            </select>

            <label for="priority">Prioridad:</label>
            <select id="priority" name="priority" class="form-input" required>
                <option value="">Seleccionar Prioridad</option>
                <option value="Alta">Alta</option>
                <option value="Media">Media</option>
                <option value="Baja">Baja</option>
            </select>

            <label for="scheduled_time">Hora Programada (Opcional):</label>
            <input type="datetime-local" id="scheduled_time" name="scheduled_time" class="form-input">

            <label for="user_id">Usuario Específico (Opcional):</label>
            <select id="user_id" name="user_id" class="form-input">
                <option value="">Todos los Usuarios</option>
                <?php
                // Cargar usuarios
                try {
                    $stmtUsr = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
                    $allUsers = $stmtUsr->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($allUsers as $u) {
                        echo '<option value="'.htmlspecialchars($u['id']).'">'.htmlspecialchars($u['username']).'</option>';
                    }
                } catch (PDOException $e) {
                    echo '<option value="">Error al cargar usuarios</option>';
                }
                ?>
            </select>

            <label for="attachment">Archivo Adjunto (Opcional):</label>
            <input type="file" id="attachment" name="attachment" class="form-input" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">

            <button type="submit" class="btn-confirm">Añadir Notificación</button>
        </form>
      </div>
    </div>

    <script>
    // Función logout (solo demo)
    function logout() {
        alert("Admin ha cerrado sesión (demo).");
        window.location.href = 'index.php';
    }

    // BOTÓN FLOTANTE
    const fabButton = document.getElementById('fabButton');
    const addModal  = document.getElementById('addModal');
    const closeAdd  = addModal.querySelector('.close');

    // Abrir modal "Añadir" al hacer clic en el FAB
    fabButton.addEventListener('click', function() {
        addModal.style.display = "block";
    });
    // Cerrar modal
    closeAdd.addEventListener('click', function() {
        addModal.style.display = "none";
    });
    window.addEventListener('click', function(e) {
        if (e.target === addModal) {
            addModal.style.display = "none";
        }
    });

    // MANEJO EDICIÓN
    const editButtons = document.querySelectorAll('.btn-edit');
    const markButtons = document.querySelectorAll('.btn-mark-read');

    // A) Lógica de Modal para editar si quisieras re-implementar un modal distinto
    //    De momento, en este ejemplo, lo dejamos en el "Edición no implementada en un modal"
    //    Podrías crear otro modal si deseas.

    // B) Marcar (No) Leído
    markButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const noticeId = this.dataset.id;
            const currentText = this.innerText;
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "update_read_status.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        btn.innerText = (currentText === 'Marcar como Leído') 
                            ? 'Marcar como No Leído' 
                            : 'Marcar como Leído';
                        const card = btn.closest('.notice-card');
                        if (currentText === 'Marcar como Leído') {
                            card.classList.remove('unread');
                        } else {
                            card.classList.add('unread');
                        }
                    } else {
                        alert("Error al actualizar estado.");
                    }
                }
            };
            xhr.send("id=" + encodeURIComponent(noticeId));
        });
    });
    </script>
</body>
</html>
