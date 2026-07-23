<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) { echo json_encode(['ok' => false, 'mensaje' => 'No autenticado']); exit(); }
if (!csrf_validate($_POST['csrf_token'] ?? '')) { echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF invalido']); exit(); }

$id_dispositivo = intval($_POST['id_dispositivo']);
$tipo_trabajo = trim($_POST['tipo_trabajo'] ?? 'General');
$problema_reportado = trim($_POST['problema_reportado']);
$notas_internas = trim($_POST['notas_internas'] ?? '');

if ($id_dispositivo <= 0 || empty($problema_reportado)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Dispositivo y problema reportado son obligatorios']);
    exit();
}

$sql = "INSERT INTO trabajo (ID_dispositivo, tipo_trabajo, problema_reportado, notas_internas) VALUES (?, ?, ?, ?)";
$stmt = @$conn->prepare($sql);
if (!$stmt) { echo json_encode(['ok' => false, 'mensaje' => 'Error de BD']); exit(); }
$stmt->bind_param("isss", $id_dispositivo, $tipo_trabajo, $problema_reportado, $notas_internas);

if ($stmt->execute()) {
    $id_trabajo = $conn->insert_id;
    $stmt->close();

    // Obtener ID_servicio para el historial
    $sql_serv = "SELECT ds.ID_servicio FROM dispositivo_servicio ds WHERE ds.ID_dispositivo=?";
    $stmt_serv = @$conn->prepare($sql_serv);
    $stmt_serv->bind_param("i", $id_dispositivo);
    $stmt_serv->execute();
    $res = $stmt_serv->get_result();
    $row = $res->fetch_assoc();
    $stmt_serv->close();

    registrar_cambio($conn, 'servicio', 'editar', $row['ID_servicio'], 'Trabajo "' . $tipo_trabajo . '" creado en dispositivo #' . $id_dispositivo . ' - Problema: ' . substr($problema_reportado, 0, 80));
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Trabajo agregado correctamente', 'id_trabajo' => $id_trabajo]);
} else {
    $error = $stmt->error;
    $stmt->close();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $error]);
}
