<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/ventas.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/ventas.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);

// Obtener información de la venta antes de eliminar
$sql_info = "SELECT ov.estado, dov.ID_producto, dov.cantidad 
             FROM orden_venta ov
             INNER JOIN detalle_orden_venta dov ON ov.ID_orden_venta = dov.ID_orden_venta
             WHERE ov.ID_orden_venta = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $codigo);
$stmt_info->execute();
$resultado_info = $stmt_info->get_result();
$stmt_info->close();

// Iniciar transacción para garantizar integridad
$conn->begin_transaction();

try {
    // Si la venta estaba "completada", devolver el stock
    while ($row = $resultado_info->fetch_assoc()) {
        if ($row['estado'] === 'completada') {
            $sql_devolver = "UPDATE producto SET stock = stock + ? WHERE ID_producto = ?";
            $stmt_stock = $conn->prepare($sql_devolver);
            $stmt_stock->bind_param("ii", $row['cantidad'], $row['ID_producto']);
            if (!$stmt_stock->execute()) {
                $stmt_stock->close();
                throw new Exception("Error al devolver stock");
            }
            $stmt_stock->close();
        }
    }
    
    // Eliminar los productos asociados en detalle_orden_venta
    $consulta_detalle = "DELETE FROM detalle_orden_venta WHERE ID_orden_venta = ?";
    $stmt_det = $conn->prepare($consulta_detalle);
    $stmt_det->bind_param("i", $codigo);
    if (!$stmt_det->execute()) {
        $stmt_det->close();
        throw new Exception("Error al eliminar detalles");
    }
    $stmt_det->close();
    
    // Eliminar la venta en orden_venta
    $consulta_orden = "DELETE FROM orden_venta WHERE ID_orden_venta = ?";
    $stmt_ord = $conn->prepare($consulta_orden);
    $stmt_ord->bind_param("i", $codigo);
    if (!$stmt_ord->execute()) {
        $stmt_ord->close();
        throw new Exception("Error al eliminar venta");
    }
    $stmt_ord->close();
    
    // Si todo salió bien, confirmar cambios
    $conn->commit();
    registrar_cambio($conn, 'venta', 'editar', $codigo, 'Venta #'.$codigo.' eliminada');
    mysqli_close($conn);
    header("Location: ../views/ventas.php?mensaje=Venta eliminada correctamente");
    exit();
    
} catch (Exception $e) {
    // Si algo falla, revertir todos los cambios
    $conn->rollback();
    mysqli_close($conn);
    echo "<script>
            alert('Error al eliminar la venta: {$e->getMessage()}');
            window.location.href = '../views/ventas.php';
          </script>";
    exit();
}
?>