<?php
// Controlador AJAX que elimina un dispositivo y todos sus registros hijos (trabajos, repuestos, etc.)
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

// Solo permitir a usuarios autenticados con token CSRF válido
if (!isset($_POST['csrf_token'])) { echo json_encode(['ok' => false, 'mensaje' => 'No autenticado']); exit(); }
if (!csrf_validate($_POST['csrf_token'] ?? '')) { echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF invalido']); exit(); }

// Validar que el ID recibido sea un número positivo
$id_dispositivo = intval($_POST['id_dispositivo']);
if ($id_dispositivo <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID invalido']);
    exit();
}

// Obtener ID_servicio antes de eliminar
$sql = "SELECT ID_servicio, dispositivo FROM dispositivo_servicio WHERE ID_dispositivo=?";
$stmt = @$conn->prepare($sql);
$stmt->bind_param("i", $id_dispositivo);
$stmt->execute();
$result = $stmt->get_result();
$disp = $result->fetch_assoc();
$stmt->close();

// Si no se encontró el dispositivo, informar y salir antes de intentar eliminar
if (!$disp) {
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => 'Dispositivo no encontrado']);
    exit();
}

$id_servicio = $disp['ID_servicio'];

// Eliminar (cascade elimina trabajos, repuestos, etc)
$sql_del = "DELETE FROM dispositivo_servicio WHERE ID_dispositivo=?";
$stmt_del = @$conn->prepare($sql_del);
$stmt_del->bind_param("i", $id_dispositivo);

// Ejecutar DELETE y registrar la acción en historial de cambios
if ($stmt_del->execute()) {
    registrar_cambio($conn, 'servicio', 'eliminar', $id_servicio, 'Dispositivo "' . $disp['dispositivo'] . ' ' . trim(($disp['marca'] ?? '') . ' ' . ($disp['modelo'] ?? '')) . '" eliminado del servicio #' . $id_servicio);
    $stmt_del->close();
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Dispositivo eliminado correctamente']);
} else {
    $error = $stmt_del->error;
    $stmt_del->close();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $error]);
}
