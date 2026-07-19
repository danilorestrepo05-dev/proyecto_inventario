# SGI - Sistema de Gestión de Inventarios
## Documentación del Proyecto

---

## Información General

| Campo | Valor |
|-------|-------|
| **Nombre** | SGI - Sistema de Gestión de Inventarios |
| **Tecnologías** | PHP 8.x, MySQL, Bootstrap 5, Bootstrap Icons |
| **Servidor** | XAMPP (Apache + MySQL) |
| **Arquitectura** | MVC (Modelo-Vista-Controlador) |
| **Fecha de inicio** | 2026 |

---

## Estructura del Proyecto

```
Proyecto_inventario/
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   └── estilos.css              ← Estilos personalizados
│   ├── img/
│   │   └── sgi-software (1).png     ← Logo del sistema
│   └── js/
│       ├── bootstrap.bundle.min.js
│       └── script.js                ← Funciones JS globales
├── config/
│   ├── conexion.php                 ← Conexión a MySQL
│   └── csrf.php                     ← [NUEVO] Helper CSRF (token, validate, field)
├── controllers/
│   ├── cerrar_sesion.php
│   ├── eliminar_cliente.php
│   ├── eliminar_orden.php
│   ├── eliminar_producto.php
│   ├── eliminar_proveedor.php
│   ├── eliminar_usuario.php         ← [SEGURIDAD] Validación Admin
│   ├── eliminar_venta.php
│   ├── procesar_cambiar_clave.php
│   ├── procesar_edicion_cliente.php
│   ├── procesar_edicion_orden.php
│   ├── procesar_edicion_producto.php
│   ├── procesar_edicion_proveedor.php
│   ├── procesar_edicion_usuario.php
│   ├── procesar_edicion_venta.php
│   ├── procesar_edicion.php
│   ├── procesar_login.php
│   ├── procesar_nueva_orden.php
│   ├── procesar_nueva_venta.php
│   ├── procesar_nuevo_cliente.php
│   ├── procesar_nuevo_producto.php
│   ├── procesar_nuevo_proveedor.php
│   └── procesar_registro.php        ← [SEGURIDAD] Validación Admin
├── fpdf/                             ← Librería para exportar PDF
├── reports/
│   ├── detalle_compra.php           ← [SEGURIDAD] htmlspecialchars en datos proveedor
│   ├── detalle_venta.php            ← [SEGURIDAD] htmlspecialchars en datos cliente
│   ├── exportar_excel.php
│   ├── exportar_pdf.php
│   ├── informe_compras.php          ← [ACTUALIZADO] Navbar + paginación + BS5 local
│   ├── informe_productos.php        ← [ACTUALIZADO] Navbar + paginación + BS5 local + redirect
│   ├── informe_ventas.php           ← [ACTUALIZADO] Navbar + paginación + BS5 local
│   └── informes.php                 ← [ACTUALIZADO] Navbar incluido
├── views/
│   ├── includes/
│   │   └── navbar.php               ← [NUEVO] Componente navbar reutilizable
│   ├── agregar_cliente.php          ← [REDISEÑADO] Navbar + BS5 form-card
│   ├── agregar_orden_compra.php     ← [REDISEÑADO] Navbar + BS5 form-card
│   ├── agregar_producto.php         ← [REDISEÑADO] Navbar + BS5 form-card
│   ├── agregar_proveedor.php        ← [REDISEÑADO] Navbar + BS5 form-card
│   ├── agregar_venta.php            ← [REDISEÑADO] Navbar + BS5 form-card
│   ├── clientes.php                 ← [ACTUALIZADO] Navbar + paginación
│   ├── editar_cliente.php           ← [REDISEÑADO] Navbar + BS5 + htmlspecialchars
│   ├── editar_orden.php             ← [REDISEÑADO] Navbar + BS5 form-card
│   ├── editar_producto.php          ← [REDISEÑADO] Navbar + BS5 + htmlspecialchars
│   ├── editar_proveedor.php         ← [REDISEÑADO] Navbar + BS5 + htmlspecialchars
│   ├── editar_usuario.php           ← [REESCRITO] BS5 completo (faltaba bootstrap CSS)
│   ├── editar_venta.php             ← [ACTUALIZADO] Grid multi-columna + cliente general
│   ├── orden_compra.php             ← [ACTUALIZADO] Navbar + paginación + JOIN proveedor + precio unitario + estado badge
│   ├── productos.php                ← [ACTUALIZADO] Navbar + paginación + ORDER BY + badges stock
│   ├── proveedores.php              ← [ACTUALIZADO] Navbar + paginación
│   ├── recuperar_clave.php          ← [REDISEÑADO] Navbar + BS5 form-card
│   ├── registro.php                 ← [REESCRITO] BS5 completo (faltaba bootstrap CSS)
│   ├── usuarios.php                 ← [ACTUALIZADO] Navbar + paginación + ORDER BY DESC
│   └── ventas.php                   ← [ACTUALIZADO] Navbar + paginación + precio unitario + estado badge
├── index.php                        ← [ACTUALIZADO] Login rediseñado
├── menu.php                         ← [ACTUALIZADO] Dashboard con navbar
└── opencode.json                    ← [NUEVO] Configuración Context7 MCP
```

---

## Historial de Cambios

### 18/07/2026 — Rediseño de UI/UX y Seguridad

#### Nuevo: Navbar reutilizable (`views/includes/navbar.php`)
- Componente BS5 `navbar-expand-lg` con fondo oscuro gradiente (`#1a2035` → `#2d3a52`)
- Bootstrap Icons vía CDN (`bootstrap-icons@1.11.3`)
- Links: Inicio, Clientes, Proveedores, Productos, Ventas, Órdenes, Informes
- **Control de roles**: Usuarios y Registro solo visibles para `$_SESSION['rol'] === 'Admin'`
- Dropdown de usuario con nombre, rol y botón de cerrar sesión
- Responsive: `navbar-toggler` para móviles
- Resalta el link activo según `basename($_SERVER['PHP_SELF'])`

#### Rediseñado: Menú principal (`menu.php`)
- Navbar incluido vía `include`
- Cards con iconos Bootstrap Icons en vez de emojis Unicode
- Gradientes de color por módulo (azul, teal, verde, naranja, púrpura, rojo, índigo, cyan)
- Hover con elevación y sombra
- Botón de cerrar sesión integrado en el navbar (eliminado del footer)

#### Rediseñado: Login (`index.php`)
- Navbar minimal con logo y nombre del sistema
- Card centrada con icono gradiente `bi-box-seam`
- Inputs con Bootstrap Icons (`bi-person`, `bi-lock`)
- Botón con gradiente oscuro coincidente con el navbar
- Eye toggle con `bi-eye` / `bi-eye-slash` (actualizado en `script.js`)
- Alertas BS5 con iconos (`bi-exclamation-triangle`, `bi-check-circle`)
- Mensaje de acceso restringido

#### Actualizado: Estilos (`assets/css/estilos.css`)
- **Eliminados**: ~300 líneas de CSS obsoleto (`.menu_opciones`, `.opciones`, `.body-inicio`, `.contenedor-usuarios`, `.encabezado-usuarios`, `.titulo-bienvenida`, `.btn-volver`, `.buscador`, `.input-busqueda`, `.acciones-laterales`, `.btn-accion`, `.btn-cerrar`, `.btn-agregar`, `.contenedor-informes`, `.encabezado-informes`, `.botones-superiores`, `.formulario-informes`, `.grupo-campos`, `.campo`, `.campo-fecha`, `.campo-descripcion`, `.campo-observaciones`, `.botones-inferiores`, `.boton_salir`, `@media` incompleto)
- **Nuevos**: `.navbar-custom`, `.login-body`, `.login-wrapper`, `.login-card`, `.login-icon-circle`, `.login-input`, `.login-eye`, `.login-btn`, `.dashboard-card`, `.card-icon` + variantes de color
- Fondo global: `#d4eaf7` (azul claro)
- Logo: `mix-blend-mode: multiply` para transparencia de fondo blanco

#### Actualizado: JavaScript (`assets/js/script.js`)
- Limpieza de código comentado
- `mostrarContrasena()`: alterna icono `bi-eye` ↔ `bi-eye-slash`
- Búsqueda en tablas mantenido

#### Todas las vistas actualizadas
| Vista | Cambios |
|-------|---------|
| `views/usuarios.php` | Navbar, header simplificado, iconos BS5 en botones, `table-dark` |
| `views/clientes.php` | Navbar, header simplificado, iconos BS5 en botones |
| `views/proveedores.php` | Navbar, header simplificado, iconos BS5 en botones |
| `views/productos.php` | Navbar, header simplificado, iconos BS5 en botones |
| `views/ventas.php` | Navbar, header simplificado, alert error corregido (`alert-danger`) |
| `views/orden_compra.php` | Navbar, header simplificado, lang corregido a `es` |
| `views/registro.php` | Navbar, formulario con Bootstrap Icons, inputs mejorados |
| `reports/informes.php` | Navbar incluido, cards con `border-0`, iconos en estadísticas |

#### Seguridad reforzada
| Archivo | Cambio |
|---------|--------|
| `controllers/eliminar_usuario.php` | `session_start()` + validación `$_SESSION['rol'] === 'Admin'`, redirect a `index.php` |
| `controllers/procesar_registro.php` | `session_start()` + validación `$_SESSION['rol'] === 'Admin'`, redirect a `index.php` |
| `views/usuarios.php` | Validación `rol !== 'Admin'` mejorada: redirect a `../menu.php` |
| `views/registro.php` | Validación `rol !== 'Admin'` mejorada: redirect a `../menu.php` |

#### Configuración Context7 MCP (`opencode.json`)
- Servidor MCP Context7 remoto configurado
- API KEY incluida en headers
- Uso: `use context7 php`, `use context7 bootstrap-5`, `use context7 frontend`

---

### 18/07/2026 — Login: Logo centrado y ajustes de visibilidad

#### Login (`index.php`)
- **Logo SGI** ahora se muestra encima del texto "Bienvenido" con `mix-blend-mode: multiply` para quitar el fondo blanco de la imagen
- Eliminado el círculo icono `.login-icon-circle` — reemplazado por el logo real del sistema

#### Navbar (`views/includes/navbar.php`)
- **Logo izquierdo**: aplicado `filter: brightness(0) invert(1)` + `opacity: 0.85` para que sea visible sobre el fondo oscuro sin perder definición
- **Dropdown rol**: color del texto del rol cambiado de `text-muted` (oscuro sobre fondo oscuro) a `rgba(255,255,255,0.7)` para legibilidad

#### CSS (`assets/css/estilos.css`)
- Nueva clase `.login-center-logo` para el logo del login (max-width: 100px, mix-blend-mode: multiply)
- Nueva clase `.navbar-logo-img` para el logo del navbar (filter para invertir colores, opacidad sutil)
- Hover en `.navbar-logo-img` sube opacidad a 1

---

### 18/07/2026 — Corrección de logos y compactación del login

#### Login (`index.php`)
- Logo cambiado de `mix-blend-mode: multiply` a `filter: brightness(1.2)` — el blend mode no funcionaba sobre fondo blanco de la card
- Card compactada: padding `p-4 p-md-5` → `p-4`, wrapper padding `2rem` → `1.5rem`, max-width `420px` → `400px`
- Texto "Bienvenido" reducido de `h3` a `h4`, margen inferior `mb-4` → `mb-3`
- Subtítulo con clase `small mb-0` para ahorrar espacio

#### Navbar (`views/includes/navbar.php`)
- Logo corregido: eliminado `filter: brightness(0) invert(1)` (creaba cuadrado blanco) — reemplazado por `filter: brightness(1.4)` para aclarar el logo sobre fondo oscuro
- Opacidad del logo: 0.9 normal, 1.0 en hover

---

## Módulos del Sistema

| Módulo | Archivos | Acceso |
|--------|----------|--------|
| **Login** | `index.php`, `controllers/procesar_login.php` | Público |
| **Menú/Dashboard** | `menu.php` | Autenticados |
| **Usuarios** | `views/usuarios.php`, `views/editar_usuario.php`, `views/agregar_usuario.php` | Admin |
| **Registro** | `views/registro.php`, `controllers/procesar_registro.php` | Admin |
| **Clientes** | `views/clientes.php`, `views/editar_cliente.php`, `views/agregar_cliente.php` | Autenticados (CRUD: Admin) |
| **Proveedores** | `views/proveedores.php`, `views/editar_proveedor.php`, `views/agregar_proveedor.php` | Autenticados (CRUD: Admin) |
| **Productos** | `views/productos.php`, `views/editar_producto.php`, `views/agregar_producto.php` | Autenticados (CRUD: Admin) |
| **Ventas** | `views/ventas.php`, `views/editar_venta.php`, `views/agregar_venta.php` | Autenticados (CRUD: Admin) |
| **Órdenes de Compra** | `views/orden_compra.php`, `views/editar_orden.php`, `views/agregar_orden_compra.php` | Autenticados (CRUD: Admin) |
| **Informes** | `reports/informes.php`, `reports/informe_*.php`, `reports/detalle_*.php` | Autenticados |
| **Exportación** | `reports/exportar_pdf.php`, `reports/exportar_excel.php` | Autenticados |

---

## Control de Roles

| Rol | Permisos |
|-----|----------|
| **Admin** | Acceso total: CRUD de todos los módulos, gestión de usuarios, registro de nuevos usuarios |
| **Operario** | Lectura de módulos (clientes, proveedores, productos, ventas, órdenes, informes). Sin CRUD. Sin acceso a usuarios ni registro |

---

## Base de Datos

| Tabla | Descripción |
|-------|-------------|
| `usuario` | Usuarios del sistema (ID_usuario, nombre, apellido, documento, correo, clave, rol) |
| `cliente` | Clientes registrados |
| `proveedor` | Proveedores |
| `producto` | Productos del inventario (ID_producto, nombre, stock, precio, descripcion, fecha) |
| `orden_venta` | Órdenes de venta |
| `detalle_orden_venta` | Detalle de cada orden de venta |
| `orden_compra` | Órdenes de compra a proveedores |
| `detalle_orden_compra` | Detalle de cada orden de compra |

---

## Servidores MCP Activos

### Context7
- **Tipo**: Remote
- **URL**: `https://mcp.context7.com/mcp`
- **Uso**: Consultar documentación actualizada de PHP, Bootstrap 5 y frontend
- **Prompts**: `use context7`, `use context7 php`, `use context7 bootstrap-5`, `use context7 frontend`

---

## Convenciones de Código

- **PHP**: `session_start()` al inicio de cada archivo que maneje sesiones
- **Seguridad**: Validación de `$_SESSION['rol']` antes de operaciones CRUD
- **Rutas**: Relativas al archivo actual (no absolutas)
- **CSS**: Un solo archivo `estilos.css` con secciones organizadas por coment
- **Bootstrap**: Clases nativas de BS5, sin override innecesario
- **Iconos**: Exclusivamente Bootstrap Icons vía CDN
- **Alertas**: Bootstrap alerts con auto-ocultar a los 5 segundos via JS

---

### 18/07/2026 — Rediseño completo de formularios, paginación y seguridad

#### Rediseñados: Formularios simples (7 archivos)
- **`views/agregar_cliente.php`**: Navbar, `.form-card`, BS5 form classes, iconos
- **`views/agregar_producto.php`**: Navbar, textarea para descripción, BS5
- **`views/agregar_proveedor.php`**: Navbar, labels agregados (originales no tenían), BS5
- **`views/editar_cliente.php`**: Navbar, `htmlspecialchars()` en todos los valores, hidden ID
- **`views/editar_producto.php`**: Navbar, `htmlspecialchars()` en todos los valores, hidden ID
- **`views/editar_proveedor.php`**: Navbar, `htmlspecialchars()`, campo `nombre_proveedor`
- **`views/recuperar_clave.php`**: Navbar, eye toggle con `icono_ojo`, validación BS5 `is-valid`/`is-invalid`

#### Rediseñados: Formularios dinámicos (4 archivos)
- **`views/agregar_venta.php`**: Eliminados ~140 líneas de `<style>` inline, navbar, `.form-card`, JS preservado
- **`views/agregar_orden_compra.php`**: Mismo tratamiento, navbar, JS preservado
- **`views/editar_venta.php`**: Navbar, restricción estado 'pendiente' preservada, `htmlspecialchars()`
- **`views/editar_orden.php`**: Navbar, restricción estado 'Procesando' preservada, `htmlspecialchars()`

#### Nuevo: Paginación server-side (6 tablas)
- **`views/clientes.php`**: 10 registros/página
- **`views/usuarios.php`**: 10 registros/página
- **`views/proveedores.php`**: 10 registros/página
- **`views/productos.php`**: 10 registros/página
- **`views/ventas.php`**: 10 registros/página
- **`views/orden_compra.php`**: 10 registros/página
- Paginación con `.pagination-container`, `.page-link`, info de registros

#### Nuevo: Navbar en informes (3 archivos)
- **`reports/informe_productos.php`**: Navbar + redirect corregido
- **`reports/informe_ventas.php`**: Navbar incluido
- **`reports/informe_compras.php`**: Navbar incluido

#### CSS (`assets/css/estilos.css`) — Expansión
- **`.form-card`**: Card de formulario con header gradiente oscuro, max-width 600px
- **`.productos-container`**: Estilos para forms dinámicos (movidos de inline)
- **`.producto-item`**: Borde hover, transiciones, border-radius 12px
- **`.producto-row`**: Grid responsive para selects/inputs de productos
- **`.btn-agregar`**: Gradiente teal con hover elevación
- **`.btn-eliminar`**: Rojo con transición
- **`.subtotal-display`**: Fondo azul claro, texto oscuro
- **`.total-general`**: Borde oscuro, centrado
- **`.pagination-container`**: Centrado, `.page-link` con border-radius 8px
- **`.pagination-info`**: Texto informativo bajo paginación

#### Seguridad — XSS Corregido
| Archivo | Corrección |
|---------|------------|
| `reports/informe_compras.php` | `htmlspecialchars()` en filtros GET (fecha_inicio, fecha_fin, estado) |
| `reports/informe_ventas.php` | `htmlspecialchars()` en filtros GET |
| `reports/detalle_venta.php` | `htmlspecialchars()` en datos de cliente (nombre, teléfono, correo) |
| `reports/detalle_compra.php` | `htmlspecialchars()` en datos de proveedor (nombre, teléfono, correo, dirección) |
| `views/editar_usuario.php` | `session_start()` + validación Admin agregada |
| `views/editar_proveedor.php` | Redirect corregido `productos.php` → `proveedores.php` |

---

### 18/07/2026 — Mejoras de UX: Tablas, Grids, Seguridad y Paginación Informes

#### Correcciones de botones y CSS
- **`views/usuarios.php`**: Botón "Agregar usuario" corregido de `agregar_usuario.php` → `registro.php`
- **`views/editar_usuario.php`**: Reescritura completa BS5 (faltaba `bootstrap.min.css`, Bootstrap Icons, navbar; usaba clases CSS inexistentes)
- **`views/registro.php`**: Reescritura completa BS5 (mismo problema de CSS faltante, classes `registrarse`/`datos_registro`/`boton_registro` eliminadas)

#### Tablas: Ordenamiento por más recientes
| Tabla | Query |
|-------|-------|
| `views/productos.php` | `ORDER BY ID_producto DESC` |
| `views/clientes.php` | `ORDER BY ID_cliente DESC` |
| `views/proveedores.php` | `ORDER BY ID_proveedor DESC` |
| `views/usuarios.php` | `ORDER BY ID_usuario DESC` |

#### Tablas: Badges de stock por nivel
- **`views/productos.php`**: Columna "Cantidad" ahora muestra badges de color:
  - `< 10` → `<span class="badge-stock-bajo">Bajo (X)</span>` (rojo)
  - `10-50` → `<span class="badge-stock-medio">Medio (X)</span>` (amarillo)
  - `> 50` → `<span class="badge-stock-alto">Alto (X)</span>` (verde)

#### Tablas: Nuevas columnas y badges de estado
- **`views/ventas.php`**: Nueva columna "Precio Unit." (campo `precio_unitario` de `detalle_orden_venta`), estado con badges BS5 (completada=verde, pendiente=amarillo, cancelada=rojo)
- **`views/orden_compra.php`**: Query modificada con `LEFT JOIN proveedor` para mostrar nombre en vez de ID, nueva columna "Precio Unit." (campo `precio_unitario_compra`), estado con badges BS5 (Aprobado=verde, Procesando=amarillo, cancelado=rojo)

#### Formularios: Grid multi-columna
- **`assets/css/estilos.css`**: `.producto-row` cambiado de `grid-template-columns: 1fr` → `2fr 1fr 1fr auto` en desktop (mobile mantiene `1fr`)
- **`views/agregar_venta.php`**: Grid 4 columnas, opción "Cliente general (sin registro)" con valor `0`
- **`views/agregar_orden_compra.php`**: Grid 4 columnas, eliminados `<br>` innecesarios
- **`views/editar_venta.php`**: Grid 4 columnas, opción "Cliente general (sin registro)"
- **`views/editar_orden.php`**: Grid 4 columnas

#### Informes: Paginación server-side (3 archivos)
- **`reports/informe_productos.php`**: 10 registros/página, filtros `nombre`/`stock` preservados en links, BS5 local
- **`reports/informe_ventas.php`**: 10 registros/página, filtros `fecha_inicio`/`fecha_fin`/`estado` preservados, BS5 local
- **`reports/informe_compras.php`**: 10 registros/página, filtros preservados, BS5 local
- Estandarizados a `bootstrap.min.css` local + Bootstrap Icons 1.11.3 CDN

#### CSS (`assets/css/estilos.css`)
- `.producto-row`: Grid `2fr 1fr 1fr auto` para forms dinámicos
- `.badge-stock-bajo`, `.badge-stock-medio`, `.badge-stock-alto`: Badges con colores para niveles de stock

---

## Pendiente / TODO

- [x] Agregar CSRF tokens en formularios
- [x] Migrar consultas SQL a prepared statements (evitar inyección SQL)
- [x] Agregar validación de seguridad en controllers de eliminación restantes

---

### 18/07/2026 — Seguridad: Prepared Statements, CSRF Tokens y Hardening

#### Nuevo: Archivo helper CSRF (`config/csrf.php`)
- Función `csrf_token()`: genera token aleatorio de 32 bytes con `bin2hex(random_bytes(32))` y lo almacena en `$_SESSION`
- Función `csrf_field()`: imprime `<input type="hidden" name="csrf_token">` para formularios
- Función `csrf_validate()`: valida token con `hash_equals()` (timing-safe)
- Token se regenera tras cada login exitoso

#### Nuevo: Prepared Statements en TODOS los controllers (21 archivos)
Todos los controllers ahora usan `mysqli_prepare()` + `bind_param()` en lugar de interpolación directa:

| Controller | Tipo |
|------------|------|
| `procesar_login.php` | SELECT con `bind_param("s", documento)` |
| `procesar_registro.php` | INSERT con `bind_param("ssssss", ...)` |
| `procesar_nuevo_cliente.php` | INSERT con `bind_param("ssss", ...)` |
| `procesar_nuevo_producto.php` | INSERT con `bind_param("sidss", ...)` |
| `procesar_nuevo_proveedor.php` | INSERT con `bind_param("ssss", ...)` |
| `procesar_edicion_usuario.php` | UPDATE con `bind_param("ssssi", ...)` |
| `procesar_edicion_cliente.php` | UPDATE con `bind_param("ssssi", ...)` |
| `procesar_edicion_producto.php` | UPDATE con `bind_param("sidssi", ...)` |
| `procesar_edicion_proveedor.php` | UPDATE con `bind_param("ssssi", ...)` |
| `procesar_edicion.php` | UPDATE con `bind_param("ssssi", ...)` |
| `procesar_cambiar_clave.php` | UPDATE con `bind_param("si", ...)` |
| `procesar_nueva_venta.php` | INSERT + UPDATE en transacción con prepared statements |
| `procesar_nueva_orden.php` | INSERT + UPDATE en transacción con prepared statements |
| `procesar_edicion_venta.php` | UPDATE + DELETE + INSERT en transacción con prepared statements |
| `procesar_edicion_orden.php` | UPDATE + DELETE + INSERT en transacción con prepared statements |
| `eliminar_usuario.php` | DELETE con `bind_param("i", ...)` |
| `eliminar_cliente.php` | SELECT + DELETE con `bind_param("i", ...)` |
| `eliminar_producto.php` | SELECT subqueries + DELETE con `bind_param("i", ...)` |
| `eliminar_proveedor.php` | SELECT subqueries + DELETE con `bind_param("i", ...)` |
| `eliminar_venta.php` | SELECT + UPDATE + DELETE en transacción con prepared statements |
| `eliminar_orden.php` | SELECT + UPDATE + DELETE en transacción con prepared statements |

#### Nuevo: Prepared Statements en views con queries inline (6 archivos)
- `views/editar_usuario.php`: SELECT con `bind_param("i", codigo)`
- `views/editar_cliente.php`: SELECT con `bind_param("i", codigo)`
- `views/editar_producto.php`: SELECT con `bind_param("i", codigo)`
- `views/editar_proveedor.php`: SELECT con `bind_param("i", codigo)`
- `views/editar_venta.php`: SELECT con `bind_param("i", codigo)` (implícito en query de edición)
- `views/editar_orden.php`: SELECT con `bind_param("i", codigo)` (implícito en query de edición)
- `views/recuperar_clave.php`: Eliminada query SQL inline (ahora delega a `procesar_cambiar_clave.php`)

#### Nuevo: CSRF Tokens en todos los formularios POST (14 archivos de vistas + 3 reportes)
**Views con `csrf_field()`:**
- `views/agregar_cliente.php`, `views/agregar_producto.php`, `views/agregar_proveedor.php`
- `views/agregar_venta.php`, `views/agregar_orden_compra.php`
- `views/editar_venta.php`, `views/editar_orden.php`
- `views/editar_usuario.php`, `views/editar_cliente.php`, `views/editar_producto.php`, `views/editar_proveedor.php`
- `views/recuperar_clave.php`, `views/registro.php`
- `index.php` (login)

**Reportes con `csrf_field()`:**
- `reports/informe_ventas.php` (2 forms: PDF + Excel)
- `reports/informe_compras.php` (2 forms: PDF + Excel)
- `reports/informe_productos.php` (2 forms: PDF + Excel)

**Controllers con `csrf_validate()`:**
- Todos los controllers POST validan `$_POST['csrf_token']` contra `$_SESSION['csrf_token']`
- Controllers de eliminación validan `$_GET['csrf_token']` para enlaces GET

#### Seguridad: Corregido orden `session_start()` en controllers de eliminación
- `eliminar_usuario.php`, `eliminar_cliente.php`, `eliminar_producto.php`, `eliminar_proveedor.php`, `eliminar_venta.php`, `eliminar_orden.php`: `session_start()` ahora se ejecuta ANTES de leer `$_REQUEST`/`$_GET`

#### Seguridad: Validación Admin en controllers restantes
- `procesar_edicion_usuario.php`: Agregada verificación `$_SESSION['rol'] === 'Admin'`
- `procesar_edicion_cliente.php`: Agregada verificación Admin
- `procesar_edicion_producto.php`: Agregada verificación Admin
- `procesar_edicion_proveedor.php`: Agregada verificación Admin
- `procesar_nuevo_cliente.php`: Agregada verificación Admin
- `procesar_nuevo_producto.php`: Agregada verificación Admin
- `procesar_nuevo_proveedor.php`: Agregada verificación Admin
