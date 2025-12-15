<?php
include("../config/conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$proveedor = $_POST['proveedor'];
$estado = $_POST['estado'];
$fecha = $_POST['fecha'];
$productos = $_POST['productos']; // Array de productos

// Validar que haya productos
if (empty($productos) || !is_array($productos)) {
    mysqli_close($conn);
    echo "<script>
            alert('❌ Error: Debe agregar al menos un producto');
            window.history.back();
          </script>";
    exit();
}

// Calcular el total de todos los productos
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
        'id' => $id_producto,
        'cantidad' => $cantidad,
        'precio' => $precio_unitario,
        'subtotal' => $subtotal
    ];
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Insertar en orden_compra
    $sql1 = "INSERT INTO orden_compra (ID_proveedor, estado, total, fecha)
             VALUES ('$proveedor', '$estado', '$total_general', '$fecha')";
    
    if (!$conn->query($sql1)) {
        throw new Exception("Error al insertar orden_compra: " . $conn->error);
    }
    
    $id_orden = $conn->insert_id;
    
    // 2. Insertar cada producto en detalle_orden_compra
    foreach ($productos_validos as $prod) {
        $sql2 = "INSERT INTO detalle_orden_compra 
                 (ID_orden_compra, ID_producto, cantidad, precio_unitario_compra, subtotal)
                 VALUES ('$id_orden', '{$prod['id']}', '{$prod['cantidad']}', 
                         '{$prod['precio']}', '{$prod['subtotal']}')";
        
        if (!$conn->query($sql2)) {
            throw new Exception("Error al insertar detalle: " . $conn->error);
        }
        
        // 3. Actualizar stock si estado es "Aprobado"
        if ($estado === 'Aprobado') {
            $sql_stock = "UPDATE producto 
                         SET stock = stock + {$prod['cantidad']} 
                         WHERE ID_producto = '{$prod['id']}'";
            
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
    header("Location: ../views/orden_compra.php?mensaje=Orden registrada correctamente. Productos: $num_productos - Total: $$total_formateado");
    exit;
    
} catch (Exception $e) {
    // Rollback en caso de error
    $conn->rollback();
    mysqli_close($conn);
    
    $error_msg = urlencode($e->getMessage());
    header("Location: ../views/orden_compra.php?error=Error al procesar la orden: $error_msg");
    exit;
}
?>