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

$id_trabajo = intval($_POST['id_trabajo']);
$id_producto = intval($_POST['id_producto']);
$cantidad = intval($_POST['cantidad']);
$precio_unitario = floatval($_POST['precio_unitario']);
$garantia_proveedor_dias = intval($_POST['garantia_proveedor_dias']);

if ($id_producto <= 0 || $cantidad <= 0) {
    mysqli_close($conn);
    echo "<script>alert('Error: Seleccione un producto y cantidad valida'); window.history.back();</script>";
    exit();
}

// Verificar stock disponible
$sql_stock = "SELECT stock, nombre FROM producto WHERE ID_producto = ?";
$stmt_stock = $conn->prepare($sql_stock);
$stmt_stock->bind_param("i", $id_producto);
$stmt_stock->execute();
$result_stock = $stmt_stock->get_result();
$producto_info = $result_stock->fetch_assoc();
$stmt_stock->close();

if ($producto_info['stock'] < $cantidad) {
    mysqli_close($conn);
    echo "<script>alert('Error: Stock insuficiente para {$producto_info['nombre']}\\nDisponible: {$producto_info['stock']}\\nSolicitado: $cantidad'); window.history.back();</script>";
    exit();
}

// Manejar archivo adjunto
$ruta_adjunto = '';
if (isset($_FILES['factura_proveedor']) && $_FILES['factura_proveedor']['error'] === UPLOAD_ERR_OK) {
    $directorio = __DIR__ . '/../assets/uploads/garantias/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }
    $extension = pathinfo($_FILES['factura_proveedor']['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'garantia_' . $id_trabajo . '_' . time() . '.' . $extension;
    $ruta_adjunto = 'assets/uploads/garantias/' . $nombre_archivo;
    move_uploaded_file($_FILES['factura_proveedor']['tmp_name'], $directorio . $nombre_archivo);
}

$conn->begin_transaction();

try {
    $sql = "INSERT INTO reparacion_repuesto (ID_trabajo, ID_producto, cantidad, precio_unitario, garantia_proveedor_dias, factura_proveedor_adjunto) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiids", $id_trabajo, $id_producto, $cantidad, $precio_unitario, $garantia_proveedor_dias, $ruta_adjunto);

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar repuesto: " . $conn->error);
    }
    $stmt->close();

    // Reducir stock
    $sql_stock = "UPDATE producto SET stock = stock - ? WHERE ID_producto = ?";
    $stmt_stock = $conn->prepare($sql_stock);
    $stmt_stock->bind_param("ii", $cantidad, $id_producto);
    if (!$stmt_stock->execute()) {
        throw new Exception("Error al actualizar stock: " . $conn->error);
    }
    $stmt_stock->close();

    $conn->commit();

    registrar_cambio($conn, 'servicio', 'editar', $id_trabajo, 'Repuesto agregado al trabajo #' . $id_trabajo);
    mysqli_close($conn);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'mensaje' => 'Repuesto agregado correctamente']);
        exit();
    }
    header("Location: ../views/editar_trabajo.php?id=$id_trabajo&mensaje=Repuesto agregado correctamente#tab-repuestos");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    mysqli_close($conn);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        exit();
    }
    echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
    exit();
}
