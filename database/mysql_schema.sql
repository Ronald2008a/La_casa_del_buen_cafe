-- ============================================================================
-- BASE DE DATOS: LA CASA DEL BUEN CAFÉ
-- Motor: XAMPP MySQL / MariaDB (Optimizado con Índices de Rendimiento)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `la_casa_del_buen_cafe` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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

-- 3. TABLA DE CLIENTES (Fidelización Club del Café)
CREATE TABLE IF NOT EXISTS `clientes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) UNIQUE,
    `telefono` VARCHAR(50),
    `direccion` TEXT,
    `puntos` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA DE USUARIOS / PERSONAL
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NULL,
    `rol` ENUM('admin', 'cajero', 'barista') DEFAULT 'admin',
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

-- ÍNDICES DE RENDIMIENTO PARA CONSULTAS FRECUENTES
CREATE INDEX `idx_productos_cat` ON `productos`(`categoria_id`);
CREATE INDEX `idx_productos_activo` ON `productos`(`activo`);
CREATE INDEX `idx_productos_destacado` ON `productos`(`destacado`);
CREATE INDEX `idx_pedidos_estado` ON `pedidos`(`estado`);
CREATE INDEX `idx_pedidos_folio` ON `pedidos`(`folio`);
CREATE INDEX `idx_pedidos_cliente` ON `pedidos`(`cliente_id`);
CREATE INDEX `idx_detalle_pedido` ON `detalle_pedidos`(`pedido_id`);
CREATE INDEX `idx_clientes_email` ON `clientes`(`email`);

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
(9, 'Bebidas', 'Café de especialidad colombiano, bebidas calientes y frías', 'fa-mug-hot'),
(10, 'Platanitos Mix de Chicharrón', 'Chicharrón con platanitos', 'fa-cookie-bite')
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `descripcion` = VALUES(`descripcion`),
  `icono` = VALUES(`icono`);

INSERT INTO `productos` (`id`, `categoria_id`, `codigo`, `nombre`, `descripcion`, `origen`, `tueste`, `precio`, `costo`, `stock`, `stock_minimo`, `imagen_url`, `destacado`) VALUES
(1, 1, 'TOR-001', 'Torta Húmeda de Café y Nueces', 'Bizcocho artesanal bañado en reducción de espresso y nuez pecana.', 'Horneado Local', 'N/A', 11500, 4200, 20, 5, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=600&q=80', 1),
(2, 1, 'TOR-002', 'Torta Casera de Zanahoria y Especias', 'Con frosting suave de queso crema y canela recién molida.', 'Horneado Local', 'N/A', 10500, 3900, 15, 5, 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?auto=format&fit=crop&w=600&q=80', 1),
(3, 2, 'GALIN-99', 'Galletas Integral de Avena', 'Paquete de galletas integrales de avena', 'Artesanal', 'N/A', 3500, 2000, 20, 5, 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=600&q=80', 1),
(4, 3, 'EMP-001', 'Empanada Mixta Especial', 'Rellena de carne desmechada, pollo', 'Cocina Local', 'N/A', 6000, 4000, 10, 3, 'uploads/prod_1793470733_605.png', 1),
(5, 4, 'EMP-002', 'Empanada de Carne', 'Masa con carne desmechada', 'Cocina Local', 'N/A', 6000, 4000, 10, 5, 'uploads/prod_1793470620_604.png', 0),
(6, 5, 'EMP-69', 'Empanada de Pollo', 'Pollo desmechado', 'Cocina Local', 'N/A', 6000, 4000, 10, 3, 'uploads/prod_1793470380_426.png', 0),
(7, 6, 'PLA-001', 'Paquete Platanitos Mixtos 150g', 'Combinación ideal de tajaditas verdes y maduras recién hechas.', 'Quindío, Colombia', 'N/A', 6500, 2200, 50, 10, 'uploads/prod_1794079260_727.jpg', 1),
(8, 7, 'PLA-002', 'Platanitos Maduros Dulces 150g', 'Tajaditas crocantes de plátano maduro con su dulzor natural.', 'Valle del Cauca', 'N/A', 6000, 2000, 40, 10, 'uploads/prod_1794079588_669.jpg', 0),
(9, 8, 'PLA-003', 'Platanitos Verdes Sabor Natural  40g', 'Chips de plátano verde extra crocantes con toque de sal.', 'Huila, Colombia', 'N/A', 3500, 2000, 5, 2, 'uploads/prod_1794073674_501.jpg', 0),
(10, 9, 'BEB-001', 'Cappuccino Artesanal de Origen 8oz', 'Doble shot de espresso colombiano con leche texturizada sedosa.', 'Huila, Colombia', 'Medio', 8500, 2800, 120, 15, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=600&q=80', 1),
(11, 9, 'BEB-002', 'Cold Brew Naranja & Café', 'Infusión de café en frío por 18 horas refrescante.', 'Sierra Nevada', 'Medio', 11500, 3800, 45, 10, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=600&q=80', 1),
(12, 9, 'CAF-001', 'Café en Grano/Molido Reserva Huila 500g', 'Notas a panela, frutos rojos y chocolate oscuro. Puntaje SCA 86.', 'Pitalito, Huila', 'Medio', 42000, 24000, 35, 8, 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80', 1),
(13, 1, 'TORED-84', 'Torta Red Velvet', 'Por porcion y completa', 'Huila, Colombia', 'N/A', 6000, 6000, 9, 3, 'https://tortasytortas.com.co/images/virtuemart/product/resized/red%20velvet%20web_640x200.jpg', 0),
(14, 9, 'SPEE-95', 'Speed Max', 'Bebida Energizante', 'Colombia', 'N/A', 3500, 25000, 25, 5, 'uploads/prod_1793473739_358.jpg', 0),
(15, 9, 'AGUILI45', 'Cerveza Aguila Ligth', 'Bebida Alcoholica', 'Colombia', 'N/A', 5000, 3500, 22, 5, 'uploads/prod_1793473266_255.jpg', 0),
(16, 10, 'PLAMC-073', 'Platanitos Mix de Chicharrón 40g', 'Chicharrón con Platanitos', 'Huila, Colombia', 'N/A', 35000, 2000, 8, 5, 'uploads/prod_1794080699_803.jpg', 0)
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
(2, 'Cajero Turno Mañana', 'caja@casadelbuencafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cajero'),
(3, 'Cajero de Turno / POS', 'cajero@casadelbuencafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cajero'),
(4, 'Barista / Jefe de Barra', 'barista@casadelbuencafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barista');
