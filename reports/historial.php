<?php
include("../config/conexion.php");
session_start();
include('../config/csrf.php');
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
if ($rol !== 'Admin') {
    header("Location: ../menu.php");
    exit();
}

$filtro_modulo = isset($_GET['modulo']) ? $_GET['modulo'] : '';
$filtro_accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$filtro_usuario = isset($_GET['usuario']) ? intval($_GET['usuario']) : 0;
$filtro_fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$filtro_fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

$sql = "SELECT h.*, u.nombre, u.apellido 
        FROM historial_cambios h 
        LEFT JOIN usuario u ON h.ID_usuario = u.ID_usuario 
        WHERE 1=1";

if (!empty($filtro_modulo)) {
    $sql .= " AND h.modulo = '" . mysqli_real_escape_string($conn, $filtro_modulo) . "'";
}
if (!empty($filtro_accion)) {
    $sql .= " AND h.accion = '" . mysqli_real_escape_string($conn, $filtro_accion) . "'";
}
if ($filtro_usuario > 0) {
    $sql .= " AND h.ID_usuario = " . intval($filtro_usuario);
}
if (!empty($filtro_fecha_inicio)) {
    $sql .= " AND h.fecha >= '" . mysqli_real_escape_string($conn, $filtro_fecha_inicio) . "'";
}
if (!empty($filtro_fecha_fin)) {
    $sql .= " AND h.fecha <= '" . mysqli_real_escape_string($conn, $filtro_fecha_fin) . " 23:59:59'";
}

$sql .= " ORDER BY h.fecha DESC";
$result = mysqli_query($conn, $sql);

$por_pagina = 15;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$total_registros = mysqli_num_rows($result);
$total_paginas = max(1, ceil($total_registros / $por_pagina));
$inicio = ($pagina_actual - 1) * $por_pagina;

$sql_paginada = $sql . " LIMIT $inicio, $por_pagina";
$result = mysqli_query($conn, $sql_paginada);

$usuarios_result = mysqli_query($conn, "SELECT ID_usuario, nombre, apellido FROM usuario WHERE activo = 1 ORDER BY nombre");

$params_paginacion = '';
if (!empty($filtro_modulo)) $params_paginacion .= '&modulo=' . urlencode($filtro_modulo);
if (!empty($filtro_accion)) $params_paginacion .= '&accion=' . urlencode($filtro_accion);
if ($filtro_usuario > 0) $params_paginacion .= '&usuario=' . $filtro_usuario;
if (!empty($filtro_fecha_inicio)) $params_paginacion .= '&fecha_inicio=' . urlencode($filtro_fecha_inicio);
if (!empty($filtro_fecha_fin)) $params_paginacion .= '&fecha_fin=' . urlencode($filtro_fecha_fin);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Cambios</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="custom-body">
    <?php $nav_base = '..'; include('../views/includes/navbar.php'); ?>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clock-history"></i> Historial de Cambios</h2>
            <a href="informes.php" class="btn btn-secondary rounded-pill">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Filtros de Búsqueda</h5>
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Módulo</label>
                        <select class="form-select" name="modulo">
                            <option value="">Todos</option>
                            <option value="producto" <?php echo $filtro_modulo == 'producto' ? 'selected' : ''; ?>>Productos</option>
                            <option value="cliente" <?php echo $filtro_modulo == 'cliente' ? 'selected' : ''; ?>>Clientes</option>
                            <option value="proveedor" <?php echo $filtro_modulo == 'proveedor' ? 'selected' : ''; ?>>Proveedores</option>
                            <option value="venta" <?php echo $filtro_modulo == 'venta' ? 'selected' : ''; ?>>Ventas</option>
                            <option value="orden_compra" <?php echo $filtro_modulo == 'orden_compra' ? 'selected' : ''; ?>>Órdenes compra</option>
                            <option value="usuario" <?php echo $filtro_modulo == 'usuario' ? 'selected' : ''; ?>>Usuarios</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Acción</label>
                        <select class="form-select" name="accion">
                            <option value="">Todas</option>
                            <option value="crear" <?php echo $filtro_accion == 'crear' ? 'selected' : ''; ?>>Crear</option>
                            <option value="editar" <?php echo $filtro_accion == 'editar' ? 'selected' : ''; ?>>Editar</option>
                            <option value="activar" <?php echo $filtro_accion == 'activar' ? 'selected' : ''; ?>>Activar</option>
                            <option value="desactivar" <?php echo $filtro_accion == 'desactivar' ? 'selected' : ''; ?>>Desactivar</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Usuario</label>
                        <select class="form-select" name="usuario">
                            <option value="">Todos</option>
                            <?php while ($u = mysqli_fetch_assoc($usuarios_result)): ?>
                            <option value="<?php echo $u['ID_usuario']; ?>" <?php echo $filtro_usuario == $u['ID_usuario'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Desde</label>
                        <input type="date" class="form-control" name="fecha_inicio" value="<?php echo htmlspecialchars($filtro_fecha_inicio); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Hasta</label>
                        <input type="date" class="form-control" name="fecha_fin" value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Registro de Actividad</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Módulo</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $badge_modulo = '';
                                    switch($row['modulo']) {
                                        case 'producto': $badge_modulo = 'bg-primary'; break;
                                        case 'cliente': $badge_modulo = 'bg-success'; break;
                                        case 'proveedor': $badge_modulo = 'bg-warning text-dark'; break;
                                        case 'venta': $badge_modulo = 'bg-purple'; break;
                                        case 'orden_compra': $badge_modulo = 'bg-danger'; break;
                                        case 'usuario': $badge_modulo = 'bg-info text-dark'; break;
                                        default: $badge_modulo = 'bg-secondary';
                                    }

                                    $badge_accion = '';
                                    switch($row['accion']) {
                                        case 'crear': $badge_accion = 'bg-success'; break;
                                        case 'editar': $badge_accion = 'bg-warning text-dark'; break;
                                        case 'activar': $badge_accion = 'bg-info text-dark'; break;
                                        case 'desactivar': $badge_accion = 'bg-danger'; break;
                                        default: $badge_accion = 'bg-secondary';
                                    }

                                    $icono_modulo = '';
                                    switch($row['modulo']) {
                                        case 'producto': $icono_modulo = 'bi-box-seam'; break;
                                        case 'cliente': $icono_modulo = 'bi-people'; break;
                                        case 'proveedor': $icono_modulo = 'bi-building'; break;
                                        case 'venta': $icono_modulo = 'bi-cash-stack'; break;
                                        case 'orden_compra': $icono_modulo = 'bi-cart3'; break;
                                        case 'usuario': $icono_modulo = 'bi-person-gear'; break;
                                        default: $icono_modulo = 'bi-info-circle';
                                    }

                                    $modulo_nombre = str_replace('_', ' ', ucfirst($row['modulo']));

                                    echo "<tr>";
                                    echo "<td class='text-nowrap'><small>" . date('d/m/Y H:i', strtotime($row['fecha'])) . "</small></td>";
                                    echo "<td>" . htmlspecialchars($row['nombre'] . ' ' . $row['apellido']) . "</td>";
                                    echo "<td><span class='badge $badge_modulo'><i class='bi $icono_modulo me-1'></i>" . $modulo_nombre . "</span></td>";
                                    echo "<td><span class='badge $badge_accion'>" . ucfirst($row['accion']) . "</span></td>";
                                    echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>No se encontraron registros</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php if ($total_paginas > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Paginación">
                            <ul class="pagination mb-0">
                                <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo $params_paginacion; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo $params_paginacion; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo $params_paginacion; ?>">
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
