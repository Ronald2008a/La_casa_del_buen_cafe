-- ============================================================================
-- LA CASA DEL BUEN CAFÉ - ESQUEMA MYSQL / MARIADB PARA XAMPP (phpMyAdmin)
-- Base de datos: la_casa_del_buen_cafe
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `la_casa_del_buen_cafe`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `la_casa_del_buen_cafe`;

-- 1. TABLA DE CATEGORÍAS
CREATE TABLE IF NOT EXISTS `categorias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `descripcion` TEXT,
    `icono` VARCHAR(50) DEFAULT 'fa-mug-hot',
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA DE PRODUCTOS (Catálogo + Inventario)
CREATE TABLE IF NOT EXISTS `productos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `categoria_id` INT NULL,
    `codigo` VARCHAR(50) NOT NULL UNIQUE,
    `nombre` VARCHAR(150) NOT NULL,
    `descripcion` TEXT,
    `origen` VARCHAR(100) DEFAULT 'Huila, Colombia',
    `tueste` VARCHAR(50) DEFAULT 'Medio',
    `precio` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `costo` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `stock` INT NOT NULL DEFAULT 0,
    `stock_minimo` INT NOT NULL DEFAULT 5,
    `imagen_url` TEXT,
    `destacado` TINYINT(1) DEFAULT 0,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA DE CLIENTES
CREATE TABLE IF NOT EXISTS `clientes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) UNIQUE,
    `telefono` VARCHAR(50),
    `direccion` TEXT,
    `puntos` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA DE USUARIOS
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NULL,
    `rol` ENUM('admin', 'cajero', 'barista', 'cliente') DEFAULT 'admin',
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA DE PEDIDOS (POS y Tienda Online)
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `folio` VARCHAR(40) NOT NULL UNIQUE,
    `cliente_id` INT NULL,
    `cliente_nombre` VARCHAR(150) DEFAULT 'Cliente Mostrador',
    `usuario_id` INT NULL,
    `tipo` ENUM('pos', 'tienda_online') DEFAULT 'pos',
    `metodo_pago` ENUM('efectivo', 'tarjeta', 'transferencia') DEFAULT 'efectivo',
    `subtotal` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `impuestos` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `estado` ENUM('pendiente', 'preparando', 'completado', 'cancelado') DEFAULT 'completado',
    `direccion_entrega` TEXT,
    `notas` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABLA DE DETALLE DE PEDIDOS
CREATE TABLE IF NOT EXISTS `detalle_pedidos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pedido_id` INT NOT NULL,
    `producto_id` INT NULL,
    `nombre_producto` VARCHAR(150) NOT NULL,
    `cantidad` INT NOT NULL DEFAULT 1,
    `precio_unitario` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABLA DE MOVIMIENTOS DE INVENTARIO (Kardex)
CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT NOT NULL,
    `tipo` ENUM('entrada', 'salida', 'ajuste', 'venta') NOT NULL,
    `cantidad` INT NOT NULL,
    `stock_anterior` INT NOT NULL DEFAULT 0,
    `stock_nuevo` INT NOT NULL DEFAULT 0,
    `motivo` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_movimiento_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DATOS INICIALES (SEED DATA)
-- ============================================================================

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `icono`) VALUES
(1, 'Tortas', 'Tortas artesanales recién horneadas para acompañar tu café', 'fa-cake-candles'),
(2, 'Galletas Integral', 'Galletas integrales saludables con avena, miel y semillas', 'fa-cookie-bite'),
(3, 'Empanadas Mixtas', 'Empanadas crujientes rellenas de combinación de carnes y queso', 'fa-utensils'),
(4, 'Empanadas de Carne', 'Empanadas tradicionales con carne sazonada de la casa', 'fa-fire-burner'),
(5, 'Empanadas de Pollo', 'Empanadas doradas rellenas de pollo desmechado criollo', 'fa-drumstick-bite'),
(6, 'Platanitos Mixtos', 'Mezcla crujiente de platanitos verdes salados y maduros dulces', 'fa-bowl-food'),
(7, 'Platanitos Maduros', 'Chips artesanales de plátano maduro naturalmente dulces', 'fa-sun'),
(8, 'Platanitos Verdes', 'Chips crocantes de plátano verde con toque de sal marina', 'fa-leaf'),
(9, 'Bebidas', 'Café de especialidad colombiano, bebidas calientes y frías', 'fa-mug-hot')
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `descripcion` = VALUES(`descripcion`),
  `icono` = VALUES(`icono`);

INSERT INTO `productos` (`id`, `categoria_id`, `codigo`, `nombre`, `descripcion`, `origen`, `tueste`, `precio`, `costo`, `stock`, `stock_minimo`, `imagen_url`, `destacado`) VALUES
(1, 1, 'TOR-001', 'Torta Húmeda de Café y Nueces', 'Bizcocho artesanal bañado en reducción de espresso y nuez pecana.', 'Horneado Local', 'N/A', 11500, 4200, 20, 5, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=600&q=80', 1),
(2, 1, 'TOR-002', 'Torta Casera de Zanahoria y Especias', 'Con frosting suave de queso crema y canela recién molida.', 'Horneado Local', 'N/A', 10500, 3900, 15, 5, 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?auto=format&fit=crop&w=600&q=80', 1),
(3, 2, 'GAL-001', 'Galletas Integral de Avena y Arándanos', 'Paquete de galletas integrales artesanales endulzadas con miel.', 'Artesanal', 'N/A', 5500, 2000, 45, 10, 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=600&q=80', 1),
(4, 3, 'EMP-001', 'Empanada Mixta Especial', 'Rellena de carne desmechada, pollo y corazón de queso fundido.', 'Cocina Local', 'N/A', 4500, 1600, 60, 15, 'https://images.unsplash.com/photo-1604908554025-aaa87c152c7b?auto=format&fit=crop&w=600&q=80', 1),
(5, 4, 'EMP-002', 'Empanada Tradicional de Carne', 'Masa crocante de maíz con carne sazonada y ají casero.', 'Cocina Local', 'N/A', 4000, 1400, 75, 15, 'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?auto=format&fit=crop&w=600&q=80', 0),
(6, 5, 'EMP-003', 'Empanada Criolla de Pollo', 'Pollo desmechado en guiso tradicional colombiano bien dorada.', 'Cocina Local', 'N/A', 4000, 1400, 70, 15, 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?auto=format&fit=crop&w=600&q=80', 0),
(7, 6, 'PLA-001', 'Paquete Platanitos Mixtos 150g', 'Combinación ideal de tajaditas verdes y maduras recién hechas.', 'Quindío, Colombia', 'N/A', 6500, 2200, 50, 10, 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=600&q=80', 1),
(8, 7, 'PLA-002', 'Platanitos Maduros Dulces 150g', 'Tajaditas crocantes de plátano maduro con su dulzor natural.', 'Valle del Cauca', 'N/A', 6000, 2000, 40, 10, 'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=600&q=80', 0),
(9, 8, 'PLA-003', 'Platanitos Verdes con Sal Marina 150g', 'Chips de plátano verde extra crocantes con toque de sal.', 'Urabá / Caldas', 'N/A', 6000, 2000, 55, 10, 'https://images.unsplash.com/photo-1613919113640-25732ec5e61f?auto=format&fit=crop&w=600&q=80', 0),
(10, 9, 'BEB-001', 'Cappuccino Artesanal de Origen 8oz', 'Doble shot de espresso colombiano con leche texturizada sedosa.', 'Huila, Colombia', 'Medio', 8500, 2800, 120, 15, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=600&q=80', 1),
(11, 9, 'BEB-002', 'Cold Brew Naranja & Café', 'Infusión de café en frío por 18 horas refrescante.', 'Sierra Nevada', 'Medio', 11500, 3800, 45, 10, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=600&q=80', 1),
(12, 9, 'CAF-001', 'Café en Grano/Molido Reserva Huila 500g', 'Notas a panela, frutos rojos y chocolate oscuro. Puntaje SCA 86.', 'Pitalito, Huila', 'Medio', 42000, 24000, 35, 8, 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80', 1)
ON DUPLICATE KEY UPDATE
  `categoria_id` = VALUES(`categoria_id`),
  `nombre` = VALUES(`nombre`),
  `descripcion` = VALUES(`descripcion`),
  `origen` = VALUES(`origen`),
  `tueste` = VALUES(`tueste`),
  `precio` = VALUES(`precio`),
  `costo` = VALUES(`costo`),
  `imagen_url` = VALUES(`imagen_url`),
  `destacado` = VALUES(`destacado`);

INSERT IGNORE INTO `clientes` (`id`, `nombre`, `email`, `telefono`, `direccion`, `puntos`) VALUES
(1, 'Cliente Mostrador', 'mostrador@casadelbuencafe.com', '0000000000', 'Venta directa en tienda', 0),
(2, 'Estefanía Olaya', 'estefania@ejemplo.com', '3105551234', 'Calle 85 #14-20, Bogotá', 150),
(3, 'Carlos Mendoza', 'carlos.mendoza@ejemplo.com', '3158889900', 'Carrera 43A #10-50, Medellín', 85),
(4, 'Laura Valencia', 'laura.valencia@ejemplo.com', '3004443322', 'Avenida 6N #25-10, Cali', 220);

INSERT IGNORE INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `rol`) VALUES
(1, 'Administrador Principal', 'admin@casadelbuencafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Cajero Turno Mañana', 'caja@casadelbuencafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cajero');

INSERT IGNORE INTO `pedidos` (`id`, `folio`, `cliente_id`, `cliente_nombre`, `tipo`, `metodo_pago`, `subtotal`, `impuestos`, `total`, `estado`, `direccion_entrega`, `notas`) VALUES
(1, 'WEB-1001', 2, 'Estefanía Olaya', 'tienda_online', 'tarjeta', 59000, 0, 59000, 'completado', 'Calle 85 #14-20, Bogotá', 'Molienda media para filtro'),
(2, 'POS-1002', 1, 'Cliente Mostrador', 'pos', 'efectivo', 20500, 0, 20500, 'preparando', 'Consumo en Barra - Mesa 4', 'Sin azúcar');

INSERT IGNORE INTO `detalle_pedidos` (`id`, `pedido_id`, `producto_id`, `nombre_producto`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 1, 'Reserva Especial Huila 500g', 1, 42000, 42000),
(2, 1, 4, 'Cappuccino Artesanal 8oz', 2, 8500, 17000),
(3, 2, 6, 'Filtrado V60 de Origen', 1, 11000, 11000),
(4, 2, 9, 'Croissant de Almendras', 1, 9500, 9500);

INSERT IGNORE INTO `movimientos_inventario` (`id`, `producto_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`, `motivo`) VALUES
(1, 1, 'entrada', 35, 0, 35, 'Lote inicial tostión octubre');


