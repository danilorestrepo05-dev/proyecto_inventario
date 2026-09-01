<?php
include("../config/conexion.php");
session_start();
include('../config/csrf.php');
if (!isset($_SESSION['usuario'])) { header("Location: ../login.php"); exit(); }

$elaborado_por = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Nueva Cotizaci&oacute;n</title>
    <link rel="icon" type="image/png" href="../assets/img/compumasterldlogo.png">
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card" style="max-width: 850px;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Nueva Cotizaci&oacute;n</h5>
            <a href="reparaciones.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../reports/pdf_cotizacion.php" method="POST" target="_blank">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="elaborado_por" value="<?php echo htmlspecialchars($elaborado_por); ?>">

                <!-- Sección 1: Datos del cliente destinatario -->
                <h6 class="text-uppercase fw-bold text-secondary mb-3">
                    <i class="bi bi-person-lines-fill me-1"></i> Cliente Destinatario
                </h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre del Cliente *</label>
                        <input type="text" class="form-control" name="cliente_nombre" placeholder="Ej: Juan P&eacute;rez o Empresa XYZ" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tipo de Documento</label>
                        <select name="cliente_tipo_identificacion" class="form-select">
                            <option value="cc" selected>C&eacute;dula de Ciudadan&iacute;a (CC)</option>
                            <option value="nit">NIT</option>
                            <option value="ti">Tarjeta de Identidad (TI)</option>
                            <option value="ce">C&eacute;dula de Extranjer&iacute;a (CE)</option>
                            <option value="pa">Pasaporte</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">N&uacute;mero</label>
                        <input type="text" class="form-control" name="cliente_identificacion" placeholder="Op. Ej: 1041149861-6">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Elaborado por</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($elaborado_por); ?>" disabled>
                </div>

                <!-- Sección 2: Equipo a cotizar -->
                <h6 class="text-uppercase fw-bold text-secondary mb-3">
                    <i class="bi bi-laptop me-1"></i> Equipo / Servicio a Cotizar
                </h6>
                <div class="mb-3">
                    <label class="form-label">Dispositivo / Descripci&oacute;n del servicio</label>
                    <input type="text" class="form-control" name="dispositivo" placeholder="Ej: Laptop HP Pavilion 15, Mantenimiento trimestral">
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Marca</label>
                        <input type="text" class="form-control" name="marca" placeholder="Ej: HP, Dell, ASUS">
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
                <div class="mb-4">
                    <label class="form-label">Motivo / Alcance del trabajo *</label>
                    <textarea class="form-control" name="alcance" rows="3" placeholder="Describa el trabajo o problema a cotizar" required></textarea>
                </div>

                <!-- Sección 3: Ítems de la cotización -->
                <h6 class="text-uppercase fw-bold text-secondary mb-3">
                    <i class="bi bi-list-check me-1"></i> Conceptos / &Iacute;tems
                </h6>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered align-middle" id="tabla_items">
                        <thead class="table-secondary">
                            <tr>
                                <th style="width: 50%;">Concepto / Descripci&oacute;n</th>
                                <th style="width: 12%;">Cantidad</th>
                                <th style="width: 20%;">Valor Unitario</th>
                                <th style="width: 18%;">Valor Total</th>
                                <th style="width: 4%;"></th>
                            </tr>
                        </thead>
                        <tbody id="items_body">
                            <tr class="fila-item">
                                <td><input type="text" class="form-control form-control-sm item-concepto" name="item_concepto[]" placeholder="Ej: Mano de obra, Repuesto, Programa..." required></td>
                                <td><input type="number" class="form-control form-control-sm item-cantidad" name="item_cantidad[]" min="1" value="1" required></td>
                                <td><input type="number" class="form-control form-control-sm item-valor" name="item_valor[]" min="0" step="any" placeholder="0" required></td>
                                <td class="item-total text-end">$0</td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarFila(this)"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mb-4">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="agregarFila()">
                        <i class="bi bi-plus-circle me-1"></i> Agregar Item
                    </button>
                </div>

                <!-- Sección 4: Descuento, envío y vigencia -->
                <h6 class="text-uppercase fw-bold text-secondary mb-3">
                    <i class="bi bi-calculator me-1"></i> Descuento, Env&iacute;o y Vigencia
                </h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Descuento (tipo)</label>
                        <select name="descuento_tipo" id="descuento_tipo" class="form-select">
                            <option value="fijo">Valor fijo</option>
                            <option value="porcentaje">Porcentaje (%)</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Valor</label>
                        <input type="number" class="form-control" name="descuento_valor" id="descuento_valor" min="0" step="any" value="0" placeholder="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Valor del Env&iacute;o</label>
                        <input type="number" class="form-control" name="envio_valor" id="envio_valor" min="0" step="any" value="0" placeholder="0">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <label class="form-label">Vigencia (d&iacute;as)</label>
                        <input type="number" class="form-control" name="vigencia_dias" id="vigencia_dias" min="1" value="15">
                    </div>
                </div>

                <!-- Sección 5: Observaciones y total -->
                <h6 class="text-uppercase fw-bold text-secondary mb-3">
                    <i class="bi bi-journal-text me-1"></i> Observaciones
                </h6>
                <div class="mb-4">
                    <textarea class="form-control" name="observaciones" rows="2" placeholder="Condiciones, garantías, notas adicionales (opcional)"></textarea>
                </div>

                <!-- Resumen de totales en vivo -->
                <div class="card bg-light mb-4">
                    <div class="card-body text-end">
                        <div>Subtotal: <strong id="resumen_subtotal" class="ms-2">$0</strong></div>
                        <div>Descuento: <strong id="resumen_descuento" class="ms-2 text-danger">-$0</strong></div>
                        <div>Env&iacute;o: <strong id="resumen_envio" class="ms-2">$0</strong></div>
                        <hr class="my-2">
                        <div class="fs-5">TOTAL: <strong id="resumen_total" class="ms-2">$0</strong></div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="window.location.href='reparaciones.php'">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Generar Cotizaci&oacute;n
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
<script>
// Agrega una fila nueva de item a la tabla
function agregarFila() {
    var tbody = document.getElementById('items_body');
    var fila = document.createElement('tr');
    fila.className = 'fila-item';
    fila.innerHTML = '<td><input type="text" class="form-control form-control-sm item-concepto" name="item_concepto[]" placeholder="Ej: Mano de obra, Repuesto, Programa..." required></td>' +
                    '<td><input type="number" class="form-control form-control-sm item-cantidad" name="item_cantidad[]" min="1" value="1" required></td>' +
                    '<td><input type="number" class="form-control form-control-sm item-valor" name="item_valor[]" min="0" step="any" placeholder="0" required></td>' +
                    '<td class="item-total text-end">$0</td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarFila(this)"><i class="bi bi-trash"></i></button></td>';
    tbody.appendChild(fila);
    fila.querySelector('.item-concepto').focus();
    recalcular();
}

// Elimina una fila de item (mantiene siempre al menos una)
function eliminarFila(btn) {
    var filas = document.querySelectorAll('#items_body .fila-item');
    if (filas.length <= 1) {
        return;
    }
    btn.closest('tr').remove();
    recalcular();
}

// Formatea números como moneda colombiana
function formatearMoneda(valor) {
    return '$' + Math.round(valor).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Recalcula totales de la cotización en vivo
function recalcular() {
    var filas = document.querySelectorAll('#items_body .fila-item');
    var subtotal = 0;
    filas.forEach(function(fila) {
        var cant = parseFloat(fila.querySelector('.item-cantidad').value) || 0;
        var val = parseFloat(fila.querySelector('.item-valor').value) || 0;
        var linea = cant * val;
        subtotal += linea;
        fila.querySelector('.item-total').textContent = formatearMoneda(linea);
    });

    var tipo = document.getElementById('descuento_tipo').value;
    var descValor = parseFloat(document.getElementById('descuento_valor').value) || 0;
    var descuento = 0;
    if (tipo === 'porcentaje') {
        descuento = subtotal * (descValor / 100);
    } else {
        descuento = Math.min(descValor, subtotal);
    }

    var envio = parseFloat(document.getElementById('envio_valor').value) || 0;
    var total = subtotal - descuento + envio;

    document.getElementById('resumen_subtotal').textContent = formatearMoneda(subtotal);
    document.getElementById('resumen_descuento').textContent = '-' + formatearMoneda(descuento);
    document.getElementById('resumen_envio').textContent = formatearMoneda(envio);
    document.getElementById('resumen_total').textContent = formatearMoneda(total);
}

// Vincula eventos de recálculo en vivo
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('item-cantidad') || e.target.classList.contains('item-valor') || e.target.id === 'descuento_valor' || e.target.id === 'envio_valor') {
        recalcular();
    }
});
document.getElementById('descuento_tipo').addEventListener('change', recalcular);
</script>
</body>
</html>