<?php
include("../config/conexion.php");
session_start();
include("../config/csrf.php");
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$mostrar_alerta = '';
if (isset($_GET['mensaje'])) {
    $mensaje = htmlspecialchars($_GET['mensaje']);
    $mostrar_alerta = "
        <div class='alert alert-success alert-dismissible fade show alert-flotante' role='alert'>
            <i class='bi bi-check-circle-fill'></i> $mensaje
        </div>
    ";
}

$mostrar_error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
    $mostrar_error = "
        <div class='alert alert-danger alert-dismissible fade show alert-flotante' role='alert'>
            <i class='bi bi-exclamation-triangle-fill'></i> $error
        </div>
    ";
}

$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

$consulta = "
SELECT 
    ov.ID_orden_venta,
    p.nombre AS nombre_producto,
    dov.cantidad,
    dov.precio_unitario,
    (dov.cantidad * dov.precio_unitario) AS subtotal,
    ov.fecha,
    ov.estado
FROM orden_venta ov
JOIN detalle_orden_venta dov ON ov.ID_orden_venta = dov.ID_orden_venta
JOIN producto p ON dov.ID_producto = p.ID_producto
ORDER BY ov.ID_orden_venta DESC
";
$resultado = $conn->query($consulta);

// Paginación
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$total_registros = $resultado->num_rows;
$total_paginas = max(1, ceil($total_registros / $por_pagina));
$inicio = ($pagina_actual - 1) * $por_pagina;

// Re-consultar con LIMIT
$consulta_paginada = $consulta . " LIMIT $inicio, $por_pagina";
$resultado_paginado = $conn->query($consulta_paginada);
?>

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
    <title>Gestión de ventas</title>
</head>
<body class="custom-body">

<?php $nav_base = '..'; include('includes/navbar.php'); ?>
<?php echo $mostrar_alerta; ?>
<?php echo $mostrar_error; ?>

<div class="container my-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch mb-3">
    <h2 class="mb-3 mb-md-0"><i class="bi bi-cash-stack me-2"></i>Gestión de ventas</h2>
    <div class="d-flex flex-column flex-sm-row gap-2">
      <a href="agregar_venta.php" class="btn btn-primary rounded-pill">
        <i class="bi bi-plus-circle me-1"></i> Agregar nueva venta
      </a>
    </div>
  </div>

  <div class="mb-3">
    <input type="text" id="busqueda" placeholder="Buscar..." class="form-control rounded-pill" style="max-width: 300px;">
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-primary">
        <tr>
          <th>Código</th>
          <th>Producto</th>
          <th>Estado</th>
          <th>Cantidad</th>
          <th>Precio Unit.</th>
          <th>Subtotal</th>
          <th>Fecha</th>
          <?php if ($rol === 'Admin'): ?><th class="th-opciones">Opciones</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($total_registros > 0) {
          while ($fila = $resultado_paginado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$fila['ID_orden_venta']}</td>";
            echo "<td>{$fila['nombre_producto']}</td>";
            echo "<td>";
            switch($fila['estado']) {
                case 'completada': echo "<span class='badge bg-success'>Completada</span>"; break;
                case 'pendiente': echo "<span class='badge bg-warning text-dark'>Pendiente</span>"; break;
                case 'cancelada': echo "<span class='badge bg-danger'>Cancelada</span>"; break;
                default: echo "<span class='badge bg-secondary'>" . htmlspecialchars($fila['estado']) . "</span>";
            }
            echo "</td>";
            echo "<td>{$fila['cantidad']}</td>";
            echo "<td>$" . number_format($fila['precio_unitario'], 0, ',', '.') . "</td>";
            echo "<td>$" . number_format($fila['subtotal'], 0, ',', '.') . "</td>";
            echo "<td>{$fila['fecha']}</td>";
            if ($rol === 'Admin') {
            echo "<td class='td-opciones'>";
              echo "<a href='editar_venta.php?id={$fila['ID_orden_venta']}' class='btn btn-sm btn-warning'><i class='bi bi-pencil'></i></a> 
                    <a href='../controllers/eliminar_venta.php?id={$fila['ID_orden_venta']}&csrf_token=" . csrf_token() . "' onclick=\"return confirm('¿Estás seguro?')\" class='btn btn-sm btn-danger'><i class='bi bi-trash'></i></a>";
            echo "</td>";
            }
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='" . ($rol === 'Admin' ? 8 : 7) . "' class='text-center'>Sin datos aún</td></tr>";
        }
        ?>
      </tbody>
    </table>
<?php if ($total_paginas > 1): ?>
<div class="pagination-container">
    <nav aria-label="Paginación">
        <ul class="pagination mb-0">
            <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>">
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
