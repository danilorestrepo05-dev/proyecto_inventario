-- ============================================
-- SGI - Sistema de Gestión Integral
-- Base de datos limpia para InfinityFree
-- Solo estructura + 1 usuario Admin
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABLAS BASE
-- ============================================

CREATE TABLE IF NOT EXISTS `usuario` (
  `ID_usuario` int(10) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `rol` enum('Admin','Operario') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `apellido` varchar(255) NOT NULL,
  `documento` varchar(15) NOT NULL,
  PRIMARY KEY (`ID_usuario`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `documento` (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cliente` (
  `ID_cliente` int(10) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `identificacion` varchar(20) DEFAULT NULL,
  `tipo_identificacion` enum('cc','nit','otro','ninguno') DEFAULT 'ninguno',
  `telefono` varchar(15) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `correo` varchar(255) NOT NULL,
  PRIMARY KEY (`ID_cliente`),
  UNIQUE KEY `telefono` (`telefono`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `proveedor` (
  `ID_proveedor` int(10) NOT NULL AUTO_INCREMENT,
  `nombre_proveedor` varchar(255) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`ID_proveedor`),
  UNIQUE KEY `telefono` (`telefono`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `producto` (
  `ID_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `precio` decimal(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `ID_proveedor` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID_producto`),
  KEY `ID_proveedor` (`ID_proveedor`),
  CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`ID_proveedor`) REFERENCES `proveedor` (`ID_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- VENTAS
-- ============================================

CREATE TABLE IF NOT EXISTS `orden_venta` (
  `ID_orden_venta` int(10) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `origen` varchar(20) NOT NULL DEFAULT 'manual',
  `ID_cliente` int(10) DEFAULT NULL,
  `estado` enum('pendiente','completada','cancelada') DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`ID_orden_venta`),
  KEY `fk_cliente` (`ID_cliente`),
  CONSTRAINT `fk_cliente` FOREIGN KEY (`ID_cliente`) REFERENCES `cliente` (`ID_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `detalle_orden_venta` (
  `ID_detalle_venta` int(11) NOT NULL AUTO_INCREMENT,
  `ID_orden_venta` int(11) NOT NULL,
  `ID_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(14,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario`) STORED,
  PRIMARY KEY (`ID_detalle_venta`),
  KEY `ID_orden_venta` (`ID_orden_venta`),
  KEY `ID_producto` (`ID_producto`),
  CONSTRAINT `detalle_orden_venta_ibfk_1` FOREIGN KEY (`ID_orden_venta`) REFERENCES `orden_venta` (`ID_orden_venta`),
  CONSTRAINT `detalle_orden_venta_ibfk_2` FOREIGN KEY (`ID_producto`) REFERENCES `producto` (`ID_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- COMPRAS
-- ============================================

CREATE TABLE IF NOT EXISTS `orden_compra` (
  `ID_orden_compra` int(10) NOT NULL AUTO_INCREMENT,
  `ID_proveedor` int(10) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('Aprobado','Procesando','cancelado') NOT NULL,
  `total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`ID_orden_compra`),
  KEY `ID_proveedor` (`ID_proveedor`),
  CONSTRAINT `orden_compra_ibfk_3` FOREIGN KEY (`ID_proveedor`) REFERENCES `proveedor` (`ID_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `detalle_orden_compra` (
  `ID_detalle_orden` int(10) NOT NULL AUTO_INCREMENT,
  `ID_orden_compra` int(10) NOT NULL,
  `ID_producto` int(10) NOT NULL,
  `cantidad` int(100) NOT NULL,
  `precio_unitario_compra` decimal(12,2) NOT NULL,
  `subtotal` decimal(14,2) NOT NULL,
  PRIMARY KEY (`ID_detalle_orden`),
  KEY `ID_orden_compra` (`ID_orden_compra`),
  KEY `ID_producto` (`ID_producto`),
  CONSTRAINT `detalle_orden_compra_ibfk_1` FOREIGN KEY (`ID_orden_compra`) REFERENCES `orden_compra` (`ID_orden_compra`),
  CONSTRAINT `detalle_orden_compra_ibfk_2` FOREIGN KEY (`ID_producto`) REFERENCES `producto` (`ID_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- SERVICIO TECNICO (3 niveles)
-- ============================================

CREATE TABLE IF NOT EXISTS `servicio` (
  `ID_servicio` int(11) NOT NULL AUTO_INCREMENT,
  `ID_cliente` int(11) NOT NULL,
  `ID_usuario_tecnico` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `descuento_valor` decimal(12,2) DEFAULT 0,
  `descuento_tipo` enum('porcentaje','fijo') DEFAULT 'fijo',
  `notas_internas` text DEFAULT NULL,
  `mano_obra_costo` decimal(10,2) DEFAULT 0.00,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`ID_servicio`),
  KEY `ID_cliente` (`ID_cliente`),
  KEY `ID_usuario_tecnico` (`ID_usuario_tecnico`),
  CONSTRAINT `servicio_ibfk_1` FOREIGN KEY (`ID_cliente`) REFERENCES `cliente` (`ID_cliente`),
  CONSTRAINT `servicio_ibfk_2` FOREIGN KEY (`ID_usuario_tecnico`) REFERENCES `usuario` (`ID_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dispositivo_servicio` (
  `ID_dispositivo` int(11) NOT NULL AUTO_INCREMENT,
  `ID_servicio` int(11) NOT NULL,
  `dispositivo` varchar(100) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ID_dispositivo`),
  KEY `ID_servicio` (`ID_servicio`),
  CONSTRAINT `dispositivo_servicio_ibfk_1` FOREIGN KEY (`ID_servicio`) REFERENCES `servicio` (`ID_servicio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `trabajo` (
  `ID_trabajo` int(11) NOT NULL AUTO_INCREMENT,
  `ID_dispositivo` int(11) NOT NULL,
  `tipo_trabajo` varchar(100) DEFAULT 'General',
  `problema_reportado` text NOT NULL,
  `diagnostico` text DEFAULT NULL,
  `notas_internas` text DEFAULT NULL,
  `estado` enum('ingresado','diagnosticado','en_progreso','reparado','entregado','cancelado') DEFAULT 'ingresado',
  `mano_obra_costo` decimal(12,2) DEFAULT 0,
  `fecha_ingreso` datetime DEFAULT current_timestamp(),
  `fecha_entrega` datetime DEFAULT NULL,
  PRIMARY KEY (`ID_trabajo`),
  KEY `ID_dispositivo` (`ID_dispositivo`),
  CONSTRAINT `trabajo_ibfk_1` FOREIGN KEY (`ID_dispositivo`) REFERENCES `dispositivo_servicio` (`ID_dispositivo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- TABLAS HIJAS DE SERVICIO
-- ============================================

CREATE TABLE IF NOT EXISTS `reparacion_repuesto` (
  `ID_reparacion_repuesto` int(11) NOT NULL AUTO_INCREMENT,
  `ID_trabajo` int(11) NOT NULL,
  `ID_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(12,2) NOT NULL DEFAULT 0,
  `garantia_proveedor_dias` int(11) DEFAULT 0,
  `factura_proveedor_adjunto` varchar(255) DEFAULT NULL,
  `ID_orden_venta` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID_reparacion_repuesto`),
  KEY `ID_trabajo` (`ID_trabajo`),
  KEY `ID_producto` (`ID_producto`),
  CONSTRAINT `reparacion_repuesto_ibfk_1` FOREIGN KEY (`ID_trabajo`) REFERENCES `trabajo` (`ID_trabajo`) ON DELETE CASCADE,
  CONSTRAINT `reparacion_repuesto_ibfk_2` FOREIGN KEY (`ID_producto`) REFERENCES `producto` (`ID_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `programa_instalado` (
  `ID_programa` int(11) NOT NULL AUTO_INCREMENT,
  `ID_trabajo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `licencia` varchar(100) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1,
  `costo` decimal(10,2) DEFAULT 0.00,
  `gar_dias` int(11) DEFAULT NULL,
  `gar_fecha_inicio` date DEFAULT NULL,
  `gar_fecha_fin` date DEFAULT NULL,
  PRIMARY KEY (`ID_programa`),
  KEY `ID_trabajo` (`ID_trabajo`),
  CONSTRAINT `programa_instalado_ibfk_1` FOREIGN KEY (`ID_trabajo`) REFERENCES `trabajo` (`ID_trabajo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `garantia` (
  `ID_garantia` int(11) NOT NULL AUTO_INCREMENT,
  `ID_trabajo` int(11) NOT NULL,
  `dias` int(11) NOT NULL DEFAULT 30,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`ID_garantia`),
  KEY `ID_trabajo` (`ID_trabajo`),
  CONSTRAINT `garantia_ibfk_1` FOREIGN KEY (`ID_trabajo`) REFERENCES `trabajo` (`ID_trabajo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `bitacora_reparacion` (
  `ID_bitacora` int(11) NOT NULL AUTO_INCREMENT,
  `ID_trabajo` int(11) NOT NULL,
  `ID_usuario` int(11) NOT NULL,
  `estado_anterior` varchar(30) DEFAULT NULL,
  `estado_nuevo` varchar(30) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `fecha_cambio` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`ID_bitacora`),
  KEY `ID_trabajo` (`ID_trabajo`),
  KEY `ID_usuario` (`ID_usuario`),
  CONSTRAINT `bitacora_reparacion_ibfk_1` FOREIGN KEY (`ID_trabajo`) REFERENCES `trabajo` (`ID_trabajo`) ON DELETE CASCADE,
  CONSTRAINT `bitacora_reparacion_ibfk_2` FOREIGN KEY (`ID_usuario`) REFERENCES `usuario` (`ID_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- BITACORA DE CONOCIMIENTO
-- ============================================

CREATE TABLE IF NOT EXISTS `bitacora_conocimiento` (
  `ID_comando` int(11) NOT NULL AUTO_INCREMENT,
  `comando` varchar(255) NOT NULL,
  `sistema_operativo` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `enlace` varchar(500) DEFAULT NULL,
  `categoria` enum('optimizacion','redes','limpieza','diagnostico','atajo') NOT NULL,
  PRIMARY KEY (`ID_comando`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- SISTEMA (historial + intentos login)
-- ============================================

CREATE TABLE IF NOT EXISTS `historial_cambios` (
  `ID_historial` int(11) NOT NULL AUTO_INCREMENT,
  `ID_usuario` int(11) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `ID_registro` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`ID_historial`),
  KEY `ID_usuario` (`ID_usuario`),
  CONSTRAINT `historial_cambios_ibfk_1` FOREIGN KEY (`ID_usuario`) REFERENCES `usuario` (`ID_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `documento` varchar(50) NOT NULL,
  `intentos` int(11) DEFAULT 1,
  `ultimo_intento` datetime DEFAULT current_timestamp(),
  `bloqueado_hasta` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_documento` (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- TABLA BACKUP (obsoleta, se conserva por si acaso)
-- ============================================

CREATE TABLE IF NOT EXISTS `reparacion_backup` (
  `ID_reparacion` int(11) NOT NULL AUTO_INCREMENT,
  `ID_cliente` int(11) NOT NULL,
  `ID_usuario_tecnico` int(11) NOT NULL,
  `dispositivo` varchar(100) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `problema_reportado` text NOT NULL,
  `diagnostico` text DEFAULT NULL,
  `estado` enum('ingresado','diagnosticado','en_progreso','reparado','entregado','cancelado') DEFAULT 'ingresado',
  `mano_obra_costo` float DEFAULT 0,
  `descuento_valor` float DEFAULT 0,
  `descuento_tipo` enum('porcentaje','fijo') DEFAULT 'fijo',
  `fecha_ingreso` datetime DEFAULT current_timestamp(),
  `fecha_entrega` datetime DEFAULT NULL,
  `notas_internas` text DEFAULT NULL,
  PRIMARY KEY (`ID_reparacion`),
  KEY `ID_cliente` (`ID_cliente`),
  KEY `ID_usuario_tecnico` (`ID_usuario_tecnico`),
  CONSTRAINT `reparacion_backup_ibfk_1` FOREIGN KEY (`ID_cliente`) REFERENCES `cliente` (`ID_cliente`),
  CONSTRAINT `reparacion_backup_ibfk_2` FOREIGN KEY (`ID_usuario_tecnico`) REFERENCES `usuario` (`ID_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- USUARIO ADMIN INICIAL
-- Contraseña: Admin123* (cambiar despues de ingresar)
-- ============================================

INSERT INTO `usuario` (`nombre`, `clave`, `correo`, `rol`, `activo`, `apellido`, `documento`)
VALUES ('Admin', '$2y$10$8K1p/a0dL1LXMIgoEDFrOOemG3VxJt3El8BiowR9CQqVSHzxMD2C', 'admin@compumasterld.com', 'Admin', 1, 'General', '1234567890');

-- ============================================
-- COMANDOS DE EJEMPLO (bitacora de conocimiento)
-- ============================================

INSERT INTO `bitacora_conocimiento` (`comando`, `sistema_operativo`, `descripcion`, `categoria`) VALUES
('%temp%', 'Windows 10/11', 'Borra archivos temporales del usuario', 'limpieza'),
('sfc /scannow', 'Windows 10/11', 'Repara archivos del sistema corruptos', 'diagnostico'),
('DISM /Online /Cleanup-Image /RestoreHealth', 'Windows 10/11', 'Repara la imagen de Windows', 'diagnostico'),
('ipconfig /flushdns', 'Windows 10/11', 'Limpia caché de resolución DNS', 'redes'),
('netsh winsock reset', 'Windows 10/11', 'Restablece el catálogo Winsock', 'redes'),
('chkdsk C: /f /r', 'Windows 10/11', 'Verifica y repara errores en disco', 'diagnostico'),
('wmic diskdrive get status', 'Windows 10/11', 'Verifica salud del disco duro', 'diagnostico'),
('msconfig', 'Windows 10/11', 'Configuración de inicio del sistema', 'atajo'),
('devmgmt.msc', 'Windows 10/11', 'Abre el Administrador de dispositivos', 'atajo'),
('cleanmgr', 'Windows 10/11', 'Limpieza de disco del sistema', 'limpieza');
