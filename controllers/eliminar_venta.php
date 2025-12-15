<?php
include("../config/conexion.php");

$codigo = $_REQUEST['id'];

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/ventas.php';</script>";
    exit();
}

// Obtener información de la venta antes de eliminar
$sql_info = "SELECT ov.estado, dov.ID_producto, dov.cantidad 
             FROM orden_venta ov
             INNER JOIN detalle_orden_venta dov ON ov.ID_orden_venta = dov.ID_orden_venta
             WHERE ov.ID_orden_venta = '$codigo'";
$resultado_info = $conn->query($sql_info);

// Iniciar transacción para garantizar integridad
$conn->begin_transaction();

try {
    // Si la venta estaba "completada", devolver el stock
    while ($row = $resultado_info->fetch_assoc()) {
        if ($row['estado'] === 'completada') {
            // Sumar al stock la cantidad que se había restado
            $sql_devolver = "UPDATE producto 
                            SET stock = stock + {$row['cantidad']} 
                            WHERE ID_producto = '{$row['ID_producto']}'";
            if (!$conn->query($sql_devolver)) {
                throw new Exception("Error al devolver stock");
            }
        }
    }
    
    // Eliminar los productos asociados en detalle_orden_venta
    $consulta_detalle = "DELETE FROM detalle_orden_venta WHERE ID_orden_venta = '$codigo'";
    if (!$conn->query($consulta_detalle)) {
        throw new Exception("Error al eliminar detalles");
    }
    
    // Eliminar la venta en orden_venta
    $consulta_orden = "DELETE FROM orden_venta WHERE ID_orden_venta = '$codigo'";
    if (!$conn->query($consulta_orden)) {
        throw new Exception("Error al eliminar venta");
    }
    
    // Si todo salió bien, confirmar cambios
    $conn->commit();
    mysqli_close($conn);
    header("Location: ../views/ventas.php?mensaje=Venta eliminada correctamente");
    exit();
    
} catch (Exception $e) {
    // Si algo falla, revertir todos los cambios
    $conn->rollback();
    mysqli_close($conn);
    echo "<script>
            alert('❌ Error al eliminar la venta: {$e->getMessage()}');
            window.location.href = '../views/ventas.php';
          </script>";
    exit();
}
?>