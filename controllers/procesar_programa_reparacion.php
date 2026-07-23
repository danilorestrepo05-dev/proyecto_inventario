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
    header("Location: ../views/reparaciones.php?error=Token CSRF invalido");
    exit();
}

// Sanitizar y validar los datos del programa enviado desde el formulario
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
    mysqli_close($conn);
    echo "<script>alert('Error: El nombre del programa es obligatorio'); window.history.back();</script>";
    exit();
}

// Insertar el programa instalado en la base de datos con sentencia preparada
$sql = "INSERT INTO programa_instalado (ID_trabajo, nombre, version, licencia, cantidad, costo, gar_dias, gar_fecha_inicio, gar_fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = @$conn->prepare($sql);
if (!$stmt) {
    $msg = 'Error de BD al preparar consulta';
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'mensaje' => $msg]);
    } else {
        echo "<script>alert('" . addslashes($msg) . "'); window.history.back();</script>";
    }
    exit();
}
$stmt->bind_param("issssidss", $id_trabajo, $nombre, $version, $licencia, $cantidad, $costo, $gar_dias, $gar_fecha_inicio, $gar_fecha_fin);

if ($stmt->execute()) {
    registrar_cambio($conn, 'servicio', 'editar', $id_trabajo, 'Programa "' . $nombre . '" agregado al trabajo #' . $id_trabajo);
    $stmt->close();
    mysqli_close($conn);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'mensaje' => 'Programa registrado correctamente']);
        exit();
    }
    header("Location: ../views/editar_trabajo.php?id=$id_trabajo&mensaje=Programa registrado correctamente#tab-programas");
    exit();
} else {
    $error = "Error al registrar el programa: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'mensaje' => $error]);
        exit();
    }
    echo $error;
}
