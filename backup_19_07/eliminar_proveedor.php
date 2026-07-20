<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/proveedores.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/proveedores.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);

// Verificar si el proveedor tiene compras o productos asociados
$sql_verificar = "SELECT 
    (SELECT COUNT(*) FROM orden_compra WHERE ID_proveedor = ?) as compras,
    (SELECT COUNT(*) FROM producto WHERE ID_proveedor = ?) as productos";
$stmt = $conn->prepare($sql_verificar);
$stmt->bind_param("ii", $codigo, $codigo);
$stmt->execute();
$verificacion = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si tiene registros asociados, NO permitir eliminación
if ($verificacion['compras'] > 0 || $verificacion['productos'] > 0) {
    $mensaje = "No se puede eliminar este proveedor\\n\\n";
    $mensaje .= "Tiene registros asociados:\\n";
    if ($verificacion['compras'] > 0) {
        $mensaje .= "- " . $verificacion['compras'] . " compra(s)\\n";
    }
    if ($verificacion['productos'] > 0) {
        $mensaje .= "- " . $verificacion['productos'] . " producto(s)\\n";
    }
    $mensaje .= "\\nNo es posible eliminar proveedores con historial de transacciones para mantener la integridad de los datos.";

    mysqli_close($conn);
    echo "<script>
            alert('$mensaje');
            window.location.href = '../views/proveedores.php';
          </script>";
    exit();
}

// Si NO tiene registros asociados, permitir eliminación
$consulta = "DELETE FROM proveedor WHERE ID_proveedor = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/proveedores.php?mensaje=Proveedor eliminado correctamente");
    exit();
} else {
    echo "Error al eliminar el proveedor: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>