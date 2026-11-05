<?php
/**
 * Vista: Sistema Punto de Venta (POS) - Terminal Táctil Barista
 * La Casa del Buen Café
 */
$lastReceipt = $_SESSION['last_receipt'] ?? null;
unset($_SESSION['last_receipt']);
?>
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Panel Izquierdo (7 cols): Teclado Táctil de Productos con Miniaturas -->
    <div class="lg:col-span-7 space-y-4">
        <div class="glass-card rounded-2xl p-4 shadow-sm space-y-3">
            <div class="relative w-full">
                <i class="fa-solid fa-barcode absolute left-4 top-3.5 text-amber-700"></i>
                <input type="text" id="posSearch" oninput="filterPosProducts()"
                       placeholder="Escanear código SKU (ej. BEB-001, CAF-002) o escribir bebida..."
                       class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-stone-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-600">
            </div>
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <button type="button" onclick="setPosCategory(0)" data-pos-cat="0"
                        class="pos-cat-btn px-3.5 py-2 rounded-xl text-xs font-extrabold gold-gradient text-stone-950 shadow whitespace-nowrap">
                    <i class="fa-solid fa-grip mr-1"></i> Todos
                </button>
                <?php foreach ($categorias as $cat): ?>
                    <button type="button" onclick="setPosCategory(<?= (int)$cat['id'] ?>)" data-pos-cat="<?= (int)$cat['id'] ?>"
                            class="pos-cat-btn px-3.5 py-2 rounded-xl text-xs font-bold bg-stone-100 text-stone-700 hover:bg-amber-50 whitespace-nowrap">
                        <i class="fa-solid <?= htmlspecialchars($cat['icono'] ?? 'fa-mug-hot') ?> mr-1 text-amber-700"></i>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Grid Táctil de Productos -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
            <?php foreach ($productos as $prod): ?>
                <?php $sinStock = ((int)$prod['stock'] <= 0); ?>
                <button type="button"
                        <?= $sinStock ? 'disabled' : '' ?>
                        data-cat="<?= (int)$prod['categoria_id'] ?>"
                        data-search="<?= htmlspecialchars(strtolower($prod['codigo'] . ' ' . $prod['nombre'])) ?>"
                        onclick='addToPosCart(<?= json_encode([
                            "id" => (int)$prod["id"],
                            "codigo" => $prod["codigo"],
                            "nombre" => $prod["nombre"],
                            "precio" => (float)$prod["precio"],
                            "stock" => (int)$prod["stock"]
                        ]) ?>)'
                        class="pos-item-card group text-left glass-card card-hover p-3 rounded-2xl shadow-sm hover:border-amber-500 flex flex-col justify-between <?= $sinStock ? 'opacity-45 cursor-not-allowed' : '' ?>">
                    <div>
                        <div class="relative h-24 rounded-xl overflow-hidden bg-stone-200 mb-2.5">
                            <img src="<?= htmlspecialchars($prod['imagen_url'] ?: 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=400&q=80') ?>"
                                 alt="" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                            <span class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-black/70 text-white">
                                <?= htmlspecialchars($prod['codigo']) ?>
                            </span>
                            <span class="absolute bottom-1.5 right-1.5 px-2 py-0.5 rounded-md text-[10px] font-bold <?= ((int)$prod['stock'] <= (int)$prod['stock_minimo']) ? 'bg-red-600 text-white' : 'bg-black/70 text-emerald-300' ?>">
                                <?= (int)$prod['stock'] ?> uds
                            </span>
                        </div>
                        <p class="font-bold text-stone-900 text-xs sm:text-sm leading-snug line-clamp-2"><?= htmlspecialchars($prod['nombre']) ?></p>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-stone-200/60 flex items-center justify-between">
                        <span class="text-base font-extrabold font-mono text-[#1C100B]">$<?= number_format((float)$prod['precio'], 0, ',', '.') ?></span>
                        <span class="w-7 h-7 rounded-xl gold-gradient text-stone-950 flex items-center justify-center text-xs font-black shadow-sm group-hover:scale-110 transition">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </div>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Panel Derecho (5 cols): Comanda & Caja Registradora -->
    <div class="lg:col-span-5" id="posRegisterPanel">
        <div class="glass-card rounded-3xl shadow-xl p-5 sticky top-28 border border-amber-500/30">
            <div class="flex items-center justify-between pb-3.5 border-b border-stone-200/80">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl coffee-gradient text-amber-400 flex items-center justify-center font-bold shadow">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-stone-900 text-base">Comanda Barra POS</h3>
                        <p class="text-[11px] text-stone-400">Facturación instantánea en MySQL</p>
                    </div>
                </div>
                <button type="button" onclick="clearPosCart()" class="px-2.5 py-1 rounded-lg bg-red-50 text-xs font-bold text-red-600 hover:bg-red-100">
                    <i class="fa-solid fa-trash-can mr-1"></i> Limpiar
                </button>
            </div>

            <!-- Ítems de la Comanda -->
            <div id="posCartContainer" class="divide-y divide-stone-200/60 my-3 max-h-56 overflow-y-auto pr-1">
                <!-- Renderizado por JS -->
            </div>

            <!-- Pantalla Digital de Cobro y Billetes Rápidos -->
            <div class="coffee-gradient text-white rounded-2xl p-4 space-y-3 mb-4 shadow-lg border border-amber-500/30">
                <div class="flex justify-between items-baseline">
                    <span class="text-xs uppercase tracking-wider text-amber-200/80 font-bold">Total a Cobrar</span>
                    <span id="posTotalDisplay" class="text-3xl font-extrabold font-mono text-amber-400">$0</span>
                </div>

                <!-- Botones de Billetes Rápidos (COP) -->
                <div class="flex flex-wrap gap-1.5 pt-1">
                    <button type="button" onclick="setQuickCash('exact')" class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-amber-500 hover:text-stone-950 text-[11px] font-bold transition">
                        Exacto
                    </button>
                    <button type="button" onclick="setQuickCash(10000)" class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-amber-500 hover:text-stone-950 text-[11px] font-mono font-bold transition">
                        $10.000
                    </button>
                    <button type="button" onclick="setQuickCash(20000)" class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-amber-500 hover:text-stone-950 text-[11px] font-mono font-bold transition">
                        $20.000
                    </button>
                    <button type="button" onclick="setQuickCash(50000)" class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-amber-500 hover:text-stone-950 text-[11px] font-mono font-bold transition">
                        $50.000
                    </button>
                    <button type="button" onclick="setQuickCash(100000)" class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-amber-500 hover:text-stone-950 text-[11px] font-mono font-bold transition">
                        $100.000
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-white/10 text-xs">
                    <div>
                        <label class="text-stone-300 block mb-1 text-[11px]">Efectivo Recibido ($)</label>
                        <input type="number" id="posCashReceived" oninput="calculatePosChange()" placeholder="0"
                               class="w-full px-3 py-1.5 rounded-xl bg-black/40 text-white font-mono border border-white/15 focus:outline-none focus:border-amber-400">
                    </div>
                    <div>
                        <label class="text-stone-300 block mb-1 text-[11px]">Cambio / Devuelta</label>
                        <div id="posChangeDisplay" class="px-3 py-1.5 rounded-xl bg-black/40 text-emerald-400 font-mono font-extrabold text-sm border border-white/10">$0</div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Cierre de Venta -->
            <form method="POST" action="index.php" onsubmit="return submitPosOrder()">
                <input type="hidden" name="action" value="create_order">
                <input type="hidden" name="tipo" value="pos">
                <input type="hidden" name="redirect_tab" value="pos">
                <input type="hidden" name="items_json" id="posItemsJson" value="[]">

                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Cliente (Fidelización)</label>
                            <select name="cliente_id" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                                <?php foreach ($clientes as $cli): ?>
                                    <option value="<?= (int)$cli['id'] ?>"><?= htmlspecialchars($cli['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Mesa / Servicio</label>
                            <input type="text" name="direccion_entrega" value="Consumo en Barra"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Método de Pago</label>
                            <select name="metodo_pago" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                                <option value="efectivo">💵 Efectivo</option>
                                <option value="tarjeta">💳 Datáfono (Tarjeta)</option>
                                <option value="transferencia">📱 QR Nequi / Daviplata</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Notas para Barista</label>
                            <input type="text" name="notas" placeholder="Leche almendras, sin azúcar..."
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white font-extrabold text-sm shadow-xl shadow-emerald-700/20 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-receipt"></i> Cobrar y Emitir Ticket POS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Botón Flotante para Pantallas Móviles para saltar a la Caja Registradora -->
<div id="mobilePosCheckoutBtn" class="lg:hidden fixed bottom-16 left-4 right-4 z-20 hidden">
    <button type="button" onclick="document.getElementById('posRegisterPanel').scrollIntoView({behavior:'smooth'})"
            class="w-full py-3.5 rounded-2xl gold-gradient text-stone-950 font-extrabold text-xs sm:text-sm shadow-2xl flex items-center justify-between px-5 border border-amber-400">
        <span class="flex items-center gap-2">
            <i class="fa-solid fa-receipt text-base"></i>
            <span>Ir a Cobrar Comanda</span>
        </span>
        <span id="mobilePosTotalLabel" class="font-mono font-black text-sm">$0</span>
    </button>
</div>

<!-- Modal de Ticket Térmico Imprimible -->
<?php if ($lastReceipt): ?>
<div id="receiptModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-stone-200 font-mono text-xs space-y-3 animate-fade-in">
        <div class="text-center border-b-2 border-dashed border-stone-300 pb-3">
            <img src="logo.php" alt="La Casa del Buen Café" class="w-16 h-16 rounded-full object-cover mx-auto mb-2 shadow border border-amber-300">
            <h4 class="font-bold text-base text-stone-900 tracking-wider">LA CASA DEL BUEN CAFÉ</h4>
            <p class="text-stone-500 text-[11px]">Tostadores de Café de Especialidad</p>
            <p class="text-stone-600 text-[10px] font-sans mt-0.5">Calle 26 # 16-24 C.C. San Juan Plaza Local 151 Primer Piso · Neiva, Huila</p>
            <p class="text-stone-400 text-[11px] mt-1">Comprobante: <strong><?= htmlspecialchars($lastReceipt['folio']) ?></strong> · <?= htmlspecialchars($lastReceipt['fecha']) ?></p>
        </div>
        <div class="space-y-1 text-[11px]">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($lastReceipt['cliente']) ?></p>
            <p><strong>Medio de Pago:</strong> <?= strtoupper(htmlspecialchars($lastReceipt['metodo_pago'])) ?></p>
            <?php if (!empty($lastReceipt['notas'])): ?>
                <p><strong>Notas Barista:</strong> <?= htmlspecialchars($lastReceipt['notas']) ?></p>
            <?php endif; ?>
        </div>
        <div class="border-y-2 border-dashed border-stone-300 py-2.5 space-y-1.5">
            <?php foreach ($lastReceipt['items'] as $it): ?>
                <div class="flex justify-between">
                    <span><?= (int)$it['cantidad'] ?>x <?= htmlspecialchars($it['nombre']) ?></span>
                    <span class="font-bold">$<?= number_format((float)$it['precio'] * (int)$it['cantidad'], 0, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-between font-extrabold text-base pt-1 text-stone-900">
            <span>TOTAL PAGADO</span>
            <span>$<?= number_format((float)$lastReceipt['total'], 0, ',', '.') ?></span>
        </div>
        <div class="text-center pt-2 space-y-1">
            <i class="fa-solid fa-qrcode text-4xl text-stone-800"></i>
            <p class="text-[10px] text-stone-400">¡Gracias por disfrutar un café 100% colombiano!</p>
        </div>
        <div class="flex gap-2 pt-3 font-sans">
            <button type="button" onclick="window.print()" class="flex-1 py-2.5 rounded-xl coffee-gradient text-white font-bold text-xs shadow">
                <i class="fa-solid fa-print mr-1"></i> Imprimir Ticket
            </button>
            <button type="button" onclick="document.getElementById('receiptModal').remove()" class="flex-1 py-2.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold text-xs">
                Cerrar
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let posCart = [];
let activePosCat = 0;

function setPosCategory(catId) {
    activePosCat = catId;
    document.querySelectorAll('.pos-cat-btn').forEach(btn => {
        if (parseInt(btn.dataset.posCat) === catId) {
            btn.className = 'pos-cat-btn px-3.5 py-2 rounded-xl text-xs font-extrabold gold-gradient text-stone-950 shadow whitespace-nowrap';
        } else {
            btn.className = 'pos-cat-btn px-3.5 py-2 rounded-xl text-xs font-bold bg-stone-100 text-stone-700 hover:bg-amber-50 whitespace-nowrap';
        }
    });
    filterPosProducts();
}

function filterPosProducts() {
    const q = (document.getElementById('posSearch').value || '').toLowerCase().trim();
    document.querySelectorAll('.pos-item-card').forEach(card => {
        const matchCat = (activePosCat === 0 || parseInt(card.dataset.cat) === activePosCat);
        const matchQ = (!q || card.dataset.search.includes(q));
        card.style.display = (matchCat && matchQ) ? 'flex' : 'none';
    });
}

function addToPosCart(prod) {
    const found = posCart.find(p => p.id === prod.id);
    if (found) {
        if (found.cantidad < prod.stock) {
            found.cantidad++;
            if (typeof showToast === 'function') showToast(`+1 ${prod.nombre}`);
        } else {
            if (typeof showToast === 'function') showToast('Sin más unidades en stock', 'warning');
        }
    } else {
        posCart.push({ ...prod, cantidad: 1 });
        if (typeof showToast === 'function') showToast(`Comanda: ${prod.nombre}`);
    }
    renderPosCart();
}

function updatePosQty(id, delta) {
    const idx = posCart.findIndex(p => p.id === id);
    if (idx === -1) return;
    posCart[idx].cantidad += delta;
    if (posCart[idx].cantidad <= 0) {
        posCart.splice(idx, 1);
    }
    renderPosCart();
}

function clearPosCart() {
    posCart = [];
    document.getElementById('posCashReceived').value = '';
    renderPosCart();
}

function getPosTotal() {
    return posCart.reduce((acc, it) => acc + (it.precio * it.cantidad), 0);
}

function setQuickCash(val) {
    const total = getPosTotal();
    const input = document.getElementById('posCashReceived');
    input.value = (val === 'exact') ? total : val;
    calculatePosChange();
}

function renderPosCart() {
    const container = document.getElementById('posCartContainer');
    const totalEl = document.getElementById('posTotalDisplay');
    const mobileBtn = document.getElementById('mobilePosCheckoutBtn');
    const mobileTotal = document.getElementById('mobilePosTotalLabel');

    if (posCart.length === 0) {
        if (mobileBtn) mobileBtn.classList.add('hidden');
        container.innerHTML = `
            <div class="py-8 text-center space-y-2">
                <i class="fa-solid fa-cash-register text-2xl text-stone-300"></i>
                <p class="text-xs text-stone-400">Toca cualquier producto del panel táctil para agregarlo a la cuenta.</p>
            </div>
        `;
        totalEl.textContent = '$0';
        calculatePosChange();
        return;
    }

    if (mobileBtn) {
        mobileBtn.classList.remove('hidden');
        if (mobileTotal) mobileTotal.textContent = '$' + getPosTotal().toLocaleString('es-CO');
    }

    container.innerHTML = posCart.map(item => `
        <div class="py-2.5 flex items-center justify-between gap-2 text-xs">
            <div class="flex-1 min-w-0">
                <p class="font-bold text-stone-900 truncate">${item.nombre}</p>
                <p class="text-stone-400 font-mono text-[11px]">${item.codigo} · $${item.precio.toLocaleString('es-CO')}</p>
            </div>
            <div class="flex items-center gap-1.5 bg-stone-100 rounded-xl p-1">
                <button type="button" onclick="updatePosQty(${item.id}, -1)" class="w-6 h-6 rounded-lg bg-white hover:bg-amber-100 font-bold shadow-sm">-</button>
                <span class="w-5 text-center font-extrabold">${item.cantidad}</span>
                <button type="button" onclick="updatePosQty(${item.id}, 1)" class="w-6 h-6 rounded-lg bg-white hover:bg-amber-100 font-bold shadow-sm">+</button>
            </div>
            <div class="font-mono font-extrabold text-stone-900 w-20 text-right">$${(item.precio * item.cantidad).toLocaleString('es-CO')}</div>
        </div>
    `).join('');

    totalEl.textContent = '$' + getPosTotal().toLocaleString('es-CO');
    calculatePosChange();
}

function calculatePosChange() {
    const total = getPosTotal();
    const received = parseFloat(document.getElementById('posCashReceived').value || '0');
    const changeEl = document.getElementById('posChangeDisplay');
    if (received >= total && total > 0) {
        changeEl.textContent = '$' + (received - total).toLocaleString('es-CO');
        changeEl.className = 'px-3 py-1.5 rounded-xl bg-black/40 text-emerald-400 font-mono font-extrabold text-sm border border-emerald-500/30';
    } else {
        changeEl.textContent = '$0';
        changeEl.className = 'px-3 py-1.5 rounded-xl bg-black/40 text-stone-400 font-mono font-bold text-sm border border-white/10';
    }
}

function submitPosOrder() {
    if (posCart.length === 0) {
        if (typeof showToast === 'function') showToast('Agrega productos a la comanda antes de cobrar.', 'error');
        return false;
    }
    document.getElementById('posItemsJson').value = JSON.stringify(posCart);
    return true;
}

renderPosCart();
</script>
