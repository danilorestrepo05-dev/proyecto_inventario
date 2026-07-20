<?php
include("../config/conexion.php");
session_start();
include('../config/csrf.php');
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

// Filtros
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir consulta con filtros
$sql = "SELECT ov.*, c.nombre, c.apellido 
        FROM orden_venta ov 
        LEFT JOIN cliente c ON ov.ID_cliente = c.ID_cliente 
        WHERE 1=1";

if (!empty($fecha_inicio)) {
    $sql .= " AND ov.fecha >= '" . mysqli_real_escape_string($conn, $fecha_inicio) . "'";
}

if (!empty($fecha_fin)) {
    $sql .= " AND ov.fecha <= '" . mysqli_real_escape_string($conn, $fecha_fin) . "'";
}

if (!empty($estado)) {
    $sql .= " AND ov.estado = '" . mysqli_real_escape_string($conn, $estado) . "'";
}

$sql .= " ORDER BY ov.ID_orden_venta DESC";
$result = mysqli_query($conn, $sql);

// Paginación
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$total_registros = mysqli_num_rows($result);
$total_paginas = max(1, ceil($total_registros / $por_pagina));
$inicio = ($pagina_actual - 1) * $por_pagina;

// Re-consultar con LIMIT
$sql_paginada = $sql . " LIMIT $inicio, $por_pagina";
$result = mysqli_query($conn, $sql_paginada);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Ventas</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="custom-body">
    <?php $nav_base = '..'; include('../views/includes/navbar.php'); ?>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-cart-check"></i> Informe de Ventas</h2>
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
                        <input type="date" class="form-control" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="">Todos</option>
                            <option value="completada" <?php echo $estado == 'completada' ? 'selected' : ''; ?>>Completada</option>
                            <option value="pendiente" <?php echo $estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="cancelada" <?php echo $estado == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
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
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tipo" value="ventas">
                <input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                <input type="hidden" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                <input type="hidden" name="estado" value="<?php echo htmlspecialchars($estado); ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-file-pdf"></i> Exportar PDF
                </button>
            </form>
            <form method="POST" action="exportar_excel.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tipo" value="ventas">
                <input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                <input type="hidden" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                <input type="hidden" name="estado" value="<?php echo htmlspecialchars($estado); ?>">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-file-excel"></i> Exportar Excel
                </button>
            </form>
        </div>

        <!-- Tabla de ventas -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Listado de Ventas</h5>
                <div class="table-responsive">
                    <table class="table table-hover informe-table">
                        <thead class="table-success">
                            <tr>
                                <th>ID Orden</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_ventas = 0;
                            $total_monto = 0;
                            
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $total_ventas++;
                                    $total_monto += $row['total'];
                                    
                                    // Clase de badge según estado
                                    $badge_clase = '';
                                    switch($row['estado']) {
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
                                    
                                    echo "<tr>";
                                    echo "<td>" . $row['ID_orden_venta'] . "</td>";
                                    echo "<td>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nombre'] . ' ' . $row['apellido']) . "</td>";
                                    echo "<td><span class='badge $badge_clase'>" . ucfirst($row['estado']) . "</span></td>";
                                    echo "<td class='fw-bold'>$" . number_format($row['total'], 0, ',', '.') . "</td>";
                                    echo "<td><button class='btn btn-sm btn-info' onclick='verDetalle(" . $row['ID_orden_venta'] . ")'>
                                            <i class='bi bi-eye'></i> Detalle
                                          </button></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No se encontraron ventas</td></tr>";
                            }
                            ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Totales:</td>
                                <td class="fw-bold" colspan="2">
                                    <?php echo $total_ventas; ?> ventas | 
                                    $<?php echo number_format($total_monto, 0, ',', '.'); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php if ($total_paginas > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Paginación">
                            <ul class="pagination mb-0">
                                <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($fecha_inicio) ? '&fecha_inicio='.htmlspecialchars($fecha_inicio) : ''; ?><?php echo !empty($fecha_fin) ? '&fecha_fin='.htmlspecialchars($fecha_fin) : ''; ?><?php echo !empty($estado) ? '&estado='.htmlspecialchars($estado) : ''; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($fecha_inicio) ? '&fecha_inicio='.htmlspecialchars($fecha_inicio) : ''; ?><?php echo !empty($fecha_fin) ? '&fecha_fin='.htmlspecialchars($fecha_fin) : ''; ?><?php echo !empty($estado) ? '&estado='.htmlspecialchars($estado) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($fecha_inicio) ? '&fecha_inicio='.htmlspecialchars($fecha_inicio) : ''; ?><?php echo !empty($fecha_fin) ? '&fecha_fin='.htmlspecialchars($fecha_fin) : ''; ?><?php echo !empty($estado) ? '&estado='.htmlspecialchars($estado) : ''; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <p class="pagination-info">Mostrando <?php echo $inicio + 1; ?>-<?php echo min($inicio + $por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> registros</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalle de venta -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Venta</h5>
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

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalle(idVenta) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
            modal.show();
            
            fetch('detalle_venta.php?id=' + idVenta)
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