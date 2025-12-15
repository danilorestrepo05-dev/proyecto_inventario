<?php
include("../config/conexion.php");
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/orden_compra.php';</script>";
    exit();
}

$id_orden = $_POST['id_orden'];
$proveedor = $_POST['proveedor'];
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

// Obtener información previa de la orden
$sql_anterior = "SELECT estado FROM orden_compra WHERE ID_orden_compra = '$id_orden'";
$result_anterior = $conn->query($sql_anterior);
$orden_anterior = $result_anterior->fetch_assoc();
$estado_anterior = $orden_anterior['estado'];

// Obtener productos anteriores
$sql_detalles_ant = "SELECT ID_producto, cantidad FROM detalle_orden_compra WHERE ID_orden_compra = '$id_orden'";
$result_detalles_ant = $conn->query($sql_detalles_ant);
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
    // 1. Revertir stock si el estado anterior era "Aprobado"
    if ($estado_anterior === 'Aprobado') {
        foreach ($productos_anteriores as $id_prod => $cant) {
            $sql_revertir = "UPDATE producto SET stock = stock - $cant WHERE ID_producto = '$id_prod'";
            if (!$conn->query($sql_revertir)) {
                throw new Exception("Error al revertir stock: " . $conn->error);
            }
        }
    }
    
    // 2. Actualizar orden_compra
    $sql_orden = "UPDATE orden_compra 
                  SET ID_proveedor = '$proveedor', 
                      estado = '$estado', 
                      total = '$total_general', 
                      fecha = '$fecha'
                  WHERE ID_orden_compra = '$id_orden'";
    
    if (!$conn->query($sql_orden)) {
        throw new Exception("Error al actualizar orden: " . $conn->error);
    }
    
    // 3. Eliminar detalles anteriores
    $sql_delete = "DELETE FROM detalle_orden_compra WHERE ID_orden_compra = '$id_orden'";
    if (!$conn->query($sql_delete)) {
        throw new Exception("Error al eliminar detalles: " . $conn->error);
    }
    
    // 4. Insertar nuevos detalles
    foreach ($productos_validos as $prod) {
        $sql_insert = "INSERT INTO detalle_orden_compra 
                       (ID_orden_compra, ID_producto, cantidad, precio_unitario_compra, subtotal)
                       VALUES ('$id_orden', '{$prod['id_producto']}', '{$prod['cantidad']}', 
                               '{$prod['precio']}', '{$prod['subtotal']}')";
        
        if (!$conn->query($sql_insert)) {
            throw new Exception("Error al insertar detalle: " . $conn->error);
        }
        
        // 5. Actualizar stock si el nuevo estado es "Aprobado"
        if ($estado === 'Aprobado') {
            $sql_stock = "UPDATE producto 
                         SET stock = stock + {$prod['cantidad']} 
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