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

        .notifications-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notification-card {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            display: flex;
            align-items: flex-start;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .notification-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--accent-color);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .notification-message {
            color: #666;
            font-size: 0.9rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.5rem;
        }

        .notification-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 0.5rem;
        }

        .notification-action {
            background: none;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 1rem;
            padding: 0;
        }

        .notification-action:hover {
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
    <nav class="sidebar">
        <div class="brand">HIP ENERGY</div>
        <div class="nav-items">
            <a href="home.html" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="consumo.html" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Consumption</span>
            </a>
            <a href="facturas.html" class="nav-item">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Bills</span>
            </a>
            <a href="notificaciones.html" class="nav-item active">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <div class="notification-badge">4</div>
            </a>
            <a href="citas.html" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Appointments</span>
            </a>
            <a href="modoaccesible.html" class="nav-item">
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
        <h1>Notifications Dashboard</h1>
        <div class="notifications-dashboard">
            <div class="notification-card">
                <div class="notification-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">High Energy Usage Alert</div>
                    <div class="notification-message">Your energy consumption is 20% higher than usual. Check your appliances to save energy.</div>
                    <div class="notification-time">2 hours ago</div>
                    <div class="notification-actions">
                        <button class="notification-action">View Details</button>
                        <button class="notification-action">Dismiss</button>
                    </div>
                </div>
            </div>
            <div class="notification-card">
                <div class="notification-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">New Bill Available</div>
                    <div class="notification-message">Your May 2023 energy bill is now available. Total amount: $145.20</div>
                    <div class="notification-time">1 day ago</div>
                    <div class="notification-actions">
                        <button class="notification-action">View Bill</button>
                        <button class="notification-action">Pay Now</button>
                    </div>
                </div>
            </div>
            <div class="notification-card">
                <div class="notification-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">Maintenance Appointment Reminder</div>
                    <div class="notification-message">Don't forget your scheduled maintenance appointment tomorrow at 2:00 PM.</div>
                    <div class="notification-time">2 days ago</div>
                    <div class="notification-actions">
                        <button class="notification-action">Confirm</button>
                        <button class="notification-action">Reschedule</button>
                    </div>
                </div>
            </div>
            <div class="notification-card">
                <div class="notification-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">Energy Saving Tip</div>
                    <div class="notification-message">Replace your old light bulbs with LED bulbs to save up to 75% on lighting costs.</div>
                    <div class="notification-time">3 days ago</div>
                    <div class="notification-actions">
                        <button class="notification-action">Learn More</button>
                        <button class="notification-action">Dismiss</button>
                    </div>
                </div>
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