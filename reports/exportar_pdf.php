<?php
require_once('conexion.php');
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// Incluir librería FPDF 
require('fpdf/fpdf.php');

$tipo = $_POST['tipo'];

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode('SGI sistema de gestión Inventarios'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Fecha: ' . date(format: 'd/m/Y H:i'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// INFORME DE PRODUCTOS
if ($tipo == 'productos') {
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Informe de Productos', 0, 1, 'C');
    $pdf->Ln(5);
    
    $filtro_nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
    $filtro_stock = isset($_POST['stock']) ? $_POST['stock'] : '';
    
    $sql = "SELECT p.*, pr.nombre_proveedor 
            FROM producto p 
            LEFT JOIN proveedor pr ON p.ID_proveedor = pr.ID_proveedor 
            WHERE 1=1";
    
    if (!empty($filtro_nombre)) {
        $sql .= " AND p.nombre LIKE '%" . mysqli_real_escape_string($conn, $filtro_nombre) . "%'";
    }
    
    if ($filtro_stock == 'bajo') {
        $sql .= " AND p.stock < 10";
    } elseif ($filtro_stock == 'medio') {
        $sql .= " AND p.stock BETWEEN 10 AND 50";
    } elseif ($filtro_stock == 'alto') {
        $sql .= " AND p.stock > 50";
    }
    
    $sql .= " ORDER BY p.ID_producto DESC";
    $result = mysqli_query($conn, $sql);
    
    // Encabezados
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Nombre', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Stock', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Precio unitario', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Fecha', 1, 1, 'C', true);
    
    // Datos
    $pdf->SetFont('Arial', '', 10);
    $total_productos = 0;
    $valor_inventario = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $total_productos++;
        $valor_inventario += $row['stock'] * $row['precio'];
        
        $pdf->Cell(15, 6, $row['ID_producto'], 1, 0, 'C');
        $pdf->Cell(60, 6, utf8_decode(substr($row['nombre'], 0, 30)), 1, 0, 'L');
        $pdf->Cell(25, 6, $row['stock'], 1, 0, 'C');
        $pdf->Cell(35, 6, '$' . number_format($row['precio'], 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell(30, 6, date('d/m/Y', strtotime($row['fecha'])), 1, 1, 'C');
    }
    
    // Totales
    $pdf->Ln(3);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(80, 6, 'Total Productos: ' . $total_productos, 0, 0, 'L');
    $pdf->Cell(0, 6, 'Valor Inventario: $' . number_format($valor_inventario, 0, ',', '.'), 0, 1, 'R');
}

// INFORME DE VENTAS
elseif ($tipo == 'ventas') {
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Informe de Ventas', 0, 1, 'C');
    $pdf->Ln(5);
    
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
    
    $sql = "SELECT ov.*, c.nombre, c.apellido 
            FROM orden_venta ov 
            LEFT JOIN cliente c ON ov.ID_cliente = c.ID_cliente 
            WHERE 1=1";
    
    if (!empty($fecha_inicio)) {
        $sql .= " AND ov.fecha >= '" . mysqli_real_escape_string($conn, $fecha_inicio) . "'";
    }
    
    if (!empty($fecha_fin)) {
        $sql .= " AND ov.fecha <= '" . mysqli_real_escape_string($conn, $fecha_fin) . "'";
    }
    
    if (!empty($estado)) {
        $sql .= " AND ov.estado = '" . mysqli_real_escape_string($conn, $estado) . "'";
    }
    
    $sql .= " ORDER BY ov.fecha DESC";
    $result = mysqli_query($conn, $sql);
    
    // Filtros aplicados
    if (!empty($fecha_inicio) || !empty($fecha_fin) || !empty($estado)) {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 5, 'Filtros: ' . 
            ($fecha_inicio ? 'Desde ' . date('d/m/Y', strtotime($fecha_inicio)) . ' ' : '') .
            ($fecha_fin ? 'Hasta ' . date('d/m/Y', strtotime($fecha_fin)) . ' ' : '') .
            ($estado ? 'Estado: ' . $estado : ''), 0, 1);
        $pdf->Ln(3);
    }
    
    // Encabezados
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(200, 255, 200);
    $pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Cliente', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Estado', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Total', 1, 1, 'C', true);
    
    // Datos
    $pdf->SetFont('Arial', '', 8);
    $total_ventas = 0;
    $total_monto = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $total_ventas++;
        $total_monto += $row['total'];
        
        $pdf->Cell(15, 6, $row['ID_orden_venta'], 1, 0, 'C');
        $pdf->Cell(25, 6, date('d/m/Y', strtotime($row['fecha'])), 1, 0, 'C');
        $pdf->Cell(60, 6, utf8_decode($row['nombre'] . ' ' . $row['apellido']), 1, 0, 'L');
        $pdf->Cell(30, 6, utf8_decode($row['estado']), 1, 0, 'C');
        $pdf->Cell(35, 6, '$' . number_format($row['total'], 0, ',', '.'), 1, 1, 'R');
    }
    
    // Totales
    $pdf->Ln(3);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(100, 6, 'Total Ventas: ' . $total_ventas, 0, 0, 'L');
    $pdf->Cell(0, 6, 'Total Monto: $' . number_format($total_monto, 0, ',', '.'), 0, 1, 'R');
}

// INFORME DE COMPRAS
elseif ($tipo == 'compras') {
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Informe de Compras', 0, 1, 'C');
    $pdf->Ln(5);
    
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
    
    $sql = "SELECT oc.*, p.nombre_proveedor 
            FROM orden_compra oc 
            LEFT JOIN proveedor p ON oc.ID_proveedor = p.ID_proveedor 
            WHERE 1=1";
    
    if (!empty($fecha_inicio)) {
        $sql .= " AND oc.fecha >= '" . mysqli_real_escape_string($conn, $fecha_inicio) . "'";
    }
    
    if (!empty($fecha_fin)) {
        $sql .= " AND oc.fecha <= '" . mysqli_real_escape_string($conn, $fecha_fin) . "'";
    }
    
    if (!empty($estado)) {
        $sql .= " AND oc.estado = '" . mysqli_real_escape_string($conn, $estado) . "'";
    }
    
    $sql .= " ORDER BY oc.fecha DESC";
    $result = mysqli_query($conn, $sql);
    
    // Filtros aplicados
    if (!empty($fecha_inicio) || !empty($fecha_fin) || !empty($estado)) {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 5, 'Filtros: ' . 
            ($fecha_inicio ? 'Desde ' . date('d/m/Y', strtotime($fecha_inicio)) . ' ' : '') .
            ($fecha_fin ? 'Hasta ' . date('d/m/Y', strtotime($fecha_fin)) . ' ' : '') .
            ($estado ? 'Estado: ' . $estado : ''), 0, 1);
        $pdf->Ln(3);
    }
    
    // Encabezados
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(255, 235, 200);
    $pdf->Cell(15, 7, 'ID', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Proveedor', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Estado', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Total', 1, 1, 'C', true);
    
    // Datos
    $pdf->SetFont('Arial', '', 8);
    $total_compras = 0;
    $total_monto = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $total_compras++;
        $total_monto += $row['total'];
        
        $pdf->Cell(15, 6, $row['ID_orden_compra'], 1, 0, 'C');
        $pdf->Cell(25, 6, date('d/m/Y', strtotime($row['fecha'])), 1, 0, 'C');
        $pdf->Cell(60, 6, utf8_decode($row['nombre_proveedor']), 1, 0, 'L');
        $pdf->Cell(30, 6, utf8_decode($row['estado']), 1, 0, 'C');
        $pdf->Cell(35, 6, '$' . number_format($row['total'], 0, ',', '.'), 1, 1, 'R');
    }
    
    // Totales
    $pdf->Ln(3);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(100, 6, 'Total Compras: ' . $total_compras, 0, 0, 'L');
    $pdf->Cell(0, 6, 'Total Monto: $' . number_format($total_monto, 0, ',', '.'), 0, 1, 'R');
}

$pdf->Output('I', 'Informe_' . $tipo . '_' . date('Y-m-d') . '.pdf');
?>