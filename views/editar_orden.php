<?php 
include("../config/conexion.php");
$codigo = $_REQUEST['id'];

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='orden_compra.php';</script>";
    exit();
}

// Consulta proveedor
$resultado_prov = $conn->query("SELECT * FROM proveedor ORDER BY nombre_proveedor");

// Consulta productos disponibles
$resultado_prod = $conn->query("SELECT * FROM producto ORDER BY nombre");

// Consulta orden principal
$sql_orden = "SELECT * FROM orden_compra WHERE ID_orden_compra = '$codigo'";
$resultado_orden = $conn->query($sql_orden);
$orden = $resultado_orden->fetch_assoc();

// Consulta productos de la orden
$sql_detalles = "SELECT doc.*, p.nombre as nombre_producto
                 FROM detalle_orden_compra doc
                 JOIN producto p ON doc.ID_producto = p.ID_producto
                 WHERE doc.ID_orden_compra = '$codigo'";
$resultado_detalles = $conn->query($sql_detalles);

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
    <title>Editar Orden de Compra</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <style>
        /* Estilos específicos para productos múltiples */
        .productos-container {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .productos-container h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }
        
        .producto-item {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 2px solid #2bb8ca;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .producto-item h3 {
            margin: 0 0 15px 0;
            color: #2bb8ca;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 2px solid #2bb8ca;
            padding-bottom: 8px;
        }
        
        .producto-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 3px;
            align-items: end;
        }
        
        .producto-row > div {
            display: flex;
            flex-direction: column;
        }
        
        .producto-row label {
            display: block;
            margin-bottom: 3px;
            font-weight: 400;
            color: #495057;
            font-size: 14px;
        }
        
        .producto-row select,
        .producto-row input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .producto-row select:focus,
        .producto-row input[type="number"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        .btn-eliminar {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
            width: 100%;
        }
        
        .btn-eliminar:hover {
            background: #c82333;
        }
        
        .btn-agregar {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            margin-top: 15px;
            transition: background 0.3s;
            display: inline-block;
        }
        
        .btn-agregar:hover {
            background: #218838;
        }
        
        .subtotal-display {
            text-align: right;
            font-weight: bold;
            color: #2bb8ca;
            margin-top: 10px;
            font-size: 16px;
            padding: 10px;
            background: #e7f3ff;
            border-radius: 4px;
        }
        
        .total-general {
            background: white;
            color: rgb(0, 0, 0);
            border: 2px solid #2bb8ca;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .producto-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .btn-eliminar {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body class="registro_usuario">
<div class="registrarse">
    <h1>Modificar información de la orden</h1>

    <form action="../controllers/procesar_edicion_orden.php" method="POST" class="formulario_registro" id="formOrden">
        
        <input type="hidden" name="id_orden" value="<?php echo $orden['ID_orden_compra']; ?>">
        
        <div class="datos_registro">
            <label for="id_orden">ID Orden</label>
            <input type="text" id="id_orden" value="<?php echo $orden['ID_orden_compra']; ?>" readonly>
        </div>

        <div class="datos_registro">
            <label for="proveedor">Proveedor</label>
            <select name="proveedor" id="proveedor" required>
                <option value="" disabled>[Seleccione un proveedor]</option>
                <?php
                while ($fila_prov = $resultado_prov->fetch_assoc()) {
                    $selected = ($fila_prov['ID_proveedor'] == $orden['ID_proveedor']) ? "selected" : "";
                    echo "<option value='{$fila_prov['ID_proveedor']}' $selected>{$fila_prov['nombre_proveedor']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="datos_registro">
            <label for="estado">Estado</label>
            <select name="estado" id="estado" required>
                <option value="Aprobado" <?= $orden['estado'] == 'Aprobado' ? 'selected' : '' ?>>Aprobado</option>
                <option value="Procesando" <?= $orden['estado'] == 'Procesando' ? 'selected' : '' ?>>Procesando</option>
                <option value="cancelado" <?= $orden['estado'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>
        </div>

        <div class="datos_registro">
            <label for="fecha">Fecha</label>
            <input type="date" name="fecha" id="fecha" value="<?php echo $orden['fecha']; ?>" required>
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

        <button type="submit" class="boton_registro">Guardar cambios</button>
    </form>
</div>

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