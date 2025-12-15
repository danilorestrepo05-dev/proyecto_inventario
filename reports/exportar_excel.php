<?php
require_once('conexion.php');
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$tipo = $_POST['tipo'];

// Configurar headers para descarga de Excel
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Informe_" . $tipo . "_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // BOM para UTF-8

// INFORME DE PRODUCTOS
if ($tipo == 'productos') {
    echo "<html xmlns:x='urn:schemas-microsoft-com:office:excel'>";
    echo "<head>";
    echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>";
    echo "<style>
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #4472C4; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; }
            td { padding: 5px; border: 1px solid #000; }
            .total { background-color: #E7E6E6; font-weight: bold; }
          </style>";
    echo "</head>";
    echo "<body>";
    echo "<h2>Informe de Productos</h2>";
    echo "<p>Fecha de generación: " . date('d/m/Y H:i') . "</p>";
    
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
    
    echo "<table border='1'>";
    echo "<tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Stock</th>
            <th>Precio</th>
            <th>Fecha Registro</th>
          </tr>";
    
    $total_productos = 0;
    $valor_inventario = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $total_productos++;
        $valor_inventario += $row['stock'] * $row['precio'];
        
        echo "<tr>";
        echo "<td>" . $row['ID_producto'] . "</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "<td>" . $row['descripcion'] . "</td>";
        echo "<td style='text-align:center'>" . $row['stock'] . "</td>";
        echo "<td style='text-align:right'>$" . number_format($row['precio'], 0, ',', '.') . "</td>";
        echo "<td style='text-align:center'>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
        echo "</tr>";
    }
    
    echo "<tr class='total'>
            <td colspan='3' style='text-align:right'>TOTALES:</td>
            <td style='text-align:center'>" . $total_productos . " productos</td>
            <td colspan='3' style='text-align:right'>Valor Inventario: $" . number_format($valor_inventario, 0, ',', '.') . "</td>
          </tr>";
    
    echo "</table>";
    echo "</body></html>";
}

// INFORME DE VENTAS
elseif ($tipo == 'ventas') {
    echo "<html xmlns:x='urn:schemas-microsoft-com:office:excel'>";
    echo "<head>";
    echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>";
    echo "<style>
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #70AD47; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; }
            td { padding: 5px; border: 1px solid #000; }
            .total { background-color: #E7E6E6; font-weight: bold; }
          </style>";
    echo "</head>";
    echo "<body>";
    echo "<h2>Informe de Ventas</h2>";
    echo "<p>Fecha de generación: " . date('d/m/Y H:i') . "</p>";
    
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
    
    if (!empty($fecha_inicio) || !empty($fecha_fin) || !empty($estado)) {
        echo "<p><strong>Filtros aplicados:</strong> ";
        if ($fecha_inicio) echo "Desde: " . date('d/m/Y', strtotime($fecha_inicio)) . " ";
        if ($fecha_fin) echo "Hasta: " . date('d/m/Y', strtotime($fecha_fin)) . " ";
        if ($estado) echo "Estado: " . $estado;
        echo "</p>";
    }
    
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
    
    echo "<table border='1'>";
    echo "<tr>
            <th>ID Orden</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Estado</th>
            <th>Total</th>
          </tr>";
    
    $total_ventas = 0;
    $total_monto = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $total_ventas++;
        $total_monto += $row['total'];
        
        echo "<tr>";
        echo "<td style='text-align:center'>" . $row['ID_orden_venta'] . "</td>";
        echo "<td style='text-align:center'>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
        echo "<td>" . $row['nombre'] . " " . $row['apellido'] . "</td>";
        echo "<td style='text-align:center'>" . $row['estado'] . "</td>";
        echo "<td style='text-align:right'>$" . number_format($row['total'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    
    echo "<tr class='total'>
            <td colspan='4' style='text-align:right'>TOTALES: " . $total_ventas . " ventas</td>
            <td style='text-align:right'>$" . number_format($total_monto, 0, ',', '.') . "</td>
          </tr>";
    
    echo "</table>";
    echo "</body></html>";
}

// INFORME DE COMPRAS
elseif ($tipo == 'compras') {
    echo "<html xmlns:x='urn:schemas-microsoft-com:office:excel'>";
    echo "<head>";
    echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>";
    echo "<style>
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #FFC000; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; }
            td { padding: 5px; border: 1px solid #000; }
            .total { background-color: #E7E6E6; font-weight: bold; }
          </style>";
    echo "</head>";
    echo "<body>";
    echo "<h2>Informe de Compras</h2>";
    echo "<p>Fecha de generación: " . date('d/m/Y H:i') . "</p>";
    
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
    
    if (!empty($fecha_inicio) || !empty($fecha_fin) || !empty($estado)) {
        echo "<p><strong>Filtros aplicados:</strong> ";
        if ($fecha_inicio) echo "Desde: " . date('d/m/Y', strtotime($fecha_inicio)) . " ";
        if ($fecha_fin) echo "Hasta: " . date('d/m/Y', strtotime($fecha_fin)) . " ";
        if ($estado) echo "Estado: " . $estado;
        echo "</p>";
    }
    
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
    
    echo "<table border='1'>";
    echo "<tr>
            <th>ID Orden</th>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Estado</th>
            <th>Total</th>
          </tr>";
    
    $total_compras = 0;
    $total_monto = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $total_compras++;
        $total_monto += $row['total'];
        
        echo "<tr>";
        echo "<td style='text-align:center'>" . $row['ID_orden_compra'] . "</td>";
        echo "<td style='text-align:center'>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
        echo "<td>" . $row['nombre_proveedor'] . "</td>";
        echo "<td style='text-align:center'>" . $row['estado'] . "</td>";
        echo "<td style='text-align:right'>$" . number_format($row['total'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    
    echo "<tr class='total'>
            <td colspan='4' style='text-align:right'>TOTALES: " . $total_compras . " compras</td>
            <td style='text-align:right'>$" . number_format($total_monto, 0, ',', '.') . "</td>
          </tr>";
    
    echo "</table>";
    echo "</body></html>";
}
?>