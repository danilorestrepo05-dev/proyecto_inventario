<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/productos.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/productos.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);

// Verificar si el producto tiene ventas o compras asociadas
$sql_verificar = "SELECT 
    (SELECT COUNT(*) FROM detalle_orden_venta WHERE ID_producto = ?) as ventas,
    (SELECT COUNT(*) FROM detalle_orden_compra WHERE ID_producto = ?) as compras";
$stmt = $conn->prepare($sql_verificar);
$stmt->bind_param("ii", $codigo, $codigo);
$stmt->execute();
$verificacion = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si tiene registros asociados, NO permitir eliminación
if ($verificacion['ventas'] > 0 || $verificacion['compras'] > 0) {
    $mensaje = "No se puede eliminar este producto\\n\\n";
    $mensaje .= "Tiene registros asociados:\\n";
    if ($verificacion['ventas'] > 0) {
        $mensaje .= "- " . $verificacion['ventas'] . " venta(s)\\n";
    }
    if ($verificacion['compras'] > 0) {
        $mensaje .= "- " . $verificacion['compras'] . " compra(s)\\n";
    }
    $mensaje .= "\\nNo es posible eliminar productos con historial de transacciones para mantener la integridad de los datos.";

    mysqli_close($conn);
    echo "<script>
            alert('$mensaje');
            window.location.href = '../views/productos.php';
          </script>";
    exit();
}

// Si NO tiene registros asociados, permitir eliminación
$consulta = "DELETE FROM producto WHERE ID_producto = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/productos.php?mensaje=Producto eliminado correctamente");
    exit();
} else {
    echo "Error al eliminar el producto: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>