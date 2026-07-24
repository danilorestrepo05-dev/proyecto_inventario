<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

$id_trabajo = intval($_REQUEST['id'] ?? 0);

if ($id_trabajo <= 0) {
    header("Location: reparaciones.php");
    exit();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Obtener trabajo
$sql = "SELECT t.*, ds.dispositivo, ds.marca, ds.modelo, ds.numero_serie, ds.ID_dispositivo,
               s.ID_servicio, s.nombre AS servicio_nombre,
               c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
               c.telefono AS cliente_telefono, c.correo AS cliente_correo,
               u.nombre AS tecnico_nombre, u.apellido AS tecnico_apellido
        FROM trabajo t
        LEFT JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
        LEFT JOIN servicio s ON ds.ID_servicio = s.ID_servicio
        LEFT JOIN cliente c ON s.ID_cliente = c.ID_cliente
        LEFT JOIN usuario u ON s.ID_usuario_tecnico = u.ID_usuario
        WHERE t.ID_trabajo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_trabajo);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

if ($resultado->num_rows == 0) {
    echo "Trabajo no encontrado.";
    exit;
}
$trabajo = $resultado->fetch_assoc();

// Obtener repuestos
$sql_rep = "SELECT rr.*, p.nombre AS producto_nombre
            FROM reparacion_repuesto rr
            LEFT JOIN producto p ON rr.ID_producto = p.ID_producto
            WHERE rr.ID_trabajo = ?";
$stmt_rep = $conn->prepare($sql_rep);
$stmt_rep->bind_param("i", $id_trabajo);
$stmt_rep->execute();
$result_rep = $stmt_rep->get_result();
$stmt_rep->close();

// Obtener programas
$sql_prog = "SELECT * FROM programa_instalado WHERE ID_trabajo = ?";
$stmt_prog = $conn->prepare($sql_prog);
$stmt_prog->bind_param("i", $id_trabajo);
$stmt_prog->execute();
$result_prog = $stmt_prog->get_result();
$stmt_prog->close();

// Obtener bitácora
$sql_bit = "SELECT br.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
            FROM bitacora_reparacion br
            LEFT JOIN usuario u ON br.ID_usuario = u.ID_usuario
            WHERE br.ID_trabajo = ?
            ORDER BY br.fecha_cambio DESC";
$stmt_bit = $conn->prepare($sql_bit);
$stmt_bit->bind_param("i", $id_trabajo);
$stmt_bit->execute();
$result_bit = $stmt_bit->get_result();
$stmt_bit->close();

// Obtener garantía
$sql_gar = "SELECT * FROM garantia WHERE ID_trabajo = ?";
$stmt_gar = $conn->prepare($sql_gar);
$stmt_gar->bind_param("i", $id_trabajo);
$stmt_gar->execute();
$result_gar = $stmt_gar->get_result();
$garantia = $result_gar->fetch_assoc();
$stmt_gar->close();

// Productos activos para dropdown
$productos = $conn->query("SELECT ID_producto, nombre, stock, precio FROM producto WHERE activo = 1 ORDER BY nombre");

$mostrar_alerta = '';
if (isset($_GET['mensaje'])) {
    $mensaje = htmlspecialchars($_GET['mensaje']);
    $mostrar_alerta = "
        <div class='alert alert-success alert-dismissible fade show alert-flotante' role='alert'>
            <i class='bi bi-check-circle-fill'></i> $mensaje
        </div>
    ";
}

$garantia_dias = $garantia ? $garantia['dias'] : 0;
?>

<script>
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.remove();
        }, 500);
    });
}, 5000);

if (window.location.search.includes('mensaje=')) {
    var params = new URLSearchParams(window.location.search);
    params.delete('mensaje');
    var cleanUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
    window.history.replaceState({}, document.title, cleanUrl);
}
</script>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Trabajo #<?php echo $id_trabajo; ?> — <?php echo htmlspecialchars($trabajo['tipo_trabajo']); ?></title>
    <link rel="icon" type="image/png" href="../assets/img/compumasterldlogo.png">
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>
<?php echo $mostrar_alerta; ?>

<!-- Encabezado del trabajo con enlace de vuelta al servicio -->
<div class="container py-4">
    <div class="card shadow-sm" style="max-width: 900px; margin: auto;">
        <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
            <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Trabajo #<?php echo $id_trabajo; ?> &mdash; <?php echo htmlspecialchars($trabajo['tipo_trabajo']); ?> &mdash; <?php echo htmlspecialchars($trabajo['dispositivo']); ?></h5>
            <a href="detalle_servicio.php?id=<?php echo $trabajo['ID_servicio']; ?>" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver al servicio
            </a>
        </div>
        <div class="card-body p-4">
            <ul class="nav nav-tabs mb-4" role="tablist">
                <!-- Pestañas de navegación: Información, Repuestos, Programas, Bitácora -->
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-info">
                        <i class="bi bi-info-circle me-1"></i> Informaci&oacute;n
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-repuestos">
                        <i class="bi bi-cpu me-1"></i> Repuestos
                        <?php if ($result_rep->num_rows > 0): ?>
                            <span class="badge bg-primary"><?php echo $result_rep->num_rows; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-programas">
                        <i class="bi bi-cd me-1"></i> Programas
                        <?php if ($result_prog->num_rows > 0): ?>
                            <span class="badge bg-primary"><?php echo $result_prog->num_rows; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-bitacora">
                        <i class="bi bi-clock-history me-1"></i> Bit&aacute;cora
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- TAB 1: INFORMACIÓN - Formulario con datos del trabajo, dispositivo y garantía -->
                <div class="tab-pane fade show active" id="tab-info">
                    <form action="../controllers/procesar_edicion_trabajo.php" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">

                        <div class="alert alert-light border mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted"><i class="bi bi-person me-1"></i><strong>Cliente:</strong> <?php echo htmlspecialchars(trim(($trabajo['cliente_nombre'] ?? '') . ' ' . ($trabajo['cliente_apellido'] ?? ''))); ?></small>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i><strong>Tel:</strong> <?php echo !empty($trabajo['cliente_telefono']) ? htmlspecialchars($trabajo['cliente_telefono']) : '<em>No registrado</em>'; ?></small>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted"><i class="bi bi-envelope me-1"></i><strong>Correo:</strong> <?php echo !empty($trabajo['cliente_correo']) ? htmlspecialchars($trabajo['cliente_correo']) : '<em>No registrado</em>'; ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Dispositivo *</label>
                                <input type="text" class="form-control" name="dispositivo" value="<?php echo htmlspecialchars($trabajo['dispositivo']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <?php
                                    $estados = ['ingresado', 'diagnosticado', 'en_progreso', 'reparado', 'entregado', 'cancelado'];
                                    $labels = ['Ingresado', 'Diagnosticado', 'En Progreso', 'Reparado', 'Entregado', 'Cancelado'];
                                    for ($i = 0; $i < count($estados); $i++):
                                    ?>
                                        <option value="<?php echo $estados[$i]; ?>" <?php echo $trabajo['estado'] === $estados[$i] ? 'selected' : ''; ?>>
                                            <?php echo $labels[$i]; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Trabajo *</label>
                                <select name="tipo_trabajo" class="form-select" required>
                                    <?php
                                    $tipos = ['General', 'Revision', 'Mantenimiento HW', 'Mantenimiento SW', 'Instalacion', 'Formateo', 'Limpieza', 'Diagnostico', 'Reparacion', 'Otro'];
                                    foreach ($tipos as $tipo):
                                    ?>
                                        <option value="<?php echo $tipo; ?>" <?php echo $trabajo['tipo_trabajo'] === $tipo ? 'selected' : ''; ?>>
                                            <?php echo $tipo; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Marca</label>
                                <input type="text" class="form-control" name="marca" value="<?php echo htmlspecialchars($trabajo['marca']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Modelo</label>
                                <input type="text" class="form-control" name="modelo" value="<?php echo htmlspecialchars($trabajo['modelo']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N&uacute;mero de Serie</label>
                                <input type="text" class="form-control" name="numero_serie" value="<?php echo htmlspecialchars($trabajo['numero_serie']); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">T&eacute;cnico Asignado</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(trim(($trabajo['tecnico_nombre'] ?? '') . ' ' . ($trabajo['tecnico_apellido'] ?? ''))); ?>" disabled>
                            <small class="text-muted">El t&eacute;cnico se asigna al crear el trabajo</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Problema Reportado *</label>
                            <textarea class="form-control" name="problema_reportado" rows="2" required><?php echo htmlspecialchars($trabajo['problema_reportado']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagn&oacute;stico</label>
                            <textarea class="form-control" name="diagnostico" rows="2"><?php echo htmlspecialchars($trabajo['diagnostico']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notas Internas</label>
                            <textarea class="form-control" name="notas_internas" rows="2"><?php echo htmlspecialchars($trabajo['notas_internas']); ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Mano de Obra ($)</label>
                                <input type="number" class="form-control" name="mano_obra_costo" step="0.01" min="0" value="<?php echo $trabajo['mano_obra_costo']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Garant&iacute;a (al entregar)</label>
                                <select name="garantia_dias" class="form-select">
                                    <option value="0" <?php echo $garantia_dias == 0 ? 'selected' : ''; ?>>Sin garant&iacute;a</option>
                                    <option value="30" <?php echo $garantia_dias == 30 ? 'selected' : ''; ?>>1 mes (30 d&iacute;as)</option>
                                    <option value="60" <?php echo $garantia_dias == 60 ? 'selected' : ''; ?>>2 meses (60 d&iacute;as)</option>
                                    <option value="90" <?php echo $garantia_dias == 90 ? 'selected' : ''; ?>>3 meses (90 d&iacute;as)</option>
                                    <option value="180" <?php echo $garantia_dias == 180 ? 'selected' : ''; ?>>6 meses (180 d&iacute;as)</option>
                                    <option value="365" <?php echo $garantia_dias == 365 ? 'selected' : ''; ?>>12 meses (365 d&iacute;as)</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-check-circle me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>

                    <?php if ($garantia): ?>
                    <hr>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-shield-check me-1"></i>
                        <strong>Garant&iacute;a vigente:</strong> <?php echo $garantia['dias']; ?> d&iacute;as
                        (<?php echo date('d/m/Y', strtotime($garantia['fecha_inicio'])); ?> &mdash; <?php echo date('d/m/Y', strtotime($garantia['fecha_fin'])); ?>)
                    </div>
                    <?php endif; ?>

                    <hr>
                    <div class="d-flex flex-wrap gap-2 align-items-start">
                        <form method="POST" action="../reports/pdf_recibido.php" target="_blank" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                                <i class="bi bi-file-pdf me-1"></i> Ficha de Ingreso
                            </button>
                        </form>
                        <form method="POST" action="../reports/pdf_operacion_garantia.php" target="_blank" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                            <input type="hidden" name="incluir_garantia" value="0">
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-outline-success btn-sm rounded-pill">
                                    <i class="bi bi-file-pdf me-1"></i> Certificado de Trabajo
                                </button>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="incluir_garantia" value="1" id="chkGarantiaCertTrab" checked>
                                    <label class="form-check-label small text-muted" for="chkGarantiaCertTrab">Garant&iacute;a</label>
                                </div>
                            </div>
                        </form>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCuentaCobro">
                            <i class="bi bi-file-pdf me-1"></i> Cuenta de Cobro
                        </button>
                    </div>
                </div>

                <!-- TAB 2: REPUESTOS - Tabla de repuestos usados y botón para agregar -->
                <div class="tab-pane fade" id="tab-repuestos">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Repuestos Utilizados</h6>
                        <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalRepuesto">
                            <i class="bi bi-plus-circle me-1"></i> Agregar Repuesto
                        </button>
                    </div>
                    <?php if ($result_rep->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-warning">
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Subtotal</th>
                                    <th>Garant&iacute;a Proveedor</th>
                                    <th>Adjunto</th>
                                    <th class="th-opciones">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($rep_row = $result_rep->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rep_row['producto_nombre']); ?></td>
                                    <td><?php echo $rep_row['cantidad']; ?></td>
                                    <td>$<?php echo number_format($rep_row['precio_unitario'], 0, ',', '.'); ?></td>
                                    <td><strong>$<?php echo number_format($rep_row['cantidad'] * $rep_row['precio_unitario'], 0, ',', '.'); ?></strong></td>
                                    <td><?php echo $rep_row['garantia_proveedor_dias'] > 0 ? $rep_row['garantia_proveedor_dias'] . ' días' : '-'; ?></td>
                                    <td>
                                        <?php if (!empty($rep_row['factura_proveedor_adjunto'])): ?>
                                            <a href="../<?php echo htmlspecialchars($rep_row['factura_proveedor_adjunto']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="td-opciones">
                                        <button class="btn btn-sm btn-warning" title="Editar"
                                            onclick='editarRepuesto(<?php echo json_encode($rep_row); ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Eliminar"
                                            onclick="eliminarRepuesto(<?php echo $rep_row['ID_reparacion_repuesto']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">No hay repuestos registrados</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 3: PROGRAMAS - Tabla de programas instalados y botón para agregar -->
                <div class="tab-pane fade" id="tab-programas">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Programas Instalados</h6>
                        <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalPrograma">
                            <i class="bi bi-plus-circle me-1"></i> Agregar Programa
                        </button>
                    </div>
                    <?php if ($result_prog->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Versi&oacute;n</th>
                                    <th>Licencia</th>
                                    <th>Cant.</th>
                                    <th>Precio U.</th>
                                    <th>Subtotal</th>
                                    <th>Garant&iacute;a hasta</th>
                                    <th class="th-opciones">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($prog = $result_prog->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prog['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($prog['version']); ?></td>
                                    <td><?php echo htmlspecialchars($prog['licencia']); ?></td>
                                    <td><?php echo $prog['cantidad'] ?? 1; ?></td>
                                    <td>$<?php echo number_format($prog['costo'] ?? 0, 0, ',', '.'); ?></td>
                                    <td><strong>$<?php echo number_format(($prog['cantidad'] ?? 1) * ($prog['costo'] ?? 0), 0, ',', '.'); ?></strong></td>
                                    <td><?php echo (!empty($prog['gar_dias']) && !empty($prog['gar_fecha_fin'])) ? '<span class="badge bg-success">' . date('d/m/Y', strtotime($prog['gar_fecha_fin'])) . '</span>' : '<span class="text-muted">-</span>'; ?></td>
                                    <td class="td-opciones">
                                        <button class="btn btn-sm btn-warning" title="Editar"
                                            onclick='editarPrograma(<?php echo json_encode($prog); ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Eliminar"
                                            onclick="eliminarPrograma(<?php echo $prog['ID_programa']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">No hay programas registrados</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 4: BITÁCORA - Historial de cambios de estado del trabajo -->
                <div class="tab-pane fade" id="tab-bitacora">
                    <h6 class="mb-3">Historial de Cambios de Estado</h6>
                    <?php if ($result_bit->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Estado Anterior</th>
                                    <th>Estado Nuevo</th>
                                    <th>Observaci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($bit = $result_bit->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($bit['fecha_cambio'])); ?></td>
                                    <td><?php echo htmlspecialchars($bit['usuario_nombre'] . ' ' . $bit['usuario_apellido']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $bit['estado_anterior'])); ?></span></td>
                                    <td><span class="badge bg-primary"><?php echo ucfirst(str_replace('_', ' ', $bit['estado_nuevo'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($bit['observacion']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-clock-history fs-1"></i>
                        <p class="mt-2">No hay cambios de estado registrados</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Agregar Repuesto -->
<div class="modal fade" id="modalRepuesto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../controllers/procesar_repuesto_reparacion.php" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title"><i class="bi bi-cpu me-1"></i> Agregar Repuesto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Producto *</label>
                        <select name="id_producto" id="select_producto" class="form-select" required>
                            <option value="" disabled selected>[Seleccione un producto]</option>
                            <?php while ($p = $productos->fetch_assoc()): ?>
                                <option value="<?php echo $p['ID_producto']; ?>" data-precio="<?php echo $p['precio']; ?>" data-stock="<?php echo $p['stock']; ?>">
                                    <?php echo htmlspecialchars($p['nombre']); ?> (Stock: <?php echo $p['stock']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" class="form-control" name="cantidad" min="1" value="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio Unitario ($)</label>
                            <input type="number" class="form-control" name="precio_unitario" id="precio_unitario" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Garant&iacute;a del Proveedor (d&iacute;as)</label>
                        <select class="form-select" name="garantia_proveedor_dias">
                            <option value="0">Sin garant&iacute;a</option>
                            <option value="30">1 mes (30 d&iacute;as)</option>
                            <option value="60">2 meses (60 d&iacute;as)</option>
                            <option value="90">3 meses (90 d&iacute;as)</option>
                            <option value="180">6 meses (180 d&iacute;as)</option>
                            <option value="365">12 meses (365 d&iacute;as)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Factura/Adjunto del Proveedor</label>
                        <input type="file" class="form-control" name="factura_proveedor" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">PDF o imagen de la factura del proveedor</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill">
                        <i class="bi bi-check-circle me-1"></i> Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Agregar Programa -->
<div class="modal fade" id="modalPrograma" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../controllers/procesar_programa_reparacion.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title"><i class="bi bi-cd me-1"></i> Agregar Programa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Programa *</label>
                        <input type="text" class="form-control" name="nombre" placeholder="Ej: Microsoft Office 2021" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Versi&oacute;n</label>
                        <input type="text" class="form-control" name="version" placeholder="Ej: 16.0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Licencia</label>
                        <input type="text" class="form-control" name="licencia" placeholder="Ej: Original, Crack, Trial">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" class="form-control" name="cantidad" min="1" value="1" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Costo Unitario ($)</label>
                            <input type="number" class="form-control" name="costo" placeholder="0" min="0" step="1" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Subtotal</label>
                            <input type="text" class="form-control" id="prog_subtotal_add" value="$0" disabled>
                        </div>
                    </div>
                    <hr class="my-2">
                    <p class="text-muted small mb-2"><i class="bi bi-shield-check me-1"></i>Garant&iacute;a del programa (opcional)</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">D&iacute;as de garant&iacute;a</label>
                            <select class="form-select" name="gar_dias">
                                <option value="0">Sin garant&iacute;a</option>
                                <option value="30">1 mes (30 d&iacute;as)</option>
                                <option value="60">2 meses (60 d&iacute;as)</option>
                                <option value="90">3 meses (90 d&iacute;as)</option>
                                <option value="180">6 meses (180 d&iacute;as)</option>
                                <option value="365">12 meses (365 d&iacute;as)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha inicio garant&iacute;a</label>
                            <input type="date" class="form-control" name="gar_fecha_inicio" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill">
                        <i class="bi bi-check-circle me-1"></i> Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Opciones Cuenta de Cobro -->
<div class="modal fade" id="modalCuentaCobro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../reports/pdf_cuenta_cobro.php" method="POST" target="_blank">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title"><i class="bi bi-file-pdf me-1"></i> Opciones de Cuenta de Cobro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Mostrar precios por &iacute;tem</label>
                        <select name="mostrar_precios" class="form-select">
                            <option value="0" selected>No, solo concepto</option>
                            <option value="1">S&iacute;, con precios individuales</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descuento (opcional)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="descuento_valor" step="0.01" min="0" value="0" placeholder="0">
                            <select name="descuento_tipo" class="form-select" style="max-width: 150px;">
                                <option value="fijo">$ (fijo)</option>
                                <option value="porcentaje">% (porcentaje)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-pill">
                        <i class="bi bi-file-pdf me-1"></i> Imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Editar Repuesto -->
<div class="modal fade" id="modalEditarRepuesto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarRepuesto" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_reparacion_repuesto" id="edit_rep_id">
                <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title"><i class="bi bi-cpu me-1"></i> Editar Repuesto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <input type="text" class="form-control" id="edit_rep_producto" disabled>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" class="form-control" name="cantidad" id="edit_rep_cantidad" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio Unitario ($)</label>
                            <input type="number" class="form-control" name="precio_unitario" id="edit_rep_precio" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Garant&iacute;a del Proveedor (d&iacute;as)</label>
                        <select class="form-select" name="garantia_proveedor_dias" id="edit_rep_garantia">
                            <option value="0">Sin garant&iacute;a</option>
                            <option value="30">1 mes (30 d&iacute;as)</option>
                            <option value="60">2 meses (60 d&iacute;as)</option>
                            <option value="90">3 meses (90 d&iacute;as)</option>
                            <option value="180">6 meses (180 d&iacute;as)</option>
                            <option value="365">12 meses (365 d&iacute;as)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Factura/Adjunto del Proveedor</label>
                        <input type="file" class="form-control" name="factura_proveedor" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">PDF o imagen. Dejar vac&iacute;o para mantener el actual.</small>
                        <div id="edit_rep_adjunto_actual" class="mt-1" style="display:none;">
                            <small class="text-success"><i class="bi bi-paperclip"></i> Adjunto actual: <a href="#" id="edit_rep_adjunto_link" target="_blank">Ver archivo</a></small>
                        </div>
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

<!-- MODAL: Editar Programa -->
<div class="modal fade" id="modalEditarPrograma" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarPrograma">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_programa" id="edit_prog_id">
                <input type="hidden" name="id_trabajo" value="<?php echo $id_trabajo; ?>">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title"><i class="bi bi-cd me-1"></i> Editar Programa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Programa *</label>
                        <input type="text" class="form-control" name="nombre" id="edit_prog_nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Versi&oacute;n</label>
                        <input type="text" class="form-control" name="version" id="edit_prog_version">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Licencia</label>
                        <input type="text" class="form-control" name="licencia" id="edit_prog_licencia">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" class="form-control" name="cantidad" id="edit_prog_cantidad" min="1" value="1" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Costo Unitario ($)</label>
                            <input type="number" class="form-control" name="costo" id="edit_prog_costo" min="0" step="1" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Subtotal</label>
                            <input type="text" class="form-control" id="prog_subtotal_edit" value="$0" disabled>
                        </div>
                    </div>
                    <hr class="my-2">
                    <p class="text-muted small mb-2"><i class="bi bi-shield-check me-1"></i>Garant&iacute;a del programa (opcional)</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">D&iacute;as de garant&iacute;a</label>
                            <select class="form-select" name="gar_dias" id="edit_prog_gar_dias">
                                <option value="0">Sin garant&iacute;a</option>
                                <option value="30">1 mes (30 d&iacute;as)</option>
                                <option value="60">2 meses (60 d&iacute;as)</option>
                                <option value="90">3 meses (90 d&iacute;as)</option>
                                <option value="180">6 meses (180 d&iacute;as)</option>
                                <option value="365">12 meses (365 d&iacute;as)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha inicio garant&iacute;a</label>
                            <input type="date" class="form-control" name="gar_fecha_inicio" id="edit_prog_gar_fecha">
                        </div>
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

<!-- Scripts JavaScript: funciones utilitarias, subtotales y AJAX para CRUD -->
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
var csrfToken = document.querySelector('input[name="csrf_token"]').value;
var idTrabajo = <?php echo $id_trabajo; ?>;

// Recarga la página preservando la pestaña activa en el hash
function recargarEnMismoTab() {
    var tabActivo = document.querySelector('.nav-tabs .nav-link.active');
    var hash = tabActivo ? tabActivo.getAttribute('href') : '';
    if (hash) window.location.hash = hash;
    location.reload(true);
}

// Formatea un número como moneda colombiana sin decimales
function fmt(n) { return '$' + Number(n || 0).toLocaleString('es-CO', {maximumFractionDigits: 0}); }

// Calcula el subtotal del modal de agregar programa (cantidad × costo)
function calcSubtotalAdd() {
    var c = parseInt(document.querySelector('#modalPrograma input[name="cantidad"]').value) || 0;
    var p = parseInt(document.querySelector('#modalPrograma input[name="costo"]').value) || 0;
    document.getElementById('prog_subtotal_add').value = fmt(c * p);
}
// Calcula el subtotal del modal de editar programa
function calcSubtotalEdit() {
    var c = parseInt(document.getElementById('edit_prog_cantidad').value) || 0;
    var p = parseInt(document.getElementById('edit_prog_costo').value) || 0;
    document.getElementById('prog_subtotal_edit').value = fmt(c * p);
}

document.querySelector('#modalPrograma input[name="cantidad"]').addEventListener('input', calcSubtotalAdd);
document.querySelector('#modalPrograma input[name="costo"]').addEventListener('input', calcSubtotalAdd);
document.getElementById('edit_prog_cantidad').addEventListener('input', calcSubtotalEdit);
document.getElementById('edit_prog_costo').addEventListener('input', calcSubtotalEdit);

// Auto-llenar precio al seleccionar un producto del dropdown
document.getElementById('select_producto').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    var precio = selected.getAttribute('data-precio');
    if (precio) {
        document.getElementById('precio_unitario').value = precio;
    }
});

// AJAX: Agregar Repuesto
document.getElementById('modalRepuesto').querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Agregando...';

    fetch('../controllers/procesar_repuesto_reparacion.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Agregar';
        if (data.ok) {
            bootstrap.Modal.getInstance(form.closest('.modal')).hide();
            form.reset();
            mostrarAlerta(data.mensaje, 'success');
            recargarEnMismoTab();
        } else {
            alert(data.mensaje);
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Agregar';
        alert('Error de conexión');
    });
});

// AJAX: Agregar Programa
document.getElementById('modalPrograma').querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Agregando...';

    fetch('../controllers/procesar_programa_reparacion.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Agregar';
        if (data.ok) {
            bootstrap.Modal.getInstance(form.closest('.modal')).hide();
            form.reset();
            mostrarAlerta(data.mensaje, 'success');
            recargarEnMismoTab();
        } else {
            alert(data.mensaje);
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Agregar';
        alert('Error de conexión');
    });
});

// Editar Repuesto
function editarRepuesto(data) {
    document.getElementById('edit_rep_id').value = data.ID_reparacion_repuesto;
    document.getElementById('edit_rep_producto').value = data.producto_nombre || data.ID_producto;
    document.getElementById('edit_rep_cantidad').value = data.cantidad;
    document.getElementById('edit_rep_precio').value = data.precio_unitario;
    document.getElementById('edit_rep_garantia').value = data.garantia_proveedor_dias;
    var adjDiv = document.getElementById('edit_rep_adjunto_actual');
    var adjLink = document.getElementById('edit_rep_adjunto_link');
    if (data.factura_proveedor_adjunto) {
        adjLink.href = '../' + data.factura_proveedor_adjunto;
        adjDiv.style.display = 'block';
    } else {
        adjDiv.style.display = 'none';
    }
    document.querySelector('#formEditarRepuesto input[name="factura_proveedor"]').value = '';
    new bootstrap.Modal(document.getElementById('modalEditarRepuesto')).show();
}

document.getElementById('formEditarRepuesto').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;

    fetch('../controllers/editar_repuesto_reparacion.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        if (data.ok) {
            bootstrap.Modal.getInstance(form.closest('.modal')).hide();
            mostrarAlerta(data.mensaje, 'success');
            recargarEnMismoTab();
        } else {
            alert(data.mensaje);
        }
    })
    .catch(function() { btn.disabled = false; alert('Error de conexión'); });
});

// Eliminar Repuesto
function eliminarRepuesto(id) {
    if (!confirm('¿Eliminar este repuesto? Se devolverá el stock.')) return;

    var fd = new FormData();
    fd.append('csrf_token', csrfToken);
    fd.append('id_reparacion_repuesto', id);
    fd.append('id_trabajo', idTrabajo);

    fetch('../controllers/eliminar_repuesto_reparacion.php', {
        method: 'POST',
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            mostrarAlerta(data.mensaje, 'success');
            recargarEnMismoTab();
        } else {
            alert(data.mensaje);
        }
    })
    .catch(function() { alert('Error de conexión'); });
}

// Editar Programa
function editarPrograma(data) {
    document.getElementById('edit_prog_id').value = data.ID_programa;
    document.getElementById('edit_prog_nombre').value = data.nombre;
    document.getElementById('edit_prog_version').value = data.version || '';
    document.getElementById('edit_prog_licencia').value = data.licencia || '';
    document.getElementById('edit_prog_costo').value = parseInt(data.costo) || 0;
    document.getElementById('edit_prog_cantidad').value = data.cantidad || 1;
    document.getElementById('edit_prog_gar_dias').value = data.gar_dias || 0;
    document.getElementById('edit_prog_gar_fecha').value = data.gar_fecha_inicio || '';
    calcSubtotalEdit();
    new bootstrap.Modal(document.getElementById('modalEditarPrograma')).show();
}

document.getElementById('formEditarPrograma').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;

    fetch('../controllers/editar_programa_reparacion.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        if (data.ok) {
            bootstrap.Modal.getInstance(form.closest('.modal')).hide();
            mostrarAlerta(data.mensaje, 'success');
            recargarEnMismoTab();
        } else {
            alert(data.mensaje);
        }
    })
    .catch(function() { btn.disabled = false; alert('Error de conexión'); });
});

// Eliminar Programa
function eliminarPrograma(id) {
    if (!confirm('¿Eliminar este programa?')) return;

    var fd = new FormData();
    fd.append('csrf_token', csrfToken);
    fd.append('id_programa', id);
    fd.append('id_trabajo', idTrabajo);

    fetch('../controllers/eliminar_programa_reparacion.php', {
        method: 'POST',
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            mostrarAlerta(data.mensaje, 'success');
            recargarEnMismoTab();
        } else {
            alert(data.mensaje);
        }
    })
    .catch(function() { alert('Error de conexión'); });
}

function mostrarAlerta(msg, tipo) {
    var div = document.createElement('div');
    div.className = 'alert alert-' + tipo + ' alert-dismissible fade show alert-flotante';
    div.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + msg;
    document.body.appendChild(div);
    setTimeout(function() {
        div.style.transition = 'opacity 0.5s';
        div.style.opacity = '0';
        setTimeout(function() { div.remove(); }, 500);
    }, 3000);
}

// Al cargar, restaurar la pestaña activa desde el hash de la URL
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.nav-tabs .nav-link').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(e) {
            history.replaceState(null, null, e.target.getAttribute('href'));
        });
    });

    if (window.location.hash) {
        var tabLink = document.querySelector('.nav-tabs a[href="' + window.location.hash + '"]');
        if (tabLink) {
            var tab = new bootstrap.Tab(tabLink);
            tab.show();
        }
    }
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
