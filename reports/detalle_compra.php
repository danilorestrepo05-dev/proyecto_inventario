<?php
include("../config/conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    exit('No autorizado');
}

$id_compra = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener información de la compra
$sql_compra = "SELECT oc.*, p.nombre_proveedor, p.telefono, p.correo, p.direccion 
               FROM orden_compra oc 
               LEFT JOIN proveedor p ON oc.ID_proveedor = p.ID_proveedor 
               WHERE oc.ID_orden_compra = $id_compra";
$result_compra = mysqli_query($conn, $sql_compra);
$compra = mysqli_fetch_assoc($result_compra);

if (!$compra) {
    echo '<div class="alert alert-danger">Compra no encontrada</div>';
    exit;
}

// Obtener productos de la compra
$sql_detalle = "SELECT doc.*, p.nombre as nombre_producto, p.descripcion 
                FROM detalle_orden_compra doc 
                INNER JOIN producto p ON doc.ID_producto = p.ID_producto 
                WHERE doc.ID_orden_compra = $id_compra";
$result_detalle = mysqli_query($conn, $sql_detalle);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h6 class="text-muted">Información de la Compra</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td class="fw-bold">ID Orden:</td>
                <td><?php echo $compra['ID_orden_compra']; ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Fecha:</td>
                <td><?php echo date('d/m/Y', strtotime($compra['fecha'])); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Estado:</td>
                <td>
                    <?php 
                    $badge_clase = '';
                    switch($compra['estado']) {
                        case 'Aprobado':
                            $badge_clase = 'bg-success';
                            break;
                        case 'Procesando':
                            $badge_clase = 'bg-warning';
                            break;
                        case 'cancelado':
                            $badge_clase = 'bg-danger';
                            break;
                        default:
                            $badge_clase = 'bg-secondary';
                    }
                    ?>
                    <span class="badge <?php echo $badge_clase; ?>">
                        <?php echo $compra['estado']; ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="text-muted">Información del Proveedor</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td class="fw-bold">Nombre:</td>
                <td><?php echo $compra['nombre_proveedor']; ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Teléfono:</td>
                <td><?php echo $compra['telefono']; ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Correo:</td>
                <td><?php echo $compra['correo']; ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Dirección:</td>
                <td><?php echo $compra['direccion']; ?></td>
            </tr>
        </table>
    </div>
</div>

<hr>

<h6 class="text-muted mb-3">Productos Comprados</h6>
<div class="table-responsive">
    <table class="table table-sm table-hover">
        <thead class="table-light">
            <tr>
                <th>Producto</th>
                <th>Descripción</th>
                <th class="text-center">Cantidad</th>
                <th class="text-end">Precio Unit.</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total = 0;
            while ($detalle = mysqli_fetch_assoc($result_detalle)) {
                // Usar el precio_unitario si existe, sino calcularlo del subtotal
                $precio_unitario = isset($detalle['precio_unitario']) && $detalle['precio_unitario'] > 0 
                    ? $detalle['precio_unitario'] 
                    : ($detalle['cantidad'] > 0 ? $detalle['subtotal'] / $detalle['cantidad'] : 0);
                
                $total += $detalle['subtotal'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                    <td><?php echo htmlspecialchars($detalle['descripcion']); ?></td>
                    <td class="text-center"><?php echo $detalle['cantidad']; ?></td>
                    <td class="text-end">$<?php echo number_format($precio_unitario, 0, ',', '.'); ?></td>
                    <td class="text-end fw-bold">$<?php echo number_format($detalle['subtotal'], 0, ',', '.'); ?></td>
                </tr>
            <?php } ?>
        </tbody>
        <tfoot class="table-secondary">
            <tr>
                <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                <td class="text-end fw-bold fs-5">$<?php echo number_format($compra['total'], 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>
</div>