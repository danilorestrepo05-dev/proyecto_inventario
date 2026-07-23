<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) { echo json_encode(['ok' => false, 'mensaje' => 'No autenticado']); exit(); }
if (!csrf_validate($_POST['csrf_token'] ?? '')) { echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF invalido']); exit(); }

$id_servicio = intval($_POST['id_servicio']);
$dispositivo = trim($_POST['dispositivo']);
$marca = trim($_POST['marca']);
$modelo = trim($_POST['modelo']);
$numero_serie = trim($_POST['numero_serie']);

if ($id_servicio <= 0 || empty($dispositivo)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Servicio y dispositivo son obligatorios']);
    exit();
}

$sql = "INSERT INTO dispositivo_servicio (ID_servicio, dispositivo, marca, modelo, numero_serie) VALUES (?, ?, ?, ?, ?)";
$stmt = @$conn->prepare($sql);
if (!$stmt) { echo json_encode(['ok' => false, 'mensaje' => 'Error de BD']); exit(); }
$stmt->bind_param("issss", $id_servicio, $dispositivo, $marca, $modelo, $numero_serie);

if ($stmt->execute()) {
    $id_dispositivo = $conn->insert_id;
    registrar_cambio($conn, 'servicio', 'editar', $id_servicio, 'Dispositivo "' . $dispositivo . '" agregado al servicio #' . $id_servicio);
    $stmt->close();
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Dispositivo agregado correctamente', 'id_dispositivo' => $id_dispositivo]);
} else {
    $error = $stmt->error;
    $stmt->close();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $error]);
}
