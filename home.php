<?php
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
            --text-color: #ffffff;
        }

        .accessible-mode .logo {
            filter: invert(1);
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
            transition: filter var(--transition-speed);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: margin-left var(--transition-speed) ease, background-color var(--transition-speed) ease, color var(--transition-speed) ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
        }

        .accessible-mode .main-content {
            background-color: var(--primary-color);
            color: var(--text-color);
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

<script>
(function() {
    let timeout;
    const redirectUrl = 'index.php';
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

    <nav class="sidebar">
        <div class="brand">HIP ENERGY</div>
        <div class="nav-items">
            <a href="home.php" class="nav-item active">
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
        <h1>Hello, Gonzalo!</h1>
        <p>Welcome back to your account</p>
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
    </script>
</body>
</html>