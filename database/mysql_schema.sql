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

INSERT IGNORE INTO `categorias` (`id`, `nombre`, `descripcion`, `icono`) VALUES
(1, 'Café de Especialidad (Grano/Molido)', 'Bolsas de café de origen único tostado artesanalmente', 'fa-seedling'),
(2, 'Bebidas Calientes Espresso Bar', 'Preparaciones clásicas y filtrados al momento', 'fa-mug-hot'),
(3, 'Bebidas Frías & Cold Brew', 'Infusiones en frío, frappés y lattes helados', 'fa-glass-water'),
(4, 'Repostería Artesanal', 'Acompañamientos recién horneados para tu café', 'fa-cookie-bite'),
(5, 'Accesorios Barista', 'Prensas francesas, métodos V60, molinos y termos', 'fa-filter');

INSERT IGNORE INTO `productos` (`id`, `categoria_id`, `codigo`, `nombre`, `descripcion`, `origen`, `tueste`, `precio`, `costo`, `stock`, `stock_minimo`, `imagen_url`, `destacado`) VALUES
(1, 1, 'CAF-001', 'Reserva Especial Huila 500g', 'Notas a panela, frutos rojos y chocolate oscuro. Puntaje SCA 86.', 'Pitalito, Huila', 'Medio', 42000, 24000, 35, 8, 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80', 1),
(2, 1, 'CAF-002', 'Geisha Sierra Nevada 250g', 'Aroma floral a jazmín, acidez cítrica brillante y cuerpo sedoso.', 'Sierra Nevada, Magdalena', 'Medio-Claro', 68000, 38000, 14, 5, 'https://images.unsplash.com/photo-1587734195503-904fca47e0e9?auto=format&fit=crop&w=600&q=80', 1),
(3, 1, 'CAF-003', 'Blend Espresso Intenso 500g', 'Cuerpo denso, notas a nuez tostada y cacao amargo. Ideal para máquina.', 'Quindío / Antioquia', 'Medio-Oscuro', 38000, 21000, 4, 10, 'https://images.unsplash.com/photo-1611854779393-1b2da9d400fe?auto=format&fit=crop&w=600&q=80', 0),
(4, 2, 'BEB-001', 'Cappuccino Artesanal 8oz', 'Doble shot de espresso de origen con leche texturizada sedosa.', 'Huila, Colombia', 'Medio', 8500, 2800, 120, 15, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=600&q=80', 1),
(5, 2, 'BEB-002', 'Latte Vainilla & Caramelo 12oz', 'Espresso suave con jarabe artesanal de vainilla natural.', 'Quindío, Colombia', 'Medio', 10500, 3500, 95, 15, 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=600&q=80', 0),
(6, 2, 'BEB-003', 'Filtrado V60 de Origen', 'Extracción limpia que resalta las notas frutales y florales del grano.', 'Cauca, Colombia', 'Medio-Claro', 11000, 3200, 80, 10, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=600&q=80', 1),
(7, 3, 'FRI-001', 'Cold Brew Naranja & Romero', 'Infusión en frío por 18 horas con toque cítrico refrescante.', 'Sierra Nevada', 'Medio', 12500, 4000, 45, 10, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=600&q=80', 1),
(8, 3, 'FRI-002', 'Iced Mocha Belga', 'Espresso doble, chocolate real al 70%, leche fría y hielo.', 'Antioquia', 'Medio-Oscuro', 13500, 4500, 60, 10, 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=600&q=80', 0),
(9, 4, 'REP-001', 'Croissant de Almendras', 'Hojaldre francés con mantequilla pura y crema de almendras tostadas.', 'Horneado Local', 'N/A', 9500, 3800, 18, 6, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80', 1),
(10, 4, 'REP-002', 'Torta Húmeda de Café y Nueces', 'Bizcocho artesanal bañado en reducción de espresso y nuez pecana.', 'Horneado Local', 'N/A', 11500, 4200, 3, 5, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=600&q=80', 0),
(11, 5, 'ACC-001', 'Prensa Francesa Cobre 600ml', 'Acero inoxidable y vidrio borosilicato resistente al calor.', 'Importado', 'N/A', 85000, 48000, 12, 4, 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?auto=format&fit=crop&w=600&q=80', 0),
(12, 5, 'ACC-002', 'Kit Dripper V60 Cerámica + Filtros', 'Cono de goteo en cerámica blanca con 100 filtros de papel.', 'Japón', 'N/A', 98000, 55000, 8, 3, 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80', 0);

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


