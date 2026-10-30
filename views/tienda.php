<?php
/**
 * Vista: Tienda en Línea / Catálogo de Café
 * La Casa del Buen Café
 */
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Columna Principal: Catálogo -->
    <div class="lg:col-span-2 space-y-5">
        <!-- Hero de la Tienda Online -->
        <div class="bg-[#2C1810] rounded-2xl p-6 text-white relative overflow-hidden shadow-md">
            <div class="relative z-10 max-w-xl">
                <span class="text-xs uppercase tracking-widest text-amber-400 font-bold">Tienda en Línea Oficial</span>
                <h2 class="text-2xl font-extrabold mt-1">Café recién tostado directo a tu taza</h2>
                <p class="text-sm text-stone-300 mt-1">
                    Explora nuestra selección de granos de origen único, bebidas de autor y accesorios barista con envío a domicilio o retiro en barra.
                </p>
            </div>
        </div>

        <!-- Buscador y Filtros por Categoría -->
        <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-sm space-y-3">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-stone-400"></i>
                <input type="text" id="storeSearch" oninput="filterStoreProducts()" placeholder="Buscar por nombre, notas de cata, origen (ej. Huila, Geisha, Cold Brew)..."
                       class="w-full pl-10 pr-4 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#6F4E37]">
            </div>
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <button type="button" onclick="setStoreCategory(0)" data-cat-btn="0"
                        class="store-cat-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#2C1810] text-white transition whitespace-nowrap">
                    <i class="fa-solid fa-layer-group mr-1"></i> Todos
                </button>
                <?php foreach ($categorias as $cat): ?>
                    <button type="button" onclick="setStoreCategory(<?= (int)$cat['id'] ?>)" data-cat-btn="<?= (int)$cat['id'] ?>"
                            class="store-cat-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-stone-100 text-stone-700 hover:bg-stone-200 transition whitespace-nowrap">
                        <i class="fa-solid <?= htmlspecialchars($cat['icono'] ?? 'fa-mug-hot') ?> mr-1"></i>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Grid de Productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="storeProductsGrid">
            <?php foreach ($productos as $prod): ?>
                <?php $agotado = ((int)$prod['stock'] <= 0); ?>
                <div class="store-product-card bg-white rounded-2xl border border-stone-200/80 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition"
                     data-cat="<?= (int)$prod['categoria_id'] ?>"
                     data-search="<?= htmlspecialchars(strtolower($prod['nombre'] . ' ' . ($prod['descripcion'] ?? '') . ' ' . ($prod['origen'] ?? ''))) ?>">
                    <div>
                        <div class="relative h-44 bg-stone-100 overflow-hidden">
                            <img src="<?= htmlspecialchars($prod['imagen_url'] ?: 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80') ?>"
                                 alt="<?= htmlspecialchars($prod['nombre']) ?>"
                                 class="w-full h-full object-cover hover:scale-105 transition duration-300">
                            <?php if (!empty($prod['destacado'])): ?>
                                <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500 text-stone-950 shadow">
                                    <i class="fa-solid fa-star mr-1"></i> Destacado
                                </span>
                            <?php endif; ?>
                            <span class="absolute bottom-2 right-2 px-2.5 py-0.5 rounded-lg text-[11px] font-semibold bg-black/70 text-white backdrop-blur-sm">
                                <?= htmlspecialchars($prod['origen'] ?? 'Colombia') ?>
                            </span>
                        </div>

                        <div class="p-4">
                            <div class="flex items-center justify-between text-xs text-stone-400 mb-1">
                                <span class="font-mono"><?= htmlspecialchars($prod['codigo']) ?></span>
                                <?php if (!empty($prod['tueste']) && $prod['tueste'] !== 'N/A'): ?>
                                    <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 font-medium">Tueste: <?= htmlspecialchars($prod['tueste']) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-bold text-stone-800 text-base"><?= htmlspecialchars($prod['nombre']) ?></h3>
                            <p class="text-xs text-stone-500 mt-1 line-clamp-2"><?= htmlspecialchars($prod['descripcion'] ?? '') ?></p>
                        </div>
                    </div>

                    <div class="px-4 pb-4 pt-2 border-t border-stone-100 flex items-center justify-between">
                        <div>
                            <span class="text-lg font-extrabold text-[#2C1810]">$<?= number_format((float)$prod['precio'], 0, ',', '.') ?></span>
                            <span class="block text-[11px] <?= $agotado ? 'text-red-600 font-bold' : 'text-stone-400' ?>">
                                <?= $agotado ? 'Agotado' : 'Disponible: ' . (int)$prod['stock'] . ' uds' ?>
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
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $agotado ? 'bg-stone-200 text-stone-400 cursor-not-allowed' : 'bg-[#6F4E37] hover:bg-[#2C1810] text-white shadow-sm' ?>">
                            <i class="fa-solid fa-cart-plus"></i> Agregar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Columna Derecha: Carrito de Compras Online y Checkout -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm p-5 sticky top-6">
            <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                <h3 class="font-bold text-stone-800 flex items-center gap-2">
                    <i class="fa-solid fa-bag-shopping text-[#6F4E37]"></i> Tu Pedido Online
                </h3>
                <button type="button" onclick="clearStoreCart()" class="text-xs text-red-600 hover:underline">Vaciar</button>
            </div>

            <div id="storeCartItems" class="divide-y divide-stone-100 my-3 max-h-64 overflow-y-auto">
                <!-- Renderizado por JS -->
            </div>

            <div class="bg-stone-50 rounded-xl p-3 space-y-1.5 text-sm mb-4">
                <div class="flex justify-between text-stone-500 text-xs">
                    <span>Envío / Preparación</span>
                    <span class="text-emerald-700 font-semibold">Gratis</span>
                </div>
                <div class="flex justify-between font-extrabold text-base text-stone-900 pt-1 border-t border-stone-200">
                    <span>Total a Pagar</span>
                    <span id="storeCartTotal">$0</span>
                </div>
            </div>

            <!-- Formulario de Confirmación de Pedido Online -->
            <form method="POST" action="index.php" onsubmit="return prepareStoreCheckout()">
                <input type="hidden" name="action" value="create_order">
                <input type="hidden" name="tipo" value="tienda_online">
                <input type="hidden" name="redirect_tab" value="tienda">
                <input type="hidden" name="items_json" id="storeItemsJson" value="[]">

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-stone-700 mb-1">Cliente Registrado (Opcional)</label>
                        <select name="cliente_id" onchange="fillStoreClientName(this)" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?= (int)$cli['id'] ?>" data-nombre="<?= htmlspecialchars($cli['nombre']) ?>" data-dir="<?= htmlspecialchars($cli['direccion'] ?? '') ?>">
                                    <?= htmlspecialchars($cli['nombre']) ?> (<?= (int)($cli['puntos'] ?? 0) ?> pts)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-stone-700 mb-1">Nombre de quien recibe *</label>
                        <input type="text" name="cliente_nombre" id="storeClienteNombre" required placeholder="Ej. Estefanía Olaya"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200">
                    </div>

                    <div>
                        <label class="block font-semibold text-stone-700 mb-1">Dirección de Envío o Retiro *</label>
                        <input type="text" name="direccion_entrega" id="storeDireccion" required placeholder="Calle / Carrera / Ciudad o 'Retiro en tienda'"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-semibold text-stone-700 mb-1">Método de Pago</label>
                            <select name="metodo_pago" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                                <option value="transferencia">Transferencia / Nequi / PSE</option>
                                <option value="tarjeta">Tarjeta Crédito / Débito</option>
                                <option value="efectivo">Efectivo Contraentrega</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-stone-700 mb-1">Molienda / Notas</label>
                            <input type="text" name="notas" placeholder="En grano, prensa, V60..."
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-[#2C1810] hover:bg-[#4A2C1D] text-white font-bold text-sm shadow transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i> Confirmar Pedido Online
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
            btn.className = 'store-cat-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#2C1810] text-white transition whitespace-nowrap';
        } else {
            btn.className = 'store-cat-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-stone-100 text-stone-700 hover:bg-stone-200 transition whitespace-nowrap';
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

function addToStoreCart(product) {
    const existing = storeCart.find(i => i.id === product.id);
    if (existing) {
        if (existing.cantidad < product.stock) {
            existing.cantidad++;
        } else {
            alert('Stock máximo disponible alcanzado para este producto.');
        }
    } else {
        storeCart.push({ ...product, cantidad: 1 });
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
    if (storeCart.length === 0) {
        container.innerHTML = '<p class="text-xs text-stone-400 py-6 text-center">Tu carrito está vacío. Selecciona tu café favorito del catálogo.</p>';
        totalEl.textContent = '$0';
        return;
    }

    let total = 0;
    container.innerHTML = storeCart.map(item => {
        const sub = item.precio * item.cantidad;
        total += sub;
        return `
            <div class="py-2.5 flex items-center justify-between gap-2 text-xs">
                <div class="flex-1">
                    <p class="font-bold text-stone-800">${item.nombre}</p>
                    <p class="text-stone-400">$${item.precio.toLocaleString('es-CO')} c/u</p>
                </div>
                <div class="flex items-center gap-1.5">
                    <button type="button" onclick="changeStoreQty(${item.id}, -1)" class="w-6 h-6 rounded-lg bg-stone-100 hover:bg-stone-200 font-bold">-</button>
                    <span class="w-5 text-center font-bold">${item.cantidad}</span>
                    <button type="button" onclick="changeStoreQty(${item.id}, 1)" class="w-6 h-6 rounded-lg bg-stone-100 hover:bg-stone-200 font-bold">+</button>
                </div>
                <div class="font-bold text-stone-800 w-20 text-right">$${sub.toLocaleString('es-CO')}</div>
            </div>
        `;
    }).join('');

    totalEl.textContent = '$' + total.toLocaleString('es-CO');
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
        alert('Agrega al menos un producto al carrito antes de confirmar tu pedido.');
        return false;
    }
    document.getElementById('storeItemsJson').value = JSON.stringify(storeCart);
    return true;
}

renderStoreCart();
</script>

