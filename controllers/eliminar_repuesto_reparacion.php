<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF invalido']);
    exit();
}

$id_reparacion_repuesto = intval($_POST['id_reparacion_repuesto']);
$id_trabajo = intval($_POST['id_trabajo']);

// Obtener datos antes de eliminar para devolver stock y eliminar venta
$sql = "SELECT rr.ID_producto, rr.cantidad, rr.ID_orden_venta, p.nombre AS producto_nombre
        FROM reparacion_repuesto rr
        INNER JOIN producto p ON rr.ID_producto = p.ID_producto
        WHERE rr.ID_reparacion_repuesto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_reparacion_repuesto);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fila) {
    echo json_encode(['ok' => false, 'mensaje' => 'Repuesto no encontrado']);
    exit();
}

$conn->begin_transaction();

try {
    // 1. Devolver stock
    $sql_stock = "UPDATE producto SET stock = stock + ? WHERE ID_producto = ?";
    $stmt_stock = $conn->prepare($sql_stock);
    $stmt_stock->bind_param("ii", $fila['cantidad'], $fila['ID_producto']);
    if (!$stmt_stock->execute()) {
        throw new Exception("Error al devolver stock: " . $conn->error);
    }
    $stmt_stock->close();

    // 2. Eliminar venta vinculada
    if (!empty($fila['ID_orden_venta'])) {
        $sql_del_det = "DELETE FROM detalle_orden_venta WHERE ID_orden_venta = ? AND ID_producto = ?";
        $stmt_del_det = $conn->prepare($sql_del_det);
        $stmt_del_det->bind_param("ii", $fila['ID_orden_venta'], $fila['ID_producto']);
        if (!$stmt_del_det->execute()) {
            throw new Exception("Error al eliminar detalle de venta: " . $conn->error);
        }
        $stmt_del_det->close();

        // Verificar si la orden quedó vacía
        $sql_count = "SELECT COUNT(*) as total FROM detalle_orden_venta WHERE ID_orden_venta = ?";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bind_param("i", $fila['ID_orden_venta']);
        $stmt_count->execute();
        $count_row = $stmt_count->get_result()->fetch_assoc();
        $stmt_count->close();

        if ($count_row['total'] == 0) {
            // Eliminar orden vacía
            $sql_del_orden = "DELETE FROM orden_venta WHERE ID_orden_venta = ?";
            $stmt_del_orden = $conn->prepare($sql_del_orden);
            $stmt_del_orden->bind_param("i", $fila['ID_orden_venta']);
            if (!$stmt_del_orden->execute()) {
                throw new Exception("Error al eliminar orden de venta: " . $conn->error);
            }
            $stmt_del_orden->close();
        } else {
            // Recalcular total
            $sql_total = "UPDATE orden_venta ov SET total = (SELECT IFNULL(SUM(dov.cantidad * dov.precio_unitario), 0) FROM detalle_orden_venta dov WHERE dov.ID_orden_venta = ov.ID_orden_venta) WHERE ov.ID_orden_venta = ?";
            $stmt_total = $conn->prepare($sql_total);
            $stmt_total->bind_param("i", $fila['ID_orden_venta']);
            if (!$stmt_total->execute()) {
                throw new Exception("Error al actualizar total de venta: " . $conn->error);
            }
            $stmt_total->close();
        }
    }

    // 3. Eliminar repuesto
    $sql_del = "DELETE FROM reparacion_repuesto WHERE ID_reparacion_repuesto = ?";
    $stmt_del = $conn->prepare($sql_del);
    $stmt_del->bind_param("i", $id_reparacion_repuesto);
    if (!$stmt_del->execute()) {
        throw new Exception("Error al eliminar repuesto: " . $conn->error);
    }
    $stmt_del->close();

    $conn->commit();
    registrar_cambio($conn, 'servicio', 'editar', $id_trabajo, 'Repuesto "' . $fila['producto_nombre'] . '" (x' . $fila['cantidad'] . ') eliminado del trabajo #' . $id_trabajo);
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Repuesto eliminado. Stock devuelto.']);
} catch (Exception $e) {
    $conn->rollback();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
