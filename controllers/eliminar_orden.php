<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/orden_compra.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/orden_compra.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);

// Obtener información de la orden antes de eliminar
$sql_info = "SELECT oc.estado, doc.ID_producto, doc.cantidad 
             FROM orden_compra oc
             INNER JOIN detalle_orden_compra doc ON oc.ID_orden_compra = doc.ID_orden_compra
             WHERE oc.ID_orden_compra = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $codigo);
$stmt_info->execute();
$resultado_info = $stmt_info->get_result();
$stmt_info->close();

// Iniciar transacción para garantizar integridad
$conn->begin_transaction();

try {
    // Si la orden estaba "Aprobada", revertir el stock
    while ($row = $resultado_info->fetch_assoc()) {
        if ($row['estado'] === 'Aprobado') {
            $sql_revertir = "UPDATE producto SET stock = stock - ? WHERE ID_producto = ?";
            $stmt_stock = $conn->prepare($sql_revertir);
            $stmt_stock->bind_param("ii", $row['cantidad'], $row['ID_producto']);
            if (!$stmt_stock->execute()) {
                $stmt_stock->close();
                throw new Exception("Error al revertir stock");
            }
            $stmt_stock->close();
        }
    }
    
    // Eliminar los productos asociados en detalle_orden_compra
    $consulta_detalle = "DELETE FROM detalle_orden_compra WHERE ID_orden_compra = ?";
    $stmt_det = $conn->prepare($consulta_detalle);
    $stmt_det->bind_param("i", $codigo);
    if (!$stmt_det->execute()) {
        $stmt_det->close();
        throw new Exception("Error al eliminar detalles");
    }
    $stmt_det->close();
    
    // Eliminar la orden en orden_compra
    $consulta_orden = "DELETE FROM orden_compra WHERE ID_orden_compra = ?";
    $stmt_ord = $conn->prepare($consulta_orden);
    $stmt_ord->bind_param("i", $codigo);
    if (!$stmt_ord->execute()) {
        $stmt_ord->close();
        throw new Exception("Error al eliminar orden");
    }
    $stmt_ord->close();
    
    // Si todo salió bien, confirmar cambios
    $conn->commit();
    mysqli_close($conn);
    header("Location: ../views/orden_compra.php?mensaje=Orden eliminada correctamente");
    exit();
    
} catch (Exception $e) {
    // Si algo falla, revertir todos los cambios
    $conn->rollback();
    mysqli_close($conn);
    echo "<script>
            alert('Error al eliminar la orden: {$e->getMessage()}');
            window.location.href = '../views/orden_compra.php';
          </script>";
    exit();
}
?>