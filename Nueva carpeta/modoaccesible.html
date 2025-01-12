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

        .chat-messages {
            height: 400px; /* Aumentado de 300px */
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .chat-input {
            display: flex;
        }

        .chat-input input {
            flex-grow: 1;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
        }

        .chat-input button {
            padding: 0.5rem 1rem;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }

        .chat-input button:hover {
            background-color: var(--primary-dark);
        }

        .quick-actions {
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .quick-action-btn {
            flex: 1;
            padding: 0.5rem 1rem;
            background-color: #d4a017; /* Amarillo más oscuro */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .quick-action-btn:hover {
            background-color: #b8860b; /* Amarillo aún más oscuro para el hover */
        }

        .bot-message {
            background-color: #f0f0f0;
            margin-right: auto;
            border-bottom-left-radius: 4px;
            margin-left: 40px; /* Añadido para dar espacio al icono */
            position: relative; /* Added to position the bot icon */
        }

        .bot-icon {
            width: 30px;
            height: 30px;
            background-color: var(--accent-color);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 16px;
            position: absolute;
            left: -35px; /* Ajustado de -40px a -35px */
            top: 5px; /* Añadido para bajar un poco el icono */
        }

        .chat-history {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            flex: 1;
            max-width: 300px;
            height: 600px; /* Añadido para igualar la altura del chat principal */
            overflow-y: auto; /* Añadido para permitir scroll */
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
            <a href="notificaciones.html" class="nav-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <div class="notification-badge">4</div>
            </a>
            <a href="citas.html" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Appointments</span>
            </a>
            <a href="modoaccesible.html" class="nav-item active">
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
        <h1>Fault Reporting</h1>
        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <!-- Chat messages will be dynamically inserted here -->
            </div>
            <div class="quick-actions">
                <button class="quick-action-btn" data-action="power-outage">Report Power Outage</button>
                <button class="quick-action-btn" data-action="billing-issue">Billing Issue</button>
                <button class="quick-action-btn" data-action="inform-problem">Inform Problem</button>
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Type your message here...">
                <button id="sendButton">Send</button>
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

        // Chat functionality
        const chatMessages = document.getElementById('chatMessages');
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
        addMessage("Welcome to Fault Reporting. How can we assist you today?", false);


        // Añadir después de la declaración de chatHistory
        let fakeChatHistory = [
            { message: "Reported power outage on Main St.", isUser: true },
            { message: "Billing inquiry about last month's statement", isUser: true },
            { message: "Request for meter inspection", isUser: true },
            { message: "Follow-up on power restoration timeline", isUser: true },
            { message: "Complaint about frequent outages", isUser: true }
        ];

        // Modificar la función updateChatHistory()
        function updateChatHistory() {
            chatHistoryList.innerHTML = '';
            let combinedHistory = [...fakeChatHistory, ...chatHistory];
            combinedHistory.forEach((item, index) => {
                const listItem = document.createElement('li');
                listItem.className = 'chat-history-item';
                listItem.textContent = item.message.substring(0, 30) + (item.message.length > 30 ? '...' : '');
                listItem.addEventListener('click', () => loadChat(index));
                chatHistoryList.appendChild(listItem);
            });
        }

        // Llamar a updateChatHistory() al final del script para mostrar el historial falso inicial
        updateChatHistory();
    </script>
</body>
</html><!DOCTYPE html>
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

        .chat-messages {
            height: 400px; /* Aumentado de 300px */
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .chat-input {
            display: flex;
        }

        .chat-input input {
            flex-grow: 1;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
        }

        .chat-input button {
            padding: 0.5rem 1rem;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }

        .chat-input button:hover {
            background-color: var(--primary-dark);
        }

        .quick-actions {
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .quick-action-btn {
            flex: 1;
            padding: 0.5rem 1rem;
            background-color: #d4a017; /* Amarillo más oscuro */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .quick-action-btn:hover {
            background-color: #b8860b; /* Amarillo aún más oscuro para el hover */
        }

        .bot-message {
            background-color: #f0f0f0;
            margin-right: auto;
            border-bottom-left-radius: 4px;
            margin-left: 40px; /* Añadido para dar espacio al icono */
            position: relative; /* Added to position the bot icon */
        }

        .bot-icon {
            width: 30px;
            height: 30px;
            background-color: var(--accent-color);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 16px;
            position: absolute;
            left: -35px; /* Ajustado de -40px a -35px */
            top: 5px; /* Añadido para bajar un poco el icono */
        }

        .chat-history {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            flex: 1;
            max-width: 300px;
            height: 600px; /* Añadido para igualar la altura del chat principal */
            overflow-y: auto; /* Añadido para permitir scroll */
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
            <a href="notificaciones.html" class="nav-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <div class="notification-badge">4</div>
            </a>
            <a href="citas.html" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Appointments</span>
            </a>
            <a href="modoaccesible.html" class="nav-item active">
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
        <h1>Fault Reporting</h1>
        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <!-- Chat messages will be dynamically inserted here -->
            </div>
            <div class="quick-actions">
                <button class="quick-action-btn" data-action="power-outage">Report Power Outage</button>
                <button class="quick-action-btn" data-action="billing-issue">Billing Issue</button>
                <button class="quick-action-btn" data-action="inform-problem">Inform Problem</button>
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Type your message here...">
                <button id="sendButton">Send</button>
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

        // Chat functionality
        const chatMessages = document.getElementById('chatMessages');
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
        addMessage("Welcome to Fault Reporting. How can we assist you today?", false);


        // Añadir después de la declaración de chatHistory
        let fakeChatHistory = [
            { message: "Reported power outage on Main St.", isUser: true },
            { message: "Billing inquiry about last month's statement", isUser: true },
            { message: "Request for meter inspection", isUser: true },
            { message: "Follow-up on power restoration timeline", isUser: true },
            { message: "Complaint about frequent outages", isUser: true }
        ];

        // Modificar la función updateChatHistory()
        function updateChatHistory() {
            chatHistoryList.innerHTML = '';
            let combinedHistory = [...fakeChatHistory, ...chatHistory];
            combinedHistory.forEach((item, index) => {
                const listItem = document.createElement('li');
                listItem.className = 'chat-history-item';
                listItem.textContent = item.message.substring(0, 30) + (item.message.length > 30 ? '...' : '');
                listItem.addEventListener('click', () => loadChat(index));
                chatHistoryList.appendChild(listItem);
            });
        }

        // Llamar a updateChatHistory() al final del script para mostrar el historial falso inicial
        updateChatHistory();
    </script>
</body>
</html>