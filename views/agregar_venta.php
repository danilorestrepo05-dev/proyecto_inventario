<?php 
include("../config/conexion.php");
session_start();
include('../config/csrf.php');
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$clientes = $conn->query("SELECT * FROM cliente");
$productos = $conn->query("SELECT * FROM producto");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Nueva Venta</title>
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card" style="max-width: 800px;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Nueva Venta</h5>
            <a href="ventas.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../controllers/procesar_nueva_venta.php" method="POST" id="formOrden">
                <?php echo csrf_field(); ?>

                <!-- Cliente -->
                <div class="mb-3">
                    <label for="cliente" class="form-label">Cliente</label>
                    <select name="cliente" id="cliente" class="form-select" required>
                        <option value="0" selected>Cliente general (sin registro)</option>
                        <?php 
                        $clientes->data_seek(0);
                        while ($cli = $clientes->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($cli['ID_cliente']) . "'>" . htmlspecialchars($cli['nombre'] . ' ' . $cli['apellido']) . "</option>";
                        } ?>
                    </select>
                </div>

                <!-- Estado -->
                <div class="mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select" required>
                        <option value="" disabled selected>[Seleccione estado]</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>

                <!-- Fecha -->
                <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" required>
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
                        <i class="bi bi-check-circle me-1"></i> Guardar Venta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    let contadorProductos = 0;
    const productos = <?php 
        $productos->data_seek(0);
        $arr = [];
        while ($p = $productos->fetch_assoc()) {
            $arr[] = $p;
        }
        echo json_encode($arr);
        mysqli_close($conn);
    ?>;

    function agregarProducto() {
        contadorProductos++;
        const div = document.createElement('div');
        div.className = 'producto-item';
        div.id = `producto-${contadorProductos}`;
        
        let optionsHTML = '<option value="" disabled selected>[Seleccione producto]</option>';
        productos.forEach(p => {
            optionsHTML += `<option value="${p.ID_producto}" data-precio="${p.precio}" data-stock="${p.stock}">${p.nombre} (Stock: ${p.stock})</option>`;
        });

        div.innerHTML = `
            <h3>Producto #${contadorProductos}</h3>
            <div class="producto-row">
                <div>
                    <label>Producto</label>
                    <select name="productos[${contadorProductos}][id]" class="producto-select" required onchange="cargarPrecioVenta(${contadorProductos})">
                        ${optionsHTML}
                    </select>
                </div>
                <div>
                    <label>Cantidad</label>
                    <input type="number" name="productos[${contadorProductos}][cantidad]" class="cantidad-input" min="1" value="1" required onchange="calcularSubtotal(${contadorProductos})">
                </div>
                <div>
                    <label>Precio Unitario</label>
                    <input type="number" step="0.01" name="productos[${contadorProductos}][precio]" class="precio-input" min="0" value="0" required readonly onchange="calcularSubtotal(${contadorProductos})">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="button" class="btn-eliminar" onclick="eliminarProducto(${contadorProductos})">Eliminar</button>
                </div>
            </div>
            <div class="subtotal-display">Subtotal: $<span id="subtotal-${contadorProductos}">0</span></div>
        `;
        
        document.getElementById('productosLista').appendChild(div);
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

    document.getElementById('formOrden').addEventListener('submit', function(e) {
        const numProductos = document.querySelectorAll('.producto-item').length;
        if (numProductos === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto a la venta');
        }
    });

    agregarProducto();
</script>
</body>
</html>
