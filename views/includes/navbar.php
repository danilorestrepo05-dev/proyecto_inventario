<?php
if (!isset($nav_base)) {
    $nav_base = '.';
}
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
$rol_nav = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
$nombre_nav = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : 'Usuario';
?>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
  <div class="container-fluid px-3 px-md-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $nav_base; ?>/menu.php">
      <img src="<?php echo $nav_base; ?>/assets/img/sgi-software (1).png" alt="SGI" height="36" class="d-inline-block align-text-top navbar-logo-img">
      <span class="fw-bold brand-text">SGI</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Abrir menú de navegación">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'menu.php') ? 'active' : ''; ?>" href="<?php echo $nav_base; ?>/menu.php">
            <i class="bi bi-house-door me-1"></i> Inicio
          </a>
        </li>
        <?php if ($rol_nav === 'Admin'): ?>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'usuarios.php') ? 'active' : ''; ?>" href="<?php echo $nav_base; ?>/views/usuarios.php">
            <i class="bi bi-person-gear me-1"></i> Usuarios
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'proveedores.php') ? 'active' : ''; ?>" href="<?php echo $nav_base; ?>/views/proveedores.php">
            <i class="bi bi-building me-1"></i> Proveedores
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'clientes.php') ? 'active' : ''; ?>" href="<?php echo $nav_base; ?>/views/clientes.php">
            <i class="bi bi-people me-1"></i> Clientes
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'productos.php') ? 'active' : ''; ?>" href="<?php echo $nav_base; ?>/views/productos.php">
            <i class="bi bi-box-seam me-1"></i> Productos
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'orden_compra.php') ? 'active' : ''; ?>" href="<?php echo $nav_base; ?>/views/orden_compra.php">
            <i class="bi bi-cart3 me-1"></i> Compras
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'ventas.php') ? 'active' : ''; ?>" href="<?php echo $nav_base; ?>/views/ventas.php">
            <i class="bi bi-cash-stack me-1"></i> Ventas
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'informes.php') ? 'active' : ''; ?>" href="<?php echo $nav_base; ?>/reports/informes.php">
            <i class="bi bi-file-earmark-bar-graph me-1"></i> Informes
          </a>
        </li>
        <?php if ($rol_nav === 'Admin'): ?>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'registro.php') ? 'active' : ''; ?>" href="<?php echo $nav_base; ?>/views/registro.php">
            <i class="bi bi-person-plus me-1"></i> Registro
          </a>
        </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle fs-5"></i>
            <span class="d-none d-lg-inline"><?php echo htmlspecialchars($nombre_nav); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
            <li><span class="dropdown-item-text small" style="color: rgba(255,255,255,0.7);"><i class="bi bi-person-badge me-1"></i><?php echo htmlspecialchars($rol_nav); ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="<?php echo $nav_base; ?>/controllers/cerrar_sesion.php" method="POST" class="px-3 py-1">
                <button type="submit" class="dropdown-item text-danger">
                  <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
                </button>
              </form>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<script>
window.addEventListener('pageshow', function(e) {
  if (e.persisted || (performance.navigation && performance.navigation.type === 2)) {
    window.location.href = '<?php echo $nav_base; ?>/index.php';
  }
});
</script>
