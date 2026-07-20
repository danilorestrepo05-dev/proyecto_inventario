<?php
require_once(__DIR__ . '/../config/conexion.php');
session_start();
include(__DIR__ . '/../config/csrf.php');
if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../menu.php?error=Token CSRF inválido");
    exit();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$tipo_permitidos = ['productos', 'ventas', 'compras'];
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';

if (!in_array($tipo, $tipo_permitidos)) {
    header("Location: ../menu.php");
    exit();
}

$tipo_safe = basename($tipo);
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Informe_" . $tipo_safe . "_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";

if ($tipo == 'productos') {
    echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>";
    echo "<head><meta charset='UTF-8'>";
    echo "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Productos</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->";
    echo "<style>
            table { mso-displayed-decimal-separator: '\\,'; mso-displayed-thousand-separator: '\\.'; }
            td { mso-number-format: '\\@'; }
            .header { background-color: #4472C4; color: white; font-weight: bold; text-align: center; padding: 8px; border: 1px solid #000; }
            .data { padding: 5px; border: 1px solid #000; }
            .total { background-color: #E7E6E6; font-weight: bold; border: 1px solid #000; padding: 5px; }
          </style></head><body>";
    echo "<h2>Informe de Productos</h2>";
    echo "<p>Fecha de generaci&oacute;n: " . date('d/m/Y H:i') . "</p>";

    $filtro_nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
    $filtro_stock = isset($_POST['stock']) ? $_POST['stock'] : '';
    $filtro_estado = isset($_POST['estado']) ? $_POST['estado'] : '';

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

    if ($filtro_estado == 'activo') {
        $sql .= " AND p.activo = 1";
    } elseif ($filtro_estado == 'inactivo') {
        $sql .= " AND p.activo = 0";
    }

    $sql .= " ORDER BY p.ID_producto DESC";
    $result = mysqli_query($conn, $sql);

    if (!empty($filtro_nombre) || !empty($filtro_stock) || !empty($filtro_estado)) {
        echo "<p><strong>Filtros aplicados:</strong> ";
        if ($filtro_nombre) echo "Nombre: " . htmlspecialchars($filtro_nombre) . " ";
        if ($filtro_stock) echo "Stock: " . htmlspecialchars($filtro_stock) . " ";
        if ($filtro_estado) echo "Estado: " . ($filtro_estado == 'activo' ? 'Activos' : 'Inactivos');
        echo "</p>";
    }

    echo "<table border='1' cellpadding='0' cellspacing='0'>";
    echo "<tr>";
    echo "<td class='header'>ID</td>";
    echo "<td class='header'>Nombre</td>";
    echo "<td class='header'>Descripci&oacute;n</td>";
    echo "<td class='header'>Stock</td>";
    echo "<td class='header'>Precio</td>";
    echo "<td class='header'>Estado</td>";
    echo "<td class='header'>Proveedor</td>";
    echo "<td class='header'>Fecha Registro</td>";
    echo "</tr>";

    $total_productos = 0;
    $valor_inventario = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $total_productos++;
        $valor_inventario += $row['stock'] * $row['precio'];
        $estado_texto = $row['activo'] ? 'Activo' : 'Inactivo';

        echo "<tr>";
        echo "<td class='data' style='text-align:center'>" . intval($row['ID_producto']) . "</td>";
        echo "<td class='data'>" . htmlspecialchars($row['nombre']) . "</td>";
        echo "<td class='data'>" . htmlspecialchars($row['descripcion']) . "</td>";
        echo "<td class='data' style='text-align:center'>" . intval($row['stock']) . "</td>";
        echo "<td class='data' style='text-align:right'>$" . number_format($row['precio'], 0, ',', '.') . "</td>";
        echo "<td class='data' style='text-align:center'>" . $estado_texto . "</td>";
        echo "<td class='data'>" . htmlspecialchars($row['nombre_proveedor'] ?? '') . "</td>";
        echo "<td class='data' style='text-align:center'>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
        echo "</tr>";
    }

    echo "<tr>";
    echo "<td class='total' colspan='3' style='text-align:right'>TOTALES:</td>";
    echo "<td class='total' style='text-align:center'>" . $total_productos . " productos</td>";
    echo "<td class='total' colspan='4' style='text-align:right'>Valor Inventario: $" . number_format($valor_inventario, 0, ',', '.') . "</td>";
    echo "</tr>";
    echo "</table></body></html>";
}

elseif ($tipo == 'ventas') {
    echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>";
    echo "<head><meta charset='UTF-8'>";
    echo "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Ventas</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->";
    echo "<style>
            td { mso-number-format: '\\@'; }
            .header { background-color: #70AD47; color: white; font-weight: bold; text-align: center; padding: 8px; border: 1px solid #000; }
            .data { padding: 5px; border: 1px solid #000; }
            .total { background-color: #E7E6E6; font-weight: bold; border: 1px solid #000; padding: 5px; }
          </style></head><body>";
    echo "<h2>Informe de Ventas</h2>";
    echo "<p>Fecha de generaci&oacute;n: " . date('d/m/Y H:i') . "</p>";

    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';

    if (!empty($fecha_inicio) || !empty($fecha_fin) || !empty($estado)) {
        echo "<p><strong>Filtros aplicados:</strong> ";
        if ($fecha_inicio) echo "Desde: " . date('d/m/Y', strtotime($fecha_inicio)) . " ";
        if ($fecha_fin) echo "Hasta: " . date('d/m/Y', strtotime($fecha_fin)) . " ";
        if ($estado) echo "Estado: " . htmlspecialchars($estado);
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

    echo "<table border='1' cellpadding='0' cellspacing='0'>";
    echo "<tr>";
    echo "<td class='header'>ID Orden</td>";
    echo "<td class='header'>Fecha</td>";
    echo "<td class='header'>Cliente</td>";
    echo "<td class='header'>Estado</td>";
    echo "<td class='header'>Total</td>";
    echo "</tr>";

    $total_ventas = 0;
    $total_monto = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $total_ventas++;
        $total_monto += $row['total'];

        echo "<tr>";
        echo "<td class='data' style='text-align:center'>" . intval($row['ID_orden_venta']) . "</td>";
        echo "<td class='data' style='text-align:center'>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
        echo "<td class='data'>" . htmlspecialchars($row['nombre'] . ' ' . $row['apellido']) . "</td>";
        echo "<td class='data' style='text-align:center'>" . htmlspecialchars($row['estado']) . "</td>";
        echo "<td class='data' style='text-align:right'>$" . number_format($row['total'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }

    echo "<tr>";
    echo "<td class='total' colspan='4' style='text-align:right'>TOTALES: " . $total_ventas . " ventas</td>";
    echo "<td class='total' style='text-align:right'>$" . number_format($total_monto, 0, ',', '.') . "</td>";
    echo "</tr>";
    echo "</table></body></html>";
}

elseif ($tipo == 'compras') {
    echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>";
    echo "<head><meta charset='UTF-8'>";
    echo "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Compras</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->";
    echo "<style>
            td { mso-number-format: '\\@'; }
            .header { background-color: #FFC000; color: white; font-weight: bold; text-align: center; padding: 8px; border: 1px solid #000; }
            .data { padding: 5px; border: 1px solid #000; }
            .total { background-color: #E7E6E6; font-weight: bold; border: 1px solid #000; padding: 5px; }
          </style></head><body>";
    echo "<h2>Informe de Compras</h2>";
    echo "<p>Fecha de generaci&oacute;n: " . date('d/m/Y H:i') . "</p>";

    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';

    if (!empty($fecha_inicio) || !empty($fecha_fin) || !empty($estado)) {
        echo "<p><strong>Filtros aplicados:</strong> ";
        if ($fecha_inicio) echo "Desde: " . date('d/m/Y', strtotime($fecha_inicio)) . " ";
        if ($fecha_fin) echo "Hasta: " . date('d/m/Y', strtotime($fecha_fin)) . " ";
        if ($estado) echo "Estado: " . htmlspecialchars($estado);
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

    echo "<table border='1' cellpadding='0' cellspacing='0'>";
    echo "<tr>";
    echo "<td class='header'>ID Orden</td>";
    echo "<td class='header'>Fecha</td>";
    echo "<td class='header'>Proveedor</td>";
    echo "<td class='header'>Estado</td>";
    echo "<td class='header'>Total</td>";
    echo "</tr>";

    $total_compras = 0;
    $total_monto = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $total_compras++;
        $total_monto += $row['total'];

        echo "<tr>";
        echo "<td class='data' style='text-align:center'>" . intval($row['ID_orden_compra']) . "</td>";
        echo "<td class='data' style='text-align:center'>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
        echo "<td class='data'>" . htmlspecialchars($row['nombre_proveedor']) . "</td>";
        echo "<td class='data' style='text-align:center'>" . htmlspecialchars($row['estado']) . "</td>";
        echo "<td class='data' style='text-align:right'>$" . number_format($row['total'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }

    echo "<tr>";
    echo "<td class='total' colspan='4' style='text-align:right'>TOTALES: " . $total_compras . " compras</td>";
    echo "<td class='total' style='text-align:right'>$" . number_format($total_monto, 0, ',', '.') . "</td>";
    echo "</tr>";
    echo "</table></body></html>";
}
?>