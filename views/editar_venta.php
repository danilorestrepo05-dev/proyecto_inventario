<?php 
include("../config/conexion.php");
session_start();
include('../config/csrf.php');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='ventas.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);

// Consulta clientes
$resultado_cli = $conn->query("SELECT * FROM cliente WHERE activo = 1 ORDER BY nombre");

// Consulta productos disponibles
$resultado_prod = $conn->query("SELECT * FROM producto WHERE activo = 1 ORDER BY nombre");

// Consulta venta principal
$sql_venta = "SELECT * FROM orden_venta WHERE ID_orden_venta = ?";
$stmt_venta = $conn->prepare($sql_venta);
$stmt_venta->bind_param("i", $codigo);
$stmt_venta->execute();
$venta = $stmt_venta->get_result()->fetch_assoc();
$stmt_venta->close();

// Consulta productos de la venta
$sql_detalles = "SELECT dov.*, p.nombre as nombre_producto
                 FROM detalle_orden_venta dov
                 JOIN producto p ON dov.ID_producto = p.ID_producto
                 WHERE dov.ID_orden_venta = ?";
$stmt_det = $conn->prepare($sql_detalles);
$stmt_det->bind_param("i", $codigo);
$stmt_det->execute();
$resultado_detalles = $stmt_det->get_result();
$stmt_det->close();

// RESTRICCIÓN: Solo permitir editar ventas pendientes
if ($venta['estado'] !== 'pendiente') {
    mysqli_close($conn);
    
    $estado_actual = ucfirst($venta['estado']);
    $id_venta = $venta['ID_orden_venta'];
    
    echo "<script>
            alert('⚠️ NO SE PUEDE EDITAR ESTA VENTA\\n\\n' +
                  'Venta #$id_venta\\n' +
                  'Estado actual: $estado_actual\\n\\n' +
                  'Solo se pueden editar ventas en estado PENDIENTE.');
            window.location='ventas.php';
          </script>";
    exit();
}

// Convertir detalles a array para JavaScript
$detalles_array = [];
while ($detalle = $resultado_detalles->fetch_assoc()) {
    $detalles_array[] = $detalle;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Editar Venta</title>
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card" style="max-width: 800px;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editar Venta</h5>
            <a href="ventas.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../controllers/procesar_edicion_venta.php" method="POST" id="formOrden">
                <?php echo csrf_field(); ?>
                
                <input type="hidden" name="id_orden" value="<?php echo htmlspecialchars($venta['ID_orden_venta']); ?>">
                
                <div class="mb-3">
                    <label for="id_orden" class="form-label">ID Venta</label>
                    <input type="text" id="id_orden" class="form-control" value="<?php echo htmlspecialchars($venta['ID_orden_venta']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="cliente" class="form-label">Cliente</label>
                    <select name="cliente" id="cliente" class="form-select" required>
                        <option value="0">Cliente general (sin registro)</option>
                        <?php
                        while ($fila_cli = $resultado_cli->fetch_assoc()) {
                            $selected = ($fila_cli['ID_cliente'] == $venta['ID_cliente']) ? "selected" : "";
                            echo "<option value='" . htmlspecialchars($fila_cli['ID_cliente']) . "' $selected>" . htmlspecialchars($fila_cli['nombre'] . ' ' . $fila_cli['apellido']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select" required>
                        <option value="pendiente" <?= $venta['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="completada" <?= $venta['estado'] == 'completada' ? 'selected' : '' ?>>Completada</option>
                        <option value="cancelada" <?= $venta['estado'] == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="<?php echo htmlspecialchars($venta['fecha']); ?>" required>
                </div>

                <!-- Contenedor de productos -->
                <div class="productos-container">
                    <h2>Productos de la venta</h2>
                    <div id="productosLista"></div>
                    <button type="button" class="btn-agregar" onclick="agregarProducto()">+ Agregar Producto</button>
                </div>

                <!-- Total General -->
                <div class="total-general">
                    Total de la Venta: $<span id="totalGeneral">0</span>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-circle me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    let contadorProductos = 0;
    const productosDisponibles = <?php 
        $resultado_prod->data_seek(0);
        $arr = [];
        while ($p = $resultado_prod->fetch_assoc()) {
            $arr[] = $p;
        }
        echo json_encode($arr);
    ?>;
    
    const detallesExistentes = <?php echo json_encode($detalles_array); ?>;

    function agregarProducto(datosExistentes = null) {
        contadorProductos++;
        const div = document.createElement('div');
        div.className = 'producto-item';
        div.id = `producto-${contadorProductos}`;
        
        let optionsHTML = '<option value="" disabled>[Seleccione producto]</option>';
        productosDisponibles.forEach(p => {
            const selected = datosExistentes && p.ID_producto == datosExistentes.ID_producto ? 'selected' : '';
            optionsHTML += `<option value="${p.ID_producto}" data-precio="${p.precio}" data-stock="${p.stock}" ${selected}>${p.nombre} (Stock: ${p.stock})</option>`;
        });

        const cantidad = datosExistentes ? datosExistentes.cantidad : 1;
        const precio = datosExistentes ? datosExistentes.precio_unitario : 0;
        const idDetalle = datosExistentes ? datosExistentes.ID_detalle_venta : '';

        div.innerHTML = `
            <h3>Producto #${contadorProductos}</h3>
            <input type="hidden" name="productos[${contadorProductos}][id_detalle]" value="${idDetalle}">
            <div class="producto-row">
                <div>
                    <label>Producto</label>
                    <select name="productos[${contadorProductos}][id]" class="producto-select" required onchange="cargarPrecioVenta(${contadorProductos})">
                        ${optionsHTML}
                    </select>
                </div>
                <div>
                    <label>Cantidad</label>
                    <input type="number" name="productos[${contadorProductos}][cantidad]" class="cantidad-input" min="1" value="${cantidad}" required onchange="calcularSubtotal(${contadorProductos})">
                </div>
                <div>
                    <label>Precio Unitario</label>
                    <input type="number" step="0.01" name="productos[${contadorProductos}][precio]" class="precio-input" min="0" value="${precio}" required readonly onchange="calcularSubtotal(${contadorProductos})">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="button" class="btn-eliminar" onclick="eliminarProducto(${contadorProductos})">Eliminar</button>
                </div>
            </div>
            <div class="subtotal-display">Subtotal: $<span id="subtotal-${contadorProductos}">0</span></div>
        `;
        
        document.getElementById('productosLista').appendChild(div);
        calcularSubtotal(contadorProductos);
    }

    function cargarPrecioVenta(id) {
        const producto = document.getElementById(`producto-${id}`);
        const select = producto.querySelector('.producto-select');
        const precioInput = producto.querySelector('.precio-input');
        const selectedOption = select.options[select.selectedIndex];
        const precio = selectedOption.getAttribute('data-precio');
        precioInput.value = precio || 0;
        calcularSubtotal(id);
    }

    function eliminarProducto(id) {
        const elemento = document.getElementById(`producto-${id}`);
        if (elemento) {
            const numProductos = document.querySelectorAll('.producto-item').length;
            if (numProductos <= 1) {
                alert('Debe haber al menos un producto en la venta');
                return;
            }
            elemento.remove();
            calcularTotales();
        }
    }

    function calcularSubtotal(id) {
        const producto = document.getElementById(`producto-${id}`);
        if (!producto) return;
        
        const cantidad = parseFloat(producto.querySelector('.cantidad-input').value) || 0;
        const precio = parseFloat(producto.querySelector('.precio-input').value) || 0;
        const subtotal = cantidad * precio;
        
        document.getElementById(`subtotal-${id}`).textContent = subtotal.toFixed(0);
        calcularTotales();
    }

    function calcularTotales() {
        let total = 0;
        const productos = document.querySelectorAll('.producto-item');
        
        productos.forEach(prod => {
            const cantidad = parseFloat(prod.querySelector('.cantidad-input').value) || 0;
            const precio = parseFloat(prod.querySelector('.precio-input').value) || 0;
            total += cantidad * precio;
        });
        
        document.getElementById('totalGeneral').textContent = total.toFixed(0);
    }

    // Validar que haya al menos un producto
    document.getElementById('formOrden').addEventListener('submit', function(e) {
        const numProductos = document.querySelectorAll('.producto-item').length;
        if (numProductos === 0) {
            e.preventDefault();
            alert('Debe tener al menos un producto en la venta');
        }
    });

    // Cargar productos existentes
    detallesExistentes.forEach(detalle => {
        agregarProducto(detalle);
    });
    
    // Si no hay productos, agregar uno vacío
    if (detallesExistentes.length === 0) {
        agregarProducto();
    }
</script>
</body>
</html>

<?php mysqli_close($conn); ?>
