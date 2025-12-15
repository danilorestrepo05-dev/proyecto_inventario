<?php
include("../config/conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    exit('No autorizado');
}

$id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener información de la venta
$sql_venta = "SELECT ov.*, c.nombre, c.apellido, c.telefono, c.correo 
              FROM orden_venta ov 
              LEFT JOIN cliente c ON ov.ID_cliente = c.ID_cliente 
              WHERE ov.ID_orden_venta = $id_venta";
$result_venta = mysqli_query($conn, $sql_venta);
$venta = mysqli_fetch_assoc($result_venta);

if (!$venta) {
    echo '<div class="alert alert-danger">Venta no encontrada</div>';
    exit;
}

// Obtener productos de la venta
$sql_detalle = "SELECT dov.*, p.nombre as nombre_producto, p.descripcion 
                FROM detalle_orden_venta dov 
                INNER JOIN producto p ON dov.ID_producto = p.ID_producto 
                WHERE dov.ID_orden_venta = $id_venta";
$result_detalle = mysqli_query($conn, $sql_detalle);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h6 class="text-muted">Información de la Venta</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td class="fw-bold">ID Orden:</td>
                <td><?php echo $venta['ID_orden_venta']; ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Fecha:</td>
                <td><?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Estado:</td>
                <td>
                    <?php 
                    $badge_clase = '';
                    switch($venta['estado']) {
                        case 'completada':
                            $badge_clase = 'bg-success';
                            break;
                        case 'pendiente':
                            $badge_clase = 'bg-warning';
                            break;
                        case 'cancelada':
                            $badge_clase = 'bg-danger';
                            break;
                        default:
                            $badge_clase = 'bg-secondary';
                    }
                    ?>
                    <span class="badge <?php echo $badge_clase; ?>">
                        <?php echo ucfirst($venta['estado']); ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="text-muted">Información del Cliente</h6>
        <table class="table table-sm table-borderless">
            <tr>
                <td class="fw-bold">Nombre:</td>
                <td><?php echo $venta['nombre'] . ' ' . $venta['apellido']; ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Teléfono:</td>
                <td><?php echo $venta['telefono']; ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Correo:</td>
                <td><?php echo $venta['correo']; ?></td>
            </tr>
        </table>
    </div>
</div>

<hr>

<h6 class="text-muted mb-3">Productos Vendidos</h6>
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
                $total += $detalle['subtotal'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                    <td><?php echo htmlspecialchars($detalle['descripcion']); ?></td>
                    <td class="text-center"><?php echo $detalle['cantidad']; ?></td>
                    <td class="text-end">$<?php echo number_format($detalle['precio_unitario'], 0, ',', '.'); ?></td>
                    <td class="text-end fw-bold">$<?php echo number_format($detalle['subtotal'], 0, ',', '.'); ?></td>
                </tr>
            <?php } ?>
        </tbody>
        <tfoot class="table-secondary">
            <tr>
                <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                <td class="text-end fw-bold fs-5">$<?php echo number_format($total, 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>
</div>