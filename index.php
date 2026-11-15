<?php
/**
 * SITIO WEB #1: TIENDA EN LÍNEA EXCLUSIVA PARA CLIENTES
 * La Casa del Buen Café — Specialty Coffee Roasters eCommerce
 * URL: http://localhost/La_casa_del_buen_cafe/
 * Optimizado para alto rendimiento (Gzip/Brotli, WebP/AVIF, Debounce, Paginación,
 * SSE Streaming IA, Optimistic UI, RUM Web Vitals, Skeleton Loading, Cero N+1).
 */

// 1. COMPRESIÓN GZIP / BROTLI A NIVEL SERVIDOR
if (!ob_start('ob_gzhandler')) {
    ob_start();
}

require_once __DIR__ . '/includes/actions.php';

// 2. OPEN TELEMETRY / SERVER-TIMING HEADER
if (isset($db) && method_exists($db, 'getServerTimingHeader')) {
    header('Server-Timing: ' . $db->getServerTimingHeader());
}
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// 3. CONSULTAS SQL OPTIMIZADAS: SELECCIÓN EXCLUSIVA DE COLUMNAS REQUERIDAS (NO SELECT *)
$categorias = $db->select('categorias', [
    'select' => 'id, nombre, descripcion, icono, color',
    'order'  => 'id.asc'
]);

$productos = $db->select('productos', [
    'select' => 'id, codigo, nombre, descripcion, precio, stock, categoria_id, destacado, origen, imagen_url',
    'order'  => 'id.asc'
]);

$categoriasMap = [];
foreach ($categorias as $c) {
    $categoriasMap[(int)$c['id']] = $c;
}

/**
 * Clasifica cada producto en las etiquetas de filtro rápido del catálogo:
 * bebidas, empaquetados, comidas, tortas, empanadas
 */
function getProductFilterTags(array $prod, array $categoriasMap): string {
    $catId = (int)($prod['categoria_id'] ?? 0);
    $catName = mb_strtolower($categoriasMap[$catId]['nombre'] ?? '');
    $text = mb_strtolower(($prod['nombre'] ?? '') . ' ' . ($prod['descripcion'] ?? '') . ' ' . $catName);
    
    $tags = ['todos'];
    
    // Bebidas
    if ($catId === 9 || str_contains($catName, 'bebida') || preg_match('/cappuccino|cold brew|latte|espresso|filtrado|café|cafe|bebida|speed|cerveza|aguila|águila/i', $text)) {
        $tags[] = 'bebidas';
    }
    
    // Empaquetados (paquetes, bolsas, platanitos, galletas, café en grano/molido sellado)
    if (in_array($catId, [2, 6, 7, 8, 10]) || preg_match('/paquete|bolsa|platanito|galleta|500g|250g|grano|molido|empaquetad|chicharr/i', $text)) {
        $tags[] = 'empaquetados';
    }
    
    // Comidas (todas las empanadas, tortas, galletas, platanitos, repostería)
    if (in_array($catId, [1, 2, 3, 4, 5, 6, 7, 8, 10]) || preg_match('/torta|empanada|galleta|platanito|croissant|pan|bizcocho|comida|snack|chicharr/i', $text)) {
        $tags[] = 'comidas';
    }
    
    // Tortas
    if ($catId === 1 || preg_match('/torta|pastel|pie|bizcocho|velvet/i', $text)) {
        $tags[] = 'tortas';
    }
    
    // Empanadas
    if (in_array($catId, [3, 4, 5]) || preg_match('/empanada/i', $text)) {
        $tags[] = 'empanadas';
    }
    
    return implode(' ', array_unique($tags));
}

$lastOrderReceipt = $_SESSION['last_receipt'] ?? null;
unset($_SESSION['last_receipt']);

// 4. ELIMINACIÓN DE CONSULTAS N+1: SOLO SE CONSULTA PEDIDOS SI SE RASTREA UN FOLIO ESPECÍFICO
$folioBuscado = strtoupper(trim($_GET['track_folio'] ?? ''));
$pedidoRastreado = null;
$itemsRastreados = [];
if (!empty($folioBuscado)) {
    $pedidosEncontrados = $db->select('pedidos', [
        'select' => 'id, folio, cliente_nombre, estado, total, direccion_entrega, created_at',
        'where'  => ['folio' => $folioBuscado]
    ]);
    if (!empty($pedidosEncontrados[0])) {
        $pedidoRastreado = $pedidosEncontrados[0];
        $itemsRastreados = $db->select('detalle_pedidos', [
            'select' => 'nombre_producto, cantidad, precio_unitario, subtotal',
            'where'  => ['pedido_id' => $pedidoRastreado['id']]
        ]);
    }
}

$ipLocalRed = function_exists('obtenerIpLocalRed') ? obtenerIpLocalRed() : '127.0.0.1';
$urlMovilTienda = "http://{$ipLocalRed}/La_casa_del_buen_cafe/";
$urlMovilAdmin  = "http://{$ipLocalRed}/La_casa_del_buen_cafe/admin.php";
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#140B07">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Buen Café">
    <meta name="description" content="La Casa del Buen Café — Café de Especialidad 100% Colombiano, micro-lotes selectos del Huila, bebidas preparadas, tortas y empanadas artesanales. San Juan Plaza Neiva.">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="logo.php">
    <title>La Casa del Buen Café — Tienda Online de Café de Especialidad</title>
    <link rel="icon" type="image/jpeg" href="logo.php">

    <!-- 5. PRECONEXIÓN Y DNS-PREFETCH A CDNs PARA CARGA ULTRARRÁPIDA DE ASSETS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://images.unsplash.com">

    <!-- 6. LIMITACIÓN DE GROSORES DE FUENTES Y CARGA ASÍNCRONA CON SWAP -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #FAF7F2;
            color: #2D241E;
        }
        .font-display { font-family: 'Playfair Display', serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .coffee-gradient { background: linear-gradient(135deg, #140B07 0%, #2A170F 60%, #442517 100%); }
        .gold-gradient { background: linear-gradient(135deg, #D49A55 0%, #B87333 100%); }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 214, 200, 0.75);
        }
        .card-hover { transition: all 0.22s ease-out; }
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px -10px rgba(28, 16, 11, 0.12);
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* ========================================================== */
        /* ESTANDARIZACIÓN DE TIPOGRAFÍA SEGÚN REQUERIMIENTO DEL USUARIO: */
        /* Títulos: 20px | Botones: 14px | Párrafos: 14px              */
        /* ========================================================== */
        h1, h2, h3, h4, .section-title, .card-title, .modal-title, .hero-title {
            font-size: 20px !important;
            line-height: 1.35 !important;
            font-weight: 700 !important;
        }
        p, .paragraph, .body-text, .text-desc {
            font-size: 14px !important;
            line-height: 1.6 !important;
        }
        button, .btn, a.btn, input[type="submit"], input[type="button"], .button-text, .store-filter-btn {
            font-size: 14px !important;
        }

        /* ========================================================== */
        /* DISEÑO SKELETON LOADING CON EFECTO SHIMMER ANIMADO         */
        /* ========================================================== */
        .skeleton-shimmer {
            background: linear-gradient(90deg, #ede7de 25%, #f7f4ee 50%, #ede7de 75%);
            background-size: 200% 100%;
            animation: shimmerWave 1.4s infinite cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes shimmerWave {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }


    </style>
</head>
<body class="min-h-screen flex flex-col pb-16 md:pb-0 text-stone-800 selection:bg-amber-500 selection:text-stone-950">

    <!-- Contenedor de Notificaciones Toast -->
    <div id="toastContainer" class="fixed bottom-20 md:bottom-5 right-4 z-50 flex flex-col gap-2.5 pointer-events-none"></div>

    <!-- Cintillo Promocional Superior -->
    <div class="bg-[#120A06] text-amber-200/90 text-xs py-2 px-4 border-b border-amber-500/20">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-4">
                <span><i class="fa-solid fa-truck-fast text-amber-400 mr-1.5"></i> <strong>Envío Gratis</strong> en todos los cafés de origen y bebidas</span>
                <span class="hidden md:inline text-white/20">|</span>
                <span class="hidden md:inline"><i class="fa-solid fa-award text-amber-400 mr-1"></i> Gana <strong>1 Punto por cada $1.000 COP</strong> en tu compra</span>
            </div>
            <div class="flex items-center gap-3 sm:gap-4">
                <button type="button" onclick="document.getElementById('deviceModal').classList.remove('hidden')"
                        class="px-2.5 py-0.5 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 hover:bg-amber-500 hover:text-stone-950 font-bold transition flex items-center gap-1.5 text-[11px]">
                    <i class="fa-solid fa-mobile-screen-button"></i> Abrir en Celular / QR
                </button>
                <span class="text-white/20">|</span>
                <?php if (!empty($_SESSION['user']) && ($_SESSION['user']['rol'] ?? '') === 'cliente'): ?>
                    <a href="portal_clientes.php" class="text-amber-300 hover:text-white font-bold transition flex items-center gap-1.5 text-[11px]">
                        <i class="fa-solid fa-award text-amber-400"></i> Portal Clientes (<?= (int)($_SESSION['user']['puntos'] ?? 0) ?> pts)
                    </a>
                <?php else: ?>
                    <a href="portal_clientes.php" class="text-amber-300 hover:text-white font-bold transition flex items-center gap-1 text-[11px]">
                        <i class="fa-solid fa-award"></i> Portal Especial Clientes
                    </a>
                <?php endif; ?>
                <span class="text-white/20">|</span>
                <a href="portal_privado.php" class="text-stone-400 hover:text-amber-300 transition flex items-center gap-1 text-[11px]">
                    <i class="fa-solid fa-lock text-amber-400"></i> Portal Privado Personal
                </a>
            </div>
        </div>
    </div>

    <!-- Navbar Principal de la Tienda Pública -->
    <header class="coffee-gradient text-white sticky top-0 z-30 shadow-xl border-b border-amber-500/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between gap-4">
            <a href="index.php" class="flex items-center gap-3.5 group">
                <img src="logo.php" alt="La Casa del Buen Café" width="56" height="56" fetchpriority="high"
                     class="w-14 h-14 rounded-full object-cover shadow-lg border-2 border-amber-300/70 group-hover:scale-105 transition">
                <div>
                    <span class="font-display font-bold text-xl sm:text-2xl tracking-wide block leading-none text-amber-50">
                        La Casa del Buen Café
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.22em] text-amber-400 font-semibold block mt-1">
                        Tienda Oficial · Café 100% Colombiano
                    </span>
                </div>
            </a>

            <!-- Enlaces de Navegación del Cliente -->
            <nav class="hidden md:flex items-center gap-7 text-xs font-bold uppercase tracking-wider text-stone-200">
                <a href="#inicio" class="hover:text-amber-400 transition">Inicio</a>
                <a href="#catalogo" class="hover:text-amber-400 transition">Nuestro Catálogo</a>
                <a href="#galeria-real" class="hover:text-amber-400 transition">Fotos Reales</a>
                <a href="#experiencia" class="hover:text-amber-400 transition">Experiencia & Tostión</a>
                <a href="#rastrear" class="hover:text-amber-400 transition">Rastrear Pedido</a>
                <a href="#ubicacion" class="hover:text-amber-400 transition flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-amber-400"></i> Ubicación</a>
            </nav>

            <!-- Botón del Carrito Deslizante con Actualización Optimista -->
            <button type="button" onclick="toggleCartDrawer(true)"
                    class="relative px-4 py-2.5 rounded-2xl gold-gradient text-stone-950 font-extrabold text-xs sm:text-sm shadow-lg hover:scale-105 transition flex items-center gap-2.5">
                <i class="fa-solid fa-bag-shopping text-base"></i>
                <span class="hidden sm:inline">Mi Bolsa</span>
                <span id="headerCartTotal" class="font-mono font-bold">$0</span>
                <span id="headerCartBadge" class="px-2 py-0.5 rounded-full bg-stone-950 text-amber-400 text-[11px] font-mono font-extrabold">
                    0
                </span>
            </button>
        </div>
    </header>

    <!-- Mensajes Flash -->
    <?php if ($flash): ?>
        <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 pt-5">
            <div class="p-4 rounded-2xl bg-emerald-950 text-emerald-100 border border-emerald-500/50 flex items-center justify-between shadow-xl text-xs sm:text-sm font-semibold">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-400 text-lg"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-white/70 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- 7. HERO PRINCIPAL CON IMÁGENES AVIF / WEBP Y CARGA PRIORITARIA -->
    <section id="inicio" class="max-w-7xl w-full mx-auto px-4 sm:px-6 pt-6 pb-4">
        <div class="relative rounded-3xl overflow-hidden shadow-xl border border-stone-800/40 min-h-[300px] flex items-center">
            <picture>
                <source srcset="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1600&fm=avif&q=75" type="image/avif">
                <source srcset="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1600&fm=webp&q=80" type="image/webp">
                <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1600&q=80"
                     alt="Café de Especialidad Colombiano"
                     fetchpriority="high"
                     decoding="async"
                     class="absolute inset-0 w-full h-full object-cover">
            </picture>
            <div class="absolute inset-0 bg-gradient-to-r from-[#120A06]/95 via-[#1C100B]/85 to-black/40"></div>

            <div class="relative z-10 p-6 sm:p-10 w-full flex flex-col lg:flex-row items-center justify-between gap-6 text-white">
                <div class="max-w-2xl space-y-3">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold gold-gradient text-stone-950 shadow-sm">
                        <i class="fa-solid fa-seedling"></i> Cosecha Selecta de Altura · SCA 86+
                    </span>
                    <h1 class="font-display text-[20px] font-bold leading-snug text-white">
                        El arte del buen café, <span class="text-amber-400 italic">recién tostado</span> para ti.
                    </h1>
                    <p class="text-[14px] text-stone-200 leading-relaxed max-w-xl">
                        Descubre micro-lotes de Huila, bebidas preparadas por baristas expertos y repostería artesanal recién horneada. Pide en línea y recibe en casa o retira en barra sin filas.
                    </p>
                    <div class="flex flex-wrap gap-3 pt-1">
                        <a href="#catalogo" class="px-5 py-2.5 rounded-xl gold-gradient text-stone-950 font-bold text-[14px] shadow-md hover:opacity-95 transition flex items-center gap-2">
                            <i class="fa-solid fa-mug-saucer"></i> Ver Menú y Catálogo
                        </a>
                        <a href="#rastrear" class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-[14px] border border-white/20 backdrop-blur-md transition flex items-center gap-2">
                            <i class="fa-solid fa-box-open text-amber-400"></i> Estado de mi Pedido
                        </a>
                    </div>
                </div>

                <!-- Sello Oficial de La Casa del Buen Café en el Hero -->
                <div class="shrink-0">
                    <div class="p-1.5 rounded-full bg-white/10 shadow-xl backdrop-blur-sm border border-amber-300/40">
                        <img src="logo.php" alt="Logo Oficial La Casa del Buen Café" width="130" height="130"
                             class="w-28 h-28 sm:w-36 sm:h-36 rounded-full object-cover shadow border-2 border-amber-300">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sellos de Confianza / Orígenes -->
    <section id="origenes" class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
            <div class="glass-card rounded-2xl p-3.5 flex items-center gap-3 shadow-sm border border-stone-200/70">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center text-base shrink-0">
                    <i class="fa-solid fa-mountain-sun"></i>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900 text-[14px]">100% Origen Único</h3>
                    <p class="text-[12px] text-stone-500">Fincas entre 1.650 y 2.000 m</p>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-3.5 flex items-center gap-3 shadow-sm border border-stone-200/70">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center text-base shrink-0">
                    <i class="fa-solid fa-fire-flame-curved"></i>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900 text-[14px]">Tostión Artesanal</h3>
                    <p class="text-[12px] text-stone-500">En grano o molienda fresca</p>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-3.5 flex items-center gap-3 shadow-sm border border-stone-200/70">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center text-base shrink-0">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900 text-[14px]">Entrega Express</h3>
                    <p class="text-[12px] text-stone-500">A domicilio o en barra</p>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-3.5 flex items-center gap-3 shadow-sm border border-stone-200/70">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center text-base shrink-0">
                    <i class="fa-solid fa-gift"></i>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900 text-[14px]">Club del Buen Café</h3>
                    <p class="text-[12px] text-stone-500">Puntos automáticos</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Catálogo Principal de Productos con Filtros Específicos -->
    <section id="catalogo" class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-6 space-y-5">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-amber-800">Menú & Tostaduría</span>
                <h2 class="font-display text-[20px] font-bold text-stone-900 leading-snug">Explora Nuestra Selección</h2>
                <p class="text-[14px] text-stone-500 mt-0.5">Bebidas preparadas, empaquetados, comidas, tortas y empanadas artesanales</p>
            </div>

            <!-- Buscador con Debounce, Autocompletado y AbortController -->
            <div class="flex flex-col sm:flex-row gap-2.5 w-full md:w-auto">
                <div class="relative sm:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-stone-400 text-xs"></i>
                    <input type="text" id="storeSearch" oninput="handleDebouncedSearch(this.value)"
                           placeholder="Buscar tortas, empanadas, bebidas..."
                           autocomplete="off"
                           class="w-full pl-9 pr-8 py-2 rounded-xl border border-stone-300 bg-white text-[14px] focus:outline-none focus:ring-2 focus:ring-amber-700">
                    <button type="button" id="clearSearchBtn" onclick="clearSearch()" class="hidden absolute right-3 top-2.5 text-stone-400 hover:text-stone-600 text-xs">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                    <!-- Sugerencias de Autocompletado -->
                    <div id="searchAutocomplete" class="hidden absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-stone-200 z-20 overflow-hidden divide-y divide-stone-100"></div>
                </div>
                <select id="storeSort" onchange="sortStoreProducts()" class="px-3.5 py-2 rounded-xl border border-stone-300 bg-white text-[14px] font-semibold text-stone-700">
                    <option value="featured">Destacados</option>
                    <option value="price_asc">Menor precio</option>
                    <option value="price_desc">Mayor precio</option>
                </select>
            </div>
        </div>

        <!-- Filtros Solicitados: Bebidas, Empaquetados, Comidas, Tortas y Empanadas -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar" id="catalogFiltersBar">
            <button type="button" onclick="setCatalogFilter('todos')" data-filter-tag="todos"
                    class="store-filter-btn px-4 py-2.5 rounded-xl font-bold bg-[#1C100B] text-amber-300 shadow-sm border border-amber-500/30 whitespace-nowrap transition flex items-center gap-2 text-[14px]">
                <i class="fa-solid fa-layer-group text-sm"></i>
                <span>Todos los Productos (<?= count($productos) ?>)</span>
            </button>
            <button type="button" onclick="setCatalogFilter('bebidas')" data-filter-tag="bebidas"
                    class="store-filter-btn px-4 py-2.5 rounded-xl font-semibold bg-white text-stone-700 hover:bg-amber-50 border border-stone-200/80 whitespace-nowrap transition flex items-center gap-2 text-[14px]">
                <i class="fa-solid fa-mug-hot text-amber-700 text-sm"></i>
                <span>Bebidas</span>
            </button>
            <button type="button" onclick="setCatalogFilter('empaquetados')" data-filter-tag="empaquetados"
                    class="store-filter-btn px-4 py-2.5 rounded-xl font-semibold bg-white text-stone-700 hover:bg-amber-50 border border-stone-200/80 whitespace-nowrap transition flex items-center gap-2 text-[14px]">
                <i class="fa-solid fa-box-open text-amber-700 text-sm"></i>
                <span>Empaquetados</span>
            </button>
            <button type="button" onclick="setCatalogFilter('comidas')" data-filter-tag="comidas"
                    class="store-filter-btn px-4 py-2.5 rounded-xl font-semibold bg-white text-stone-700 hover:bg-amber-50 border border-stone-200/80 whitespace-nowrap transition flex items-center gap-2 text-[14px]">
                <i class="fa-solid fa-utensils text-amber-700 text-sm"></i>
                <span>Comidas</span>
            </button>
            <button type="button" onclick="setCatalogFilter('tortas')" data-filter-tag="tortas"
                    class="store-filter-btn px-4 py-2.5 rounded-xl font-semibold bg-white text-stone-700 hover:bg-amber-50 border border-stone-200/80 whitespace-nowrap transition flex items-center gap-2 text-[14px]">
                <i class="fa-solid fa-cake-candles text-amber-700 text-sm"></i>
                <span>Tortas</span>
            </button>
            <button type="button" onclick="setCatalogFilter('empanadas')" data-filter-tag="empanadas"
                    class="store-filter-btn px-4 py-2.5 rounded-xl font-semibold bg-white text-stone-700 hover:bg-amber-50 border border-stone-200/80 whitespace-nowrap transition flex items-center gap-2 text-[14px]">
                <i class="fa-solid fa-drumstick-bite text-amber-700 text-sm"></i>
                <span>Empanadas</span>
            </button>
        </div>

        <!-- 8. DISEÑO SKELETON PLACEHOLDER DE CARGA -->
        <div id="catalogSkeletonContainer" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php for ($sk = 0; $sk < 6; $sk++): ?>
                <div class="rounded-2xl overflow-hidden glass-card p-4 space-y-3 border border-stone-200/80">
                    <div class="h-44 rounded-xl skeleton-shimmer"></div>
                    <div class="h-5 w-3/4 rounded-lg skeleton-shimmer"></div>
                    <div class="h-4 w-full rounded-lg skeleton-shimmer"></div>
                    <div class="flex justify-between items-center pt-2">
                        <div class="h-6 w-20 rounded-lg skeleton-shimmer"></div>
                        <div class="h-10 w-28 rounded-xl skeleton-shimmer"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- 9. GRID DE PRODUCTOS SSR CON LAZY LOADING & DECODING ASYNC -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="storeProductsGrid">
            <?php foreach ($productos as $prod): ?>
                <?php
                $agotado = ((int)$prod['stock'] <= 0);
                $tags = getProductFilterTags($prod, $categoriasMap);
                $catId = (int)($prod['categoria_id'] ?? 0);
                $catNombre = $categoriasMap[$catId]['nombre'] ?? 'Especialidad';
                $imgUrl = !empty($prod['imagen_url']) ? $prod['imagen_url'] : 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&fm=webp&q=80';
                ?>
                <div class="store-product-card glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between shadow-sm border border-stone-200/80"
                     data-cat="<?= $catId ?>"
                     data-tags="<?= htmlspecialchars($tags) ?>"
                     data-price="<?= (float)$prod['precio'] ?>"
                     data-featured="<?= !empty($prod['destacado']) ? '1' : '0' ?>"
                     data-search="<?= htmlspecialchars(strtolower($prod['nombre'] . ' ' . ($prod['descripcion'] ?? '') . ' ' . ($prod['origen'] ?? '') . ' ' . $catNombre)) ?>">
                    <div>
                        <div class="relative h-48 bg-stone-100 overflow-hidden group">
                            <?php if (str_starts_with($imgUrl, 'http')): ?>
                                <picture>
                                    <source srcset="<?= htmlspecialchars($imgUrl) ?>&fm=avif" type="image/avif">
                                    <source srcset="<?= htmlspecialchars($imgUrl) ?>&fm=webp" type="image/webp">
                                    <img src="<?= htmlspecialchars($imgUrl) ?>"
                                         alt="<?= htmlspecialchars($prod['nombre']) ?>"
                                         loading="lazy"
                                         decoding="async"
                                         width="400"
                                         height="250"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                </picture>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($imgUrl) ?>"
                                     alt="<?= htmlspecialchars($prod['nombre']) ?>"
                                     loading="lazy"
                                     decoding="async"
                                     width="400"
                                     height="250"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <?php endif; ?>
                            
                            <?php if (!empty($prod['destacado'])): ?>
                                <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-xs font-bold gold-gradient text-stone-950 shadow-sm">
                                    <i class="fa-solid fa-star text-[10px] mr-1"></i> Favorito
                                </span>
                            <?php endif; ?>

                            <div class="absolute bottom-2.5 left-3 right-3 flex items-center justify-between text-white">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-black/60 backdrop-blur-sm text-stone-200">
                                    <i class="fa-solid fa-tag text-[10px] text-amber-300 mr-1"></i> <?= htmlspecialchars($catNombre) ?>
                                </span>
                                <?php if (!empty($prod['origen']) && $prod['origen'] !== 'N/A'): ?>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-black/60 backdrop-blur-sm text-stone-300">
                                        <?= htmlspecialchars($prod['origen']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-4 space-y-1.5">
                            <h3 class="font-display font-bold text-stone-900 text-[20px] leading-snug line-clamp-1"><?= htmlspecialchars($prod['nombre']) ?></h3>
                            <p class="text-[14px] text-stone-600 line-clamp-2 leading-relaxed"><?= htmlspecialchars($prod['descripcion'] ?? '') ?></p>
                        </div>
                    </div>

                    <div class="px-4 pb-4 pt-2.5 border-t border-stone-100 flex items-center justify-between">
                        <div>
                            <span class="text-xs uppercase tracking-wider text-stone-400 block font-semibold">Precio</span>
                            <span class="text-xl font-extrabold font-mono text-[#1C100B]">$<?= number_format((float)$prod['precio'], 0, ',', '.') ?></span>
                            <span class="block text-xs <?= $agotado ? 'text-red-600 font-bold' : 'text-emerald-700 font-medium' ?>">
                                <?= $agotado ? 'Agotado' : '● Disponible' ?>
                            </span>
                        </div>
                        <button type="button"
                                <?= $agotado ? 'disabled' : '' ?>
                                onclick='addToStoreCart(<?= json_encode([
                                    "id" => (int)$prod["id"],
                                    "nombre" => $prod["nombre"],
                                    "precio" => (float)$prod["precio"],
                                    "stock" => (int)$prod["stock"],
                                    "codigo" => $prod["codigo"]
                                ]) ?>)'
                                class="px-4 py-2.5 rounded-xl text-[14px] font-bold transition flex items-center gap-2 <?= $agotado ? 'bg-stone-200 text-stone-400 cursor-not-allowed' : 'coffee-gradient hover:opacity-95 text-amber-200 shadow-sm hover:shadow' ?>">
                            <i class="fa-solid fa-cart-plus text-amber-400"></i> Comprar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 10. CONTROLES DE PAGINACIÓN / VIRTUALIZACIÓN DE LISTA LARGA -->
        <div id="catalogPaginationBar" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-stone-200 text-xs">
            <span id="paginationInfoLabel" class="text-stone-500 font-medium">Mostrando productos del menú</span>
            <div class="flex items-center gap-2">
                <button type="button" id="prevPageBtn" onclick="changeCatalogPage(-1)" class="px-3.5 py-1.5 rounded-xl bg-white border border-stone-300 text-stone-700 font-bold hover:bg-amber-50 disabled:opacity-40 disabled:cursor-not-allowed transition">
                    <i class="fa-solid fa-chevron-left mr-1"></i> Anterior
                </button>
                <span id="currentPageIndicator" class="px-3 py-1 font-mono font-bold text-amber-900 bg-amber-100 rounded-lg">1 / 1</span>
                <button type="button" id="nextPageBtn" onclick="changeCatalogPage(1)" class="px-3.5 py-1.5 rounded-xl bg-white border border-stone-300 text-stone-700 font-bold hover:bg-amber-50 disabled:opacity-40 disabled:cursor-not-allowed transition">
                    Siguiente <i class="fa-solid fa-chevron-right ml-1"></i>
                </button>
            </div>
        </div>

        <div id="storeEmptyState" class="hidden py-12 text-center space-y-2 bg-white rounded-2xl border border-stone-200">
            <i class="fa-solid fa-magnifying-glass text-3xl text-stone-300"></i>
            <h3 class="font-display font-bold text-stone-800 text-[20px]">No se encontraron productos</h3>
            <p class="text-[14px] text-stone-500">Prueba con otro filtro o término de búsqueda.</p>
        </div>
    </section>

    <!-- 11. SECCIÓN VISUAL DE EXPERIENCIA & TOSTADURÍA CON IMÁGENES WEBP/AVIF -->
    <section id="experiencia" class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
        <div class="rounded-3xl bg-[#1C100B] text-white p-6 sm:p-10 border border-stone-800 shadow-xl space-y-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-widest text-amber-400">Origen & Pasión</span>
                    <h2 class="font-display font-bold text-[20px] text-white">Nuestra Experiencia de Grano a Taza</h2>
                    <p class="text-[14px] text-stone-300">Conoce los pilares que hacen de cada preparación una experiencia memorable en Neiva.</p>
                </div>
                <a href="#catalogo" class="px-5 py-2.5 rounded-xl gold-gradient text-stone-950 font-bold text-[14px] self-start md:self-auto flex items-center gap-2 shadow hover:opacity-95 transition">
                    <i class="fa-solid fa-mug-saucer"></i> Explorar Nuestro Menú
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 pt-2">
                <div class="rounded-2xl overflow-hidden bg-white/5 border border-white/10 flex flex-col group">
                    <div class="h-44 overflow-hidden relative">
                        <picture>
                            <source srcset="https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&fm=avif&q=75" type="image/avif">
                            <source srcset="https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&fm=webp&q=80" type="image/webp">
                            <img src="https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80"
                                 alt="Barismo de Especialidad" loading="lazy" decoding="async"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </picture>
                        <span class="absolute bottom-2 left-2 px-2.5 py-0.5 rounded-md bg-black/70 backdrop-blur-sm text-[11px] font-bold text-amber-300">
                            Barismo Profesional
                        </span>
                    </div>
                    <div class="p-4 space-y-1 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-[20px] text-white leading-snug">Extracciones Precisas</h3>
                            <p class="text-[14px] text-stone-300 mt-1">Recetas calibradas al gramo para V60, Chemex, Aeropress y espresso doble.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl overflow-hidden bg-white/5 border border-white/10 flex flex-col group">
                    <div class="h-44 overflow-hidden relative">
                        <picture>
                            <source srcset="https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=600&fm=avif&q=75" type="image/avif">
                            <source srcset="https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=600&fm=webp&q=80" type="image/webp">
                            <img src="https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=600&q=80"
                                 alt="Granos de Café Tostados" loading="lazy" decoding="async"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </picture>
                        <span class="absolute bottom-2 left-2 px-2.5 py-0.5 rounded-md bg-black/70 backdrop-blur-sm text-[11px] font-bold text-amber-300">
                            Micro-Lotes Huila
                        </span>
                    </div>
                    <div class="p-4 space-y-1 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-[20px] text-white leading-snug">Café de Altura 100% Huila</h3>
                            <p class="text-[14px] text-stone-300 mt-1">Cultivado por caficultores locales entre 1.650 y 2.000 metros de altitud.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl overflow-hidden bg-white/5 border border-white/10 flex flex-col group">
                    <div class="h-44 overflow-hidden relative">
                        <picture>
                            <source srcset="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&fm=avif&q=75" type="image/avif">
                            <source srcset="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&fm=webp&q=80" type="image/webp">
                            <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&q=80"
                                 alt="Panadería y Tortas Artesanales" loading="lazy" decoding="async"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </picture>
                        <span class="absolute bottom-2 left-2 px-2.5 py-0.5 rounded-md bg-black/70 backdrop-blur-sm text-[11px] font-bold text-amber-300">
                            Horneado Diario
                        </span>
                    </div>
                    <div class="p-4 space-y-1 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-[20px] text-white leading-snug">Repostería & Empanadas</h3>
                            <p class="text-[14px] text-stone-300 mt-1">Tortas caseras húmedas, empanadas recién horneadas y snacks auténticos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================================== -->
    <!-- VITRINA DE FOTOS REALES: PRODUCTOS Y EMPAQUES DIRECTOS DE BARRA    -->
    <!-- ================================================================== -->
    <section id="galeria-real" class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-amber-800">Vitrina Auténtica</span>
                <h2 class="font-display font-bold text-stone-900 text-[20px] leading-snug">Galería de Fotos Reales de Nuestros Productos</h2>
                <p class="text-[14px] text-stone-500 mt-0.5">Imágenes auténticas tomadas directamente en nuestro local de San Juan Plaza Neiva.</p>
            </div>
            <a href="#catalogo" class="text-amber-800 hover:text-amber-950 font-bold text-[14px] flex items-center gap-1.5 self-start sm:self-auto transition">
                <span>Ver catálogo completo</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <!-- 1. Empanada Mixta Especial -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-stone-200/80 shadow-sm group">
                <div class="relative h-44 bg-stone-100 overflow-hidden">
                    <img src="uploads/prod_1793470733_605.png" alt="Empanada Mixta Especial" loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-white font-bold text-[11px]">
                        Recién Horneada
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">Empanada Mixta Especial</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">Carne desmechada, pollo y queso fundido.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="font-mono font-extrabold text-[#1C100B] text-base">$6.000</span>
                        <button type="button" onclick='addToStoreCart({"id":4,"nombre":"Empanada Mixta Especial","precio":6000,"stock":10,"codigo":"EMP-001"})'
                                class="px-3 py-1.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] hover:opacity-95 transition">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Empanada Tradicional de Carne -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-stone-200/80 shadow-sm group">
                <div class="relative h-44 bg-stone-100 overflow-hidden">
                    <img src="uploads/prod_1793470620_604.png" alt="Empanada de Carne" loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-white font-bold text-[11px]">
                        Artesanal
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">Empanada de Carne</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">Masa crocante rellena de carne sazonada.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="font-mono font-extrabold text-[#1C100B] text-base">$6.000</span>
                        <button type="button" onclick='addToStoreCart({"id":5,"nombre":"Empanada de Carne","precio":6000,"stock":10,"codigo":"EMP-002"})'
                                class="px-3 py-1.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] hover:opacity-95 transition">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Empanada Criolla de Pollo -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-stone-200/80 shadow-sm group">
                <div class="relative h-44 bg-stone-100 overflow-hidden">
                    <img src="uploads/prod_1793470380_426.png" alt="Empanada de Pollo" loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-white font-bold text-[11px]">
                        Dorada y Crujiente
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">Empanada de Pollo</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">Pechuga desmechada con guiso criollo.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="font-mono font-extrabold text-[#1C100B] text-base">$6.000</span>
                        <button type="button" onclick='addToStoreCart({"id":6,"nombre":"Empanada de Pollo","precio":6000,"stock":10,"codigo":"EMP-69"})'
                                class="px-3 py-1.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] hover:opacity-95 transition">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. Platanitos Mix de Chicharrón 40g -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-stone-200/80 shadow-sm group">
                <div class="relative h-44 bg-stone-100 overflow-hidden">
                    <img src="uploads/prod_1794080699_803.jpg" alt="Platanitos Mix de Chicharrón" loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg gold-gradient text-stone-950 font-bold text-[11px] shadow">
                        Snack Estrella
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">Mix de Chicharrón 40g</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">Crocante chicharrón con plátanos seleccionados.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="font-mono font-extrabold text-[#1C100B] text-base">$35.000</span>
                        <button type="button" onclick='addToStoreCart({"id":16,"nombre":"Platanitos Mix de Chicharrón 40g","precio":35000,"stock":8,"codigo":"PLAMC-073"})'
                                class="px-3 py-1.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] hover:opacity-95 transition">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 5. Platanitos Verdes Sabor Natural 40g -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-stone-200/80 shadow-sm group">
                <div class="relative h-44 bg-stone-100 overflow-hidden">
                    <img src="uploads/prod_1794073674_501.jpg" alt="Platanitos Verdes" loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-white font-bold text-[11px]">
                        Extra Crocante
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">Platanitos Verdes 40g</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">Tajaditas crocantes con sal marina.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="font-mono font-extrabold text-[#1C100B] text-base">$3.500</span>
                        <button type="button" onclick='addToStoreCart({"id":9,"nombre":"Platanitos Verdes Sabor Natural 40g","precio":3500,"stock":5,"codigo":"PLA-003"})'
                                class="px-3 py-1.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] hover:opacity-95 transition">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 6. Paquete Platanitos Mixtos 150g -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-stone-200/80 shadow-sm group">
                <div class="relative h-44 bg-stone-100 overflow-hidden">
                    <img src="uploads/prod_1794079260_727.jpg" alt="Platanitos Mixtos" loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-white font-bold text-[11px]">
                        Verde & Maduro
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">Platanitos Mixtos 150g</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">Combinación perfecta dulce y salado.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="font-mono font-extrabold text-[#1C100B] text-base">$6.500</span>
                        <button type="button" onclick='addToStoreCart({"id":7,"nombre":"Paquete Platanitos Mixtos 150g","precio":6500,"stock":50,"codigo":"PLA-001"})'
                                class="px-3 py-1.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] hover:opacity-95 transition">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 7. Platanitos Maduros Dulces 150g -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-stone-200/80 shadow-sm group">
                <div class="relative h-44 bg-stone-100 overflow-hidden">
                    <img src="uploads/prod_1794079588_669.jpg" alt="Platanitos Maduros" loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-white font-bold text-[11px]">
                        Dulzor Natural
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">Platanitos Maduros 150g</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">Tajaditas maduras seleccionadas y doradas.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="font-mono font-extrabold text-[#1C100B] text-base">$6.000</span>
                        <button type="button" onclick='addToStoreCart({"id":8,"nombre":"Platanitos Maduros Dulces 150g","precio":6000,"stock":40,"codigo":"PLA-002"})'
                                class="px-3 py-1.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] hover:opacity-95 transition">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 8. Speed Max Energizante -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-stone-200/80 shadow-sm group">
                <div class="relative h-44 bg-stone-100 overflow-hidden">
                    <img src="uploads/prod_1793473739_358.jpg" alt="Speed Max" loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-white font-bold text-[11px]">
                        Bien Frío
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">Speed Max Energizante</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">Bebida fría energizante para tu día.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="font-mono font-extrabold text-[#1C100B] text-base">$3.500</span>
                        <button type="button" onclick='addToStoreCart({"id":14,"nombre":"Speed Max Energizante","precio":3500,"stock":25,"codigo":"SPEE-95"})'
                                class="px-3 py-1.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] hover:opacity-95 transition">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 9. Cerveza Águila Light -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-stone-200/80 shadow-sm group">
                <div class="relative h-44 bg-stone-100 overflow-hidden">
                    <img src="uploads/prod_1793473266_255.jpg" alt="Cerveza Águila Light" loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg bg-black/70 backdrop-blur-sm text-white font-bold text-[11px]">
                        Refrescante
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">Cerveza Águila Light</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">Cerveza nacional ligera en botella fría.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="font-mono font-extrabold text-[#1C100B] text-base">$5.000</span>
                        <button type="button" onclick='addToStoreCart({"id":15,"nombre":"Cerveza Águila Light","precio":5000,"stock":22,"codigo":"AGUILI45"})'
                                class="px-3 py-1.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] hover:opacity-95 transition">
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 10. Sello Oficial La Casa del Buen Café -->
            <div class="glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between border border-amber-300/80 shadow-sm group bg-amber-50/50">
                <div class="relative h-44 bg-[#140B07] flex items-center justify-center p-3">
                    <img src="logo.php" alt="Logo La Casa del Buen Café" loading="lazy" decoding="async"
                         class="w-28 h-28 rounded-full object-cover shadow-lg border-2 border-amber-300 group-hover:scale-110 transition duration-300">
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-lg gold-gradient text-stone-950 font-bold text-[11px] shadow">
                        Marca Oficial
                    </span>
                </div>
                <div class="p-3.5 space-y-1">
                    <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight line-clamp-1">La Casa del Buen Café</h3>
                    <p class="text-[14px] text-stone-500 line-clamp-2">San Juan Plaza · Local 151 Neiva, Huila.</p>
                    <div class="pt-2 flex items-center justify-between border-t border-stone-100">
                        <span class="text-xs font-bold text-amber-800">100% Colombiano</span>
                        <a href="#ubicacion" class="px-3 py-1.5 rounded-xl bg-stone-100 hover:bg-amber-100 text-stone-900 font-bold text-[14px] transition">
                            Visitar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Rastreo de Pedido en Vivo para el Cliente -->
    <section id="rastrear" class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-6">
        <div class="glass-card rounded-2xl p-6 sm:p-8 shadow-sm border border-stone-200/80">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <div class="lg:col-span-5 space-y-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-amber-800">Seguimiento en Línea</span>
                    <h2 class="font-display font-bold text-stone-900 text-[20px] leading-snug">¿Ya hiciste tu pedido? Rastréalo aquí</h2>
                    <p class="text-[14px] text-stone-500 leading-relaxed">Ingresa el número de folio de tu compra (ej. <code>WEB-1001</code>) para consultar si tu orden está en preparación o lista.</p>
                    <form method="GET" action="index.php#rastrear" class="flex gap-2 pt-2">
                        <input type="text" name="track_folio" value="<?= htmlspecialchars($folioBuscado) ?>" required
                               placeholder="Ej. WEB-1001"
                               class="flex-1 px-4 py-2.5 rounded-xl border border-stone-300 font-mono text-[14px] uppercase focus:outline-none focus:ring-2 focus:ring-amber-700">
                        <button type="submit" class="px-5 py-2.5 rounded-xl coffee-gradient text-amber-200 font-bold text-[14px] shadow-sm hover:opacity-95 transition">
                            Consultar
                        </button>
                    </form>
                </div>

                <div class="lg:col-span-7">
                    <?php if (!empty($folioBuscado)): ?>
                        <?php if ($pedidoRastreado): ?>
                            <?php $est = $pedidoRastreado['estado'] ?? 'pendiente'; ?>
                            <div class="bg-white rounded-2xl p-5 border border-stone-200 shadow-sm space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-stone-100">
                                    <div>
                                        <span class="font-mono font-extrabold text-[20px] text-stone-900"><?= htmlspecialchars($pedidoRastreado['folio']) ?></span>
                                        <p class="text-[14px] text-stone-500">Cliente: <strong><?= htmlspecialchars($pedidoRastreado['cliente_nombre']) ?></strong> · Destino: <?= htmlspecialchars($pedidoRastreado['direccion_entrega'] ?? 'Entrega en barra') ?></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $est === 'completado' ? 'bg-emerald-100 text-emerald-800' : ($est === 'preparando' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800') ?>">
                                        <i class="fa-solid fa-mug-hot mr-1"></i> <?= htmlspecialchars($est) ?>
                                    </span>
                                </div>
                                <div class="space-y-1.5 text-[14px]">
                                    <?php foreach ($itemsRastreados as $it): ?>
                                        <div class="flex justify-between text-stone-700">
                                            <span><strong><?= (int)$it['cantidad'] ?>x</strong> <?= htmlspecialchars($it['nombre_producto']) ?></span>
                                            <span class="font-mono font-bold">$<?= number_format((float)$it['subtotal'], 0, ',', '.') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="pt-2.5 border-t border-stone-100 flex justify-between font-extrabold text-[14px]">
                                    <span>Total del Pedido</span>
                                    <span class="font-mono text-[#1C100B] text-base">$<?= number_format((float)$pedidoRastreado['total'], 0, ',', '.') ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-[14px] text-amber-900">
                                No encontramos ningún pedido con el folio <strong><?= htmlspecialchars($folioBuscado) ?></strong>. Verifica el código en tu comprobante.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-sm">
                                <i class="fa-solid fa-file-invoice text-lg text-amber-800 mb-1.5"></i>
                                <h3 class="font-bold text-stone-800 text-[14px]">1. Folio</h3>
                                <p class="text-[12px] text-stone-500">Recibes tu código de orden.</p>
                            </div>
                            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-sm">
                                <i class="fa-solid fa-fire-burner text-lg text-amber-800 mb-1.5"></i>
                                <h3 class="font-bold text-stone-800 text-[14px]">2. Barista</h3>
                                <p class="text-[12px] text-stone-500">Preparación inmediata.</p>
                            </div>
                            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-sm">
                                <i class="fa-solid fa-box-check text-lg text-emerald-600 mb-1.5"></i>
                                <h3 class="font-bold text-stone-800 text-[14px]">3. Listo</h3>
                                <p class="text-[12px] text-stone-500">Entrega rápida en barra o envío.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN DE UBICACIÓN & TIENDA FÍSICA -->
    <section id="ubicacion" class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1A100B] via-[#26150D] to-[#120A06] text-white border border-stone-800/80 shadow-xl p-6 sm:p-9">
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-7 space-y-5">
                    <div class="space-y-2">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold gold-gradient text-stone-950 shadow-sm">
                            <i class="fa-solid fa-location-dot"></i> Punto Físico de Venta · Neiva, Huila
                        </span>
                        <h2 class="font-display text-[20px] font-bold leading-snug text-white">
                            Visítanos en Nuestra Cafetería & <span class="text-amber-400 italic">Barra de Especialidad</span>
                        </h2>
                        <p class="text-[14px] text-stone-300 leading-relaxed max-w-xl">
                            Ven a disfrutar de una taza preparada por baristas profesionales, granos recién tostados de las mejores fincas del Huila, tortas caseras y empanadas artesanales.
                        </p>
                    </div>

                    <!-- Dirección Oficial -->
                    <div class="p-5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10 space-y-3.5 shadow-inner">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl gold-gradient text-stone-950 flex items-center justify-center text-lg font-black shrink-0 shadow mt-0.5">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-300">Dirección Oficial</span>
                                <h3 class="text-[20px] font-bold text-white leading-snug">
                                    Calle 26 # 16-24 Centro Comercial San Juan Plaza Local 151 Primer Piso, Neiva-Huila
                                </h3>
                                <p class="text-[14px] text-stone-300 flex items-center gap-2 pt-0.5">
                                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                                    <span>Punto de atención al público, pedidos en mesa y retiro en barra</span>
                                </p>
                            </div>
                        </div>

                        <!-- Horario de Atención -->
                        <div class="pt-3 border-t border-white/10">
                            <div class="p-3.5 rounded-xl bg-black/35 border border-white/5 space-y-2">
                                <div class="flex items-center gap-2 text-white font-bold text-[14px]">
                                    <i class="fa-regular fa-clock text-amber-400"></i>
                                    <span>Horario de Atención</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[14px]">
                                    <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-white/5 border border-white/5">
                                        <span class="text-stone-300 font-medium"><i class="fa-regular fa-calendar-days text-amber-400/80 mr-1.5"></i> Lunes - Viernes</span>
                                        <span class="font-mono font-bold text-amber-300">9:00 AM - 8:00 PM</span>
                                    </div>
                                    <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-white/5 border border-white/5">
                                        <span class="text-stone-300 font-medium"><i class="fa-regular fa-calendar-days text-amber-400/80 mr-1.5"></i> Sábados - Domingos</span>
                                        <span class="font-mono font-bold text-amber-300">10:00 AM - 8:00 PM</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Enlace -->
                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                        <a href="https://www.google.com/maps/search/?api=1&query=Calle+26+%23+16-24+Centro+Comercial+San+Juan+Plaza+Local+151+Neiva+Huila"
                           target="_blank" rel="noopener noreferrer"
                           class="px-5 py-2.5 rounded-xl gold-gradient text-stone-950 font-bold text-[14px] shadow-md hover:scale-105 transition flex items-center gap-2">
                            <i class="fa-solid fa-diamond-turn-right text-sm"></i> Abrir en Google Maps
                        </a>
                        <a href="https://www.facebook.com/sanjuanplaza" target="_blank" rel="noopener noreferrer"
                           class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-[#1877F2] text-white font-semibold text-[14px] border border-white/20 transition flex items-center gap-2">
                            <i class="fa-brands fa-facebook text-sm"></i> Facebook San Juan Plaza
                        </a>
                        <a href="https://www.instagram.com/lacasadel_buencafe/" target="_blank" rel="noopener noreferrer"
                           class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-gradient-to-r hover:from-[#833ab4] hover:via-[#fd1d1d] hover:to-[#fcb045] text-white font-semibold text-[14px] border border-white/20 transition flex items-center gap-2">
                            <i class="fa-brands fa-instagram text-sm"></i> Instagram @lacasadel_buencafe
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5 space-y-3.5">
                    <div class="relative rounded-2xl overflow-hidden border border-stone-700 shadow-xl bg-stone-900 h-64 sm:h-72">
                        <iframe
                            title="Ubicación San Juan Plaza Neiva"
                            class="w-full h-full border-0 filter contrast-105 brightness-95"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://maps.google.com/maps?q=Centro+Comercial+San+Juan+Plaza+Neiva+Calle+26+%23+16-24&t=&z=16&ie=UTF8&iwloc=&output=embed">
                        </iframe>
                        <div class="absolute bottom-2 left-2 right-2 bg-[#120A06]/90 backdrop-blur-md px-3 py-2 rounded-xl border border-white/10 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-store text-amber-400"></i>
                                <span class="font-bold text-white text-[12px]">San Juan Plaza · Local 151 (Piso 1)</span>
                            </div>
                            <span class="text-[11px] text-amber-300 font-mono">Neiva - Huila</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-center text-[12px]">
                        <div class="p-2.5 rounded-xl bg-white/5 border border-white/10">
                            <i class="fa-solid fa-square-parking text-amber-400 text-sm mb-1 block"></i>
                            <span class="text-stone-300 font-medium">Parqueadero</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-white/5 border border-white/10">
                            <i class="fa-solid fa-bag-shopping text-amber-400 text-sm mb-1 block"></i>
                            <span class="text-stone-300 font-medium">Pick-up Barra</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Drawer Lateral Deslizante: Bolsa de Compras & Checkout -->
    <div id="cartDrawerBackdrop" onclick="toggleCartDrawer(false)" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-40"></div>
    <aside id="cartDrawer" class="fixed top-0 right-0 h-full w-full max-w-md bg-[#FAF6F0] z-50 shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col border-l border-amber-500/30">
        <div class="coffee-gradient text-white p-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl gold-gradient text-stone-950 flex items-center justify-center font-extrabold">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-lg leading-none">Tu Bolsa de Compras</h3>
                    <p id="drawerCountLabel" class="text-[11px] text-amber-300 mt-1">0 productos seleccionados</p>
                </div>
            </div>
            <button type="button" onclick="toggleCartDrawer(false)" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Lista de Productos en la Bolsa -->
        <div id="storeCartItems" class="flex-1 overflow-y-auto p-5 divide-y divide-stone-200/70">
            <!-- Renderizado dinámicamente con Actualización Optimista -->
        </div>

        <!-- Formulario de Checkout para el Cliente -->
        <div class="p-5 bg-white border-t border-stone-200 shadow-inner">
            <div class="bg-[#1C100B] text-white rounded-2xl p-3.5 mb-4 space-y-1">
                <div class="flex justify-between text-xs text-amber-300">
                    <span><i class="fa-solid fa-star mr-1"></i> Puntos Club del Café a ganar</span>
                    <span id="storePointsEarned" class="font-mono font-bold">+0 pts</span>
                </div>
                <div class="flex justify-between items-baseline pt-1 border-t border-white/10">
                    <span class="text-xs uppercase font-bold text-stone-300">Total a Pagar</span>
                    <span id="storeCartTotal" class="text-2xl font-mono font-extrabold text-amber-400">$0</span>
                </div>
            </div>

            <form method="POST" action="index.php" onsubmit="return prepareStoreCheckout()" class="space-y-2.5 text-xs">
                <input type="hidden" name="action" value="create_order">
                <input type="hidden" name="tipo" value="tienda_online">
                <input type="hidden" name="source_portal" value="index.php">
                <input type="hidden" name="redirect_tab" value="catalogo">
                <input type="hidden" name="items_json" id="storeItemsJson" value="[]">

                <div>
                    <label class="block font-bold text-stone-700 mb-1">Tu Nombre Completo *</label>
                    <input type="text" name="cliente_nombre" required placeholder="Ej. Estefanía Olaya"
                           class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Correo (Para Puntos)</label>
                        <input type="email" name="cliente_email" placeholder="tu@correo.com"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">WhatsApp / Teléfono *</label>
                        <input type="text" name="cliente_telefono" required placeholder="310 000 0000"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-stone-700 mb-1">Dirección de Entrega o 'Retiro en Tienda' *</label>
                    <input type="text" name="direccion_entrega" required placeholder="Dirección completa y ciudad"
                           class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Método de Pago</label>
                        <select name="metodo_pago" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                            <option value="transferencia">Nequi / PSE / Transferencia</option>
                            <option value="tarjeta">Tarjeta Crédito / Débito</option>
                            <option value="efectivo">Efectivo Contraentrega</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Molienda / Notas</label>
                        <input type="text" name="notas" placeholder="En grano, V60, prensa..."
                               class="w-full px-3 py-2 rounded-xl border border-stone-200">
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 rounded-2xl gold-gradient text-stone-950 font-extrabold text-[14px] shadow-lg hover:opacity-95 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-bag-shopping"></i> Finalizar Compra Ahora
                </button>
            </form>
        </div>
    </aside>

    <!-- Modal de Confirmación de Compra para el Cliente -->
    <?php if ($lastOrderReceipt): ?>
    <div id="orderSuccessModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-7 shadow-2xl border border-amber-500/40 text-xs space-y-4">
            <div class="text-center space-y-1.5">
                <img src="logo.php" alt="La Casa del Buen Café" class="w-16 h-16 rounded-full mx-auto shadow-md border-2 border-amber-400">
                <span class="text-[11px] font-extrabold uppercase tracking-widest text-emerald-700 block pt-1">¡Compra Confirmada!</span>
                <h3 class="font-display text-2xl font-bold text-stone-900">Gracias por tu pedido, <?= htmlspecialchars($lastOrderReceipt['cliente']) ?></h3>
                <p class="text-stone-500">Nuestro equipo de baristas ya recibió tu orden en el sistema.</p>
                <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-stone-700 text-[11px] text-left">
                    <p class="font-bold text-amber-900 flex items-center gap-1.5 mb-0.5">
                        <i class="fa-solid fa-location-dot text-amber-700"></i> Punto de Retiro / Atención:
                    </p>
                    <p class="text-stone-600">Calle 26 # 16-24 Centro Comercial San Juan Plaza Local 151 Primer Piso, Neiva - Huila</p>
                </div>
            </div>

            <div class="bg-stone-900 text-white rounded-2xl p-4 flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase text-stone-400 block">Folio de Seguimiento</span>
                    <span class="font-mono font-extrabold text-xl text-amber-400"><?= htmlspecialchars($lastOrderReceipt['folio']) ?></span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] uppercase text-stone-400 block">Total Pagado</span>
                    <span class="font-mono font-extrabold text-lg text-white">$<?= number_format((float)$lastOrderReceipt['total'], 0, ',', '.') ?></span>
                </div>
            </div>

            <div class="border-y border-dashed border-stone-200 py-3 space-y-1.5 max-h-40 overflow-y-auto">
                <?php foreach ($lastOrderReceipt['items'] as $it): ?>
                    <div class="flex justify-between text-stone-700">
                        <span><strong><?= (int)$it['cantidad'] ?>x</strong> <?= htmlspecialchars($it['nombre']) ?></span>
                        <span class="font-mono font-bold">$<?= number_format((float)$it['precio'] * (int)$it['cantidad'], 0, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="window.print()" class="flex-1 py-2.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-800 font-bold text-[14px]">
                    <i class="fa-solid fa-print mr-1"></i> Imprimir Recibo
                </button>
                <button type="button" onclick="document.getElementById('orderSuccessModal').remove()" class="flex-1 py-2.5 rounded-xl coffee-gradient text-amber-300 font-bold text-[14px]">
                    Continuar en la Tienda
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pie de Página Oficial con Redes Sociales y Términos y Condiciones -->
    <footer class="bg-[#232221] text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-7 flex flex-col md:flex-row items-center justify-between gap-6 text-xs">
            <div class="flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
                <img src="logo.php" alt="La Casa del Buen Café" class="w-12 h-12 rounded-full object-cover border border-amber-300/60 shadow-lg">
                <div>
                    <p class="font-display font-bold text-white text-base">La Casa del Buen Café — Tienda en Línea</p>
                    <p class="text-[11px] text-stone-400">Café 100% Colombiano de Especialidad · Tostión Artesanal</p>
                    <p class="text-xs text-amber-300 font-medium mt-1 flex items-center justify-center sm:justify-start gap-1.5">
                        <i class="fa-solid fa-location-dot text-amber-400"></i>
                        <span>Calle 26 # 16-24 Centro Comercial San Juan Plaza Local 151 Primer Piso, Neiva-Huila</span>
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2.5">
                <a href="#ubicacion" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-stone-200 font-bold transition flex items-center gap-2 text-[14px]">
                    <i class="fa-solid fa-map-location-dot text-amber-400"></i> Ver Punto Físico
                </a>
                <button type="button" onclick="openTermsModal()" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-amber-300 hover:text-white font-bold transition flex items-center gap-2 text-[14px]">
                    <i class="fa-solid fa-file-contract text-amber-400"></i> Términos y Condiciones
                </button>
                <a href="portal_clientes.php" class="px-4 py-2 rounded-xl bg-amber-500/20 border border-amber-400/40 text-amber-300 hover:bg-amber-500 hover:text-stone-950 font-extrabold transition flex items-center gap-2 shadow text-[14px]">
                    <i class="fa-solid fa-award"></i> Portal Clientes
                </a>
                <a href="portal_privado.php" class="px-4 py-2 rounded-xl gold-gradient text-stone-950 font-extrabold transition flex items-center gap-2 shadow text-[14px]">
                    <i class="fa-solid fa-lock"></i> Portal Privado Personal →
                </a>
            </div>
        </div>

        <div class="border-t border-white/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-8 py-6 grid grid-cols-1 sm:grid-cols-3 items-center gap-4">
                <div class="text-center sm:text-left text-xs text-stone-300">
                    <i class="fa-solid fa-store text-amber-400 mr-1.5"></i>
                    <span>C.C. San Juan Plaza · Local 151 Primer Piso, Neiva</span>
                </div>

                <!-- Logos de Redes Sociales Centrados -->
                <div class="flex items-center justify-center gap-6">
                    <a href="https://www.facebook.com/sanjuanplaza" target="_blank" rel="noopener noreferrer"
                       title="Síguenos en Facebook"
                       class="w-9 h-9 rounded-full bg-white flex items-center justify-center hover:scale-110 transition shadow">
                        <i class="fa-brands fa-facebook-f text-[#1877F2] text-lg translate-y-[1px]"></i>
                    </a>
                    <a href="https://www.instagram.com/lacasadel_buencafe/" target="_blank" rel="noopener noreferrer"
                       title="Síguenos en Instagram"
                       class="w-9 h-9 rounded-lg border-2 border-white flex items-center justify-center text-white hover:scale-110 hover:bg-white/10 transition">
                        <i class="fa-brands fa-instagram text-lg"></i>
                    </a>
                </div>

                <!-- Copyright y Enlace a Términos (Derecha) -->
                <div class="text-xs font-medium tracking-wide text-white/95 text-center sm:text-right flex flex-col sm:items-end gap-1">
                    <span>© <?= date('Y') ?> - La Casa del Buen Café<sup>®</sup></span>
                    <button type="button" onclick="openTermsModal()" class="text-[12px] text-amber-300/90 hover:text-white underline font-semibold transition inline-block">
                        Términos y Condiciones
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <!-- Barra de Navegación Inferior para Dispositivos Móviles -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-[#140B07]/95 backdrop-blur-md text-stone-300 border-t border-amber-500/30 z-30 px-1.5 py-1.5 flex items-center justify-around shadow-2xl">
        <a href="#inicio" class="flex flex-col items-center py-1 px-2 text-[11px] font-bold hover:text-amber-400">
            <i class="fa-solid fa-house text-sm text-amber-400 mb-0.5"></i>
            <span>Inicio</span>
        </a>
        <a href="#catalogo" class="flex flex-col items-center py-1 px-2 text-[11px] font-bold hover:text-amber-400">
            <i class="fa-solid fa-mug-hot text-sm text-amber-400 mb-0.5"></i>
            <span>Menú</span>
        </a>
        <a href="#galeria-real" class="flex flex-col items-center py-1 px-2 text-[11px] font-bold hover:text-amber-400">
            <i class="fa-solid fa-camera text-sm text-amber-400 mb-0.5"></i>
            <span>Fotos</span>
        </a>
        <a href="#rastrear" class="flex flex-col items-center py-1 px-2 text-[11px] font-bold hover:text-amber-400">
            <i class="fa-solid fa-magnifying-glass-location text-sm text-amber-400 mb-0.5"></i>
            <span>Rastrear</span>
        </a>
        <button type="button" onclick="toggleCartDrawer(true)" class="relative flex flex-col items-center py-1 px-2 text-[11px] font-extrabold text-amber-300">
            <i class="fa-solid fa-bag-shopping text-sm mb-0.5"></i>
            <span>Mi Bolsa</span>
            <span id="mobileCartBadge" class="absolute -top-0.5 right-1 px-1.5 py-0.2 rounded-full bg-amber-500 text-stone-950 text-[10px] font-mono font-black">0</span>
        </button>
    </nav>

    <!-- Modal para Dispositivos Móviles / QR -->
    <div id="deviceModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-amber-500/40 text-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-stone-200">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl gold-gradient text-stone-950 flex items-center justify-center font-black text-base">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-stone-900 text-[20px] leading-tight">Ver en Celular, Tablet u otro PC</h3>
                        <p class="text-[14px] text-stone-500">Conecta tus dispositivos a la misma red y escanea el código QR</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('deviceModal').classList.add('hidden')" class="text-stone-400 hover:text-stone-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-center">
                <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200 space-y-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-extrabold text-[10px] uppercase">Sitio 1 · Clientes</span>
                    <h4 class="font-bold text-stone-900 text-sm">Tienda Online</h4>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($urlMovilTienda) ?>"
                         alt="QR Tienda" loading="lazy" class="w-36 h-36 mx-auto rounded-xl border border-stone-200 bg-white p-1.5 shadow-sm">
                    <input type="text" readonly value="<?= htmlspecialchars($urlMovilTienda) ?>"
                           onclick="this.select(); navigator.clipboard.writeText(this.value);"
                           class="w-full px-2.5 py-1.5 rounded-lg border border-stone-200 bg-white font-mono text-[11px] text-center text-stone-700">
                </div>

                <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200 space-y-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-stone-800 text-amber-300 font-extrabold text-[10px] uppercase">Sitio 2 · Personal</span>
                    <h4 class="font-bold text-stone-900 text-sm">Admin & Caja POS</h4>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($urlMovilAdmin) ?>"
                         alt="QR Admin" loading="lazy" class="w-36 h-36 mx-auto rounded-xl border border-stone-200 bg-white p-1.5 shadow-sm">
                    <input type="text" readonly value="<?= htmlspecialchars($urlMovilAdmin) ?>"
                           onclick="this.select(); navigator.clipboard.writeText(this.value);"
                           class="w-full px-2.5 py-1.5 rounded-lg border border-stone-200 bg-white font-mono text-[11px] text-center text-stone-700">
                </div>
            </div>

            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/80 text-[12px] text-amber-950 leading-relaxed">
                <strong><i class="fa-solid fa-network-wired mr-1"></i> IP de red detectada:</strong> <code class="font-mono font-bold"><?= htmlspecialchars($ipLocalRed) ?></code>.
                El diseño es <strong>100% Responsivo (Mobile-First & PWA)</strong>: optimizado para Android, iPhone, iPad y pantallas de escritorio.
            </div>
        </div>
    </div>

    <!-- Modal Oficial de Términos y Condiciones (Sin Botón Imprimir) -->
    <div id="termsModal" onclick="if(event.target === this) closeTermsModal()" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-5">
        <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[85vh] flex flex-col shadow-2xl border border-stone-300 overflow-hidden text-stone-800 animate-fade-in">
            <div class="coffee-gradient text-white p-5 sm:p-6 flex items-center justify-between shrink-0 border-b border-amber-500/30">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-2xl gold-gradient text-stone-950 flex items-center justify-center font-bold text-lg shadow">
                        <i class="fa-solid fa-file-contract"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-white text-[20px] leading-tight">Términos y Condiciones</h3>
                        <p class="text-xs text-amber-300/90 mt-0.5">La Casa del Buen Café · Políticas de Compra, Envíos y Privacidad</p>
                    </div>
                </div>
                <button type="button" onclick="closeTermsModal()" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-5 sm:p-6 overflow-y-auto space-y-4 text-[14px] leading-relaxed text-stone-700 divide-y divide-stone-100">
                <div class="space-y-1.5 pb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-store text-amber-600"></i> 1. Identificación y Establecimiento
                    </span>
                    <p>Bienvenido a la plataforma en línea y servicio presencial de <strong>La Casa del Buen Café</strong>. Al hacer uso de nuestra página web, solicitar pedidos o comprar en barra, aceptas los presentes Términos y Condiciones que rigen la relación comercial.</p>
                    <p class="text-stone-700 bg-stone-50 p-3 rounded-xl border border-stone-200 text-xs">
                        <i class="fa-solid fa-location-dot text-amber-700 mr-1.5"></i>
                        <strong>Sede Principal:</strong> Calle 26 # 16-24 Centro Comercial San Juan Plaza, Local 151 Primer Piso, Neiva - Huila, Colombia.
                    </p>
                </div>

                <div class="space-y-1.5 pt-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-mug-hot text-amber-600"></i> 2. Productos y Calidad de Especialidad
                    </span>
                    <p>Ofrecemos café 100% colombiano de origen único y micro-lotes selectos (en grano entero o molienda artesanal para V60, Chemex, prensa o espresso), bebidas frías y calientes de barista, repostería artesanal, tortas, galletas integrales y empanadas elaboradas bajo estrictas normas de inocuidad alimentaria.</p>
                    <p>Nos comprometemos a garantizar la frescura de cada lote tostado y la preparación con ingredientes de alta calidad.</p>
                </div>

                <div class="space-y-1.5 pt-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-credit-card text-amber-600"></i> 3. Precios y Métodos de Pago
                    </span>
                    <p>Todos los precios se publican en Pesos Colombianos (COP) e incluyen los tributos aplicables. Los medios de pago habilitados son:</p>
                    <ul class="list-disc pl-5 space-y-1 text-stone-600">
                        <li><strong>Transferencias Digitales:</strong> Nequi, Daviplata, Bancolombia y pasarelas autorizadas.</li>
                        <li><strong>Tarjetas Débito y Crédito:</strong> Procesamiento seguro presencial o en línea.</li>
                        <li><strong>Efectivo Contraentrega / En Barra:</strong> Pago en moneda local al recibir o retirar tu pedido.</li>
                    </ul>
                </div>

                <div class="space-y-1.5 pt-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-clock text-amber-600"></i> 4. Pedidos, Horarios y Retiro en Barra
                    </span>
                    <p>Cada orden confirmada genera un código único de seguimiento (Folio ej. <code>WEB-XXXX</code>) que puedes rastrear en tiempo real en nuestra sección de rastreo.</p>
                    <ul class="list-disc pl-5 space-y-1 text-stone-600">
                        <li><strong>Retiro en Barra (Pick-up):</strong> Sin costo de envío adicional en nuestro local del C.C. San Juan Plaza.</li>
                        <li><strong>Horarios de Atención y Despacho:</strong> Lunes a Viernes de 9:00 AM a 8:00 PM; Sábados y Domingos de 10:00 AM a 8:00 PM.</li>
                    </ul>
                </div>

                <div class="space-y-1.5 pt-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-left text-amber-600"></i> 5. Cambios, Garantías y Reclamaciones
                    </span>
                    <p>De conformidad con la <strong>Ley 1480 de 2011 (Estatuto del Consumidor en Colombia)</strong>, si un paquete de café sellado o producto empaquetado presenta defectos de fábrica o averías imputables al despacho, el cliente tiene derecho al cambio inmediato o reembolso acreditando su comprobante de compra.</p>
                    <p>Para bebidas y alimentos recién preparados, cualquier inconformidad sobre la preparación debe ser informada al barista de inmediato para proceder con su ajuste o reposición.</p>
                </div>

                <div class="space-y-1.5 pt-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-user-shield text-amber-600"></i> 6. Protección de Datos (Habeas Data)
                    </span>
                    <p>En cumplimiento de la <strong>Ley 1581 de 2012</strong>, los datos proporcionados (nombre, teléfono y correo electrónico) son tratados con estricta confidencialidad para la gestión de tus comandas, asignación de puntos del <strong>Club del Buen Café</strong> y notificaciones sobre el estado de tu compra. Nunca compartimos tu información con terceros.</p>
                </div>
            </div>

            <div class="p-4 bg-stone-50 border-t border-stone-200 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <span class="text-xs text-stone-500 flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-check text-emerald-600"></i> Términos vigentes · San Juan Plaza Neiva
                </span>
                <button type="button" onclick="closeTermsModal()" class="px-5 py-2 rounded-xl gold-gradient text-stone-950 font-bold text-[14px] shadow hover:opacity-95 transition">
                    Aceptar y Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- SCRIPTS OPTIMIZADOS: DEBOUNCE, PAGINACIÓN, SSE IA, OPTIMISTIC UI -->
    <!-- ========================================================== -->
    <script>
        // --- 13. ESTADO GLOBAL REACTIVO & OPTIMISTIC UI ---
        let storeCart = [];
        let activeCatalogFilter = 'todos';
        let currentSearchQuery = '';
        let catalogPage = 1;
        const PAGE_SIZE = 6; // Virtualización / Paginación amigable para celular y escritorio

        // --- 14. PREVENCIÓN DE LLAMADAS DUPLICADAS (ABORT CONTROLLER) ---
        let searchAbortController = null;

        // --- 15. UTILIDADES DE DEBOUNCE (250ms) ---
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        const handleDebouncedSearch = debounce(function(val) {
            currentSearchQuery = (val || '').toLowerCase().trim();
            catalogPage = 1;
            document.getElementById('clearSearchBtn').classList.toggle('hidden', !currentSearchQuery);
            renderAutocompleteSuggestions(currentSearchQuery);
            filterStoreProducts();
        }, 220);

        function clearSearch() {
            const input = document.getElementById('storeSearch');
            input.value = '';
            currentSearchQuery = '';
            document.getElementById('clearSearchBtn').classList.add('hidden');
            document.getElementById('searchAutocomplete').classList.add('hidden');
            catalogPage = 1;
            filterStoreProducts();
        }

        // --- 16. AUTOCOMPLETADO INTELIGENTE LIGERO ---
        function renderAutocompleteSuggestions(q) {
            const container = document.getElementById('searchAutocomplete');
            if (!q || q.length < 2) {
                container.classList.add('hidden');
                return;
            }
            const allCards = Array.from(document.querySelectorAll('.store-product-card'));
            const matches = allCards.filter(c => c.dataset.search.includes(q)).slice(0, 4);

            if (matches.length === 0) {
                container.classList.add('hidden');
                return;
            }

            container.innerHTML = matches.map(c => {
                const title = c.querySelector('h3').textContent;
                const price = c.querySelector('.font-mono').textContent;
                return `
                    <div onclick="selectAutocomplete('${title.replace(/'/g, "\\'")}')"
                         class="px-4 py-2 hover:bg-amber-50 cursor-pointer flex justify-between items-center text-xs text-stone-800 transition">
                        <span class="font-semibold truncate">${title}</span>
                        <span class="font-mono text-amber-900 font-bold ml-2">${price}</span>
                    </div>
                `;
            }).join('');
            container.classList.remove('hidden');
        }

        function selectAutocomplete(term) {
            const input = document.getElementById('storeSearch');
            input.value = term;
            currentSearchQuery = term.toLowerCase().trim();
            document.getElementById('searchAutocomplete').classList.add('hidden');
            document.getElementById('clearSearchBtn').classList.remove('hidden');
            catalogPage = 1;
            filterStoreProducts();
        }

        // Ocultar autocompletado al hacer clic fuera
        document.addEventListener('click', function(e) {
            const autoEl = document.getElementById('searchAutocomplete');
            if (autoEl && !e.target.closest('#storeSearch') && !e.target.closest('#searchAutocomplete')) {
                autoEl.classList.add('hidden');
            }
        });

        // --- 17. FILTROS & PAGINACIÓN / VIRTUALIZACIÓN DE PRODUCTOS ---
        function setCatalogFilter(filterTag) {
            activeCatalogFilter = filterTag;
            catalogPage = 1;
            document.querySelectorAll('.store-filter-btn').forEach(btn => {
                if (btn.dataset.filterTag === filterTag) {
                    btn.className = 'store-filter-btn px-4 py-2.5 rounded-xl font-bold bg-[#1C100B] text-amber-300 shadow-sm border border-amber-500/30 whitespace-nowrap transition flex items-center gap-2 text-[14px]';
                } else {
                    btn.className = 'store-filter-btn px-4 py-2.5 rounded-xl font-semibold bg-white text-stone-700 hover:bg-amber-50 border border-stone-200/80 whitespace-nowrap transition flex items-center gap-2 text-[14px]';
                }
            });

            // Animación visual Skeleton de 100ms para feedback táctil instantáneo
            const skel = document.getElementById('catalogSkeletonContainer');
            const grid = document.getElementById('storeProductsGrid');
            if (skel && grid) {
                skel.classList.remove('hidden');
                grid.classList.add('hidden');
                setTimeout(() => {
                    skel.classList.add('hidden');
                    grid.classList.remove('hidden');
                    filterStoreProducts();
                }, 90);
            } else {
                filterStoreProducts();
            }
        }

        function filterStoreProducts() {
            const allCards = Array.from(document.querySelectorAll('.store-product-card'));
            const matchingCards = [];

            allCards.forEach(card => {
                const tags = (card.dataset.tags || '').split(' ');
                const matchFilter = (activeCatalogFilter === 'todos' || tags.includes(activeCatalogFilter));
                const matchText = (!currentSearchQuery || card.dataset.search.includes(currentSearchQuery));
                if (matchFilter && matchText) {
                    matchingCards.push(card);
                } else {
                    card.style.display = 'none';
                }
            });

            const totalMatches = matchingCards.length;
            const totalPages = Math.max(1, Math.ceil(totalMatches / PAGE_SIZE));
            if (catalogPage > totalPages) catalogPage = totalPages;

            const startIndex = (catalogPage - 1) * PAGE_SIZE;
            const endIndex = startIndex + PAGE_SIZE;

            matchingCards.forEach((card, idx) => {
                if (idx >= startIndex && idx < endIndex) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });

            // Actualizar controles de paginación
            const paginationBar = document.getElementById('catalogPaginationBar');
            const emptyEl = document.getElementById('storeEmptyState');
            const pageInfo = document.getElementById('paginationInfoLabel');
            const pageIndicator = document.getElementById('currentPageIndicator');
            const prevBtn = document.getElementById('prevPageBtn');
            const nextBtn = document.getElementById('nextPageBtn');

            if (emptyEl) {
                emptyEl.classList.toggle('hidden', totalMatches > 0);
            }

            if (paginationBar) {
                if (totalMatches <= PAGE_SIZE) {
                    paginationBar.classList.add('hidden');
                } else {
                    paginationBar.classList.remove('hidden');
                    pageInfo.textContent = `Mostrando ${Math.min(totalMatches, startIndex + 1)} - ${Math.min(totalMatches, endIndex)} de ${totalMatches} productos`;
                    pageIndicator.textContent = `${catalogPage} / ${totalPages}`;
                    prevBtn.disabled = (catalogPage <= 1);
                    nextBtn.disabled = (catalogPage >= totalPages);
                }
            }
        }

        function changeCatalogPage(delta) {
            catalogPage += delta;
            filterStoreProducts();
            const catSec = document.getElementById('catalogo');
            if (catSec) {
                catSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function sortStoreProducts() {
            const grid = document.getElementById('storeProductsGrid');
            const mode = document.getElementById('storeSort').value;
            const cards = Array.from(grid.querySelectorAll('.store-product-card'));
            cards.sort((a, b) => {
                if (mode === 'price_asc') return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                if (mode === 'price_desc') return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                return parseInt(b.dataset.featured) - parseInt(a.dataset.featured);
            });
            cards.forEach(c => grid.appendChild(c));
            filterStoreProducts();
        }

        // --- 18. NOTIFICACIONES TOAST LIGERAS ---
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const icon = type === 'warning' ? 'fa-triangle-exclamation text-amber-400' : (type === 'error' ? 'fa-circle-exclamation text-red-400' : 'fa-bag-shopping text-amber-400');
            toast.className = 'pointer-events-auto px-4 py-3 rounded-2xl border-l-4 border-amber-400 bg-stone-900 text-white shadow-2xl flex items-center gap-3 text-xs font-semibold animate-fade-in';
            toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'transition', 'duration-300');
                setTimeout(() => toast.remove(), 320);
            }, 2300);
        }

        // --- 19. CARRITO REACTIVO & ACTUALIZACIÓN OPTIMISTA (0ms LAG) ---
        function toggleCartDrawer(open) {
            const drawer = document.getElementById('cartDrawer');
            const backdrop = document.getElementById('cartDrawerBackdrop');
            if (open) {
                backdrop.classList.remove('hidden');
                drawer.classList.remove('translate-x-full');
            } else {
                backdrop.classList.add('hidden');
                drawer.classList.add('translate-x-full');
            }
        }

        function addToStoreCart(product) {
            // Actualización optimista inmediata en memoria local
            const existing = storeCart.find(i => i.id === product.id);
            if (existing) {
                if (existing.cantidad < product.stock) {
                    existing.cantidad++;
                    showToast(`+1 ${product.nombre}`);
                } else {
                    showToast('Stock máximo alcanzado', 'warning');
                    return;
                }
            } else {
                storeCart.push({ ...product, cantidad: 1 });
                showToast(`Agregado a tu bolsa: ${product.nombre}`);
            }
            renderStoreCart();
            toggleCartDrawer(true);
        }

        function changeStoreQty(id, delta) {
            const idx = storeCart.findIndex(i => i.id === id);
            if (idx === -1) return;
            storeCart[idx].cantidad += delta;
            if (storeCart[idx].cantidad <= 0) {
                storeCart.splice(idx, 1);
            }
            renderStoreCart();
        }

        function renderStoreCart() {
            const container = document.getElementById('storeCartItems');
            const totalEl = document.getElementById('storeCartTotal');
            const ptsEl = document.getElementById('storePointsEarned');
            const countLabel = document.getElementById('drawerCountLabel');
            const badge = document.getElementById('headerCartBadge');
            const headerTotal = document.getElementById('headerCartTotal');
            const mobileBadge = document.getElementById('mobileCartBadge');

            const count = storeCart.reduce((acc, it) => acc + it.cantidad, 0);
            const total = storeCart.reduce((acc, it) => acc + (it.precio * it.cantidad), 0);

            if (mobileBadge) mobileBadge.textContent = count;
            if (badge) badge.textContent = count;
            if (headerTotal) headerTotal.textContent = '$' + total.toLocaleString('es-CO');
            if (countLabel) countLabel.textContent = `${count} producto(s) seleccionado(s)`;
            if (totalEl) totalEl.textContent = '$' + total.toLocaleString('es-CO');
            if (ptsEl) ptsEl.textContent = '+' + Math.floor(total / 1000) + ' pts';

            if (storeCart.length === 0) {
                container.innerHTML = `
                    <div class="py-12 text-center space-y-2">
                        <i class="fa-solid fa-mug-saucer text-3xl text-stone-300"></i>
                        <p class="text-xs text-stone-400">Tu bolsa está vacía. Selecciona tu café favorito del catálogo.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = storeCart.map(item => `
                <div class="py-3 flex items-center justify-between gap-2 text-xs">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-stone-900 truncate">${item.nombre}</p>
                        <p class="text-stone-400 font-mono">$${item.precio.toLocaleString('es-CO')} c/u</p>
                    </div>
                    <div class="flex items-center gap-1.5 bg-white border border-stone-200 rounded-xl p-1">
                        <button type="button" onclick="changeStoreQty(${item.id}, -1)" class="w-6 h-6 rounded-lg bg-stone-100 hover:bg-amber-100 font-bold">-</button>
                        <span class="w-5 text-center font-extrabold">${item.cantidad}</span>
                        <button type="button" onclick="changeStoreQty(${item.id}, 1)" class="w-6 h-6 rounded-lg bg-stone-100 hover:bg-amber-100 font-bold">+</button>
                    </div>
                    <div class="font-mono font-extrabold text-stone-900 w-20 text-right">$${(item.precio * item.cantidad).toLocaleString('es-CO')}</div>
                </div>
            `).join('');
        }

        function prepareStoreCheckout() {
            if (storeCart.length === 0) {
                showToast('Agrega al menos un producto a tu bolsa', 'error');
                return false;
            }
            document.getElementById('storeItemsJson').value = JSON.stringify(storeCart);
            return true;
        }

        // --- 20. MODAL DE TÉRMINOS Y CONDICIONES ---
        function openTermsModal() {
            const m = document.getElementById('termsModal');
            if (m) m.classList.remove('hidden');
        }

        function closeTermsModal() {
            const m = document.getElementById('termsModal');
            if (m) m.classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTermsModal();
                const devModal = document.getElementById('deviceModal');
                if (devModal) devModal.classList.add('hidden');
            }
        });

        // --- 22. REAL USER MONITORING (RUM) & WEB VITALS ---
        window.addEventListener('load', () => {
            if ('PerformanceObserver' in window) {
                try {
                    // Medición de Largest Contentful Paint (LCP)
                    new PerformanceObserver((entryList) => {
                        for (const entry of entryList.getEntries()) {
                            console.info('%c[RUM Web Vitals] LCP:', 'color: #10b981; font-weight: bold;', entry.startTime.toFixed(1) + 'ms');
                        }
                    }).observe({ type: 'largest-contentful-paint', buffered: true });

                    // Medición de First Input Delay / Interaction (FID / INP)
                    new PerformanceObserver((entryList) => {
                        for (const entry of entryList.getEntries()) {
                            console.info('%c[RUM Web Vitals] FID/INP:', 'color: #3b82f6; font-weight: bold;', entry.processingStart - entry.startTime + 'ms');
                        }
                    }).observe({ type: 'first-input', buffered: true });

                    // Medición de Cumulative Layout Shift (CLS)
                    let clsScore = 0;
                    new PerformanceObserver((entryList) => {
                        for (const entry of entryList.getEntries()) {
                            if (!entry.hadRecentInput) {
                                clsScore += entry.value;
                            }
                        }
                        console.info('%c[RUM Web Vitals] CLS Score:', 'color: #f59e0b; font-weight: bold;', clsScore.toFixed(3));
                    }).observe({ type: 'layout-shift', buffered: true });
                } catch (e) {
                    // Observers opcionales en navegadores que no soportan todos los tipos
                }
            }
        });

        // Inicialización del catálogo con paginación
        filterStoreProducts();
        renderStoreCart();
    </script>
</body>
</html>
