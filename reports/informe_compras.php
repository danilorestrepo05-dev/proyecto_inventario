<?php
include("../config/conexion.php");
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

// Filtros
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir consulta con filtros
$sql = "SELECT oc.*, p.nombre_proveedor 
        FROM orden_compra oc 
        LEFT JOIN proveedor p ON oc.ID_proveedor = p.ID_proveedor 
        WHERE 1=1";

if (!empty($fecha_inicio)) {
    $sql .= " AND oc.fecha >= '" . mysqli_real_escape_string($conn, $fecha_inicio) . "'";
}

if (!empty($fecha_fin)) {
    $sql .= " AND oc.fecha <= '" . mysqli_real_escape_string($conn, $fecha_fin) . "'";
}

if (!empty($estado)) {
    $sql .= " AND oc.estado = '" . mysqli_real_escape_string($conn, $estado) . "'";
}

$sql .= " ORDER BY oc.fecha DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="custom-body">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-bag-check"></i> Informe de Compras</h2>
            <a href="informes.php" class="btn btn-secondary rounded-pill">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Filtros de Búsqueda</h5>
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="">Todos</option>
                            <option value="Aprobado" <?php echo $estado == 'Aprobado' ? 'selected' : ''; ?>>Aprobado</option>
                            <option value="Procesando" <?php echo $estado == 'Procesando' ? 'selected' : ''; ?>>Procesando</option>
                            <option value="cancelado" <?php echo $estado == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Botones de exportación -->
        <div class="mb-3 d-flex gap-2">
            <form method="POST" action="exportar_pdf.php" target="_blank">
                <input type="hidden" name="tipo" value="compras">
                <input type="hidden" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                <input type="hidden" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                <input type="hidden" name="estado" value="<?php echo $estado; ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-file-pdf"></i> Exportar PDF
                </button>
            </form>
            <form method="POST" action="exportar_excel.php">
                <input type="hidden" name="tipo" value="compras">
                <input type="hidden" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                <input type="hidden" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                <input type="hidden" name="estado" value="<?php echo $estado; ?>">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-file-excel"></i> Exportar Excel
                </button>
            </form>
        </div>

        <!-- Tabla de compras -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Listado de Compras</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-warning">
                            <tr>
                                <th>ID Orden</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_compras = 0;
                            $total_monto = 0;
                            
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $total_compras++;
                                    $total_monto += $row['total'];
                                    
                                    // Clase de badge según estado
                                    $badge_clase = '';
                                    switch($row['estado']) {
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
                                    
                                    echo "<tr>";
                                    echo "<td>" . $row['ID_orden_compra'] . "</td>";
                                    echo "<td>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nombre_proveedor']) . "</td>";
                                    echo "<td><span class='badge $badge_clase'>" . $row['estado'] . "</span></td>";
                                    echo "<td class='fw-bold'>$" . number_format($row['total'], 0, ',', '.') . "</td>";
                                    echo "<td><button class='btn btn-sm btn-info' onclick='verDetalle(" . $row['ID_orden_compra'] . ")'>
                                            <i class='bi bi-eye'></i> Detalle
                                          </button></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No se encontraron compras</td></tr>";
                            }
                            ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Totales:</td>
                                <td class="fw-bold" colspan="2">
                                    <?php echo $total_compras; ?> compras | 
                                    $<?php echo number_format($total_monto, 0, ',', '.'); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalle de compra -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoDetalle">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalle(idCompra) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
            modal.show();
            
            fetch('detalle_compra.php?id=' + idCompra)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('contenidoDetalle').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('contenidoDetalle').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar el detalle</div>';
                });
        }
    </script>
</body>
</html>