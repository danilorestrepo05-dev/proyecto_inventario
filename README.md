# SGI - Sistema de Gestión de Inventarios

Sistema web para el monitoreo de existencias, compras, ventas y reportes con control de acceso por roles. Desarrollado con PHP 8.x, MySQL y Bootstrap 5.

## Características

- **Módulo de Productos** — CRUD completo con badges de nivel de stock (bajo/medio/alto)
- **Módulo de Clientes y Proveedores** — Gestión con paginación server-side
- **Módulo de Ventas** — Órdenes de venta con líneas dinámicas, cliente general opcional
- **Módulo de Órdenes de Compra** — Órdenes con selección de proveedor y actualización automática de stock
- **Módulo de Historial** — Registro de actividad con filtros por módulo, acción, usuario y fechas (Admin)
- **Informes** — Filtros por fecha, estado, nombre y stock con paginación
- **Exportación** — PDF (FPDF) y Excel (HTML .xls) para informes de ventas, compras y productos
- **Dashboard** — Panel principal con cards de acceso rápido por módulo
- **Control de roles** — Admin (CRUD completo + historial + gestión usuarios) y Operario (lectura + agregar clientes/proveedores/productos)
- **Seguridad** — Prepared statements, tokens CSRF, hash de contraseñas con `password_verify()`, headers anti-caché
- **Soft Delete** — Activar/desactivar registros (clientes, proveedores, productos, usuarios) en lugar de eliminación física
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
   git clone https://github.com/TU_USUARIO/Proyecto_inventario.git
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
   - `orden_venta`
   - `detalle_orden_venta`
   - `orden_compra`
   - `detalle_orden_compra`
   - `historial_cambios`

5. Acceder al sistema:
   ```
   http://localhost/Proyecto_inventario/
   ```

## Estructura del Proyecto

```
Proyecto_inventario/
├── assets/
│   ├── css/          → Bootstrap 5 + estilos personalizados
│   ├── img/          → Logo del sistema
│   └── js/           → Bootstrap 5 + script.js (eye toggle, búsqueda)
├── config/
│   ├── conexion.php  → Conexión MySQL
│   ├── csrf.php      → Helper CSRF (token, validate, field)
│   └── historial.php → Helper de registro de cambios
├── controllers/      → 22 archivos de lógica de negocio
├── fpdf/             → Librería FPDF para exportación PDF
├── reports/          → Informes, historial de cambios, detalle de ventas/compras, exportación
├── views/
│   ├── includes/     → navbar.php (reutilizable)
│   └── *.php         → Formularios CRUD y tablas con paginación
├── index.php         → Página de login (punto de entrada)
├── menu.php          → Dashboard principal
└── CHANGELOG.md      → Documentación completa del proyecto
```

## Roles

| Rol | Permisos |
|-----|----------|
| **Admin** | CRUD completo en todos los módulos, gestión de usuarios, historial de cambios, exportación de reportes, activar/desactivar registros |
| **Operario** | Lectura de tablas e informes, agregar clientes, proveedores y productos |

## Seguridad

- **Prepared Statements** — Todos los controllers y vistas con queries usan `mysqli_prepare()` + `bind_param()` para prevenir inyección SQL
- **CSRF Tokens** — Todos los formularios POST incluyen tokens generados con `random_bytes()` y validados con `hash_equals()`
- **Hash de Contraseñas** — Autenticación con `password_verify()` (bcrypt)
- **XSS** — Salida de datos sanitizada con `htmlspecialchars()`
- **Headers Anti-Caché** — Páginas autenticadas envían `Cache-Control: no-cache, no-store, must-revalidate` + listener `pageshow` para prevenir acceso post-logout
- **Validación de Rol** — Controllers de escritura verifican `$_SESSION['rol'] === 'Admin'`

## Uso

1. Crear un usuario con rol **Admin** en la base de datos
2. Iniciar sesión en `http://localhost/Proyecto_inventario/`
3. Desde el dashboard, acceder a los módulos de gestión
4. Los usuarios **Admin** tienen acceso completo al historial de cambios y gestión de usuarios
5. Los usuarios **Operario** pueden ver tablas, informes y agregar clientes, proveedores y productos

## Licencia

Proyecto académico. Uso interno.
