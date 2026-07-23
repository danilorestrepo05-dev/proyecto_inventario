<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/clientes.php?error=Token CSRF inválido");
    exit();
}

$codigo = intval($_REQUEST['id']);
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$telefono = $_POST['telefono'];
$tipo_identificacion = $_POST['tipo_identificacion'] ?? 'ninguno';
$identificacion = trim($_POST['identificacion'] ?? '');

$tipos_permitidos = ['cc', 'nit', 'otro', 'ninguno'];
if (!in_array($tipo_identificacion, $tipos_permitidos)) {
    $tipo_identificacion = 'ninguno';
}

$consulta = "UPDATE cliente SET nombre = ?, apellido = ?, correo = ?, telefono = ?, tipo_identificacion = ?, identificacion = ? WHERE ID_cliente = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("ssssssi", $nombre, $apellido, $correo, $telefono, $tipo_identificacion, $identificacion, $codigo);

if ($stmt->execute()) {
    registrar_cambio($conn, 'cliente', 'editar', $codigo, 'Cliente "' . $nombre . ' ' . $apellido . '" editado');
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/clientes.php?mensaje=Cliente actualizado correctamente");
    exit();
} else {
    echo "Error al modificar los datos del cliente: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>