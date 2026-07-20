<?php 
include("../config/conexion.php");
session_start();
include('../config/csrf.php');
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}


$proveedores = $conn->query("SELECT * FROM proveedor WHERE activo = 1");
$productos = $conn->query("SELECT * FROM producto WHERE activo = 1");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Nueva Orden de Compra</title>
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card" style="max-width: 800px;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cart3 me-2"></i>Nueva Orden de Compra</h5>
            <a href="orden_compra.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../controllers/procesar_nueva_orden.php" method="POST" id="formOrden">
                <?php echo csrf_field(); ?>

                <!-- Proveedor -->
                <div class="mb-3">
                    <label for="proveedor" class="form-label">Proveedor</label>
                    <select name="proveedor" id="proveedor" class="form-select" required>
                        <option value="" disabled selected>[Seleccione una opción]</option>
                        <?php 
                        $proveedores->data_seek(0);
                        while ($prov = $proveedores->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($prov['ID_proveedor']) . "'>" . htmlspecialchars($prov['nombre_proveedor']) . "</option>";
                        } ?>
                    </select>
                </div>

                <!-- Estado -->
                <div class="mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select" required>
                        <option value="" disabled selected>[Seleccione una opción]</option>
                        <option value="Aprobado">Aprobado</option>
                        <option value="Procesando">Procesando</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>

                <!-- Fecha -->
                <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" required>
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
                        <i class="bi bi-check-circle me-1"></i> Guardar Orden
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
            optionsHTML += `<option value="${p.ID_producto}">${p.nombre} (Stock: ${p.stock})</option>`;
        });

        div.innerHTML = `
            <h3>Producto #${contadorProductos}</h3>
            <div class="producto-row">
                <div>
                    <label>Producto</label>
                    <select name="productos[${contadorProductos}][id]" class="producto-select" required onchange="calcularTotales()">
                        ${optionsHTML}
                    </select>
                </div>
                <div>
                    <label>Cantidad</label>
                    <input type="number" name="productos[${contadorProductos}][cantidad]" class="cantidad-input" min="1" value="1" required onchange="calcularSubtotal(${contadorProductos})">
                </div>
                <div>
                    <label>Precio Unitario</label>
                    <input type="number" step="0.01" name="productos[${contadorProductos}][precio]" class="precio-input" min="0" value="0" required onchange="calcularSubtotal(${contadorProductos})">
                </div> 
            </div>
            <div class="subtotal-display">Subtotal: $<span id="subtotal-${contadorProductos}">0</span></div>
            <div>
                    <label>&nbsp;</label>
                    <button type="button" class="btn-eliminar" onclick="eliminarProducto(${contadorProductos})">Eliminar</button>
                </div>
        `;
        
        document.getElementById('productosLista').appendChild(div);
        
        if (contadorProductos === 1) {
            calcularTotales();
        }
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

    // Validar que haya al menos un producto
    document.getElementById('formOrden').addEventListener('submit', function(e) {
        const numProductos = document.querySelectorAll('.producto-item').length;
        if (numProductos === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto a la orden');
            return;
        }
        localStorage.removeItem('svt_orden_draft');
    });

    // === LOCALSTORAGE: Guardar/Cargar borrador ===
    const STORAGE_KEY = 'svt_orden_draft';

    function guardarBorrador() {
        const filas = [];
        document.querySelectorAll('.producto-item').forEach(item => {
            filas.push({
                producto: item.querySelector('.producto-select').value,
                cantidad: item.querySelector('.cantidad-input').value,
                precio: item.querySelector('.precio-input').value
            });
        });
        const data = {
            proveedor: document.getElementById('proveedor').value,
            estado: document.getElementById('estado').value,
            fecha: document.getElementById('fecha').value,
            productos: filas
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    function cargarBorrador() {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return false;
        const data = JSON.parse(raw);
        if (data.proveedor) document.getElementById('proveedor').value = data.proveedor;
        if (data.estado) document.getElementById('estado').value = data.estado;
        if (data.fecha) document.getElementById('fecha').value = data.fecha;
        if (data.productos && data.productos.length > 0) {
            data.productos.forEach((p, i) => {
                agregarProducto();
                const item = document.getElementById(`producto-${contadorProductos}`);
                if (item) {
                    const sel = item.querySelector('.producto-select');
                    sel.value = p.producto;
                    item.querySelector('.cantidad-input').value = p.cantidad;
                    item.querySelector('.precio-input').value = p.precio;
                    calcularSubtotal(contadorProductos);
                }
            });
        }
        return true;
    }

    document.getElementById('proveedor').addEventListener('change', guardarBorrador);
    document.getElementById('estado').addEventListener('change', guardarBorrador);
    document.getElementById('fecha').addEventListener('change', guardarBorrador);

    const observer = new MutationObserver(() => {
        document.querySelectorAll('.producto-item').forEach(item => {
            item.querySelector('.producto-select').removeEventListener('change', guardarBorrador);
            item.querySelector('.producto-select').addEventListener('change', guardarBorrador);
            item.querySelector('.cantidad-input').removeEventListener('input', guardarBorrador);
            item.querySelector('.cantidad-input').addEventListener('input', guardarBorrador);
        });
    });
    observer.observe(document.getElementById('productosLista'), { childList: true });

    document.addEventListener('DOMContentLoaded', function() {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            if (confirm('Tienes una orden sin guardar. ¿Deseas restaurarla?')) {
                cargarBorrador();
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
        }
    });

    // Agregar el primer producto automáticamente
    agregarProducto();
</script>
</body>
</html>
