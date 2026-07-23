<?php
// Controlador para eliminar un trabajo de reparación junto con sus datos dependientes en cascada
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) { echo json_encode(['ok' => false, 'mensaje' => 'No autenticado']); exit(); }
if (!csrf_validate($_POST['csrf_token'] ?? '')) { echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF invalido']); exit(); }

// Validar que se reciba un ID de trabajo válido
$id_trabajo = intval($_POST['id_trabajo']);
if ($id_trabajo <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID invalido']);
    exit();
}

// Recuperar datos del trabajo antes de borrarlos para el historial
$sql = "SELECT t.ID_dispositivo, t.tipo_trabajo, ds.ID_servicio
        FROM trabajo t
        INNER JOIN dispositivo_servicio ds ON ds.ID_dispositivo = t.ID_dispositivo
        WHERE t.ID_trabajo=?";
$stmt = @$conn->prepare($sql);
$stmt->bind_param("i", $id_trabajo);
$stmt->execute();
$result = $stmt->get_result();
$trab = $result->fetch_assoc();
$stmt->close();

if (!$trab) {
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => 'Trabajo no encontrado']);
    exit();
}

// Eliminar el trabajo; el cascade en BD borra repuestos, bitácora y garantía asociados
$sql_del = "DELETE FROM trabajo WHERE ID_trabajo=?";
$stmt_del = @$conn->prepare($sql_del);
$stmt_del->bind_param("i", $id_trabajo);

if ($stmt_del->execute()) {
    registrar_cambio($conn, 'servicio', 'eliminar', $trab['ID_servicio'], 'Trabajo "' . $trab['tipo_trabajo'] . '" eliminado del dispositivo #' . $trab['ID_dispositivo'] . ' - Estado: ' . $trab['estado']);
    $stmt_del->close();
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Trabajo eliminado correctamente']);
} else {
    $error = $stmt_del->error;
    $stmt_del->close();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $error]);
}
