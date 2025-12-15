<?php
include("../config/conexion.php");
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/ventas.php';</script>";
    exit();
}

$id_orden = $_POST['id_orden'];
$cliente = $_POST['cliente'];
$estado = $_POST['estado'];
$fecha = $_POST['fecha'];
$productos = $_POST['productos'];

// Validar que haya productos
if (empty($productos) || !is_array($productos)) {
    mysqli_close($conn);
    echo "<script>
            alert('❌ Error: Debe tener al menos un producto');
            window.history.back();
          </script>";
    exit();
}

// Obtener información previa de la venta
$sql_anterior = "SELECT estado FROM orden_venta WHERE ID_orden_venta = '$id_orden'";
$result_anterior = $conn->query($sql_anterior);
$venta_anterior = $result_anterior->fetch_assoc();
$estado_anterior = $venta_anterior['estado'];

// Obtener productos anteriores
$sql_detalles_ant = "SELECT ID_producto, cantidad FROM detalle_orden_venta WHERE ID_orden_venta = '$id_orden'";
$result_detalles_ant = $conn->query($sql_detalles_ant);
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
    $id_detalle = !empty($prod['id_detalle']) ? intval($prod['id_detalle']) : 0;
    
    // Validaciones
    if ($cantidad <= 0) {
        mysqli_close($conn);
        echo "<script>
                alert('❌ Error: La cantidad debe ser mayor a 0');
                window.history.back();
              </script>";
        exit();
    }
    
    if ($precio_unitario < 0) {
        mysqli_close($conn);
        echo "<script>
                alert('❌ Error: El precio no puede ser negativo');
                window.history.back();
              </script>";
        exit();
    }
    
    $subtotal = $cantidad * $precio_unitario;
    $total_general += $subtotal;
    
    $productos_validos[] = [
        'id_detalle' => $id_detalle,
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
        foreach ($productos_anteriores as $id_prod => $cant) {
            $sql_devolver = "UPDATE producto SET stock = stock + $cant WHERE ID_producto = '$id_prod'";
            if (!$conn->query($sql_devolver)) {
                throw new Exception("Error al devolver stock: " . $conn->error);
            }
        }
    }
    
    // 2. Actualizar orden_venta
    $sql_venta = "UPDATE orden_venta 
                  SET ID_cliente = '$cliente', 
                      estado = '$estado', 
                      total = '$total_general', 
                      fecha = '$fecha'
                  WHERE ID_orden_venta = '$id_orden'";
    
    if (!$conn->query($sql_venta)) {
        throw new Exception("Error al actualizar venta: " . $conn->error);
    }
    
    // 3. Eliminar detalles anteriores
    $sql_delete = "DELETE FROM detalle_orden_venta WHERE ID_orden_venta = '$id_orden'";
    if (!$conn->query($sql_delete)) {
        throw new Exception("Error al eliminar detalles: " . $conn->error);
    }
    
    // 4. Insertar nuevos detalles y verificar stock si es necesario
    foreach ($productos_validos as $prod) {
        // Si el nuevo estado es "completada", verificar stock
        if ($estado === 'completada') {
            $sql_verificar = "SELECT stock, nombre FROM producto WHERE ID_producto = '{$prod['id_producto']}'";
            $result_stock = $conn->query($sql_verificar);
            $producto_stock = $result_stock->fetch_assoc();
            
            if ($producto_stock['stock'] < $prod['cantidad']) {
                throw new Exception("Stock insuficiente para {$producto_stock['nombre']}. Disponible: {$producto_stock['stock']}, Solicitado: {$prod['cantidad']}");
            }
        }
        
        $sql_insert = "INSERT INTO detalle_orden_venta 
                       (ID_orden_venta, ID_producto, cantidad, precio_unitario)
                       VALUES ('$id_orden', '{$prod['id_producto']}', '{$prod['cantidad']}', '{$prod['precio']}')";
        
        if (!$conn->query($sql_insert)) {
            throw new Exception("Error al insertar detalle: " . $conn->error);
        }
        
        // 5. Descontar stock si el nuevo estado es "completada"
        if ($estado === 'completada') {
            $sql_stock = "UPDATE producto 
                         SET stock = stock - {$prod['cantidad']} 
                         WHERE ID_producto = '{$prod['id_producto']}'";
            
            if (!$conn->query($sql_stock)) {
                throw new Exception("Error al actualizar stock: " . $conn->error);
            }
        }
    }
    
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