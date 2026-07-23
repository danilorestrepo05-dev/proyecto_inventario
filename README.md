# SGI - Sistema de Gestión Integral

Sistema web para el monitoreo de existencias, compras, ventas, servicio técnico y reportes con control de acceso por roles. Desarrollado con PHP 8.x, MySQL y Bootstrap 5.

## Características

- **Módulo de Productos** — CRUD completo con badges de nivel de stock (bajo/medio/alto)
- **Módulo de Clientes y Proveedores** — Gestión con identificación flexible (CC/NIT/otro) y paginación server-side
- **Módulo de Ventas** — Órdenes de venta con líneas dinámicas, cliente general opcional, badge de origen (manual/servicio)
- **Módulo de Órdenes de Compra** — Órdenes con selección de proveedor y actualización automática de stock
- **Módulo de Soporte Técnico** — Servicios con jerarquía 3 niveles: Servicio → Dispositivo → Trabajo. Incluye repuestos, programas instalados, bitácora y garantías por ítem
- **Módulo de Bitácora de Conocimiento** — Base de datos global de comandos útiles por categoría y sistema operativo (Admin)
- **Módulo de Historial** — Registro de actividad con filtros por módulo (incluye Servicio Técnico y Reparación), acción, usuario y fechas (Admin)
- **Informes** — Filtros por fecha, estado, nombre y stock con paginación
- **Exportación** — PDF (FPDF) y Excel (HTML .xls) para informes de ventas, compras y productos
- **Reportes de Servicio** — Ficha de ingreso, certificado de trabajo con garantías por ítem y T&C dinámicos, cuenta de cobro (FPDF)
- **Venta automática** — Al agregar repuestos a un servicio se genera automáticamente la venta correspondiente
- **Dashboard** — Panel principal con cards de acceso rápido por módulo
- **Control de roles** — Admin (CRUD completo + historial + gestión usuarios) y Operario (lectura + crear/editar comandos)
- **Registro de usuarios** — Formulario de creación de usuarios con selección de rol (Admin)
- **Seguridad** — Prepared statements, tokens CSRF, hash de contraseñas con `password_verify()`, headers anti-caché, rate limiting en login
- **Soft Delete** — Activar/desactivar registros (clientes, proveedores, productos, usuarios, servicios)
- **Borrador automático** — Formularios de ventas y órdenes guardan progreso en localStorage

## Stack Tecnológico

- **Backend:** PHP 8.x (extensión `mysqli`)
- **Base de Datos:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5, Bootstrap Icons
- **Servidor Local:** XAMPP (Apache + MySQL)
- **Exportación PDF:** FPDF
- **Arquitectura:** MVC (Modelo-Vista-Controlador)

## Requisitos Previos

- [XAMPP](https://www.apachefriends.org/) (o cualquier servidor con PHP 8.x + MySQL)
- Navegador web actualizado

## Instalación

1. Clonar el repositorio en la carpeta `htdocs` de XAMPP:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/danilorestrepo05-dev/Proyecto_inventario.git
   ```

2. Iniciar Apache y MySQL desde el panel de control de XAMPP.

3. Crear la base de datos en phpMyAdmin (`http://localhost/phpmyadmin/`):
   ```sql
   CREATE DATABASE inventariodb;
   ```

4. Importar la estructura de tablas. Las tablas necesarias son:
   - `usuario`
   - `cliente`
   - `proveedor`
   - `producto`
   - `orden_venta` (con columna `origen`: manual/servicio)
   - `detalle_orden_venta`
   - `orden_compra`
   - `detalle_orden_compra`
   - `historial_cambios`
   - `login_attempts`
   - `servicio`
   - `dispositivo_servicio`
   - `trabajo`
   - `reparacion_repuesto` (con FK `ID_orden_venta`)
   - `programa_instalado` (con campos de garantía)
   - `garantia`
   - `bitacora_reparacion`
   - `bitacora_conocimiento`

5. Ejecutar las migraciones del archivo `sql_modulo_reparaciones.sql` si se importa una versión anterior.

6. Acceder al sistema:
   ```
   http://localhost/Proyecto_inventario/
   ```

## Estructura del Proyecto

```
Proyecto_inventario/
├── assets/
│   ├── css/          → Bootstrap 5 + estilos personalizados
│   ├── img/          → Logo del sistema y logo PDFs
│   ├── js/           → Bootstrap 5 + script.js (eye toggle, búsqueda)
│   └── uploads/      → Archivos adjuntos (garantías)
├── config/
│   ├── conexion.php  → Conexión MySQL con charset utf8mb4
│   ├── csrf.php      → Helper CSRF (token, validate, field)
│   ├── historial.php → Helper de registro de cambios
│   └── rate_limit.php → Rate limiting para login (5 intentos / 15 min)
├── controllers/      → Lógica de negocio y procesamiento de formularios
├── fpdf/             → Librería FPDF para exportación PDF
├── reports/          → Informes, historial, reportes de servicio y exportación
├── views/
│   ├── includes/     → navbar.php (reutilizable con variable $nav_base)
│   └── *.php         → Formularios CRUD, tablas con paginación y vistas de servicio
├── sql_modulo_reparaciones.sql → Script de tablas de servicio técnico y migraciones
├── index.php         → Página de login (punto de entrada)
├── menu.php          → Dashboard principal
├── CHANGELOG.md      → Documentación completa de cambios
└── AGENTS.md         → Instrucciones para asistentes de desarrollo
```

## Modelo de Datos - Servicio Técnico (3 niveles)

```
servicio (ID_servicio, nombre, ID_cliente, ID_usuario_tecnico, mano_obra_costo, descuento)
  └── dispositivo_servicio (ID_dispositivo, dispositivo, marca, modelo, numero_serie)
        └── trabajo (ID_trabajo, tipo_trabajo, problema_reportado, diagnostico, estado, mano_obra_costo)
              ├── reparacion_repuesto (ID_producto, cantidad, precio_unitario, garantía proveedor, ID_orden_venta)
              ├── programa_instalado (nombre, versión, costo, cantidad, garantía días/fechas)
              ├── garantia (dias, fecha_inicio, fecha_fin)
              └── bitacora_reparacion (descripcion, archivos adjuntos)
```

## Roles

| Rol | Permisos |
|-----|----------|
| **Admin** | CRUD completo en todos los módulos, gestión de usuarios, historial de cambios, exportación de reportes, activar/desactivar registros |
| **Operario** | Lectura de tablas e informes, crear/editar comandos en bitácora |

## Seguridad

- **Prepared Statements** — Todos los controllers y vistas con queries usan `mysqli_prepare()` + `bind_param()` para prevenir inyección SQL
- **CSRF Tokens** — Todos los formularios POST incluyen tokens generados con `random_bytes()` y validados con `hash_equals()`
- **Hash de Contraseñas** — Autenticación con `password_verify()` (bcrypt)
- **XSS** — Salida de datos sanitizada con `htmlspecialchars()`
- **Headers Anti-Caché** — Páginas autenticadas envían `Cache-Control: no-cache, no-store, must-revalidate` + listener `pageshow` para prevenir acceso post-logout
- **Validación de Rol** — Controllers de escritura verifican `$_SESSION['rol'] === 'Admin'`
- **Rate Limiting** — Protección contra fuerza bruta: máximo 5 intentos fallidos por IP + documento, bloqueo temporal de 15 minutos

## Uso

1. Crear un usuario con rol **Admin** en la base de datos
2. Iniciar sesión en `http://localhost/Proyecto_inventario/`
3. Desde el dashboard, acceder a los módulos de gestión
4. Los usuarios **Admin** tienen acceso completo al historial de cambios y gestión de usuarios
5. Los usuarios **Operario** pueden ver tablas, informes y crear/editar comandos

## Licencia

Proyecto académico. Uso interno.
