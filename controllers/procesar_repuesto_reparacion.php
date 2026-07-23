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

// Obtener ID_cliente del servicio a través del trabajo
$sql_cliente = "SELECT s.ID_cliente
                FROM trabajo t
                INNER JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
                INNER JOIN servicio s ON ds.ID_servicio = s.ID_servicio
                WHERE t.ID_trabajo = ?";
$stmt_cliente = $conn->prepare($sql_cliente);
$stmt_cliente->bind_param("i", $id_trabajo);
$stmt_cliente->execute();
$cliente_row = $stmt_cliente->get_result()->fetch_assoc();
$stmt_cliente->close();

if (!$cliente_row || empty($cliente_row['ID_cliente'])) {
    mysqli_close($conn);
    echo "<script>alert('Error: No se encontro el cliente del servicio'); window.history.back();</script>";
    exit();
}

$id_cliente = $cliente_row['ID_cliente'];
$fecha_venta = date('Y-m-d');
$total_venta = $cantidad * $precio_unitario;

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
    // 1. Insertar repuesto
    $sql = "INSERT INTO reparacion_repuesto (ID_trabajo, ID_producto, cantidad, precio_unitario, garantia_proveedor_dias, factura_proveedor_adjunto) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiids", $id_trabajo, $id_producto, $cantidad, $precio_unitario, $garantia_proveedor_dias, $ruta_adjunto);

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar repuesto: " . $conn->error);
    }
    $id_reparacion_repuesto = $conn->insert_id;
    $stmt->close();

    // 2. Crear venta automática
    $sql_venta = "INSERT INTO orden_venta (ID_cliente, estado, total, fecha, origen) VALUES (?, 'completada', ?, ?, 'servicio')";
    $stmt_venta = $conn->prepare($sql_venta);
    $stmt_venta->bind_param("ids", $id_cliente, $total_venta, $fecha_venta);
    if (!$stmt_venta->execute()) {
        throw new Exception("Error al crear venta: " . $conn->error);
    }
    $id_orden_venta = $conn->insert_id;
    $stmt_venta->close();

    // 3. Insertar detalle de venta
    $sql_detalle = "INSERT INTO detalle_orden_venta (ID_orden_venta, ID_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt_detalle = $conn->prepare($sql_detalle);
    $stmt_detalle->bind_param("iiid", $id_orden_venta, $id_producto, $cantidad, $precio_unitario);
    if (!$stmt_detalle->execute()) {
        throw new Exception("Error al insertar detalle de venta: " . $conn->error);
    }
    $stmt_detalle->close();

    // 4. Vincular repuesto con venta
    $sql_link = "UPDATE reparacion_repuesto SET ID_orden_venta = ? WHERE ID_reparacion_repuesto = ?";
    $stmt_link = $conn->prepare($sql_link);
    $stmt_link->bind_param("ii", $id_orden_venta, $id_reparacion_repuesto);
    if (!$stmt_link->execute()) {
        throw new Exception("Error al vincular repuesto con venta: " . $conn->error);
    }
    $stmt_link->close();

    // 5. Reducir stock
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
