<?php
/**
 * Vista: Sistema Punto de Venta (POS) - Caja Rápida Barista
 * La Casa del Buen Café
 */
$lastReceipt = $_SESSION['last_receipt'] ?? null;
unset($_SESSION['last_receipt']);
?>
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Panel Izquierdo (7 cols): Selección Rápida de Productos -->
    <div class="lg:col-span-7 space-y-4">
        <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="relative w-full">
                    <i class="fa-solid fa-barcode absolute left-3.5 top-3 text-stone-400"></i>
                    <input type="text" id="posSearch" oninput="filterPosProducts()" placeholder="Escanear código (ej. BEB-001) o escribir producto..."
                           class="w-full pl-10 pr-4 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-600">
                </div>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <button type="button" onclick="setPosCategory(0)" data-pos-cat="0"
                        class="pos-cat-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-600 text-white whitespace-nowrap">
                    Todos
                </button>
                <?php foreach ($categorias as $cat): ?>
                    <button type="button" onclick="setPosCategory(<?= (int)$cat['id'] ?>)" data-pos-cat="<?= (int)$cat['id'] ?>"
                            class="pos-cat-btn px-3 py-1.5 rounded-xl text-xs font-semibold bg-stone-100 text-stone-700 hover:bg-stone-200 whitespace-nowrap">
                        <i class="fa-solid <?= htmlspecialchars($cat['icono'] ?? 'fa-mug-hot') ?> mr-1"></i>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Botones Táctiles de Productos -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
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
                        class="pos-item-card text-left bg-white p-3 rounded-2xl border border-stone-200/90 shadow-sm hover:border-amber-600 hover:shadow transition flex flex-col justify-between <?= $sinStock ? 'opacity-50 cursor-not-allowed' : '' ?>">
                    <div>
                        <div class="flex items-center justify-between text-[11px] text-stone-400 mb-1">
                            <span class="font-mono font-semibold"><?= htmlspecialchars($prod['codigo']) ?></span>
                            <span class="<?= ((int)$prod['stock'] <= (int)$prod['stock_minimo']) ? 'text-red-600 font-bold' : 'text-stone-500' ?>">
                                Stock: <?= (int)$prod['stock'] ?>
                            </span>
                        </div>
                        <p class="font-bold text-stone-800 text-sm leading-snug line-clamp-2"><?= htmlspecialchars($prod['nombre']) ?></p>
                    </div>
                    <div class="mt-3 pt-2 border-t border-stone-100 flex items-center justify-between">
                        <span class="text-base font-extrabold text-[#2C1810]">$<?= number_format((float)$prod['precio'], 0, ',', '.') ?></span>
                        <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </div>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Panel Derecho (5 cols): Comanda / Caja Registradora -->
    <div class="lg:col-span-5">
        <div class="bg-white rounded-2xl border border-stone-200/80 shadow-md p-5 sticky top-6">
            <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                <div>
                    <h3 class="font-extrabold text-stone-800 text-base flex items-center gap-2">
                        <i class="fa-solid fa-cash-register text-amber-600"></i> Terminal POS — Barra
                    </h3>
                    <p class="text-xs text-stone-400">Descuenta inventario y suma puntos automáticamente</p>
                </div>
                <button type="button" onclick="clearPosCart()" class="text-xs font-semibold text-red-600 hover:underline">
                    <i class="fa-solid fa-trash-can mr-1"></i> Limpiar
                </button>
            </div>

            <!-- Lista de Ítems en Comanda -->
            <div id="posCartContainer" class="divide-y divide-stone-100 my-3 max-h-60 overflow-y-auto">
                <!-- Renderizado por JS -->
            </div>

            <!-- Totales y Calculadora de Cambio -->
            <div class="bg-stone-900 text-white rounded-2xl p-4 space-y-2 mb-4">
                <div class="flex justify-between items-center">
                    <span class="text-xs uppercase tracking-wider text-stone-400">Total a Cobrar</span>
                    <span id="posTotalDisplay" class="text-2xl font-extrabold text-amber-400">$0</span>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-stone-700 text-xs">
                    <div>
                        <label class="text-stone-400 block mb-1">Efectivo Recibido ($)</label>
                        <input type="number" id="posCashReceived" oninput="calculatePosChange()" placeholder="Ej. 50000"
                               class="w-full px-2.5 py-1.5 rounded-lg bg-stone-800 text-white border border-stone-700 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-stone-400 block mb-1">Cambio / Devuelta</label>
                        <div id="posChangeDisplay" class="px-2.5 py-1.5 rounded-lg bg-stone-800 text-emerald-400 font-bold text-sm">$0</div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Cobro -->
            <form method="POST" action="index.php" onsubmit="return submitPosOrder()">
                <input type="hidden" name="action" value="create_order">
                <input type="hidden" name="tipo" value="pos">
                <input type="hidden" name="redirect_tab" value="pos">
                <input type="hidden" name="items_json" id="posItemsJson" value="[]">

                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-semibold text-stone-700 mb-1">Cliente (Fidelización)</label>
                            <select name="cliente_id" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                                <?php foreach ($clientes as $cli): ?>
                                    <option value="<?= (int)$cli['id'] ?>"><?= htmlspecialchars($cli['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-stone-700 mb-1">Mesa / Ubicación</label>
                            <input type="text" name="direccion_entrega" value="Consumo en Barra"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-semibold text-stone-700 mb-1">Método de Pago</label>
                            <select name="metodo_pago" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Datáfono (Tarjeta)</option>
                                <option value="transferencia">QR / Nequi / Daviplata</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-stone-700 mb-1">Notas para Barista</label>
                            <input type="text" name="notas" placeholder="Leche almendras, tibio..."
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-sm shadow-lg transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-receipt"></i> Registrar Venta y Emitir Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Ticket Imprimible si se acaba de realizar una venta -->
<?php if ($lastReceipt): ?>
<div id="receiptModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-stone-200 font-mono text-xs space-y-3">
        <div class="text-center border-b border-dashed border-stone-300 pb-3">
            <h4 class="font-bold text-base text-stone-900">LA CASA DEL BUEN CAFÉ</h4>
            <p class="text-stone-500">Café Especial de Origen Colombiano</p>
            <p class="text-stone-400 text-[11px] mt-1">Folio: <?= htmlspecialchars($lastReceipt['folio']) ?> · <?= htmlspecialchars($lastReceipt['fecha']) ?></p>
        </div>
        <div class="space-y-1 text-[11px]">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($lastReceipt['cliente']) ?></p>
            <p><strong>Pago:</strong> <?= strtoupper(htmlspecialchars($lastReceipt['metodo_pago'])) ?></p>
            <?php if (!empty($lastReceipt['notas'])): ?>
                <p><strong>Notas:</strong> <?= htmlspecialchars($lastReceipt['notas']) ?></p>
            <?php endif; ?>
        </div>
        <div class="border-y border-dashed border-stone-300 py-2 space-y-1.5">
            <?php foreach ($lastReceipt['items'] as $it): ?>
                <div class="flex justify-between">
                    <span><?= (int)$it['cantidad'] ?>x <?= htmlspecialchars($it['nombre']) ?></span>
                    <span>$<?= number_format((float)$it['precio'] * (int)$it['cantidad'], 0, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-between font-bold text-sm pt-1">
            <span>TOTAL PAGADO</span>
            <span>$<?= number_format((float)$lastReceipt['total'], 0, ',', '.') ?></span>
        </div>
        <p class="text-center text-[11px] text-stone-400 pt-2">¡Gracias por disfrutar un buen café!</p>
        <div class="flex gap-2 pt-3 font-sans">
            <button type="button" onclick="window.print()" class="flex-1 py-2 rounded-xl bg-stone-900 text-white font-semibold text-xs">
                <i class="fa-solid fa-print mr-1"></i> Imprimir
            </button>
            <button type="button" onclick="document.getElementById('receiptModal').remove()" class="flex-1 py-2 rounded-xl bg-stone-100 text-stone-700 font-semibold text-xs">
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
            btn.className = 'pos-cat-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-600 text-white whitespace-nowrap';
        } else {
            btn.className = 'pos-cat-btn px-3 py-1.5 rounded-xl text-xs font-semibold bg-stone-100 text-stone-700 hover:bg-stone-200 whitespace-nowrap';
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
        } else {
            alert('No hay más unidades en stock.');
        }
    } else {
        posCart.push({ ...prod, cantidad: 1 });
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
    renderPosCart();
}

function getPosTotal() {
    return posCart.reduce((acc, it) => acc + (it.precio * it.cantidad), 0);
}

function renderPosCart() {
    const container = document.getElementById('posCartContainer');
    const totalEl = document.getElementById('posTotalDisplay');

    if (posCart.length === 0) {
        container.innerHTML = '<p class="text-xs text-stone-400 py-8 text-center">Toca cualquier producto a la izquierda para agregarlo a la cuenta.</p>';
        totalEl.textContent = '$0';
        calculatePosChange();
        return;
    }

    container.innerHTML = posCart.map(item => `
        <div class="py-2.5 flex items-center justify-between gap-2 text-xs">
            <div class="flex-1">
                <p class="font-bold text-stone-800">${item.nombre}</p>
                <p class="text-stone-400 font-mono">${item.codigo} · $${item.precio.toLocaleString('es-CO')}</p>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" onclick="updatePosQty(${item.id}, -1)" class="w-6 h-6 rounded-lg bg-stone-100 hover:bg-stone-200 font-bold">-</button>
                <span class="w-5 text-center font-bold">${item.cantidad}</span>
                <button type="button" onclick="updatePosQty(${item.id}, 1)" class="w-6 h-6 rounded-lg bg-stone-100 hover:bg-stone-200 font-bold">+</button>
            </div>
            <div class="font-extrabold text-stone-800 w-20 text-right">$${(item.precio * item.cantidad).toLocaleString('es-CO')}</div>
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
        changeEl.className = 'px-2.5 py-1.5 rounded-lg bg-stone-800 text-emerald-400 font-bold text-sm';
    } else {
        changeEl.textContent = '$0';
        changeEl.className = 'px-2.5 py-1.5 rounded-lg bg-stone-800 text-stone-400 font-bold text-sm';
    }
}

function submitPosOrder() {
    if (posCart.length === 0) {
        alert('Agrega productos a la comanda antes de cobrar.');
        return false;
    }
    document.getElementById('posItemsJson').value = JSON.stringify(posCart);
    return true;
}

renderPosCart();
</script>

