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

// Filtro por categoría desde URL
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';

// Consulta base con condición WHERE siempre verdadera para facilitar filtros dinámicos
$sql_base = "SELECT * FROM bitacora_conocimiento WHERE 1=1";

if (!empty($filtro_categoria)) {
    $sql_base .= " AND categoria = '" . mysqli_real_escape_string($conn, $filtro_categoria) . "'";
}

$sql_base .= " ORDER BY categoria, comando";
$resultado = $conn->query($sql_base);

// Paginación
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$total_registros = $resultado->num_rows;
$total_paginas = max(1, ceil($total_registros / $por_pagina));
$inicio = ($pagina_actual - 1) * $por_pagina;

$consulta_paginada = $sql_base . " LIMIT $inicio, $por_pagina";
$resultado_paginado = $conn->query($consulta_paginada);

// Construye parámetros GET para preservar filtros en paginación
$params_filtro = '';
if (!empty($filtro_categoria)) $params_filtro .= "&categoria=" . urlencode($filtro_categoria);

// Colores de badge por categoría
$categorias_badges = [
    'optimizacion' => 'bg-success',
    'redes' => 'bg-info',
    'limpieza' => 'bg-warning text-dark',
    'diagnostico' => 'bg-danger',
    'atajo' => 'bg-purple'
];
$categorias_labels = [
    'optimizacion' => 'Optimización',
    'redes' => 'Redes',
    'limpieza' => 'Limpieza',
    'diagnostico' => 'Diagnóstico',
    'atajo' => 'Atajo'
];
?>

<script>
// Ocultar alertas automáticamente tras 5 segundos
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
    <title>Comandos</title>
    <link rel="icon" type="image/png" href="../assets/img/compumasterldlogo.png">
</head>
<body class="custom-body">

<?php $nav_base = '..'; include('includes/navbar.php'); ?>
<?php echo $mostrar_alerta; ?>

<div class="container my-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch mb-3">
        <h2 class="mb-3 mb-md-0"><i class="bi bi-command me-2"></i>Comandos</h2>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalComando">
                <i class="bi bi-plus-circle me-1"></i> Agregar Comando
            </button>
        </div>
    </div>

    <!-- Formulario de filtros por búsqueda y categoría -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="busqueda" placeholder="Comando, descripción..." value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categor&iacute;a</label>
                    <select name="categoria" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($categorias_labels as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $filtro_categoria === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="bitacora_comandos.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Comando</th>
                    <th>Sistema Operativo</th>
                    <th>Descripci&oacute;n</th>
                    <th>Categor&iacute;a</th>
                    <th class="th-opciones">Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Renderiza cada fila de comandos encontrados
                if ($total_registros > 0) {
                    while ($fila = $resultado_paginado->fetch_assoc()) {
                        $cat = $fila['categoria'];
                        $badge = $categorias_badges[$cat] ?? 'bg-secondary';
                        $label = $categorias_labels[$cat] ?? $cat;
                        echo "<tr>";
                        echo "<td>{$fila['ID_comando']}</td>";
                        echo "<td><code class='text-primary fw-bold'>" . htmlspecialchars($fila['comando']) . "</code></td>";
                        echo "<td>" . htmlspecialchars($fila['sistema_operativo']) . "</td>";
                        echo "<td>" . htmlspecialchars($fila['descripcion']) . "</td>";
                        echo "<td><span class='badge $badge'>$label</span></td>";
                        echo "<td class='td-opciones'>";
                            echo "<button class='btn btn-sm btn-outline-secondary' title='Copiar' onclick='copiarComando(this)' data-comando=\"" . htmlspecialchars($fila['comando']) . "\"><i class='bi bi-clipboard'></i></button> ";
                            echo "<button class='btn btn-sm btn-warning' title='Editar' onclick='editarComando(" . $fila['ID_comando'] . ", " . json_encode($fila) . ")'><i class='bi bi-pencil'></i></button> ";
                            if ($rol === 'Admin') {
                                echo "<a href='../controllers/eliminar_bitacora.php?id={$fila['ID_comando']}&csrf_token=" . csrf_token() . "' class='btn btn-sm btn-outline-danger' title='Eliminar' onclick=\"return confirm('¿Eliminar este comando?')\"><i class='bi bi-trash'></i></a>";
                            }
                            echo "</td>";
                        echo "</tr>";
                    }
                // Mensaje cuando no hay resultados
                } else {
                    echo "<tr><td colspan='6' class='text-center'>No se encontraron comandos</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <?php if ($total_paginas > 1): ?>
        <div class="pagination-container">
            <nav aria-label="Paginación">
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

<!-- MODAL: Formulario para crear o editar comandos de la base de conocimiento -->
<div class="modal fade" id="modalComando" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../controllers/procesar_bitacora_conocimiento.php" method="POST" id="formComando">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="accion" id="accion_comando" value="crear">
                <input type="hidden" name="id_comando" id="id_comando" value="">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title" id="tituloModal"><i class="bi bi-command me-1"></i> Agregar Comando</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Comando *</label>
                        <input type="text" class="form-control" name="comando" id="campo_comando" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sistema Operativo *</label>
                        <input type="text" class="form-control" name="sistema_operativo" id="campo_so" placeholder="Ej: Windows 10/11" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripci&oacute;n *</label>
                        <textarea class="form-control" name="descripcion" id="campo_descripcion" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categor&iacute;a *</label>
                        <select name="categoria" id="campo_categoria" class="form-select" required>
                            <option value="optimizacion">Optimización</option>
                            <option value="redes">Redes</option>
                            <option value="limpieza">Limpieza</option>
                            <option value="diagnostico">Diagnóstico</option>
                            <option value="atajo">Atajo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill">
                        <i class="bi bi-check-circle me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
<script>
// Copia comando al portapapeles con feedback visual
function copiarComando(btn) {
    var comando = btn.getAttribute('data-comando');
    navigator.clipboard.writeText(comando).then(function() {
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-success');
        btn.innerHTML = '<i class="bi bi-check"></i>';
        setTimeout(function() {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
        }, 1500);
    });
}

// Rellena el modal con datos del comando seleccionado para edición
function editarComando(id, data) {
    document.getElementById('accion_comando').value = 'editar';
    document.getElementById('id_comando').value = id;
    document.getElementById('campo_comando').value = data.comando;
    document.getElementById('campo_so').value = data.sistema_operativo;
    document.getElementById('campo_descripcion').value = data.descripcion;
    document.getElementById('campo_categoria').value = data.categoria;
    document.getElementById('tituloModal').innerHTML = '<i class="bi bi-command me-1"></i> Editar Comando';
    var modal = new bootstrap.Modal(document.getElementById('modalComando'));
    modal.show();
}

// Resetea el modal a estado "crear" al cerrarlo
document.getElementById('modalComando').addEventListener('hidden.bs.modal', function() {
    document.getElementById('accion_comando').value = 'crear';
    document.getElementById('id_comando').value = '';
    document.getElementById('campo_comando').value = '';
    document.getElementById('campo_so').value = '';
    document.getElementById('campo_descripcion').value = '';
    document.getElementById('campo_categoria').value = 'optimizacion';
    document.getElementById('tituloModal').innerHTML = '<i class="bi bi-command me-1"></i> Agregar Comando';
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
