# Sistema de Gestión de Inventarios Interno
Monitoreo de existencias, compras, ventas y reportes con control de acceso por roles.

## Stack Tecnológico
- **Backend:** PHP puro utilizando la extensión nativa `mysqli` (conector `mysqli_connect`).
- **Arquitectura:** Patrón de diseño de software MVC básico (Model-View-Controller).
- **Servidor Local:** Entorno de desarrollo local XAMPP (servidor Apache).
- **Base de Datos:** MySQL administrado a través de la interfaz web de phpMyAdmin.
- **Frontend:** Estructura web estándar con HTML5, CSS3, JavaScript puro y maquetación responsive en Bootstrap 5 (con Bootstrap Icons vía CDN).
- **Seguridad:** Gestión de sesiones nativas de PHP (`session_start()`) y verificación de contraseñas mediante la función segura `password_verify()`.

## Comandos del Proyecto
- URL de acceso en navegador: `http://localhost/Proyecto_inventario/`
- Servidor de base de datos: Acceso web local en `http://localhost/phpmyadmin/`

## Estructura del Proyecto (MVC)
- `controllers/` — Lógica de negocio y procesamiento seguro de formularios y peticiones.
- `models/` — Consultas SQL directas y manipulación de datos en la base MySQL.
- `views/` — Archivos de presentación visual e interfaces de usuario para los módulos.
- `views/includes/` — Fragmentos de código repetitivos y componentes globales (ej. `navbar.php`).
- `fpdf/` — Librería de terceros para la exportación y generación automática de reportes en PDF.

## Restricciones Estrictas (No Hagas)
- No realices ninguna inserción, actualización o eliminación en la base de datos sin implementar **Prepared Statements (Sentencias preparadas)** para mitigar vulnerabilidades de Inyección SQL.
- No modifiques ni elimines la lógica de autenticación segura basada en hashes que implementa `password_verify()`.
- No alteres los layouts o componentes visuales de Bootstrap 5 sin garantizar que el diseño sea 100% móvil-responsive y compatible con pantallas de escritorio.
- No instales ni migres el proyecto a frameworks de PHP modernos (como Laravel o Symfony) bajo ninguna circunstancia.

## Flujo de Trabajo
- Antes de modificar código crítico de lógica en un controlador o la estética de una vista, utiliza el **Modo Plan** (`TAB`) para proponer y revisar el impacto técnico en el software.
- Modifica un archivo técnico a la vez de forma atómica. Informa claramente los cambios realizados antes de proceder con el siguiente archivo del checklist.
- Si la certeza en la implementación de una función es menor al 80%, detén el proceso autónomo y solicita confirmación de requerimientos al usuario desarrollador.
- Documenta y lee todos los cambios y actualizaciónes desde el archivo CHANGELOG.md.
- Siempre ten en cuenta el uso del archivo opencode.json MCP context7 con los parámetros de php, bootstrap, frontend, JavaScript, backend.

## Instrucciones para actualizar CHANGELOG.md

### Formato obligatorio
Cada entrada debe seguir esta estructura exacta:

```
### DD/MM/AAAA — Título descriptivo de la categoría

#### Subtítulo específico del cambio
- **`ruta/archivo.php`**: Descripción clara y concisa de qué se hizo y por qué.
```

### Reglas de escritura
1. **Fecha**: Formato `DD/MM/AAAA` (ej: `19/07/2026`). Siempre al inicio de la entrada.
2. **Título principal**: Una categoría descriptiva (ej: "Seguridad", "Tablas", "Formularios", "Corregido", "Nuevo").
3. **Ruta del archivo**: Siempre entre backticks y negrita: `**`routes/archivo.php`**`.
4. **Descripción**: Explicar QUÉ se hizo y POR QUÉ, no cómo. Ejemplo correcto: "Corregido token CSRF que se escapaba incorrectamente dentro de echo". Ejemplo incorrecto: "Se cambió la línea 119".
5. **Punto y coma final**: Cada_bullet_termina_en_punto.
6. **Una entrada por cada cambio distinto**, no agrupar múltiples cambios en un solo bullet.

### Categorías permitidas
| Categoría | Cuándo usarla |
|-----------|---------------|
| `Nuevo` | Archivos, funciones, módulos o features que no existían antes |
| `Corregido` | Bugs, errores de lógica, CSRF, XSS, rutas rotas |
| `Actualizado` | Mejoras a funcionalidad existente (paginación, estilos, UX) |
| `Seguridad` | Cambios específicos de hardening (prepared statements, headers, validación) |
| `Removido` | Archivos, dependencias o código eliminado deliberadamente |

### Ejemplo de entrada completa
```
### 19/07/2026 — Seguridad de sesión y mejoras en tablas

#### Seguridad: Prevenir acceso post-logout con botón "atrás"
- **`controllers/cerrar_sesion.php`**: Agregados headers `Cache-Control: no-cache, no-store, must-revalidate` antes de destruir la sesión.
- **`views/includes/navbar.php`**: Agregado listener JavaScript `pageshow` que redirige al login cuando el navegador carga desde caché.

#### Tablas: Columna ID uniforme
- **`assets/css/estilos.css`**: Nueva regla `.tabla-usuarios th:first-child` con `width: 80px` y `white-space: nowrap`.
```

### Qué NO documentar
- Cambios en archivos de configuración de herramientas AI (`opencode.json`, `AGENTS.md`)
- Cambios en `.gitignore` (ya documentados como "Nuevo" la primera vez)
- Merge commits o pushes a GitHub (eso es gestión de repo, no del software)