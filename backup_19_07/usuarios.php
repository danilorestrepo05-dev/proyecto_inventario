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

if ($_SESSION['rol'] !== 'Admin') {
    header("Location: ../menu.php");
    exit();
}

$consulta = "SELECT * FROM usuario ORDER BY ID_usuario DESC";
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
    <title>Gestión de usuarios</title>
</head>
<body class="custom-body">
<?php echo $mostrar_alerta; ?>

<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container my-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch mb-3">
    <h2 class="mb-3 mb-md-0"><i class="bi bi-person-gear me-2"></i>Gestión de usuarios</h2>
    <div class="d-flex flex-column flex-sm-row gap-2">
      <a href="registro.php" class="btn btn-primary rounded-pill">
        <i class="bi bi-plus-circle me-1"></i> Agregar usuario
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
          <th>ID</th>
          <th>Nombre</th>
          <th>Apellido</th>
          <th>Correo electrónico</th>
          <th>Tipo de usuario</th>
          <th>Opciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($total_registros > 0) {
          while ($fila = $resultado_paginado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$fila['ID_usuario']}</td>";
            echo "<td>{$fila['nombre']}</td>";
            echo "<td>{$fila['apellido']}</td>";
            echo "<td>{$fila['correo']}</td>";
            echo "<td>{$fila['rol']}</td>";
            echo "<td>
                    <a href='editar_usuario.php?id={$fila['ID_usuario']}' class='btn btn-sm btn-warning'><i class='bi bi-pencil'></i></a>
                    <a href='../controllers/eliminar_usuario.php?id={$fila['ID_usuario']}&csrf_token=<?php echo csrf_token(); ?>' onclick=\"return confirm('¿Estás seguro de eliminar este usuario?')\" class='btn btn-sm btn-danger'><i class='bi bi-trash'></i></a>
                    <a href='recuperar_clave.php?id={$fila['ID_usuario']}' class='btn btn-sm btn-info'><i class='bi bi-key'></i></a>
                  </td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='6' class='text-center'>Sin datos aún</td></tr>";
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
