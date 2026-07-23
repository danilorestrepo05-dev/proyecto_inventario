<?php
// Controlador para editar un repuesto ya asignado a un trabajo, ajustando stock y venta vinculada
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF invalido']);
    exit();
}

$id_reparacion_repuesto = intval($_POST['id_reparacion_repuesto']);
$id_trabajo = intval($_POST['id_trabajo']);
$cantidad = intval($_POST['cantidad']);
$precio_unitario = floatval($_POST['precio_unitario']);
$garantia_proveedor_dias = intval($_POST['garantia_proveedor_dias']);

if ($cantidad <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'La cantidad debe ser mayor a 0']);
    exit();
}

// Recuperar los datos originales del repuesto para calcular diferencias de stock
$sql_actual = "SELECT rr.ID_producto, rr.cantidad AS cantidad_actual, rr.precio_unitario AS precio_actual, rr.ID_orden_venta, p.nombre AS producto_nombre
               FROM reparacion_repuesto rr
               INNER JOIN producto p ON rr.ID_producto = p.ID_producto
               WHERE rr.ID_reparacion_repuesto = ?";
$stmt = $conn->prepare($sql_actual);
$stmt->bind_param("i", $id_reparacion_repuesto);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$actual) {
    echo json_encode(['ok' => false, 'mensaje' => 'Repuesto no encontrado']);
    exit();
}

// Calcular cuántas unidades se suman o restan respecto a la cantidad actual
$diferencia = $cantidad - $actual['cantidad_actual'];

// Solo validar stock si se están usando más unidades que antes
if ($diferencia > 0) {
    $sql_stock = "SELECT stock, nombre FROM producto WHERE ID_producto = ?";
    $stmt_stock = $conn->prepare($sql_stock);
    $stmt_stock->bind_param("i", $actual['ID_producto']);
    $stmt_stock->execute();
    $info = $stmt_stock->get_result()->fetch_assoc();
    $stmt_stock->close();

    if ($info['stock'] < $diferencia) {
        echo json_encode(['ok' => false, 'mensaje' => "Stock insuficiente para {$info['nombre']}. Disponible: {$info['stock']}"]);
        exit();
    }
}

// Iniciar transacción para mantener consistencia entre repuesto, stock y venta
$conn->begin_transaction();

try {
    // Paso 1: Actualizar cantidad, precio y garantía del repuesto en el trabajo
    $sql = "UPDATE reparacion_repuesto SET cantidad=?, precio_unitario=?, garantia_proveedor_dias=? WHERE ID_reparacion_repuesto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $cantidad, $precio_unitario, $garantia_proveedor_dias, $id_reparacion_repuesto);
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar repuesto: " . $conn->error);
    }
    $stmt->close();

    if (isset($_FILES['factura_proveedor']) && $_FILES['factura_proveedor']['error'] === UPLOAD_ERR_OK) {
        $directorio = __DIR__ . '/../assets/uploads/garantias/';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $extension = pathinfo($_FILES['factura_proveedor']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'garantia_' . $id_trabajo . '_' . time() . '.' . $extension;
        $ruta_adjunto = 'assets/uploads/garantias/' . $nombre_archivo;
        move_uploaded_file($_FILES['factura_proveedor']['tmp_name'], $directorio . $nombre_archivo);

        $sql_adj = "UPDATE reparacion_repuesto SET factura_proveedor_adjunto=? WHERE ID_reparacion_repuesto=?";
        $stmt_adj = $conn->prepare($sql_adj);
        $stmt_adj->bind_param("si", $ruta_adjunto, $id_reparacion_repuesto);
        $stmt_adj->execute();
        $stmt_adj->close();
    }

    // Paso 2: Actualizar la venta automática asociada al repuesto
    if (!empty($actual['ID_orden_venta'])) {
        $nuevo_subtotal = $cantidad * $precio_unitario;
        $sql_det = "UPDATE detalle_orden_venta SET cantidad=?, precio_unitario=? WHERE ID_orden_venta=? AND ID_producto=?";
        $stmt_det = $conn->prepare($sql_det);
        $stmt_det->bind_param("iiii", $cantidad, $precio_unitario, $actual['ID_orden_venta'], $actual['ID_producto']);
        if (!$stmt_det->execute()) {
            throw new Exception("Error al actualizar detalle de venta: " . $conn->error);
        }
        $stmt_det->close();

        // Recalcular el total de la orden sumando todos sus detalles
        $sql_total = "UPDATE orden_venta ov SET total = (SELECT IFNULL(SUM(dov.cantidad * dov.precio_unitario), 0) FROM detalle_orden_venta dov WHERE dov.ID_orden_venta = ov.ID_orden_venta) WHERE ov.ID_orden_venta = ?";
        $stmt_total = $conn->prepare($sql_total);
        $stmt_total->bind_param("i", $actual['ID_orden_venta']);
        if (!$stmt_total->execute()) {
            throw new Exception("Error al actualizar total de venta: " . $conn->error);
        }
        $stmt_total->close();
    }

    // Paso 3: Ajustar el inventario según la diferencia (positiva o negativa)
    if ($diferencia != 0) {
        $sql_stock = "UPDATE producto SET stock = stock - ? WHERE ID_producto = ?";
        $stmt_stock = $conn->prepare($sql_stock);
        $stmt_stock->bind_param("ii", $diferencia, $actual['ID_producto']);
        if (!$stmt_stock->execute()) {
            throw new Exception("Error al ajustar stock: " . $conn->error);
        }
        $stmt_stock->close();
    }

    $conn->commit();
    registrar_cambio($conn, 'servicio', 'editar', $id_trabajo, 'Repuesto "' . $actual['producto_nombre'] . '" (x' . $cantidad . ') actualizado en trabajo #' . $id_trabajo);
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'mensaje' => 'Repuesto actualizado correctamente']);
} catch (Exception $e) {
    $conn->rollback();
    mysqli_close($conn);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
