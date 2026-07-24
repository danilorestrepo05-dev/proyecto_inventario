<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

$id_servicio = intval($_GET['id'] ?? 0);
if ($id_servicio <= 0) { header("Location: reparaciones.php"); exit(); }
if (!isset($_SESSION['usuario'])) { header("Location: ../index.php"); exit(); }

// Consulta principal: servicio con datos del cliente y técnico
$sql = "SELECT s.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
               c.telefono AS cliente_telefono, c.correo AS cliente_correo,
               c.identificacion, c.tipo_identificacion,
               u.nombre AS tecnico_nombre, u.apellido AS tecnico_apellido
        FROM servicio s
        LEFT JOIN cliente c ON s.ID_cliente = c.ID_cliente
        LEFT JOIN usuario u ON s.ID_usuario_tecnico = u.ID_usuario
        WHERE s.ID_servicio = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_servicio);
$stmt->execute();
$serv = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$serv) { echo "Servicio no encontrado."; exit; }

// Get all devices for this service
$sql_disp = "SELECT ds.* FROM dispositivo_servicio ds WHERE ds.ID_servicio = ? ORDER BY ds.ID_dispositivo";
$stmt_disp = $conn->prepare($sql_disp);
$stmt_disp->bind_param("i", $id_servicio);
$stmt_disp->execute();
$result_disp = $stmt_disp->get_result();
$stmt_disp->close();

// For each device, get its tasks
$dispositivos = [];
while ($d = $result_disp->fetch_assoc()) {
    $sql_trab = "SELECT t.* FROM trabajo t WHERE t.ID_dispositivo = ? ORDER BY t.ID_trabajo";
    $stmt_trab = $conn->prepare($sql_trab);
    $stmt_trab->bind_param("i", $d['ID_dispositivo']);
    $stmt_trab->execute();
    $result_trab = $stmt_trab->get_result();
    $d['trabajos'] = [];
    while ($t = $result_trab->fetch_assoc()) {
        $d['trabajos'][] = $t;
    }
    $stmt_trab->close();
    $dispositivos[] = $d;
}

// Alerta flotante de éxito pasada por query string
$mostrar_alerta = '';
if (isset($_GET['mensaje'])) {
    $mensaje = htmlspecialchars($_GET['mensaje']);
    $mostrar_alerta = "<div class='alert alert-success alert-dismissible fade show alert-flotante' role='alert'><i class='bi bi-check-circle-fill'></i> $mensaje</div>";
}

$estados_labels = [
    'ingresado' => 'Ingresado', 'diagnosticado' => 'Diagnosticado',
    'en_progreso' => 'En Progreso', 'reparado' => 'Reparado',
    'entregado' => 'Entregado', 'cancelado' => 'Cancelado'
];
$badge_clases = [
    'ingresado' => 'bg-info', 'diagnosticado' => 'bg-warning text-dark',
    'en_progreso' => 'bg-primary', 'reparado' => 'bg-success',
    'entregado' => 'bg-secondary', 'cancelado' => 'bg-danger'
];
?>

<!-- Auto-ocultar alertas y limpiar URL del parámetro "mensaje" -->
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
    <title>Servicio #<?php echo $id_servicio; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/compumasterldlogo.png">
</head>
<style>
    #accordionDispositivos .accordion-button {
        background-color: #ffffff;
        color: #1a2035;
    }
    #accordionDispositivos .accordion-button:not(.collapsed) {
        background-color: #e9ecef;
        color: #1a2035;
    }
    #accordionDispositivos .accordion-button::after {
        filter: invert(30%);
    }
</style>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>
<?php echo $mostrar_alerta; ?>

<!-- Contenedor principal de la página -->
<div class="container py-4">

    <!-- Encabezado del servicio: título, técnico y botones de navegación -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-tools me-2"></i>Servicio #<?php echo $id_servicio; ?> &mdash;
                <?php echo htmlspecialchars(trim(($serv['cliente_nombre'] ?? '') . ' ' . ($serv['cliente_apellido'] ?? ''))); ?>
            </h2>
            <p class="text-muted mb-0">
                <i class="bi bi-person-badge me-1"></i>T&eacute;cnico: <?php echo htmlspecialchars(trim(($serv['tecnico_nombre'] ?? '') . ' ' . ($serv['tecnico_apellido'] ?? ''))); ?>
                &nbsp;&bull;&nbsp;
                <i class="bi bi-calendar me-1"></i>Creado: <?php echo date('d/m/Y H:i', strtotime($serv['fecha_creacion'])); ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="reparaciones.php" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="agregar_servicio.php" class="btn btn-primary rounded-pill">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Servicio
            </a>
        </div>
    </div>

    <!-- Tarjeta de información del cliente: nombre, identificación, teléfono, correo, mano de obra -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body py-3">
            <div class="row">
                <div class="col-md-2 mb-2 mb-md-0">
                    <small class="text-muted fw-bold d-block">Cliente</small>
                    <span><?php echo htmlspecialchars(trim(($serv['cliente_nombre'] ?? '') . ' ' . ($serv['cliente_apellido'] ?? ''))); ?></span>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <small class="text-muted fw-bold d-block">Identificaci&oacute;n</small>
                    <span>
                        <?php if (!empty($serv['identificacion'])): ?>
                            <?php echo htmlspecialchars(($serv['tipo_identificacion'] ?? '') . ' ' . $serv['identificacion']); ?>
                        <?php else: ?>
                            <em>No registrada</em>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <small class="text-muted fw-bold d-block">Tel&eacute;fono</small>
                    <span><?php echo !empty($serv['cliente_telefono']) ? htmlspecialchars($serv['cliente_telefono']) : '<em>No registrado</em>'; ?></span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted fw-bold d-block">Correo</small>
                    <span><?php echo !empty($serv['cliente_correo']) ? htmlspecialchars($serv['cliente_correo']) : '<em>No registrado</em>'; ?></span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted fw-bold d-block">Mano de Obra</small>
                    <span class="fw-bold text-primary">$<?php echo number_format($serv['mano_obra_costo'] ?? 0, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de acción: agregar dispositivo, PDFs y mano de obra -->
    <div class="d-flex flex-wrap gap-2 mb-4 align-items-start">
        <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAgregarDispositivo">
            <i class="bi bi-plus-circle me-1"></i> Agregar Dispositivo
        </button>
        <form method="POST" action="../reports/pdf_recibido.php" target="_blank" class="d-inline">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id_servicio" value="<?php echo $id_servicio; ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                <i class="bi bi-file-pdf me-1"></i> Ficha de Ingreso
            </button>
        </form>
        <form method="POST" action="../reports/pdf_operacion_garantia.php" target="_blank" class="d-inline">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id_servicio" value="<?php echo $id_servicio; ?>">
            <input type="hidden" name="incluir_garantia" value="0">
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-outline-success btn-sm rounded-pill">
                    <i class="bi bi-file-pdf me-1"></i> Certificado de Trabajo
                </button>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="incluir_garantia" value="1" id="chkGarantiaCert" checked>
                    <label class="form-check-label small text-muted" for="chkGarantiaCert">Garant&iacute;a</label>
                </div>
            </div>
        </form>
        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCuentaCobro">
            <i class="bi bi-file-pdf me-1"></i> Cuenta de Cobro
        </button>
        <button type="button" class="btn btn-outline-dark btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalManoObraTotal">
            <i class="bi bi-cash-stack me-1"></i> Mano de Obra Total
        </button>
    </div>

    <!-- Sección de dispositivos: acordeón colapsable o mensaje vacío -->
    <?php if (empty($dispositivos)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-inbox fs-1"></i>
        <p class="mt-2">No hay dispositivos registrados en este servicio</p>
        <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAgregarDispositivo">
            <i class="bi bi-plus-circle me-1"></i> Agregar Dispositivo
        </button>
    </div>
    <?php else: ?>

    <div class="accordion" id="accordionDispositivos">
        <?php foreach ($dispositivos as $idx => $disp): ?>
        <div class="accordion-item mb-3 shadow-sm border-0 rounded-3 overflow-hidden">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#disp-<?php echo $disp['ID_dispositivo']; ?>">
                    <div class="d-flex align-items-center gap-2 w-100">
                        <i class="bi bi-laptop me-1 text-primary"></i>
                        <strong><?php echo htmlspecialchars($disp['dispositivo']); ?></strong>
                        <?php if (!empty($disp['marca'])): ?>
                            <span class="text-muted"><?php echo htmlspecialchars($disp['marca']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($disp['modelo'])): ?>
                            <span class="text-muted"><?php echo htmlspecialchars($disp['modelo']); ?></span>
                        <?php endif; ?>
                        <span class="badge bg-primary rounded-pill"><?php echo count($disp['trabajos']); ?> trabajo(s)</span>
                    </div>
                </button>
            </h2>
            <div id="disp-<?php echo $disp['ID_dispositivo']; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionDispositivos">
                <div class="accordion-body p-0">
                    <div class="d-flex justify-content-end gap-1 px-3 pt-3">
                        <button class="btn btn-sm btn-warning rounded-pill" title="Editar dispositivo"
                            type="button"
                            onclick='editarDispositivo(<?php echo json_encode($disp); ?>)'>
                            <i class="bi bi-pencil me-1"></i> Editar
                        </button>
                        <button class="btn btn-sm btn-outline-danger rounded-pill" title="Eliminar dispositivo"
                            onclick="eliminarDispositivo(<?php echo $disp['ID_dispositivo']; ?>)">
                            <i class="bi bi-trash me-1"></i> Eliminar
                        </button>
                    </div>

                    <?php if (!empty($disp['numero_serie'])): ?>
                    <div class="px-3 pt-2">
                        <small class="text-muted"><i class="bi bi-upc-scan me-1"></i>N&uacute;mero de Serie: <strong><?php echo htmlspecialchars($disp['numero_serie']); ?></strong></small>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($disp['trabajos'])): ?>
                    <div class="table-responsive p-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Tipo Trabajo</th>
                                    <th>Problema</th>
                                    <th>Estado</th>
                                    <th>Mano de Obra</th>
                                    <th class="th-opciones">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($disp['trabajos'] as $trab): ?>
                                <tr>
                                    <td><?php echo $trab['ID_trabajo']; ?></td>
                                    <td><?php echo htmlspecialchars($trab['tipo_trabajo']); ?></td>
                                    <td>
                                        <span class="d-inline-block text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($trab['problema_reportado']); ?>">
                                            <?php echo htmlspecialchars($trab['problema_reportado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $estado_trab = $trab['estado'];
                                        $clase_trab = $badge_clases[$estado_trab] ?? 'bg-secondary';
                                        $label_trab = $estados_labels[$estado_trab] ?? ucfirst(str_replace('_', ' ', $estado_trab));
                                        ?>
                                        <span class="badge <?php echo $clase_trab; ?>"><?php echo $label_trab; ?></span>
                                    </td>
                                    <td>$<?php echo number_format($trab['mano_obra_costo'] ?? 0, 0, ',', '.'); ?></td>
                                    <td class="td-opciones">
                                        <a href="editar_trabajo.php?id=<?php echo $trab['ID_trabajo']; ?>" class="btn btn-sm btn-warning" title="Editar trabajo">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" title="Eliminar trabajo"
                                            onclick="eliminarTrabajo(<?php echo $trab['ID_trabajo']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-3 px-3">
                        <i class="bi bi-clipboard me-1"></i> No hay trabajos registrados para este dispositivo
                    </div>
                    <?php endif; ?>

                    <div class="px-3 pb-3 pt-1">
                        <button class="btn btn-sm btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAgregarTrabajo"
                            onclick="document.getElementById('modalAgregarTrabajo_id_dispositivo').value='<?php echo $disp['ID_dispositivo']; ?>'">
                            <i class="bi bi-plus-circle me-1"></i> Agregar Trabajo
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<!-- MODAL: Agregar Dispositivo -->
<div class="modal fade" id="modalAgregarDispositivo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAgregarDispositivo">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_servicio" value="<?php echo $id_servicio; ?>">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title"><i class="bi bi-laptop me-1"></i> Agregar Dispositivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Dispositivo *</label>
                        <input type="text" class="form-control" name="dispositivo" placeholder="Ej: Laptop HP, PC de Escritorio" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" class="form-control" name="marca" placeholder="Ej: HP, Dell">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" class="form-control" name="modelo" placeholder="Ej: Pavilion 15">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">N&uacute;mero de Serie</label>
                            <input type="text" class="form-control" name="numero_serie" placeholder="Opcional">
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

<!-- MODAL: Editar Dispositivo -->
<div class="modal fade" id="modalEditarDispositivo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarDispositivo">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_dispositivo" id="edit_disp_id">
                <input type="hidden" name="id_servicio" value="<?php echo $id_servicio; ?>">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title"><i class="bi bi-laptop me-1"></i> Editar Dispositivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Dispositivo *</label>
                        <input type="text" class="form-control" name="dispositivo" id="edit_disp_nombre" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" class="form-control" name="marca" id="edit_disp_marca">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" class="form-control" name="modelo" id="edit_disp_modelo">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">N&uacute;mero de Serie</label>
                            <input type="text" class="form-control" name="numero_serie" id="edit_disp_serie">
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

<!-- MODAL: Agregar Trabajo -->
<div class="modal fade" id="modalAgregarTrabajo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAgregarTrabajo">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_dispositivo" id="modalAgregarTrabajo_id_dispositivo">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title"><i class="bi bi-wrench me-1"></i> Agregar Trabajo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipo de Trabajo *</label>
                        <select name="tipo_trabajo" class="form-select" required>
                            <option value="" disabled selected>[Seleccione un tipo]</option>
                            <option value="General">General</option>
                            <option value="Revisi&oacute;n">Revisi&oacute;n</option>
                            <option value="Mantenimiento HW">Mantenimiento HW</option>
                            <option value="Mantenimiento SW">Mantenimiento SW</option>
                            <option value="Instalaci&oacute;n">Instalaci&oacute;n</option>
                            <option value="Formateo">Formateo</option>
                            <option value="Limpieza">Limpieza</option>
                            <option value="Diagn&oacute;stico">Diagn&oacute;stico</option>
                            <option value="Reparaci&oacute;n">Reparaci&oacute;n</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Problema Reportado *</label>
                        <textarea class="form-control" name="problema_reportado" rows="3" placeholder="Describa el problema que presenta el equipo" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas Internas</label>
                        <textarea class="form-control" name="notas_internas" rows="2" placeholder="Notas privadas del t&eacute;cnico (opcional)"></textarea>
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

<!-- MODAL: Cuenta de Cobro -->
<div class="modal fade" id="modalCuentaCobro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../reports/pdf_cuenta_cobro.php" method="POST" target="_blank">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_servicio" value="<?php echo $id_servicio; ?>">
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

<!-- MODAL: Mano de Obra General -->
<div class="modal fade" id="modalManoObraTotal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formManoObraGeneral">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_servicio" value="<?php echo $id_servicio; ?>">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a2035, #2d3a52); color: #fff;">
                    <h5 class="modal-title"><i class="bi bi-cash-stack me-1"></i> Mano de Obra Total</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Costo de mano de obra <strong>total del servicio</strong> (una sola vez, no por equipo).</p>
                    <div class="mb-3">
                        <label class="form-label">Costo total ($)</label>
                        <input type="number" class="form-control" name="costo" step="1" min="0" required placeholder="Ej: 50000" value="<?php echo intval($serv['mano_obra_costo'] ?? 0); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark rounded-pill">
                        <i class="bi bi-check-circle me-1"></i> Aplicar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts JavaScript: AJAX para CRUD y persistencia del acordeón -->
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
var csrfToken = document.querySelector('input[name="csrf_token"]').value;
var idServicio = <?php echo $id_servicio; ?>;

// Muestra una alerta flotante temporal en la pantalla
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

function recargarEnMismoTab() {
    location.reload(true);
}

// AJAX: Agregar Dispositivo
document.getElementById('formAgregarDispositivo').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Agregando...';

    fetch('../controllers/procesar_agregar_dispositivo.php', {
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
        alert('Error de conexi\u00f3n');
    });
});

// Editar Dispositivo: abrir modal con datos
function editarDispositivo(data) {
    document.getElementById('edit_disp_id').value = data.ID_dispositivo;
    document.getElementById('edit_disp_nombre').value = data.dispositivo || '';
    document.getElementById('edit_disp_marca').value = data.marca || '';
    document.getElementById('edit_disp_modelo').value = data.modelo || '';
    document.getElementById('edit_disp_serie').value = data.numero_serie || '';
    new bootstrap.Modal(document.getElementById('modalEditarDispositivo')).show();
}

// AJAX: Editar Dispositivo
document.getElementById('formEditarDispositivo').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;

    fetch('../controllers/procesar_edicion_dispositivo.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
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
    .catch(function() {
        btn.disabled = false;
        alert('Error de conexi\u00f3n');
    });
});

// Eliminar Dispositivo
function eliminarDispositivo(id) {
    if (!confirm('\u00bfEst\u00e1 seguro de eliminar este dispositivo? Se eliminar\u00e1n todos sus trabajos asociados.')) return;

    var fd = new FormData();
    fd.append('csrf_token', csrfToken);
    fd.append('id_dispositivo', id);

    fetch('../controllers/eliminar_dispositivo.php', {
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
    .catch(function() { alert('Error de conexi\u00f3n'); });
}

// AJAX: Agregar Trabajo
document.getElementById('formAgregarTrabajo').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Agregando...';

    fetch('../controllers/procesar_nuevo_trabajo.php', {
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
        alert('Error de conexi\u00f3n');
    });
});

// Eliminar Trabajo
function eliminarTrabajo(id) {
    if (!confirm('\u00bfEst\u00e1 seguro de eliminar este trabajo?')) return;

    var fd = new FormData();
    fd.append('csrf_token', csrfToken);
    fd.append('id_trabajo', id);

    fetch('../controllers/eliminar_trabajo.php', {
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
    .catch(function() { alert('Error de conexi\u00f3n'); });
}

// AJAX: Mano de Obra General
document.getElementById('formManoObraGeneral').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Aplicando...';

    fetch('../controllers/procesar_mano_obra_general.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Aplicar';
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
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Aplicar';
        alert('Error de conexi\u00f3n');
    });
});

// --- Persistir acordeón abierto (guarda en localStorage y restaura al recargar) ---
(function(){
    var KEY = 'servicio_acordeon_' + <?php echo $serv['ID_servicio']; ?>;
    var accordion = document.getElementById('accordionDispositivos');
    if (!accordion) return;

    // Al abrir un acordeón, guardamos su ID
    accordion.addEventListener('show.bs.collapse', function(e){
        localStorage.setItem(KEY, e.target.id);
    });
    // Al cerrar todos, limpiamos
    accordion.addEventListener('hide.bs.collapse', function(e){
        // Si el que se cierra es el que estaba guardado y no hay otro abierto
        setTimeout(function(){
            var open = accordion.querySelector('.accordion-collapse.show');
            if (!open) localStorage.removeItem(KEY);
        }, 50);
    });

    // Restaurar al cargar
    var guardado = localStorage.getItem(KEY);
    if (guardado) {
        var target = document.getElementById(guardado);
        if (target) {
            new bootstrap.Collapse(target, {toggle: false}).show();
        }
    }
})();
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
