<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/orden_compra.php';</script>";
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/orden_compra.php?error=Token CSRF inválido");
    exit();
}

$id_orden = intval($_POST['id_orden']);
$proveedor = intval($_POST['proveedor']);
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

// Obtener información previa de la orden
$sql_anterior = "SELECT estado FROM orden_compra WHERE ID_orden_compra = ?";
$stmt_ant = $conn->prepare($sql_anterior);
$stmt_ant->bind_param("i", $id_orden);
$stmt_ant->execute();
$orden_anterior = $stmt_ant->get_result()->fetch_assoc();
$stmt_ant->close();
$estado_anterior = $orden_anterior['estado'];

// Obtener productos anteriores
$sql_detalles_ant = "SELECT ID_producto, cantidad FROM detalle_orden_compra WHERE ID_orden_compra = ?";
$stmt_det_ant = $conn->prepare($sql_detalles_ant);
$stmt_det_ant->bind_param("i", $id_orden);
$stmt_det_ant->execute();
$result_detalles_ant = $stmt_det_ant->get_result();
$stmt_det_ant->close();
$productos_anteriores = [];
while ($det = $result_detalles_ant->fetch_assoc()) {
    $productos_anteriores[$det['ID_producto']] = $det['cantidad'];
}

// Calcular nuevo total
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
    // 1. Revertir stock si el estado anterior era "Aprobado"
    if ($estado_anterior === 'Aprobado') {
        $sql_revertir = "UPDATE producto SET stock = stock - ? WHERE ID_producto = ?";
        $stmt_rev = $conn->prepare($sql_revertir);
        foreach ($productos_anteriores as $id_prod => $cant) {
            $stmt_rev->bind_param("ii", $cant, $id_prod);
            if (!$stmt_rev->execute()) {
                $stmt_rev->close();
                throw new Exception("Error al revertir stock: " . $conn->error);
            }
        }
        $stmt_rev->close();
    }
    
    // 2. Actualizar orden_compra
    $sql_orden = "UPDATE orden_compra SET ID_proveedor = ?, estado = ?, total = ?, fecha = ? WHERE ID_orden_compra = ?";
    $stmt_ord = $conn->prepare($sql_orden);
    $stmt_ord->bind_param("sisdi", $proveedor, $estado, $total_general, $fecha, $id_orden);
    
    if (!$stmt_ord->execute()) {
        $stmt_ord->close();
        throw new Exception("Error al actualizar orden: " . $conn->error);
    }
    $stmt_ord->close();
    
    // 3. Eliminar detalles anteriores
    $sql_delete = "DELETE FROM detalle_orden_compra WHERE ID_orden_compra = ?";
    $stmt_del = $conn->prepare($sql_delete);
    $stmt_del->bind_param("i", $id_orden);
    if (!$stmt_del->execute()) {
        $stmt_del->close();
        throw new Exception("Error al eliminar detalles: " . $conn->error);
    }
    $stmt_del->close();
    
    // 4. Insertar nuevos detalles
    $sql_insert = "INSERT INTO detalle_orden_compra (ID_orden_compra, ID_producto, cantidad, precio_unitario_compra, subtotal) VALUES (?, ?, ?, ?, ?)";
    $stmt_ins = $conn->prepare($sql_insert);
    
    $sql_stock_add = "UPDATE producto SET stock = stock + ? WHERE ID_producto = ?";
    $stmt_stock = $conn->prepare($sql_stock_add);
    
    foreach ($productos_validos as $prod) {
        $stmt_ins->bind_param("iiidd", $id_orden, $prod['id_producto'], $prod['cantidad'], $prod['precio'], $prod['subtotal']);
        if (!$stmt_ins->execute()) {
            $stmt_ins->close();
            $stmt_stock->close();
            throw new Exception("Error al insertar detalle: " . $conn->error);
        }
        
        // 5. Actualizar stock si el nuevo estado es "Aprobado"
        if ($estado === 'Aprobado') {
            $stmt_stock->bind_param("ii", $prod['cantidad'], $prod['id_producto']);
            if (!$stmt_stock->execute()) {
                $stmt_ins->close();
                $stmt_stock->close();
                throw new Exception("Error al actualizar stock: " . $conn->error);
            }
        }
    }
    $stmt_ins->close();
    $stmt_stock->close();
    
    // Commit de la transacción
    $conn->commit();
    mysqli_close($conn);
    
    $num_productos = count($productos_validos);
    $total_formateado = number_format($total_general, 0);
    header("Location: ../views/orden_compra.php?mensaje=Orden actualizada correctamente. Productos: $num_productos - Total: $$total_formateado");
    exit;
    
} catch (Exception $e) {
    // Rollback en caso de error
    $conn->rollback();
    mysqli_close($conn);
    
    $error_msg = urlencode($e->getMessage());
    header("Location: ../views/orden_compra.php?error=Error al actualizar la orden: $error_msg");
    exit;
}
?>