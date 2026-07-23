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

$id_programa = intval($_POST['id_programa']);
$id_trabajo = intval($_POST['id_trabajo']);

// Obtener nombre antes de eliminar
$sql = "SELECT nombre FROM programa_instalado WHERE ID_programa = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_programa);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fila) {
    echo json_encode(['ok' => false, 'mensaje' => 'Programa no encontrado']);
    exit();
}

$sql_del = "DELETE FROM programa_instalado WHERE ID_programa = ?";
$stmt_del = $conn->prepare($sql_del);
$stmt_del->bind_param("i", $id_programa);

if ($stmt_del->execute()) {
    registrar_cambio($conn, 'servicio', 'editar', $id_trabajo, 'Programa "' . $fila['nombre'] . '" eliminado del trabajo #' . $id_trabajo);
    $stmt_del->close();
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Programa eliminado correctamente']);
} else {
    $error = "Error al eliminar: " . $conn->error;
    $stmt_del->close();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => $error]);
}
