<?php
include("../config/conexion.php");
session_start();
include("../config/csrf.php");
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

$mostrar_alerta = '';
if (isset($_GET['mensaje'])) {
    $mensaje = htmlspecialchars($_GET['mensaje']);
    $mostrar_alerta = "
        <div class='alert alert-success alert-dismissible fade show alert-flotante' role='alert'>
            <i class='bi bi-check-circle-fill'></i> $mensaje
        </div>
    ";
}

// Variables de filtro recibidas por query string
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$filtro_fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Consulta base: servicios activos con conteo de dispositivos y trabajos
$sql_base = "SELECT s.ID_servicio, s.nombre, s.fecha_creacion, s.activo,
                    c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                    c.telefono AS cliente_telefono, c.correo AS cliente_correo,
                    u.nombre AS tecnico_nombre, u.apellido AS tecnico_apellido,
                    (SELECT COUNT(*) FROM dispositivo_servicio ds WHERE ds.ID_servicio = s.ID_servicio) AS total_dispositivos,
                    (SELECT COUNT(*) FROM trabajo t INNER JOIN dispositivo_servicio ds2 ON t.ID_dispositivo = ds2.ID_dispositivo WHERE ds2.ID_servicio = s.ID_servicio) AS total_trabajos,
                    (SELECT COUNT(*) FROM trabajo t2 INNER JOIN dispositivo_servicio ds3 ON t2.ID_dispositivo = ds3.ID_dispositivo WHERE ds3.ID_servicio = s.ID_servicio AND t2.estado = 'entregado') AS trabajos_entregados
             FROM servicio s
             LEFT JOIN cliente c ON s.ID_cliente = c.ID_cliente
             LEFT JOIN usuario u ON s.ID_usuario_tecnico = u.ID_usuario
             WHERE s.activo = 1";

if (!empty($filtro_busqueda)) {
    $escaped = mysqli_real_escape_string($conn, $filtro_busqueda);
    $sql_base .= " AND (s.nombre LIKE '%$escaped%'
                    OR c.nombre LIKE '%$escaped%'
                    OR c.apellido LIKE '%$escaped%')";
}
if (!empty($filtro_fecha_inicio)) {
    $sql_base .= " AND s.fecha_creacion >= '" . mysqli_real_escape_string($conn, $filtro_fecha_inicio) . "'";
}
if (!empty($filtro_fecha_fin)) {
    $sql_base .= " AND s.fecha_creacion <= '" . mysqli_real_escape_string($conn, $filtro_fecha_fin) . " 23:59:59'";
}

if ($filtro_estado === 'completado') {
    $sql_base .= " AND EXISTS (SELECT 1 FROM trabajo t JOIN dispositivo_servicio ds4 ON t.ID_dispositivo = ds4.ID_dispositivo WHERE ds4.ID_servicio = s.ID_servicio)
                   AND NOT EXISTS (SELECT 1 FROM trabajo t JOIN dispositivo_servicio ds4 ON t.ID_dispositivo = ds4.ID_dispositivo WHERE ds4.ID_servicio = s.ID_servicio AND t.estado != 'entregado')";
} elseif ($filtro_estado === 'sin_trabajos') {
    $sql_base .= " AND NOT EXISTS (SELECT 1 FROM trabajo t JOIN dispositivo_servicio ds4 ON t.ID_dispositivo = ds4.ID_dispositivo WHERE ds4.ID_servicio = s.ID_servicio)";
} elseif (in_array($filtro_estado, ['ingresado','diagnosticado','en_progreso','reparado','entregado','cancelado'])) {
    $sql_base .= " AND EXISTS (SELECT 1 FROM trabajo t JOIN dispositivo_servicio ds4 ON t.ID_dispositivo = ds4.ID_dispositivo WHERE ds4.ID_servicio = s.ID_servicio AND t.estado = '$filtro_estado')";
}

$sql_base .= " ORDER BY s.ID_servicio DESC";
$resultado = $conn->query($sql_base);

// Paginación: 10 registros por página con cálculo de páginas
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$total_registros = $resultado->num_rows;
$total_paginas = max(1, ceil($total_registros / $por_pagina));
$inicio = ($pagina_actual - 1) * $por_pagina;

$consulta_paginada = $sql_base . " LIMIT $inicio, $por_pagina";
$resultado_paginado = $conn->query($consulta_paginada);

$params_filtro = '';
if (!empty($filtro_busqueda)) $params_filtro .= "&busqueda=" . urlencode($filtro_busqueda);
if (!empty($filtro_fecha_inicio)) $params_filtro .= "&fecha_inicio=" . urlencode($filtro_fecha_inicio);
if (!empty($filtro_fecha_fin)) $params_filtro .= "&fecha_fin=" . urlencode($filtro_fecha_fin);
if (!empty($filtro_estado)) $params_filtro .= "&estado=" . urlencode($filtro_estado);
?>

<!-- Auto-ocultar alertas de éxito después de 5 segundos -->
<script>
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.remove();
            if (window.history.replaceState) {
                const url = window.location.href.split('?')[0];
                window.history.replaceState({}, document.title, url);
            }
        }, 500);
    });
}, 5000);
</script>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Soporte T&eacute;cnico</title>
</head>
<body class="custom-body">

<?php $nav_base = '..'; include('includes/navbar.php'); ?>
<?php echo $mostrar_alerta; ?>

<div class="container my-4">
    <!-- Encabezado de la página con botón de nuevo servicio -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch mb-3">
        <h2 class="mb-3 mb-md-0"><i class="bi bi-tools me-2"></i>Soporte T&eacute;cnico</h2>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="agregar_servicio.php" class="btn btn-primary rounded-pill">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Servicio
            </a>
        </div>
    </div>

    <!-- Filtros de búsqueda: texto, estado, rango de fechas -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="busqueda" placeholder="Servicio, cliente..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="sin_trabajos" <?php echo $filtro_estado === 'sin_trabajos' ? 'selected' : ''; ?>>Sin trabajos</option>
                        <option value="ingresado" <?php echo $filtro_estado === 'ingresado' ? 'selected' : ''; ?>>Ingresado</option>
                        <option value="diagnosticado" <?php echo $filtro_estado === 'diagnosticado' ? 'selected' : ''; ?>>Diagnosticado</option>
                        <option value="en_progreso" <?php echo $filtro_estado === 'en_progreso' ? 'selected' : ''; ?>>En Progreso</option>
                        <option value="reparado" <?php echo $filtro_estado === 'reparado' ? 'selected' : ''; ?>>Reparado</option>
                        <option value="entregado" <?php echo $filtro_estado === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                        <option value="cancelado" <?php echo $filtro_estado === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        <option value="completado" <?php echo $filtro_estado === 'completado' ? 'selected' : ''; ?>>Completado</option>
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
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="reparaciones.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla principal de servicios con estados y acciones -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Servicio</th>
                    <th>Cliente</th>
                    <th>T&eacute;cnico</th>
                    <th>Dispositivos</th>
                    <th>Trabajos</th>
                    <th>Fecha</th>
                    <th class="th-opciones">Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($total_registros > 0) {
                    while ($fila = $resultado_paginado->fetch_assoc()) {
                        $trabajos_entregados = intval($fila['trabajos_entregados']);
                        $total_trabajos = intval($fila['total_trabajos']);
                        if ($total_trabajos > 0 && $trabajos_entregados == $total_trabajos) {
                            $badge_estado = '<span class="badge bg-success">Completado</span>';
                        } elseif ($total_trabajos > 0) {
                            $badge_estado = '<span class="badge bg-warning text-dark">En Proceso</span>';
                        } else {
                            $badge_estado = '<span class="badge bg-info">Sin trabajos</span>';
                        }

                        echo "<tr>";
                        echo "<td>{$fila['ID_servicio']}</td>";
                        echo "<td><strong>Servicio #" . $fila['ID_servicio'] . "</strong></td>";
                        echo "<td>" . htmlspecialchars($fila['cliente_nombre'] . ' ' . $fila['cliente_apellido']) . "</td>";
                        echo "<td>" . htmlspecialchars($fila['tecnico_nombre'] . ' ' . $fila['tecnico_apellido']) . "</td>";
                        echo "<td><span class='badge bg-secondary'>" . $fila['total_dispositivos'] . "</span></td>";
                        echo "<td>" . $badge_estado . " <small class='text-muted'>(" . $fila['total_trabajos'] . ")</small></td>";
                        echo "<td>" . date('d/m/Y', strtotime($fila['fecha_creacion'])) . "</td>";
                        echo "<td class='td-opciones'>";
                        echo "<a href='detalle_servicio.php?id={$fila['ID_servicio']}' class='btn btn-sm btn-warning' title='Ver Detalle'><i class='bi bi-eye'></i></a> ";
                        echo "<a href='../controllers/eliminar_servicio.php?id={$fila['ID_servicio']}&csrf_token=" . csrf_token() . "' class='btn btn-sm btn-outline-danger' title='Eliminar' onclick=\"return confirm('Seguro de eliminar este servicio y todos sus dispositivos/trabajos?')\"><i class='bi bi-trash'></i></a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>No se encontraron servicios</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Navegación de páginas con preservación de filtros -->
        <?php if ($total_paginas > 1): ?>
        <div class="pagination-container">
            <nav aria-label="Paginacion">
                <ul class="pagination mb-0">
                    <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo $params_filtro; ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo $params_filtro; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo $params_filtro; ?>">
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

<script src="../assets/js/bootstrap.bundle.min.js" defer></script>
<script src="../assets/js/script.js" defer></script>
</body>
</html>
<?php mysqli_close($conn); ?>
