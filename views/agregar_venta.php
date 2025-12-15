<?php 
include("../config/conexion.php");
session_start();
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
    <title>Agregar nueva venta</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <style>
        /* Estilos específicos para productos múltiples */
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
            width: 100%;
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
            border-color: #2bb8ca;
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
            color: color: rgb(0, 0, 0);
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
        <h1>Información de la venta a realizar</h1>
        <form action="../controllers/procesar_nueva_venta.php" method="POST" class="formulario_registro" id="formOrden">

            <!-- Cliente -->
            <div class="datos_registro">
                <label for="cliente">Cliente</label>
                <select name="cliente" id="cliente" required>
                    <option value="" disabled selected>[Seleccione un cliente]</option>
                    <?php 
                    $clientes->data_seek(0);
                    while ($cli = $clientes->fetch_assoc()) {
                        echo "<option value='{$cli['ID_cliente']}'>{$cli['nombre']} {$cli['apellido']}</option>";
                    } ?>
                </select>
            </div>

            <!-- Estado -->
            <div class="datos_registro">
                <label for="estado">Estado</label>
                <select name="estado" id="estado" required>
                    <option value="" disabled selected>[Seleccione estado]</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="completada">Completada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>

            <!-- Fecha -->
            <div class="datos_registro">
                <label for="fecha">Fecha</label>
                <input type="date" name="fecha" id="fecha" required>
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

            <button type="submit" class="boton_registro">Guardar venta</button>
        </form>
    </div>

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