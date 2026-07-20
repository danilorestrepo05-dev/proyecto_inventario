<?php 
include("../config/conexion.php");
session_start();
include('../config/csrf.php');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='orden_compra.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);

// Consulta proveedor
$resultado_prov = $conn->query("SELECT * FROM proveedor WHERE activo = 1 ORDER BY nombre_proveedor");

// Consulta productos disponibles
$resultado_prod = $conn->query("SELECT * FROM producto WHERE activo = 1 ORDER BY nombre");

// Consulta orden principal
$sql_orden = "SELECT * FROM orden_compra WHERE ID_orden_compra = ?";
$stmt_ord = $conn->prepare($sql_orden);
$stmt_ord->bind_param("i", $codigo);
$stmt_ord->execute();
$orden = $stmt_ord->get_result()->fetch_assoc();
$stmt_ord->close();

// Consulta productos de la orden
$sql_detalles = "SELECT doc.*, p.nombre as nombre_producto
                 FROM detalle_orden_compra doc
                 JOIN producto p ON doc.ID_producto = p.ID_producto
                 WHERE doc.ID_orden_compra = ?";
$stmt_det = $conn->prepare($sql_detalles);
$stmt_det->bind_param("i", $codigo);
$stmt_det->execute();
$resultado_detalles = $stmt_det->get_result();
$stmt_det->close();

// RESTRICCIÓN: Solo permitir editar órdenes en estado "Procesando"
if ($orden['estado'] !== 'Procesando') {
    mysqli_close($conn);
    
    $estado_actual = $orden['estado'];
    $id_orden = $orden['ID_orden_compra'];
    
    echo "<script>
            alert('⚠️ NO SE PUEDE EDITAR ESTA ORDEN\\n\\n' +
                  'Orden #$id_orden\\n' +
                  'Estado actual: $estado_actual\\n\\n' +
                  'Solo se pueden editar órdenes en estado PROCESANDO.');
            window.location='orden_compra.php';
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
    <title>Editar Orden de Compra</title>
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card" style="max-width: 800px;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editar Orden de Compra</h5>
            <a href="orden_compra.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../controllers/procesar_edicion_orden.php" method="POST" id="formOrden">
                <?php echo csrf_field(); ?>
                
                <input type="hidden" name="id_orden" value="<?php echo htmlspecialchars($orden['ID_orden_compra']); ?>">
                
                <div class="mb-3">
                    <label for="id_orden" class="form-label">ID Orden</label>
                    <input type="text" id="id_orden" class="form-control" value="<?php echo htmlspecialchars($orden['ID_orden_compra']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="proveedor" class="form-label">Proveedor</label>
                    <select name="proveedor" id="proveedor" class="form-select" required>
                        <option value="" disabled>[Seleccione un proveedor]</option>
                        <?php
                        while ($fila_prov = $resultado_prov->fetch_assoc()) {
                            $selected = ($fila_prov['ID_proveedor'] == $orden['ID_proveedor']) ? "selected" : "";
                            echo "<option value='" . htmlspecialchars($fila_prov['ID_proveedor']) . "' $selected>" . htmlspecialchars($fila_prov['nombre_proveedor']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select" required>
                        <option value="Aprobado" <?= $orden['estado'] == 'Aprobado' ? 'selected' : '' ?>>Aprobado</option>
                        <option value="Procesando" <?= $orden['estado'] == 'Procesando' ? 'selected' : '' ?>>Procesando</option>
                        <option value="cancelado" <?= $orden['estado'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="<?php echo htmlspecialchars($orden['fecha']); ?>" required>
                </div>

                <!-- Contenedor de productos -->
                <div class="productos-container">
                    <h2>Productos de la orden</h2>
                    <div id="productosLista"></div>
                    <button type="button" class="btn-agregar" onclick="agregarProducto()">+ Agregar Producto</button>
                </div>

                <!-- Total General -->
                <div class="total-general">
                    Total de la Orden: $<span id="totalGeneral">0</span>
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
            optionsHTML += `<option value="${p.ID_producto}" ${selected}>${p.nombre}</option>`;
        });

        const cantidad = datosExistentes ? datosExistentes.cantidad : 1;
        const precio = datosExistentes ? datosExistentes.precio_unitario_compra : 0;
        const idDetalle = datosExistentes ? datosExistentes.ID_detalle_orden : '';

        div.innerHTML = `
            <h3>Producto #${contadorProductos}</h3>
            <input type="hidden" name="productos[${contadorProductos}][id_detalle]" value="${idDetalle}">
            <div class="producto-row">
                <div>
                    <label>Producto</label>
                    <select name="productos[${contadorProductos}][id]" class="producto-select" required onchange="calcularTotales()">
                        ${optionsHTML}
                    </select>
                </div>
                <div>
                    <label>Cantidad</label>
                    <input type="number" name="productos[${contadorProductos}][cantidad]" class="cantidad-input" min="1" value="${cantidad}" required onchange="calcularSubtotal(${contadorProductos})">
                </div>
                <div>
                    <label>Precio Unitario</label>
                    <input type="number" step="0.01" name="productos[${contadorProductos}][precio]" class="precio-input" min="0" value="${precio}" required onchange="calcularSubtotal(${contadorProductos})">
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

    function eliminarProducto(id) {
        const elemento = document.getElementById(`producto-${id}`);
        if (elemento) {
            const numProductos = document.querySelectorAll('.producto-item').length;
            if (numProductos <= 1) {
                alert('Debe haber al menos un producto en la orden');
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
            alert('Debe tener al menos un producto en la orden');
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
