-- ============================================================================
-- LA CASA DEL BUEN CAFÉ - ESQUEMA DE BASE DE DATOS PARA SUPABASE (PostgreSQL)
-- Instrucciones: Copia y pega este script en el "SQL Editor" de tu proyecto
-- en Supabase (https://supabase.com/dashboard) y haz clic en "Run".
-- ============================================================================

-- Habilitar extensión para UUIDs
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 1. TABLA DE CATEGORÍAS
CREATE TABLE IF NOT EXISTS public.categorias (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    icono VARCHAR(50) DEFAULT 'fa-mug-hot',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. TABLA DE PRODUCTOS (Catálogo + Inventario)
CREATE TABLE IF NOT EXISTS public.productos (
    id SERIAL PRIMARY KEY,
    categoria_id INTEGER REFERENCES public.categorias(id) ON DELETE SET NULL,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    origen VARCHAR(100) DEFAULT 'Huila, Colombia',
    tueste VARCHAR(50) DEFAULT 'Medio',
    precio NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    costo NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    stock INTEGER NOT NULL DEFAULT 0,
    stock_minimo INTEGER NOT NULL DEFAULT 5,
    imagen_url TEXT,
    destacado BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 3. TABLA DE CLIENTES
CREATE TABLE IF NOT EXISTS public.clientes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE,
    telefono VARCHAR(50),
    direccion TEXT,
    puntos INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 4. TABLA DE USUARIOS / PERFILES (Vinculada con Supabase Auth opcionalmente)
CREATE TABLE IF NOT EXISTS public.usuarios (
    id SERIAL PRIMARY KEY,
    auth_user_id UUID UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    rol VARCHAR(30) DEFAULT 'admin' CHECK (rol IN ('admin', 'cajero', 'barista', 'cliente')),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 5. TABLA DE PEDIDOS (Tanto POS como Tienda Online)
CREATE TABLE IF NOT EXISTS public.pedidos (
    id SERIAL PRIMARY KEY,
    folio VARCHAR(40) UNIQUE NOT NULL,
    cliente_id INTEGER REFERENCES public.clientes(id) ON DELETE SET NULL,
    cliente_nombre VARCHAR(150) DEFAULT 'Cliente Mostrador',
    usuario_id INTEGER REFERENCES public.usuarios(id) ON DELETE SET NULL,
    tipo VARCHAR(30) DEFAULT 'pos' CHECK (tipo IN ('pos', 'tienda_online')),
    metodo_pago VARCHAR(40) DEFAULT 'efectivo' CHECK (metodo_pago IN ('efectivo', 'tarjeta', 'transferencia')),
    subtotal NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    impuestos NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    total NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    estado VARCHAR(30) DEFAULT 'completado' CHECK (estado IN ('pendiente', 'preparando', 'completado', 'cancelado')),
    direccion_entrega TEXT,
    notas TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 6. TABLA DE DETALLE DE PEDIDOS
CREATE TABLE IF NOT EXISTS public.detalle_pedidos (
    id SERIAL PRIMARY KEY,
    pedido_id INTEGER NOT NULL REFERENCES public.pedidos(id) ON DELETE CASCADE,
    producto_id INTEGER REFERENCES public.productos(id) ON DELETE SET NULL,
    nombre_producto VARCHAR(150) NOT NULL,
    cantidad INTEGER NOT NULL DEFAULT 1,
    precio_unitario NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    subtotal NUMERIC(10, 2) NOT NULL DEFAULT 0.00
);

-- 7. TABLA DE MOVIMIENTOS DE INVENTARIO (Kardex)
CREATE TABLE IF NOT EXISTS public.movimientos_inventario (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER NOT NULL REFERENCES public.productos(id) ON DELETE CASCADE,
    tipo VARCHAR(30) NOT NULL CHECK (tipo IN ('entrada', 'salida', 'ajuste', 'venta')),
    cantidad INTEGER NOT NULL,
    stock_anterior INTEGER NOT NULL DEFAULT 0,
    stock_nuevo INTEGER NOT NULL DEFAULT 0,
    motivo TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================================
-- POLÍTICAS RLS (Row Level Security) PARA ACCESO DESDE LA API REST DE PHP
-- ============================================================================
ALTER TABLE public.categorias ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.productos ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.clientes ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.usuarios ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pedidos ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.detalle_pedidos ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.movimientos_inventario ENABLE ROW LEVEL SECURITY;

-- Políticas permisivas para rol anon y authenticated (Ideal para desarrollo/demo;
-- en producción puedes restringir escritura solo a usuarios autenticados)
DROP POLICY IF EXISTS "Acceso total categorias" ON public.categorias;
CREATE POLICY "Acceso total categorias" ON public.categorias FOR ALL USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "Acceso total productos" ON public.productos;
CREATE POLICY "Acceso total productos" ON public.productos FOR ALL USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "Acceso total clientes" ON public.clientes;
CREATE POLICY "Acceso total clientes" ON public.clientes FOR ALL USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "Acceso total usuarios" ON public.usuarios;
CREATE POLICY "Acceso total usuarios" ON public.usuarios FOR ALL USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "Acceso total pedidos" ON public.pedidos;
CREATE POLICY "Acceso total pedidos" ON public.pedidos FOR ALL USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "Acceso total detalle_pedidos" ON public.detalle_pedidos;
CREATE POLICY "Acceso total detalle_pedidos" ON public.detalle_pedidos FOR ALL USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "Acceso total movimientos_inventario" ON public.movimientos_inventario;
CREATE POLICY "Acceso total movimientos_inventario" ON public.movimientos_inventario FOR ALL USING (true) WITH CHECK (true);

-- ============================================================================
-- DATOS SEMILLA (SEED DATA) - LA CASA DEL BUEN CAFÉ
-- ============================================================================

INSERT INTO public.categorias (nombre, descripcion, icono) VALUES
('Café de Especialidad (Grano/Molido)', 'Bolsas de café de origen único tostado artesanalmente', 'fa-seedling'),
('Bebidas Calientes Espresso Bar', 'Preparaciones clásicas y filtrados al momento', 'fa-mug-hot'),
('Bebidas Frías & Cold Brew', 'Infusiones en frío, frappés y lattes helados', 'fa-glass-water'),
('Repostería Artesanal', 'Acompañamientos recién horneados para tu café', 'fa-cookie-bite'),
('Accesorios Barista', 'Prensas francesas, métodos V60, molinos y termos', 'fa-filter')
ON CONFLICT DO NOTHING;

INSERT INTO public.productos (categoria_id, codigo, nombre, descripcion, origen, tueste, precio, costo, stock, stock_minimo, imagen_url, destacado) VALUES
(1, 'CAF-001', 'Reserva Especial Huila 500g', 'Notas a panela, frutos rojos y chocolate oscuro. Puntaje SCA 86.', 'Pitalito, Huila', 'Medio', 42000, 24000, 35, 8, 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80', true),
(1, 'CAF-002', 'Geisha Sierra Nevada 250g', 'Aroma floral a jazmín, acidez cítrica brillante y cuerpo sedoso.', 'Sierra Nevada, Magdalena', 'Medio-Claro', 68000, 38000, 14, 5, 'https://images.unsplash.com/photo-1587734195503-904fca47e0e9?auto=format&fit=crop&w=600&q=80', true),
(1, 'CAF-003', 'Blend Espresso Intenso 500g', 'Cuerpo denso, notas a nuez tostada y cacao amargo. Ideal para máquina.', 'Quindío / Antioquia', 'Medio-Oscuro', 38000, 21000, 4, 10, 'https://images.unsplash.com/photo-1611854779393-1b2da9d400fe?auto=format&fit=crop&w=600&q=80', false),
(2, 'BEB-001', 'Cappuccino Artesanal 8oz', 'Doble shot de espresso de origen con leche texturizada sedosa.', 'Huila, Colombia', 'Medio', 8500, 2800, 120, 15, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=600&q=80', true),
(2, 'BEB-002', 'Latte Vainilla & Caramelo 12oz', 'Espresso suave con jarabe artesanal de vainilla natural.', 'Quindío, Colombia', 'Medio', 10500, 3500, 95, 15, 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=600&q=80', false),
(2, 'BEB-003', 'Filtrado V60 de Origen', 'Extracción limpia que resalta las notas frutales y florales del grano.', 'Cauca, Colombia', 'Medio-Claro', 11000, 3200, 80, 10, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=600&q=80', true),
(3, 'FRI-001', 'Cold Brew Naranja & Romero', 'Infusión en frío por 18 horas con toque cítrico refrescante.', 'Sierra Nevada', 'Medio', 12500, 4000, 45, 10, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=600&q=80', true),
(3, 'FRI-002', 'Iced Mocha Belga', 'Espresso doble, chocolate real al 70%, leche fría y hielo.', 'Antioquia', 'Medio-Oscuro', 13500, 4500, 60, 10, 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=600&q=80', false),
(4, 'REP-001', 'Croissant de Almendras', 'Hojaldre francés con mantequilla pura y crema de almendras tostadas.', 'Horneado Local', 'N/A', 9500, 3800, 18, 6, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80', true),
(4, 'REP-002', 'Torta Húmeda de Café y Nueces', 'Bizcocho artesanal bañado en reducción de espresso y nuez pecana.', 'Horneado Local', 'N/A', 11500, 4200, 3, 5, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=600&q=80', false),
(5, 'ACC-001', 'Prensa Francesa Cobre 600ml', 'Acero inoxidable y vidrio borosilicato resistente al calor.', 'Importado', 'N/A', 85000, 48000, 12, 4, 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?auto=format&fit=crop&w=600&q=80', false),
(5, 'ACC-002', 'Kit Dripper V60 Cerámica + Filtros', 'Cono de goteo en cerámica blanca con 100 filtros de papel.', 'Japón', 'N/A', 98000, 55000, 8, 3, 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80', false)
ON CONFLICT (codigo) DO NOTHING;

INSERT INTO public.clientes (nombre, email, telefono, direccion, puntos) VALUES
('Cliente Mostrador', 'mostrador@casadelbuencafe.com', '0000000000', 'Venta directa en tienda', 0),
('Estefanía Olaya', 'estefania@ejemplo.com', '3105551234', 'Calle 85 #14-20, Bogotá', 150),
('Carlos Mendoza', 'carlos.mendoza@ejemplo.com', '3158889900', 'Carrera 43A #10-50, Medellín', 85),
('Laura Valencia', 'laura.valencia@ejemplo.com', '3004443322', 'Avenida 6N #25-10, Cali', 220)
ON CONFLICT (email) DO NOTHING;

INSERT INTO public.usuarios (nombre, email, rol) VALUES
('Administrador Principal', 'admin@casadelbuencafe.com', 'admin'),
('Cajero Turno Mañana', 'caja@casadelbuencafe.com', 'cajero')
ON CONFLICT (email) DO NOTHING;

