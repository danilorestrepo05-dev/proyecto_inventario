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
    header("Location: ../views/productos.php?error=Token CSRF inválido");
    exit();
}

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

$sql = "INSERT INTO producto (nombre, stock, precio, descripcion, fecha) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sidss", $nombre, $cantidad, $precio, $descripcion, $fecha);

if ($stmt->execute()) {
    $id_producto = $conn->insert_id;
    registrar_cambio($conn, 'producto', 'crear', $id_producto, 'Producto "' . $nombre . '" creado con stock ' . $cantidad);
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/productos.php?mensaje=Producto agregado correctamente");
    exit();
} else {
    echo "Error al registrar el producto: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>