<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/productos.php?error=Token CSRF inválido");
    exit();
}

$codigo = intval($_REQUEST['id']);
$nombre = $_POST['nombre'];
$cantidad = intval($_POST['stock']);
$precio = floatval($_POST['precio']);
$descripcion = $_POST['descripcion'];
$fecha = $_POST['fecha'];

// VALIDACIÓN: Stock y precio no pueden ser negativos
if ($cantidad < 0) {
    mysqli_close($conn);
    echo "<script>
            alert('Error: El stock no puede ser negativo');
            window.history.back();
          </script>";
    exit();
}

if ($precio < 0) {
    mysqli_close($conn);
    echo "<script>
            alert('Error: El precio no puede ser negativo');
            window.history.back();
          </script>";
    exit();
}

$consulta = "UPDATE producto SET nombre = ?, stock = ?, precio = ?, descripcion = ?, fecha = ? WHERE ID_producto = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("sidssi", $nombre, $cantidad, $precio, $descripcion, $fecha, $codigo);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/productos.php?mensaje=Producto actualizado correctamente");
    exit();
} else {
    echo "Error al modificar los datos del producto: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>