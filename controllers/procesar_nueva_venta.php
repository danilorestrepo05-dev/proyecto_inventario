<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/ventas.php?error=Token CSRF inválido");
    exit();
}

$cliente = intval($_POST['cliente']) ?: null;
$estado = $_POST['estado'];
$fecha = $_POST['fecha'];
$productos = $_POST['productos'];

// Validar que haya productos
if (empty($productos) || !is_array($productos)) {
    mysqli_close($conn);
    echo "<script>
            alert('Error: Debe agregar al menos un producto');
            window.history.back();
          </script>";
    exit();
}

// Calcular el total y validar stock
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
    
    // Verificar stock disponible (solo si estado es "completada")
    if ($estado === 'completada') {
        $sql_stock = "SELECT stock, nombre FROM producto WHERE ID_producto = ?";
        $stmt_stock = $conn->prepare($sql_stock);
        $stmt_stock->bind_param("i", $id_producto);
        $stmt_stock->execute();
        $result_stock = $stmt_stock->get_result();
        $producto_info = $result_stock->fetch_assoc();
        $stmt_stock->close();
        
        if ($producto_info['stock'] < $cantidad) {
            mysqli_close($conn);
            echo "<script>
                    alert('Stock insuficiente\\n\\nProducto: {$producto_info['nombre']}\\nStock disponible: {$producto_info['stock']}\\nCantidad solicitada: $cantidad');
                    window.history.back();
                  </script>";
            exit();
        }
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
    // 1. Insertar en orden_venta
    $sql1 = "INSERT INTO orden_venta (ID_cliente, estado, total, fecha) VALUES (?, ?, ?, ?)";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("isds", $cliente, $estado, $total_general, $fecha);
    
    if (!$stmt1->execute()) {
        $stmt1->close();
        throw new Exception("Error al insertar orden_venta: " . $conn->error);
    }
    $stmt1->close();
    
    $id_orden = $conn->insert_id;
    
    // 2. Insertar cada producto en detalle_orden_venta
    $sql2 = "INSERT INTO detalle_orden_venta (ID_orden_venta, ID_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sql2);
    
    $sql_stock_update = "UPDATE producto SET stock = stock - ? WHERE ID_producto = ?";
    $stmt_stock = $conn->prepare($sql_stock_update);
    
    foreach ($productos_validos as $prod) {
        $stmt2->bind_param("iiid", $id_orden, $prod['id'], $prod['cantidad'], $prod['precio']);
        
        if (!$stmt2->execute()) {
            $stmt2->close();
            $stmt_stock->close();
            throw new Exception("Error al insertar detalle: " . $conn->error);
        }
        
        // 3. Reducir stock si estado es "completada"
        if ($estado === 'completada') {
            $stmt_stock->bind_param("ii", $prod['cantidad'], $prod['id']);
            if (!$stmt_stock->execute()) {
                $stmt2->close();
                $stmt_stock->close();
                throw new Exception("Error al actualizar stock: " . $conn->error);
            }
        }
    }
    $stmt2->close();
    $stmt_stock->close();
    
    // Commit de la transacción
    $conn->commit();
    
    $num_productos = count($productos_validos);
    $total_formateado = number_format($total_general, 0);
    registrar_cambio($conn, 'venta', 'crear', $id_orden, 'Venta #'.$id_orden.' registrada con '.$num_productos.' productos - Total: $'.$total_formateado);
    mysqli_close($conn);
    
    header("Location: ../views/ventas.php?mensaje=Venta registrada correctamente. Productos: $num_productos - Total: $$total_formateado");
    exit;
    
} catch (Exception $e) {
    // Rollback en caso de error
    $conn->rollback();
    mysqli_close($conn);
    
    $error_msg = urlencode($e->getMessage());
    header("Location: ../views/ventas.php?error=Error al procesar la venta: $error_msg");
    exit;
}
?>