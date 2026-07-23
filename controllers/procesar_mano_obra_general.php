<?php
// Controlador AJAX para guardar o actualizar el costo de mano de obra de un servicio
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

// Forzar respuesta JSON ya que se llama vía XMLHttpRequest
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sesion no valida']);
    exit();
}

// Solo aceptar peticiones AJAX por POST para evitar accesos directos
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Peticion invalida']);
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF invalido']);
    exit();
}

// Sanitizar y convertir los valores recibidos del formulario
$id_servicio = intval($_POST['id_servicio'] ?? 0);
$costo = floatval($_POST['costo'] ?? 0);

if ($id_servicio <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID de servicio invalido']);
    exit();
}

if ($costo < 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'El costo no puede ser negativo']);
    exit();
}

// Actualizar el costo de mano de obra del servicio con sentencia preparada
$sql = "UPDATE servicio SET mano_obra_costo = ? WHERE ID_servicio = ?";
$stmt = @$conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error de base de datos']);
    exit();
}
$stmt->bind_param("di", $costo, $id_servicio);
$stmt->execute();
$stmt->close();

mysqli_close($conn);

echo json_encode([
    'ok' => true,
    'mensaje' => 'Mano de obra del servicio: $' . number_format($costo, 0, ',', '.')
]);
