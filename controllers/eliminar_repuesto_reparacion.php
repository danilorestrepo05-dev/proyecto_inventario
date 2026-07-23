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

// Obtener datos antes de eliminar para devolver stock
$sql = "SELECT ID_producto, cantidad FROM reparacion_repuesto WHERE ID_reparacion_repuesto = ?";
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
    // Devolver stock
    $sql_stock = "UPDATE producto SET stock = stock + ? WHERE ID_producto = ?";
    $stmt_stock = $conn->prepare($sql_stock);
    $stmt_stock->bind_param("ii", $fila['cantidad'], $fila['ID_producto']);
    if (!$stmt_stock->execute()) {
        throw new Exception("Error al devolver stock: " . $conn->error);
    }
    $stmt_stock->close();

    // Eliminar repuesto
    $sql_del = "DELETE FROM reparacion_repuesto WHERE ID_reparacion_repuesto = ?";
    $stmt_del = $conn->prepare($sql_del);
    $stmt_del->bind_param("i", $id_reparacion_repuesto);
    if (!$stmt_del->execute()) {
        throw new Exception("Error al eliminar repuesto: " . $conn->error);
    }
    $stmt_del->close();

    $conn->commit();
    registrar_cambio($conn, 'servicio', 'editar', $id_trabajo, 'Repuesto eliminado del trabajo #' . $id_trabajo);
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Repuesto eliminado. Stock devuelto.']);
} catch (Exception $e) {
    $conn->rollback();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
