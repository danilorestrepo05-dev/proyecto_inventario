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
$filtro_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$filtro_stock = isset($_GET['stock']) ? $_GET['stock'] : '';

// Construir consulta con filtros
$sql = "SELECT p.*, pr.nombre_proveedor 
        FROM producto p 
        LEFT JOIN proveedor pr ON p.ID_proveedor = pr.ID_proveedor 
        WHERE 1=1";

if (!empty($filtro_nombre)) {
    $sql .= " AND p.nombre LIKE '%" . mysqli_real_escape_string($conn, $filtro_nombre) . "%'";
}

if ($filtro_stock == 'bajo') {
    $sql .= " AND p.stock < 10";
} elseif ($filtro_stock == 'medio') {
    $sql .= " AND p.stock BETWEEN 10 AND 50";
} elseif ($filtro_stock == 'alto') {
    $sql .= " AND p.stock > 50";
}

$sql .= " ORDER BY p.ID_producto DESC";
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
    <title>Informe de Productos</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="custom-body">
    <?php $nav_base = '..'; include('../views/includes/navbar.php'); ?>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-box-seam"></i> Informe de Productos</h2>
            <a href="informes.php" class="btn btn-secondary rounded-pill">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Filtros de Búsqueda</h5>
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre del Producto</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($filtro_nombre); ?>" placeholder="Buscar por nombre...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nivel de Stock</label>
                        <select class="form-select" name="stock">
                            <option value="">Todos</option>
                            <option value="bajo" <?php echo $filtro_stock == 'bajo' ? 'selected' : ''; ?>>Bajo (< 10)</option>
                            <option value="medio" <?php echo $filtro_stock == 'medio' ? 'selected' : ''; ?>>Medio (10-50)</option>
                            <option value="alto" <?php echo $filtro_stock == 'alto' ? 'selected' : ''; ?>>Alto (> 50)</option>
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
                <input type="hidden" name="tipo" value="productos">
                <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($filtro_nombre); ?>">
                <input type="hidden" name="stock" value="<?php echo htmlspecialchars($filtro_stock); ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-file-pdf"></i> Exportar PDF
                </button>
            </form>
            <form method="POST" action="exportar_excel.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tipo" value="productos">
                <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($filtro_nombre); ?>">
                <input type="hidden" name="stock" value="<?php echo htmlspecialchars($filtro_stock); ?>">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-file-excel"></i> Exportar Excel
                </button>
            </form>
        </div>

        <!-- Tabla de productos -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Listado de Productos</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                
                                <th>Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_productos = 0;
                            $valor_inventario = 0;
                            
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $total_productos++;
                                    $valor_inventario += $row['stock'] * $row['precio'];
                                    
                                    // Clase de alerta según stock
                                    $clase_stock = '';
                                    if ($row['stock'] < 10) {
                                        $clase_stock = 'text-danger fw-bold';
                                    } elseif ($row['stock'] < 50) {
                                        $clase_stock = 'text-warning fw-bold';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td>" . $row['ID_producto'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
                                    echo "<td class='$clase_stock'>" . $row['stock'] . "</td>";
                                    echo "<td>$" . number_format($row['precio'], 0, ',', '.') . "</td>";
                                    
                                    echo "<td>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>No se encontraron productos</td></tr>";
                            }
                            ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Totales:</td>
                                <td class="fw-bold"><?php echo $total_productos; ?> productos</td>
                                <td colspan="3" class="fw-bold">Valor inventario: $<?php echo number_format($valor_inventario, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php if ($total_paginas > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Paginación">
                            <ul class="pagination mb-0">
                                <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($filtro_nombre) ? '&nombre='.htmlspecialchars($filtro_nombre) : ''; ?><?php echo !empty($filtro_stock) ? '&stock='.htmlspecialchars($filtro_stock) : ''; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($filtro_nombre) ? '&nombre='.htmlspecialchars($filtro_nombre) : ''; ?><?php echo !empty($filtro_stock) ? '&stock='.htmlspecialchars($filtro_stock) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($filtro_nombre) ? '&nombre='.htmlspecialchars($filtro_nombre) : ''; ?><?php echo !empty($filtro_stock) ? '&stock='.htmlspecialchars($filtro_stock) : ''; ?>">
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

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>