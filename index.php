<?php
/**
 * SITIO WEB #1: TIENDA EN LÍNEA EXCLUSIVA PARA CLIENTES
 * La Casa del Buen Café — Specialty Coffee Roasters eCommerce
 * URL: http://localhost/La_casa_del_buen_cafe/
 */

require_once __DIR__ . '/includes/actions.php';

$categorias     = $db->select('categorias', ['select' => '*', 'order' => 'id.asc']);
$productos      = $db->select('productos', ['select' => '*', 'order' => 'id.asc']);
$pedidos        = $db->select('pedidos', ['select' => '*', 'order' => 'id.asc']);
$detallePedidos = $db->select('detalle_pedidos', ['select' => '*', 'order' => 'id.asc']);

$lastOrderReceipt = $_SESSION['last_receipt'] ?? null;
unset($_SESSION['last_receipt']);

// Consulta opcional de seguimiento de pedido por folio
$folioBuscado = strtoupper(trim($_GET['track_folio'] ?? ''));
$pedidoRastreado = null;
$itemsRastreados = [];
if (!empty($folioBuscado)) {
    foreach ($pedidos as $ped) {
        if (strtoupper($ped['folio'] ?? '') === $folioBuscado) {
            $pedidoRastreado = $ped;
            foreach ($detallePedidos as $det) {
                if ((int)($det['pedido_id'] ?? 0) === (int)$ped['id']) {
                    $itemsRastreados[] = $det;
                }
            }
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Casa del Buen Café — Tienda Online de Café de Especialidad</title>
    <link rel="icon" type="image/jpeg" href="logo.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #FAF6F0;
            background-image:
                radial-gradient(at 0% 0%, rgba(200, 138, 75, 0.09) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(46, 26, 17, 0.06) 0px, transparent 50%);
        }
        .font-display { font-family: 'Playfair Display', serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .coffee-gradient { background: linear-gradient(135deg, #140B07 0%, #2C1810 55%, #4A2C1D 100%); }
        .gold-gradient { background: linear-gradient(135deg, #E2AA63 0%, #B87333 100%); }
        .glass-card {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(214, 199, 182, 0.65);
        }
        .card-hover { transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1); }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 22px 35px -12px rgba(28, 16, 11, 0.14);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-stone-800 selection:bg-amber-500 selection:text-stone-950">

    <!-- Contenedor de Notificaciones Toast -->
    <div id="toastContainer" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none"></div>

    <!-- Cintillo Promocional Superior -->
    <div class="bg-[#120A06] text-amber-200/90 text-xs py-2 px-4 border-b border-amber-500/20">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-4">
                <span><i class="fa-solid fa-truck-fast text-amber-400 mr-1.5"></i> <strong>Envío Gratis</strong> en todos los cafés de origen y bebidas</span>
                <span class="hidden md:inline text-white/20">|</span>
                <span class="hidden md:inline"><i class="fa-solid fa-award text-amber-400 mr-1"></i> Gana <strong>1 Punto por cada $1.000 COP</strong> en tu compra</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="#rastrear" class="hover:text-white transition flex items-center gap-1">
                    <i class="fa-solid fa-magnifying-glass-location text-amber-400"></i> Rastrear mi pedido
                </a>
                <span class="text-white/20">|</span>
                <a href="admin.php" class="text-stone-400 hover:text-amber-300 transition flex items-center gap-1 text-[11px]">
                    <i class="fa-solid fa-lock"></i> Portal Admin / Trabajador
                </a>
            </div>
        </div>
    </div>

    <!-- Navbar Principal de la Tienda Pública -->
    <header class="coffee-gradient text-white sticky top-0 z-30 shadow-xl border-b border-amber-500/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between gap-4">
            <a href="index.php" class="flex items-center gap-3.5 group">
                <img src="logo.php" alt="La Casa del Buen Café"
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
                <a href="#origenes" class="hover:text-amber-400 transition">Orígenes & Tostión</a>
                <a href="#rastrear" class="hover:text-amber-400 transition">Rastrear Pedido</a>
            </nav>

            <!-- Botón del Carrito Deslizante -->
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

    <!-- Hero Principal de la Tienda -->
    <section id="inicio" class="max-w-7xl w-full mx-auto px-4 sm:px-6 pt-6 pb-4">
        <div class="relative rounded-3xl overflow-hidden shadow-2xl border border-amber-500/30 min-h-[380px] flex items-center">
            <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1600&q=80"
                 alt="Café de Especialidad Colombiano"
                 class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-[#120A06]/95 via-[#1C100B]/85 to-black/30"></div>

            <div class="relative z-10 p-8 sm:p-12 w-full flex flex-col lg:flex-row items-center justify-between gap-8 text-white">
                <div class="max-w-2xl space-y-4">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold gold-gradient text-stone-950 shadow">
                        <i class="fa-solid fa-seedling"></i> Cosecha Selecta de Altura · Puntaje SCA 86+
                    </span>
                    <h1 class="font-display text-3xl sm:text-5xl font-bold leading-tight">
                        El arte del buen café, <span class="text-amber-400 italic">recién tostado</span> para ti.
                    </h1>
                    <p class="text-stone-300 text-sm sm:text-base leading-relaxed">
                        Descubre micro-lotes de Huila, Sierra Nevada y Cauca, bebidas preparadas por baristas expertos y repostería artesanal recién horneada. Pide en línea y recibe en casa o retira sin filas.
                    </p>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <a href="#catalogo" class="px-6 py-3.5 rounded-2xl gold-gradient text-stone-950 font-extrabold text-sm shadow-xl hover:opacity-95 transition flex items-center gap-2">
                            <i class="fa-solid fa-mug-saucer"></i> Comprar Café Ahora
                        </a>
                        <a href="#rastrear" class="px-6 py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm border border-white/20 backdrop-blur-md transition flex items-center gap-2">
                            <i class="fa-solid fa-box-open text-amber-400"></i> Estado de mi Pedido
                        </a>
                    </div>
                </div>

                <!-- Sello Oficial de La Casa del Buen Café en el Hero -->
                <div class="shrink-0">
                    <div class="p-2 rounded-full bg-gradient-to-tr from-amber-500/40 via-amber-200/30 to-transparent shadow-2xl backdrop-blur-sm">
                        <img src="logo.php" alt="Logo Oficial La Casa del Buen Café"
                             class="w-44 h-44 sm:w-56 sm:h-56 rounded-full object-cover shadow-2xl border-4 border-[#E8B78D]">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sellos de Confianza / Orígenes -->
    <section id="origenes" class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-5">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-900 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-mountain-sun"></i>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900 text-xs sm:text-sm">100% Origen Único</h3>
                    <p class="text-[11px] text-stone-500">Fincas cafeteras entre 1.650 y 2.000 m.s.n.m.</p>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-900 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-fire-flame-curved"></i>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900 text-xs sm:text-sm">Tostión Artesanal</h3>
                    <p class="text-[11px] text-stone-500">Molienda personalizada para V60, prensa o espresso.</p>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-900 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900 text-xs sm:text-sm">Entrega Express</h3>
                    <p class="text-[11px] text-stone-500">Envío a domicilio o retiro rápido en barra.</p>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-900 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-gift"></i>
                </div>
                <div>
                    <h3 class="font-bold text-stone-900 text-xs sm:text-sm">Club del Buen Café</h3>
                    <p class="text-[11px] text-stone-500">Acumula puntos automáticamente con tu correo.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Catálogo Principal de Productos -->
    <section id="catalogo" class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-6 space-y-6">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-xs font-extrabold uppercase tracking-widest text-amber-700">Menú & Tostaduría</span>
                <h2 class="font-display text-2xl sm:text-3xl font-bold text-stone-900">Explora Nuestra Selección</h2>
            </div>

            <!-- Buscador y Ordenamiento -->
            <div class="flex flex-col sm:flex-row gap-2.5 w-full md:w-auto">
                <div class="relative sm:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-amber-700 text-xs"></i>
                    <input type="text" id="storeSearch" oninput="filterStoreProducts()"
                           placeholder="Buscar Geisha, Huila, Cold Brew, Croissant..."
                           class="w-full pl-9 pr-4 py-2 rounded-xl border border-stone-200 bg-white text-xs focus:outline-none focus:ring-2 focus:ring-amber-600">
                </div>
                <select id="storeSort" onchange="sortStoreProducts()" class="px-3.5 py-2 rounded-xl border border-stone-200 bg-white text-xs font-bold text-stone-700">
                    <option value="featured">Destacados primero</option>
                    <option value="price_asc">Menor precio</option>
                    <option value="price_desc">Mayor precio</option>
                </select>
            </div>
        </div>

        <!-- Pestañas de Categorías -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2">
            <button type="button" onclick="setStoreCategory(0)" data-cat-btn="0"
                    class="store-cat-btn px-4 py-2.5 rounded-2xl text-xs font-extrabold gold-gradient text-stone-950 shadow transition whitespace-nowrap">
                <i class="fa-solid fa-layer-group mr-1.5"></i> Todo el Menú (<?= count($productos) ?>)
            </button>
            <?php foreach ($categorias as $cat): ?>
                <button type="button" onclick="setStoreCategory(<?= (int)$cat['id'] ?>)" data-cat-btn="<?= (int)$cat['id'] ?>"
                        class="store-cat-btn px-4 py-2.5 rounded-2xl text-xs font-bold bg-white text-stone-700 border border-stone-200/80 hover:border-amber-500 transition whitespace-nowrap">
                    <i class="fa-solid <?= htmlspecialchars($cat['icono'] ?? 'fa-mug-hot') ?> mr-1.5 text-amber-700"></i>
                    <?= htmlspecialchars($cat['nombre']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de Productos para Comprar -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="storeProductsGrid">
            <?php foreach ($productos as $prod): ?>
                <?php
                $agotado = ((int)$prod['stock'] <= 0);
                $tueste = $prod['tueste'] ?? 'N/A';
                $nivelTueste = match($tueste) {
                    'Medio-Claro' => 2,
                    'Medio' => 3,
                    'Medio-Oscuro' => 4,
                    'Oscuro' => 5,
                    default => 0
                };
                ?>
                <div class="store-product-card glass-card card-hover rounded-3xl overflow-hidden flex flex-col justify-between shadow-sm"
                     data-cat="<?= (int)$prod['categoria_id'] ?>"
                     data-price="<?= (float)$prod['precio'] ?>"
                     data-featured="<?= !empty($prod['destacado']) ? '1' : '0' ?>"
                     data-search="<?= htmlspecialchars(strtolower($prod['nombre'] . ' ' . ($prod['descripcion'] ?? '') . ' ' . ($prod['origen'] ?? ''))) ?>">
                    <div>
                        <div class="relative h-56 bg-stone-200 overflow-hidden group">
                            <img src="<?= htmlspecialchars($prod['imagen_url'] ?: 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80') ?>"
                                 alt="<?= htmlspecialchars($prod['nombre']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent"></div>

                            <?php if (!empty($prod['destacado'])): ?>
                                <span class="absolute top-3.5 left-3.5 px-3 py-1 rounded-full text-[11px] font-extrabold gold-gradient text-stone-950 shadow-md">
                                    <i class="fa-solid fa-crown mr-1"></i> Recomendado
                                </span>
                            <?php endif; ?>

                            <div class="absolute bottom-3 left-3.5 right-3.5 flex items-center justify-between text-white">
                                <span class="text-xs font-bold flex items-center gap-1.5 text-amber-200">
                                    <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($prod['origen'] ?? 'Colombia') ?>
                                </span>
                                <?php if ($nivelTueste > 0): ?>
                                    <div class="flex items-center gap-1 bg-black/55 px-2.5 py-0.5 rounded-full text-[10px]">
                                        <span class="text-stone-300 mr-1">Tueste <?= htmlspecialchars($tueste) ?></span>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="w-1.5 h-1.5 rounded-full <?= $i <= $nivelTueste ? 'bg-amber-400' : 'bg-white/25' ?>"></span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-5">
                            <h3 class="font-display font-bold text-stone-900 text-lg leading-snug"><?= htmlspecialchars($prod['nombre']) ?></h3>
                            <p class="text-xs text-stone-500 mt-1.5 line-clamp-2 leading-relaxed"><?= htmlspecialchars($prod['descripcion'] ?? '') ?></p>
                        </div>
                    </div>

                    <div class="px-5 pb-5 pt-3 border-t border-stone-200/60 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase tracking-wider text-stone-400 block font-bold">Precio</span>
                            <span class="text-2xl font-extrabold font-mono text-[#1C100B]">$<?= number_format((float)$prod['precio'], 0, ',', '.') ?></span>
                            <span class="block text-[11px] <?= $agotado ? 'text-red-600 font-bold' : 'text-emerald-700 font-semibold' ?>">
                                <?= $agotado ? 'Agotado' : '● Disponible para envío' ?>
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
                                class="px-4 py-3 rounded-2xl text-xs font-extrabold transition flex items-center gap-2 <?= $agotado ? 'bg-stone-200 text-stone-400 cursor-not-allowed' : 'coffee-gradient hover:opacity-95 text-amber-200 shadow-md hover:shadow-xl' ?>">
                            <i class="fa-solid fa-cart-plus text-amber-400"></i> Comprar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Sección de Rastreo de Pedido en Vivo para el Cliente -->
    <section id="rastrear" class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
        <div class="glass-card rounded-3xl p-6 sm:p-8 shadow-md border border-amber-500/30">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <div class="lg:col-span-5 space-y-2">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-amber-700">Seguimiento en Línea</span>
                    <h3 class="font-display text-2xl font-bold text-stone-900">¿Ya hiciste tu pedido? Rastréalo aquí</h3>
                    <p class="text-xs text-stone-500">Ingresa el número de folio de tu compra (ej. <code>WEB-1001</code>) para consultar en vivo si el barista ya lo está preparando.</p>
                    <form method="GET" action="index.php#rastrear" class="flex gap-2 pt-2">
                        <input type="text" name="track_folio" value="<?= htmlspecialchars($folioBuscado) ?>" required
                               placeholder="Ej. WEB-1001"
                               class="flex-1 px-4 py-2.5 rounded-xl border border-stone-300 font-mono text-xs uppercase focus:outline-none focus:ring-2 focus:ring-amber-600">
                        <button type="submit" class="px-5 py-2.5 rounded-xl coffee-gradient text-amber-300 font-bold text-xs shadow">
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
                                        <span class="font-mono font-extrabold text-base text-stone-900"><?= htmlspecialchars($pedidoRastreado['folio']) ?></span>
                                        <p class="text-xs text-stone-500">Cliente: <strong><?= htmlspecialchars($pedidoRastreado['cliente_nombre']) ?></strong> · Destino: <?= htmlspecialchars($pedidoRastreado['direccion_entrega'] ?? '') ?></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase <?= $est === 'completado' ? 'bg-emerald-100 text-emerald-800' : ($est === 'preparando' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800') ?>">
                                        <i class="fa-solid fa-mug-hot mr-1"></i> <?= htmlspecialchars($est) ?>
                                    </span>
                                </div>
                                <div class="space-y-1 text-xs">
                                    <?php foreach ($itemsRastreados as $it): ?>
                                        <div class="flex justify-between text-stone-700">
                                            <span><strong><?= (int)$it['cantidad'] ?>x</strong> <?= htmlspecialchars($it['nombre_producto']) ?></span>
                                            <span class="font-mono font-bold">$<?= number_format((float)$it['subtotal'], 0, ',', '.') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="pt-2 border-t border-stone-100 flex justify-between font-extrabold text-sm">
                                    <span>Total del Pedido</span>
                                    <span class="font-mono text-[#1C100B]">$<?= number_format((float)$pedidoRastreado['total'], 0, ',', '.') ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
                                No encontramos ningún pedido con el folio <strong><?= htmlspecialchars($folioBuscado) ?></strong>. Verifica el código en tu comprobante.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="grid grid-cols-3 gap-3 text-center text-xs">
                            <div class="p-4 rounded-2xl bg-white/80 border border-stone-200/70">
                                <i class="fa-solid fa-file-invoice text-xl text-amber-700 mb-1.5"></i>
                                <p class="font-bold text-stone-800">1. Recibe tu Folio</p>
                                <p class="text-[11px] text-stone-500">Al confirmar tu compra obtienes un código `WEB-XXXX`.</p>
                            </div>
                            <div class="p-4 rounded-2xl bg-white/80 border border-stone-200/70">
                                <i class="fa-solid fa-fire-burner text-xl text-amber-700 mb-1.5"></i>
                                <p class="font-bold text-stone-800">2. Preparación Barista</p>
                                <p class="text-[11px] text-stone-500">Nuestro equipo recibe tu comanda en tiempo real.</p>
                            </div>
                            <div class="p-4 rounded-2xl bg-white/80 border border-stone-200/70">
                                <i class="fa-solid fa-box-check text-xl text-emerald-600 mb-1.5"></i>
                                <p class="font-bold text-stone-800">3. Listo / En Camino</p>
                                <p class="text-[11px] text-stone-500">Recibe tu café recién tostado o retíralo en barra.</p>
                            </div>
                        </div>
                    <?php endif; ?>
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
            <!-- Renderizado por JS -->
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

                <button type="submit" class="w-full py-3.5 rounded-2xl gold-gradient text-stone-950 font-extrabold text-sm shadow-lg hover:opacity-95 transition flex items-center justify-center gap-2">
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
                <button type="button" onclick="window.print()" class="flex-1 py-2.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-800 font-bold">
                    <i class="fa-solid fa-print mr-1"></i> Imprimir Recibo
                </button>
                <button type="button" onclick="document.getElementById('orderSuccessModal').remove()" class="flex-1 py-2.5 rounded-xl coffee-gradient text-amber-300 font-bold">
                    Continuar en la Tienda
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pie de Página de la Tienda Pública -->
    <footer class="coffee-gradient text-stone-300 border-t border-amber-500/20 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-3">
                <img src="logo.php" alt="La Casa del Buen Café" class="w-11 h-11 rounded-full object-cover border border-amber-300/60 shadow">
                <div>
                    <p class="font-display font-bold text-white text-sm">La Casa del Buen Café — Tienda en Línea</p>
                    <p class="text-[11px] text-stone-400">Café 100% Colombiano de Especialidad · Todos los derechos reservados.</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="admin.php" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-amber-300 font-bold transition flex items-center gap-2">
                    <i class="fa-solid fa-user-lock"></i> Ir al Portal de Administrador / Trabajador →
                </a>
            </div>
        </div>
    </footer>

    <script>
        let storeCart = [];
        let activeStoreCategory = 0;

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto px-4 py-3 rounded-2xl border-l-4 border-amber-400 bg-stone-900 text-white shadow-2xl flex items-center gap-3 text-xs font-semibold';
            toast.innerHTML = `<i class="fa-solid fa-bag-shopping text-amber-400"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }

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

        function setStoreCategory(catId) {
            activeStoreCategory = catId;
            document.querySelectorAll('.store-cat-btn').forEach(btn => {
                if (parseInt(btn.dataset.catBtn) === catId) {
                    btn.className = 'store-cat-btn px-4 py-2.5 rounded-2xl text-xs font-extrabold gold-gradient text-stone-950 shadow transition whitespace-nowrap';
                } else {
                    btn.className = 'store-cat-btn px-4 py-2.5 rounded-2xl text-xs font-bold bg-white text-stone-700 border border-stone-200/80 hover:border-amber-500 transition whitespace-nowrap';
                }
            });
            filterStoreProducts();
        }

        function filterStoreProducts() {
            const q = (document.getElementById('storeSearch').value || '').toLowerCase().trim();
            document.querySelectorAll('.store-product-card').forEach(card => {
                const matchCat = (activeStoreCategory === 0 || parseInt(card.dataset.cat) === activeStoreCategory);
                const matchText = (!q || card.dataset.search.includes(q));
                card.style.display = (matchCat && matchText) ? 'flex' : 'none';
            });
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
        }

        function addToStoreCart(product) {
            const existing = storeCart.find(i => i.id === product.id);
            if (existing) {
                if (existing.cantidad < product.stock) {
                    existing.cantidad++;
                    showToast(`+1 ${product.nombre}`);
                } else {
                    showToast('Stock máximo alcanzado', 'warning');
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

            const count = storeCart.reduce((acc, it) => acc + it.cantidad, 0);
            const total = storeCart.reduce((acc, it) => acc + (it.precio * it.cantidad), 0);

            badge.textContent = count;
            headerTotal.textContent = '$' + total.toLocaleString('es-CO');
            countLabel.textContent = `${count} producto(s) seleccionado(s)`;
            totalEl.textContent = '$' + total.toLocaleString('es-CO');
            ptsEl.textContent = '+' + Math.floor(total / 1000) + ' pts';

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

        renderStoreCart();
    </script>
</body>
</html>
