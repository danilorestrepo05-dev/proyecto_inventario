<?php
include("../config/conexion.php");

$codigo = $_REQUEST['id'];

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/orden_compra.php';</script>";
    exit();
}

// Obtener información de la orden antes de eliminar
$sql_info = "SELECT oc.estado, doc.ID_producto, doc.cantidad 
             FROM orden_compra oc
             INNER JOIN detalle_orden_compra doc ON oc.ID_orden_compra = doc.ID_orden_compra
             WHERE oc.ID_orden_compra = '$codigo'";
$resultado_info = $conn->query($sql_info);

// Iniciar transacción para garantizar integridad
$conn->begin_transaction();

try {
    // Si la orden estaba "Aprobada", revertir el stock
    while ($row = $resultado_info->fetch_assoc()) {
        if ($row['estado'] === 'Aprobado') {
            // Restar del stock la cantidad que se había sumado
            $sql_revertir = "UPDATE producto 
                            SET stock = stock - {$row['cantidad']} 
                            WHERE ID_producto = '{$row['ID_producto']}'";
            if (!$conn->query($sql_revertir)) {
                throw new Exception("Error al revertir stock");
            }
        }
    }
    
    // Eliminar los productos asociados en detalle_orden_compra
    $consulta_detalle = "DELETE FROM detalle_orden_compra WHERE ID_orden_compra = '$codigo'";
    if (!$conn->query($consulta_detalle)) {
        throw new Exception("Error al eliminar detalles");
    }
    
    // Eliminar la orden en orden_compra
    $consulta_orden = "DELETE FROM orden_compra WHERE ID_orden_compra = '$codigo'";
    if (!$conn->query($consulta_orden)) {
        throw new Exception("Error al eliminar orden");
    }
    
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
            alert('❌ Error al eliminar la orden: {$e->getMessage()}');
            window.location.href = '../views/orden_compra.php';
          </script>";
    exit();
}
?>