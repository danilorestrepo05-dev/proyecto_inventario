<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/ventas.php';</script>";
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/ventas.php?error=Token CSRF inválido");
    exit();
}

$id_orden = intval($_POST['id_orden']);
$cliente = intval($_POST['cliente']);
$estado = $_POST['estado'];
$fecha = $_POST['fecha'];
$productos = $_POST['productos'];

// Validar que haya productos
if (empty($productos) || !is_array($productos)) {
    mysqli_close($conn);
    echo "<script>
            alert('Error: Debe tener al menos un producto');
            window.history.back();
          </script>";
    exit();
}

// Obtener información previa de la venta
$sql_anterior = "SELECT estado FROM orden_venta WHERE ID_orden_venta = ?";
$stmt_ant = $conn->prepare($sql_anterior);
$stmt_ant->bind_param("i", $id_orden);
$stmt_ant->execute();
$venta_anterior = $stmt_ant->get_result()->fetch_assoc();
$stmt_ant->close();
$estado_anterior = $venta_anterior['estado'];

// Obtener productos anteriores
$sql_detalles_ant = "SELECT ID_producto, cantidad FROM detalle_orden_venta WHERE ID_orden_venta = ?";
$stmt_det_ant = $conn->prepare($sql_detalles_ant);
$stmt_det_ant->bind_param("i", $id_orden);
$stmt_det_ant->execute();
$result_detalles_ant = $stmt_det_ant->get_result();
$stmt_det_ant->close();
$productos_anteriores = [];
while ($det = $result_detalles_ant->fetch_assoc()) {
    $productos_anteriores[$det['ID_producto']] = $det['cantidad'];
}

// Calcular nuevo total y validar stock
$total_general = 0;
$productos_validos = [];

foreach ($productos as $prod) {
    $id_producto = intval($prod['id']);
    $cantidad = intval($prod['cantidad']);
    $precio_unitario = floatval($prod['precio']);
    
    // Validaciones
    if ($cantidad <= 0) {
        mysqli_close($conn);
        echo "<script>
                alert('Error: La cantidad debe ser mayor a 0');
                window.history.back();
              </script>";
        exit();
    }
    
    if ($precio_unitario < 0) {
        mysqli_close($conn);
        echo "<script>
                alert('Error: El precio no puede ser negativo');
                window.history.back();
              </script>";
        exit();
    }
    
    $subtotal = $cantidad * $precio_unitario;
    $total_general += $subtotal;
    
    $productos_validos[] = [
        'id_producto' => $id_producto,
        'cantidad' => $cantidad,
        'precio' => $precio_unitario,
        'subtotal' => $subtotal
    ];
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Devolver stock si el estado anterior era "completada"
    if ($estado_anterior === 'completada') {
        $sql_devolver = "UPDATE producto SET stock = stock + ? WHERE ID_producto = ?";
        $stmt_dev = $conn->prepare($sql_devolver);
        foreach ($productos_anteriores as $id_prod => $cant) {
            $stmt_dev->bind_param("ii", $cant, $id_prod);
            if (!$stmt_dev->execute()) {
                $stmt_dev->close();
                throw new Exception("Error al devolver stock: " . $conn->error);
            }
        }
        $stmt_dev->close();
    }
    
    // 2. Actualizar orden_venta
    $sql_venta = "UPDATE orden_venta SET ID_cliente = ?, estado = ?, total = ?, fecha = ? WHERE ID_orden_venta = ?";
    $stmt_venta = $conn->prepare($sql_venta);
    $stmt_venta->bind_param("sisdi", $cliente, $estado, $total_general, $fecha, $id_orden);
    
    if (!$stmt_venta->execute()) {
        $stmt_venta->close();
        throw new Exception("Error al actualizar venta: " . $conn->error);
    }
    $stmt_venta->close();
    
    // 3. Eliminar detalles anteriores
    $sql_delete = "DELETE FROM detalle_orden_venta WHERE ID_orden_venta = ?";
    $stmt_del = $conn->prepare($sql_delete);
    $stmt_del->bind_param("i", $id_orden);
    if (!$stmt_del->execute()) {
        $stmt_del->close();
        throw new Exception("Error al eliminar detalles: " . $conn->error);
    }
    $stmt_del->close();
    
    // 4. Insertar nuevos detalles y verificar stock si es necesario
    $sql_insert = "INSERT INTO detalle_orden_venta (ID_orden_venta, ID_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt_ins = $conn->prepare($sql_insert);
    
    $sql_verificar_stock = "SELECT stock, nombre FROM producto WHERE ID_producto = ?";
    $stmt_verif = $conn->prepare($sql_verificar_stock);
    
    $sql_stock_desc = "UPDATE producto SET stock = stock - ? WHERE ID_producto = ?";
    $stmt_stock = $conn->prepare($sql_stock_desc);
    
    foreach ($productos_validos as $prod) {
        // Si el nuevo estado es "completada", verificar stock
        if ($estado === 'completada') {
            $stmt_verif->bind_param("i", $prod['id_producto']);
            $stmt_verif->execute();
            $producto_stock = $stmt_verif->get_result()->fetch_assoc();
            
            if ($producto_stock['stock'] < $prod['cantidad']) {
                $stmt_ins->close();
                $stmt_verif->close();
                $stmt_stock->close();
                throw new Exception("Stock insuficiente para {$producto_stock['nombre']}. Disponible: {$producto_stock['stock']}, Solicitado: {$prod['cantidad']}");
            }
        }
        
        $stmt_ins->bind_param("iiid", $id_orden, $prod['id_producto'], $prod['cantidad'], $prod['precio']);
        if (!$stmt_ins->execute()) {
            $stmt_ins->close();
            $stmt_verif->close();
            $stmt_stock->close();
            throw new Exception("Error al insertar detalle: " . $conn->error);
        }
        
        // 5. Descontar stock si el nuevo estado es "completada"
        if ($estado === 'completada') {
            $stmt_stock->bind_param("ii", $prod['cantidad'], $prod['id_producto']);
            if (!$stmt_stock->execute()) {
                $stmt_ins->close();
                $stmt_verif->close();
                $stmt_stock->close();
                throw new Exception("Error al actualizar stock: " . $conn->error);
            }
        }
    }
    $stmt_ins->close();
    $stmt_verif->close();
    $stmt_stock->close();
    
    // Commit de la transacción
    $conn->commit();
    mysqli_close($conn);
    
    $num_productos = count($productos_validos);
    $total_formateado = number_format($total_general, 0);
    header("Location: ../views/ventas.php?mensaje=Venta actualizada correctamente. Productos: $num_productos - Total: $total_formateado");
    exit;
    
} catch (Exception $e) {
    // Rollback en caso de error
    $conn->rollback();
    mysqli_close($conn);
    
    $error_msg = urlencode($e->getMessage());
    header("Location: ../views/ventas.php?error=Error al actualizar la venta: $error_msg");
    exit;
}
?>