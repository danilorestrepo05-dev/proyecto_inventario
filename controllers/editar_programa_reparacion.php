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

// Sanitizar y validar los datos del programa editado desde el formulario
$id_programa = intval($_POST['id_programa']);
$id_trabajo = intval($_POST['id_trabajo']);
$nombre = trim($_POST['nombre']);
$version = trim($_POST['version']);
$licencia = trim($_POST['licencia']);
$cantidad = max(1, intval($_POST['cantidad'] ?? 1));
$costo = floatval($_POST['costo'] ?? 0);
$gar_dias = intval($_POST['gar_dias'] ?? 0);
$gar_fecha_inicio = trim($_POST['gar_fecha_inicio'] ?? '');

// Calcular fecha de fin de garantía sumando los días a la fecha de inicio
if ($gar_dias > 0) {
    if (empty($gar_fecha_inicio)) $gar_fecha_inicio = date('Y-m-d');
    $gar_fecha_fin = date('Y-m-d', strtotime($gar_fecha_inicio . " +{$gar_dias} days"));
} else {
    $gar_dias = null;
    $gar_fecha_inicio = null;
    $gar_fecha_fin = null;
}

// El nombre es obligatorio, abortar si está vacío
if (empty($nombre)) {
    echo json_encode(['ok' => false, 'mensaje' => 'El nombre del programa es obligatorio']);
    exit();
}

// Actualizar el registro del programa en la base de datos
$sql = "UPDATE programa_instalado SET nombre=?, version=?, licencia=?, cantidad=?, costo=?, gar_dias=?, gar_fecha_inicio=?, gar_fecha_fin=? WHERE ID_programa=?";
$stmt = @$conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error de BD']);
    exit();
}
$stmt->bind_param("sssiddssi", $nombre, $version, $licencia, $cantidad, $costo, $gar_dias, $gar_fecha_inicio, $gar_fecha_fin, $id_programa);

if ($stmt->execute()) {
    registrar_cambio($conn, 'servicio', 'editar', $id_trabajo, 'Programa "' . $nombre . '" actualizado en trabajo #' . $id_trabajo);
    $stmt->close();
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Programa actualizado correctamente']);
} else {
    $error = "Error al actualizar: " . $stmt->error;
    $stmt->close();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => $error]);
}
