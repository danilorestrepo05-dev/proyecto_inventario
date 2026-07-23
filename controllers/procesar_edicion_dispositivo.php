<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) { echo json_encode(['ok' => false, 'mensaje' => 'No autenticado']); exit(); }
if (!csrf_validate($_POST['csrf_token'] ?? '')) { echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF invalido']); exit(); }

$id_dispositivo = intval($_POST['id_dispositivo']);
$dispositivo = trim($_POST['dispositivo']);
$marca = trim($_POST['marca']);
$modelo = trim($_POST['modelo']);
$numero_serie = trim($_POST['numero_serie']);

if ($id_dispositivo <= 0 || empty($dispositivo)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos invalidos']);
    exit();
}

$sql = "UPDATE dispositivo_servicio SET dispositivo=?, marca=?, modelo=?, numero_serie=? WHERE ID_dispositivo=?";
$stmt = @$conn->prepare($sql);
if (!$stmt) { echo json_encode(['ok' => false, 'mensaje' => 'Error de BD']); exit(); }
$stmt->bind_param("ssssi", $dispositivo, $marca, $modelo, $numero_serie, $id_dispositivo);

if ($stmt->execute()) {
    registrar_cambio($conn, 'servicio', 'editar', $id_dispositivo, 'Dispositivo #' . $id_dispositivo . ' actualizado');
    $stmt->close();
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Dispositivo actualizado correctamente']);
} else {
    $error = $stmt->error;
    $stmt->close();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $error]);
}
