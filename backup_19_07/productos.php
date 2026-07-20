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
        <div class='alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3' style='z-index: 9999; width: auto;' role='alert'>
            <i class='bi bi-check-circle-fill'></i> $mensaje
        </div>
    ";
}

$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

$consulta = "SELECT * FROM producto ORDER BY ID_producto DESC";
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
    <title>Gestión de productos</title>
</head>
<body class="custom-body">
<?php echo $mostrar_alerta; ?>

<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container my-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch mb-3">
    <h2 class="mb-3 mb-md-0"><i class="bi bi-box-seam me-2"></i>Gestión de productos</h2>
    <div class="d-flex flex-column flex-sm-row gap-2">
      <a href="agregar_producto.php" class="btn btn-primary rounded-pill">
        <i class="bi bi-plus-circle me-1"></i> Agregar producto
      </a>
    </div>
  </div>

  <div class="mb-3">
    <input type="text" id="busqueda" placeholder="Buscar..." class="form-control-lg rounded-pill">
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle tabla-usuarios">
      <thead class="table-dark">
        <tr>
          <th>Código</th>
          <th>Nombre</th>
          <th>Cantidad</th>
          <th>Precio Unidad</th>
          <th>Descripción</th>
          <th>Fecha</th>
          <?php if ($rol === 'Admin'): ?><th class="th-opciones">Opciones</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($total_registros > 0) {
          while ($fila = $resultado_paginado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$fila['ID_producto']}</td>";
            echo "<td>{$fila['nombre']}</td>";
            echo "<td>";
            $stock = intval($fila['stock']);
            if ($stock < 10) {
                echo "<span class='badge-stock-bajo'>Bajo (" . $stock . ")</span>";
            } elseif ($stock <= 50) {
                echo "<span class='badge-stock-medio'>Medio (" . $stock . ")</span>";
            } else {
                echo "<span class='badge-stock-alto'>Alto (" . $stock . ")</span>";
            }
            echo "</td>";
            echo "<td>$" . number_format($fila['precio']) . "</td>";
            echo "<td>{$fila['descripcion']}</td>";
            echo "<td>{$fila['fecha']}</td>";
            if ($rol === 'Admin') {
            echo "<td class='td-opciones'>";
              echo "<a href='editar_producto.php?id={$fila['ID_producto']}' class='btn btn-sm btn-warning'><i class='bi bi-pencil'></i></a> 
                    <a href='../controllers/eliminar_producto.php?id={$fila['ID_producto']}&csrf_token=" . csrf_token() . "' onclick=\"return confirm('¿Estás seguro de eliminar este producto?')\" class='btn btn-sm btn-danger'><i class='bi bi-trash'></i></a>";
            echo "</td>";
            }
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='" . ($rol === 'Admin' ? 7 : 6) . "' class='text-center'>Sin datos aún</td></tr>";
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
