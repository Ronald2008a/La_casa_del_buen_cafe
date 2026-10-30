<?php
/**
 * Vista: Tienda en Línea Boutique / Catálogo de Café de Especialidad
 * La Casa del Buen Café
 */
?>
<div class="grid grid-cols-1 lg:grid-cols-12 gap-7">
    <!-- Columna Principal (8 cols): Catálogo Boutique -->
    <div class="lg:col-span-8 space-y-6">
        <!-- Hero de la Tienda Boutique -->
        <div class="relative rounded-3xl overflow-hidden shadow-xl border border-amber-500/30">
            <img src="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1400&q=80"
                 alt="Tienda de Café"
                 class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-[#120A06]/95 via-[#1C100B]/85 to-transparent"></div>
            <div class="relative z-10 p-6 sm:p-8 max-w-xl space-y-2.5 text-white">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold gold-gradient text-stone-950 shadow">
                    <i class="fa-solid fa-award"></i> Micro-Lotes Colombianos · Tostión a Pedido
                </div>
                <h2 class="font-display text-2xl sm:text-3xl font-bold leading-tight">
                    De las montañas de Colombia directo a tu taza
                </h2>
                <p class="text-xs sm:text-sm text-stone-300 leading-relaxed">
                    Selecciona tus granos favoritos, bebidas de autor o métodos de filtrado. Acumula <strong>1 punto por cada $1.000 COP</strong> en el Club del Buen Café.
                </p>
            </div>
        </div>

        <!-- Barra de Búsqueda y Filtros por Categoría -->
        <div class="glass-card rounded-2xl p-4 shadow-sm space-y-3.5">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-amber-700"></i>
                    <input type="text" id="storeSearch" oninput="filterStoreProducts()"
                           placeholder="Buscar por variedad, notas de cata, origen (ej. Geisha, Huila, V60, Croissant)..."
                           class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-stone-200 bg-white/90 text-sm focus:outline-none focus:ring-2 focus:ring-amber-600">
                </div>
                <select id="storeSort" onchange="sortStoreProducts()" class="px-3.5 py-2.5 rounded-xl border border-stone-200 bg-white text-xs font-bold text-stone-700">
                    <option value="featured">Destacados primero</option>
                    <option value="price_asc">Precio: Menor a Mayor</option>
                    <option value="price_desc">Precio: Mayor a Menor</option>
                </select>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <button type="button" onclick="setStoreCategory(0)" data-cat-btn="0"
                        class="store-cat-btn px-4 py-2 rounded-xl text-xs font-extrabold gold-gradient text-stone-950 shadow transition whitespace-nowrap">
                    <i class="fa-solid fa-layer-group mr-1"></i> Todos (<?= count($productos) ?>)
                </button>
                <?php foreach ($categorias as $cat): ?>
                    <button type="button" onclick="setStoreCategory(<?= (int)$cat['id'] ?>)" data-cat-btn="<?= (int)$cat['id'] ?>"
                            class="store-cat-btn px-4 py-2 rounded-xl text-xs font-bold bg-stone-100 text-stone-700 hover:bg-amber-50 hover:text-amber-900 transition whitespace-nowrap">
                        <i class="fa-solid <?= htmlspecialchars($cat['icono'] ?? 'fa-mug-hot') ?> mr-1 text-amber-700"></i>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Grid de Productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5" id="storeProductsGrid">
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
                <div class="store-product-card glass-card card-hover rounded-2xl overflow-hidden flex flex-col justify-between shadow-sm"
                     data-cat="<?= (int)$prod['categoria_id'] ?>"
                     data-price="<?= (float)$prod['precio'] ?>"
                     data-featured="<?= !empty($prod['destacado']) ? '1' : '0' ?>"
                     data-search="<?= htmlspecialchars(strtolower($prod['nombre'] . ' ' . ($prod['descripcion'] ?? '') . ' ' . ($prod['origen'] ?? ''))) ?>">
                    <div>
                        <div class="relative h-52 bg-stone-200 overflow-hidden group">
                            <img src="<?= htmlspecialchars($prod['imagen_url'] ?: 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80') ?>"
                                 alt="<?= htmlspecialchars($prod['nombre']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>

                            <?php if (!empty($prod['destacado'])): ?>
                                <span class="absolute top-3 left-3 px-3 py-1 rounded-full text-[11px] font-extrabold gold-gradient text-stone-950 shadow-md">
                                    <i class="fa-solid fa-crown mr-1"></i> Selección Oro
                                </span>
                            <?php endif; ?>

                            <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-black/60 text-white backdrop-blur-md">
                                <?= htmlspecialchars($prod['codigo']) ?>
                            </span>

                            <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-white">
                                <span class="text-xs font-bold flex items-center gap-1.5 text-amber-200">
                                    <i class="fa-solid fa-mountain-sun"></i> <?= htmlspecialchars($prod['origen'] ?? 'Colombia') ?>
                                </span>
                                <?php if ($nivelTueste > 0): ?>
                                    <div class="flex items-center gap-1 bg-black/50 px-2 py-0.5 rounded-full text-[10px]" title="Perfil de Tueste: <?= htmlspecialchars($tueste) ?>">
                                        <span class="text-stone-300 mr-1"><?= htmlspecialchars($tueste) ?></span>
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
                            <span class="text-xl font-extrabold font-mono text-[#1C100B]">$<?= number_format((float)$prod['precio'], 0, ',', '.') ?></span>
                            <span class="block text-[11px] <?= $agotado ? 'text-red-600 font-bold' : 'text-emerald-700 font-semibold' ?>">
                                <?= $agotado ? 'Agotado temporalmente' : '● En stock (' . (int)$prod['stock'] . ' uds)' ?>
                            </span>
                        </div>
                        <button type="button"
                                <?= $agotado ? 'disabled' : '' ?>
                                onclick='addToStoreCart(<?= json_encode([
                                    "id" => (int)$prod["id"],
                                    "nombre" => $prod["nombre"],
                                    "precio" => (float)$prod["precio"],
                                    "stock" => (int)$prod["stock"],
                                    "codigo" => $prod["codigo"],
                                    "imagen" => $prod["imagen_url"]
                                ]) ?>)'
                                class="px-4 py-2.5 rounded-xl text-xs font-extrabold transition flex items-center gap-2 <?= $agotado ? 'bg-stone-200 text-stone-400 cursor-not-allowed' : 'coffee-gradient hover:opacity-95 text-amber-200 shadow-md hover:shadow-lg' ?>">
                            <i class="fa-solid fa-bag-shopping text-amber-400"></i> Añadir a la Bolsa
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Columna Derecha (4 cols): Bolsa de Compras & Checkout Boutique -->
    <div class="lg:col-span-4">
        <div class="glass-card rounded-3xl shadow-xl p-6 sticky top-28 border border-amber-500/30">
            <div class="flex items-center justify-between pb-4 border-b border-stone-200/80">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl gold-gradient text-stone-950 flex items-center justify-center font-extrabold shadow">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-stone-900 text-base">Tu Bolsa de Café</h3>
                        <p id="storeCartCountLabel" class="text-[11px] text-stone-500">0 productos seleccionados</p>
                    </div>
                </div>
                <button type="button" onclick="clearStoreCart()" class="text-xs font-bold text-red-600 hover:underline">
                    Vaciar
                </button>
            </div>

            <!-- Ítems del Carrito -->
            <div id="storeCartItems" class="divide-y divide-stone-200/60 my-4 max-h-64 overflow-y-auto pr-1">
                <!-- Renderizado por JS -->
            </div>

            <!-- Resumen Financiero y Puntos -->
            <div class="bg-[#1C100B] text-white rounded-2xl p-4 space-y-2 mb-5 shadow-inner">
                <div class="flex justify-between text-xs text-stone-300">
                    <span>Despacho / Tostión</span>
                    <span class="text-emerald-400 font-bold">Cortesía de la Casa</span>
                </div>
                <div class="flex justify-between text-xs text-amber-300">
                    <span><i class="fa-solid fa-star mr-1"></i> Puntos que acumulas</span>
                    <span id="storePointsEarned" class="font-mono font-bold">+0 pts</span>
                </div>
                <div class="flex justify-between items-baseline font-extrabold pt-2 border-t border-white/10">
                    <span class="text-xs uppercase tracking-wider text-stone-300">Total Pedido</span>
                    <span id="storeCartTotal" class="text-2xl font-mono text-amber-400">$0</span>
                </div>
            </div>

            <!-- Formulario Checkout -->
            <form method="POST" action="index.php" onsubmit="return prepareStoreCheckout()">
                <input type="hidden" name="action" value="create_order">
                <input type="hidden" name="tipo" value="tienda_online">
                <input type="hidden" name="redirect_tab" value="tienda">
                <input type="hidden" name="items_json" id="storeItemsJson" value="[]">

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Socio del Club del Café</label>
                        <select name="cliente_id" onchange="fillStoreClientName(this)" class="w-full px-3.5 py-2.5 rounded-xl border border-stone-200 bg-white font-medium">
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?= (int)$cli['id'] ?>" data-nombre="<?= htmlspecialchars($cli['nombre']) ?>" data-dir="<?= htmlspecialchars($cli['direccion'] ?? '') ?>">
                                    <?= htmlspecialchars($cli['nombre']) ?> (<?= (int)($cli['puntos'] ?? 0) ?> pts)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Nombre de quien recibe *</label>
                        <input type="text" name="cliente_nombre" id="storeClienteNombre" required placeholder="Ej. Estefanía Olaya"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-stone-200 bg-white">
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Dirección de Entrega o Retiro en Barra *</label>
                        <input type="text" name="direccion_entrega" id="storeDireccion" required placeholder="Calle / Carrera / Ciudad o 'Retiro en tienda'"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-stone-200 bg-white">
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Medio de Pago</label>
                            <select name="metodo_pago" class="w-full px-3 py-2.5 rounded-xl border border-stone-200 bg-white">
                                <option value="transferencia">Nequi / PSE / QR</option>
                                <option value="tarjeta">Tarjeta Online</option>
                                <option value="efectivo">Contraentrega</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Tipo de Molienda</label>
                            <input type="text" name="notas" placeholder="Grano, V60, Prensa..."
                                   class="w-full px-3 py-2.5 rounded-xl border border-stone-200 bg-white">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 rounded-2xl gold-gradient hover:opacity-95 text-stone-950 font-extrabold text-sm shadow-lg shadow-amber-500/20 transition flex items-center justify-center gap-2 mt-2">
                        <i class="fa-solid fa-check-double"></i> Confirmar Pedido Ahora
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let storeCart = [];
let activeStoreCategory = 0;

function setStoreCategory(catId) {
    activeStoreCategory = catId;
    document.querySelectorAll('.store-cat-btn').forEach(btn => {
        if (parseInt(btn.dataset.catBtn) === catId) {
            btn.className = 'store-cat-btn px-4 py-2 rounded-xl text-xs font-extrabold gold-gradient text-stone-950 shadow transition whitespace-nowrap';
        } else {
            btn.className = 'store-cat-btn px-4 py-2 rounded-xl text-xs font-bold bg-stone-100 text-stone-700 hover:bg-amber-50 hover:text-amber-900 transition whitespace-nowrap';
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
            if (typeof showToast === 'function') showToast(`+1 ${product.nombre} en tu bolsa`);
        } else {
            if (typeof showToast === 'function') showToast('Alcanzaste el límite de stock disponible', 'warning');
        }
    } else {
        storeCart.push({ ...product, cantidad: 1 });
        if (typeof showToast === 'function') showToast(`Agregado: ${product.nombre}`);
    }
    renderStoreCart();
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

function clearStoreCart() {
    storeCart = [];
    renderStoreCart();
}

function renderStoreCart() {
    const container = document.getElementById('storeCartItems');
    const totalEl = document.getElementById('storeCartTotal');
    const ptsEl = document.getElementById('storePointsEarned');
    const countEl = document.getElementById('storeCartCountLabel');

    const totalItems = storeCart.reduce((acc, it) => acc + it.cantidad, 0);
    countEl.textContent = `${totalItems} producto(s) seleccionado(s)`;

    if (storeCart.length === 0) {
        container.innerHTML = `
            <div class="py-8 text-center space-y-2">
                <i class="fa-solid fa-mug-saucer text-3xl text-stone-300"></i>
                <p class="text-xs text-stone-400">Tu bolsa está vacía. Elige tu café de origen favorito.</p>
            </div>
        `;
        totalEl.textContent = '$0';
        ptsEl.textContent = '+0 pts';
        return;
    }

    let total = 0;
    container.innerHTML = storeCart.map(item => {
        const sub = item.precio * item.cantidad;
        total += sub;
        return `
            <div class="py-3 flex items-center justify-between gap-2 text-xs">
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-stone-900 truncate">${item.nombre}</p>
                    <p class="text-stone-400 font-mono">$${item.precio.toLocaleString('es-CO')} c/u</p>
                </div>
                <div class="flex items-center gap-1.5 bg-stone-100 rounded-xl p-1">
                    <button type="button" onclick="changeStoreQty(${item.id}, -1)" class="w-6 h-6 rounded-lg bg-white hover:bg-amber-100 font-bold shadow-sm">-</button>
                    <span class="w-5 text-center font-extrabold">${item.cantidad}</span>
                    <button type="button" onclick="changeStoreQty(${item.id}, 1)" class="w-6 h-6 rounded-lg bg-white hover:bg-amber-100 font-bold shadow-sm">+</button>
                </div>
                <div class="font-mono font-extrabold text-stone-900 w-20 text-right">$${sub.toLocaleString('es-CO')}</div>
            </div>
        `;
    }).join('');

    totalEl.textContent = '$' + total.toLocaleString('es-CO');
    ptsEl.textContent = '+' + Math.floor(total / 1000) + ' pts';
}

function fillStoreClientName(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (opt && parseInt(selectEl.value) > 1) {
        document.getElementById('storeClienteNombre').value = opt.dataset.nombre || '';
        document.getElementById('storeDireccion').value = opt.dataset.dir || '';
    }
}

function prepareStoreCheckout() {
    if (storeCart.length === 0) {
        if (typeof showToast === 'function') showToast('Tu bolsa está vacía. Agrega al menos un café.', 'error');
        return false;
    }
    document.getElementById('storeItemsJson').value = JSON.stringify(storeCart);
    return true;
}

renderStoreCart();
</script>
