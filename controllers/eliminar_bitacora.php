<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['rol'] !== 'Admin') {
    header("Location: ../views/bitacora_comandos.php?error=No tiene permisos");
    exit();
}

if (!isset($_GET['id']) || !isset($_GET['csrf_token'])) {
    header("Location: ../views/bitacora_comandos.php?error=Parámetros inválidos");
    exit();
}

if (!csrf_validate($_GET['csrf_token'])) {
    header("Location: ../views/bitacora_comandos.php?error=Token CSRF inválido");
    exit();
}

$id = intval($_GET['id']);

$sql_select = "SELECT comando FROM bitacora_conocimiento WHERE ID_comando = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$resultado = $stmt_select->get_result();

if ($resultado->num_rows === 0) {
    $stmt_select->close();
    mysqli_close($conn);
    header("Location: ../views/bitacora_comandos.php?error=Comando no encontrado");
    exit();
}

$fila = $resultado->fetch_assoc();
$nombre_comando = $fila['comando'];
$stmt_select->close();

$sql_delete = "DELETE FROM bitacora_conocimiento WHERE ID_comando = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    registrar_cambio($conn, 'bitacora_conocimiento', 'eliminar', $id, 'Comando "' . $nombre_comando . '" eliminado');
    $stmt_delete->close();
    mysqli_close($conn);
    header("Location: ../views/bitacora_comandos.php?mensaje=" . urlencode("Comando \"$nombre_comando\" eliminado correctamente"));
    exit();
} else {
    $stmt_delete->close();
    mysqli_close($conn);
    header("Location: ../views/bitacora_comandos.php?error=Error al eliminar");
    exit();
}
?>
