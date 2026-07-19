<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/clientes.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/clientes.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);

// Verificar si el cliente tiene ventas asociadas
$sql_verificar = "SELECT COUNT(*) as ventas FROM orden_venta WHERE ID_cliente = ?";
$stmt = $conn->prepare($sql_verificar);
$stmt->bind_param("i", $codigo);
$stmt->execute();
$verificacion = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si tiene ventas registradas, NO permitir eliminación
if ($verificacion['ventas'] > 0) {
    $mensaje = "No se puede eliminar este cliente\\n\\n";
    $mensaje .= "Tiene " . $verificacion['ventas'] . " venta(s) registrada(s)\\n\\n";
    $mensaje .= "No es posible eliminar clientes con historial de compras para mantener la integridad de los datos.";

    mysqli_close($conn);
    echo "<script>
            alert('$mensaje');
            window.location.href = '../views/clientes.php';
          </script>";
    exit();
}

// Si NO tiene ventas asociadas, permitir eliminación
$consulta = "DELETE FROM cliente WHERE ID_cliente = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/clientes.php?mensaje=Cliente eliminado correctamente");
    exit();
} else {
    echo "Error al eliminar el cliente: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>