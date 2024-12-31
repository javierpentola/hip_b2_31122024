<?php
// download_bill.php

require('fpdf/fpdf.php'); // Asegúrate de que la ruta sea correcta
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
    $stmt = $pdo->prepare("SELECT b.*, c.name as customer_name, c.email, c.phone, c.address 
                           FROM bills b 
                           JOIN customers c ON b.customer_id = c.id 
                           WHERE b.id = :id");
    $stmt->execute(['id' => $bill_id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$bill) {
        die("Factura no encontrada.");
    }
} catch (PDOException $e) {
    die("Error al obtener la factura: " . $e->getMessage());
}

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();

// Título
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'HIP ENERGY - Bill', 0, 1, 'C');

// Información del Cliente
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(40, 10, 'Customer Name: ' . $bill['customer_name']);
$pdf->Ln(5);
$pdf->Cell(40, 10, 'Email: ' . $bill['email']);
$pdf->Ln(5);
$pdf->Cell(40, 10, 'Phone: ' . $bill['phone']);
$pdf->Ln(5);
$pdf->MultiCell(0, 10, 'Address: ' . $bill['address']);
$pdf->Ln(10);

// Detalles de la Factura
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Bill ID: ' . $bill['id']);
$pdf->Ln(5);
$pdf->Cell(40, 10, 'Amount: ' . getCurrencySymbol($settings['currency']) . number_format($bill['amount'], 2));
$pdf->Ln(5);
$pdf->Cell(40, 10, 'Due Date: ' . date('M d, Y', strtotime($bill['due_date'])));
$pdf->Ln(5);
$pdf->Cell(40, 10, 'Status: ' . $bill['status']);
$pdf->Ln(10);

// (Opcional) Agregar más detalles o gráficos

// Salida del PDF
$pdf->Output('D', 'Bill_' . $bill['id'] . '.pdf');

// (Opcional) Registrar actividad
logActivity($pdo, $admin_id, "Descargó la factura ID: " . $bill['id']);
?>
