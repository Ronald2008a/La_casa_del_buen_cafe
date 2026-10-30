<?php
/**
 * Vista: Gestión de Inventario, Kardex y CRUD de Productos / Categorías
 * La Casa del Buen Café
 */

$mapaCats = [];
foreach ($categorias as $c) {
    $mapaCats[(int)$c['id']] = $c['nombre'];
}
$mapaProds = [];
foreach ($productos as $p) {
    $mapaProds[(int)$p['id']] = $p['nombre'];
}
?>
<div class="space-y-6">
    <!-- Encabezado y Acciones Rápidas -->
    <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-stone-800 flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked text-[#6F4E37]"></i> Inventario & Catálogo CRUD
            </h2>
            <p class="text-xs text-stone-500">Administra productos, precios, orígenes de café, stock mínimo y movimientos de bodega</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="openProductModal()"
                    class="px-4 py-2.5 rounded-xl bg-[#2C1810] hover:bg-[#4A2C1D] text-white font-bold text-xs shadow flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Nuevo Producto
            </button>
            <button type="button" onclick="document.getElementById('categoryModal').classList.remove('hidden')"
                    class="px-4 py-2.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-800 font-semibold text-xs flex items-center gap-2">
                <i class="fa-solid fa-tags"></i> Nueva Categoría
            </button>
        </div>
    </div>

    <!-- Tabla CRUD de Productos -->
    <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-stone-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h3 class="font-bold text-stone-800 text-sm">Listado Maestro de Productos (<?= count($productos) ?>)</h3>
            <input type="text" id="invSearch" oninput="filterInvTable()" placeholder="Filtrar inventario por código o nombre..."
                   class="px-3 py-1.5 rounded-xl border border-stone-200 text-xs w-full sm:w-64">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-stone-50 text-stone-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3">Origen / Tueste</th>
                        <th class="px-4 py-3">Costo / Precio</th>
                        <th class="px-4 py-3">Stock Actual</th>
                        <th class="px-4 py-3 text-right">Acciones CRUD</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100" id="invTableBody">
                    <?php foreach ($productos as $prod): ?>
                        <?php
                        $esBajo = ((int)$prod['stock'] <= (int)$prod['stock_minimo']);
                        ?>
                        <tr class="inv-row hover:bg-stone-50/80" data-search="<?= htmlspecialchars(strtolower($prod['codigo'] . ' ' . $prod['nombre'])) ?>">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="<?= htmlspecialchars($prod['imagen_url'] ?: 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=200&q=80') ?>"
                                         alt="" class="w-10 h-10 rounded-xl object-cover bg-stone-100">
                                    <div>
                                        <span class="font-mono text-[11px] text-stone-400"><?= htmlspecialchars($prod['codigo']) ?></span>
                                        <p class="font-bold text-stone-800 text-xs sm:text-sm"><?= htmlspecialchars($prod['nombre']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-stone-600">
                                <?= htmlspecialchars($mapaCats[(int)$prod['categoria_id']] ?? 'General') ?>
                            </td>
                            <td class="px-4 py-3 text-xs text-stone-600">
                                <span class="font-medium"><?= htmlspecialchars($prod['origen'] ?? '-') ?></span>
                                <span class="block text-[11px] text-stone-400">Tueste: <?= htmlspecialchars($prod['tueste'] ?? 'N/A') ?></span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="text-stone-400 block">Costo: $<?= number_format((float)($prod['costo'] ?? 0), 0, ',', '.') ?></span>
                                <span class="font-extrabold text-stone-900 text-sm">$<?= number_format((float)$prod['precio'], 0, ',', '.') ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold <?= $esBajo ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800' ?>">
                                        <?= (int)$prod['stock'] ?> uds
                                    </span>
                                    <button type="button"
                                            onclick='openStockModal(<?= (int)$prod["id"] ?>, <?= json_encode($prod["nombre"]) ?>, <?= (int)$prod["stock"] ?>)'
                                            title="Ajustar Entrada/Salida de Stock"
                                            class="px-2 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold">
                                        <i class="fa-solid fa-arrows-rotate"></i> Stock
                                    </button>
                                </div>
                                <span class="text-[10px] text-stone-400">Mínimo: <?= (int)$prod['stock_minimo'] ?></span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button"
                                        onclick='openProductModal(<?= json_encode($prod) ?>)'
                                        class="px-2.5 py-1.5 rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-semibold mr-1">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                </button>
                                <form method="POST" action="index.php" class="inline" onsubmit="return confirm('¿Eliminar este producto del catálogo?')">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Historial Kardex de Movimientos de Inventario -->
    <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-stone-100">
            <h3 class="font-bold text-stone-800 text-sm flex items-center gap-2">
                <i class="fa-solid fa-clipboard-list text-amber-700"></i> Kardex de Movimientos de Inventario Recientes
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-stone-50 text-stone-500 uppercase">
                    <tr>
                        <th class="px-4 py-2.5">Fecha</th>
                        <th class="px-4 py-2.5">Producto</th>
                        <th class="px-4 py-2.5">Tipo</th>
                        <th class="px-4 py-2.5">Cantidad</th>
                        <th class="px-4 py-2.5">Stock Ant. → Nuevo</th>
                        <th class="px-4 py-2.5">Motivo / Referencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    <?php foreach (array_slice(array_reverse($movimientos), 0, 10) as $mov): ?>
                        <tr>
                            <td class="px-4 py-2.5 text-stone-400"><?= !empty($mov['created_at']) ? date('d/m/Y H:i', strtotime($mov['created_at'])) : 'Hoy' ?></td>
                            <td class="px-4 py-2.5 font-semibold text-stone-800"><?= htmlspecialchars($mapaProds[(int)$mov['producto_id']] ?? ('ID #' . $mov['producto_id'])) ?></td>
                            <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 rounded uppercase font-bold text-[10px] <?= ($mov['tipo'] === 'entrada') ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                    <?= htmlspecialchars($mov['tipo']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-2.5 font-bold"><?= (int)$mov['cantidad'] ?></td>
                            <td class="px-4 py-2.5 font-mono"><?= (int)$mov['stock_anterior'] ?> → <strong><?= (int)$mov['stock_nuevo'] ?></strong></td>
                            <td class="px-4 py-2.5 text-stone-600"><?= htmlspecialchars($mov['motivo'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear / Editar Producto -->
<div id="productModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-stone-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-stone-100">
            <h3 id="productModalTitle" class="font-extrabold text-stone-800 text-base">Nuevo Producto</h3>
            <button type="button" onclick="document.getElementById('productModal').classList.add('hidden')" class="text-stone-400 hover:text-stone-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form method="POST" action="index.php" class="space-y-3 mt-4 text-xs">
            <input type="hidden" name="action" value="save_product">
            <input type="hidden" name="id" id="prodId" value="0">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Código SKU *</label>
                    <input type="text" name="codigo" id="prodCodigo" required placeholder="CAF-010"
                           class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Categoría *</label>
                    <select name="categoria_id" id="prodCategoria" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-stone-700 mb-1">Nombre del Producto *</label>
                <input type="text" name="nombre" id="prodNombre" required placeholder="Ej. Café Bourbon Rosado 500g"
                       class="w-full px-3 py-2 rounded-xl border border-stone-200">
            </div>

            <div>
                <label class="block font-semibold text-stone-700 mb-1">Descripción / Notas de Cata</label>
                <textarea name="descripcion" id="prodDescripcion" rows="2" placeholder="Notas dulces a miel, frutos amarillos..."
                          class="w-full px-3 py-2 rounded-xl border border-stone-200"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Origen</label>
                    <input type="text" name="origen" id="prodOrigen" value="Huila, Colombia"
                           class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Nivel de Tueste</label>
                    <select name="tueste" id="prodTueste" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                        <option value="Medio">Medio</option>
                        <option value="Medio-Claro">Medio-Claro</option>
                        <option value="Medio-Oscuro">Medio-Oscuro</option>
                        <option value="Oscuro">Oscuro</option>
                        <option value="N/A">No Aplica (N/A)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Precio Venta ($)</label>
                    <input type="number" step="100" name="precio" id="prodPrecio" required value="12000"
                           class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Costo ($)</label>
                    <input type="number" step="100" name="costo" id="prodCosto" value="5000"
                           class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Stock Inicial</label>
                    <input type="number" name="stock" id="prodStock" required value="20"
                           class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Stock Mínimo</label>
                    <input type="number" name="stock_minimo" id="prodStockMin" required value="5"
                           class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-stone-700 mb-1">URL de Imagen</label>
                <input type="url" name="imagen_url" id="prodImagen" placeholder="https://images.unsplash.com/..."
                       class="w-full px-3 py-2 rounded-xl border border-stone-200">
            </div>

            <label class="inline-flex items-center gap-2 pt-1 cursor-pointer">
                <input type="checkbox" name="destacado" id="prodDestacado" class="rounded text-[#6F4E37]">
                <span class="font-semibold text-stone-700">Destacar en la portada de la Tienda Online</span>
            </label>

            <div class="flex justify-end gap-2 pt-3 border-t border-stone-100">
                <button type="button" onclick="document.getElementById('productModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-stone-100 text-stone-700 font-semibold">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#2C1810] text-white font-bold">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajuste Rápido de Stock -->
<div id="stockModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-stone-200">
        <h3 class="font-extrabold text-stone-800 text-base">Movimiento de Bodega</h3>
        <p id="stockModalProdName" class="text-xs text-stone-500 mt-0.5"></p>
        <form method="POST" action="index.php" class="space-y-3 mt-4 text-xs">
            <input type="hidden" name="action" value="adjust_stock">
            <input type="hidden" name="producto_id" id="stockProdId" value="0">

            <div>
                <label class="block font-semibold text-stone-700 mb-1">Tipo de Movimiento</label>
                <select name="tipo_movimiento" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                    <option value="entrada">Entrada de Mercancía / Tostión (+)</option>
                    <option value="salida">Salida por Merma / Degustación (-)</option>
                    <option value="ajuste">Fijar Stock Exacto (=)</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold text-stone-700 mb-1">Cantidad de Unidades</label>
                <input type="number" name="cantidad" min="1" value="10" required class="w-full px-3 py-2 rounded-xl border border-stone-200">
            </div>
            <div>
                <label class="block font-semibold text-stone-700 mb-1">Motivo / Lote</label>
                <input type="text" name="motivo" required value="Reposición de lote recién tostado" class="w-full px-3 py-2 rounded-xl border border-stone-200">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('stockModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-stone-100 text-stone-700 font-semibold">Cancelar</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 text-white font-bold">Aplicar Movimiento</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nueva Categoría -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-stone-200">
        <h3 class="font-extrabold text-stone-800 text-base">Crear Categoría</h3>
        <form method="POST" action="index.php" class="space-y-3 mt-4 text-xs">
            <input type="hidden" name="action" value="save_category">
            <div>
                <label class="block font-semibold text-stone-700 mb-1">Nombre *</label>
                <input type="text" name="nombre" required placeholder="Ej. Métodos de Filtrado" class="w-full px-3 py-2 rounded-xl border border-stone-200">
            </div>
            <div>
                <label class="block font-semibold text-stone-700 mb-1">Descripción</label>
                <input type="text" name="descripcion" placeholder="Descripción breve" class="w-full px-3 py-2 rounded-xl border border-stone-200">
            </div>
            <div>
                <label class="block font-semibold text-stone-700 mb-1">Ícono FontAwesome</label>
                <select name="icono" class="w-full px-3 py-2 rounded-xl border border-stone-200 bg-white">
                    <option value="fa-mug-hot">Taza Caliente (fa-mug-hot)</option>
                    <option value="fa-seedling">Grano / Planta (fa-seedling)</option>
                    <option value="fa-glass-water">Bebida Fría (fa-glass-water)</option>
                    <option value="fa-cookie-bite">Repostería (fa-cookie-bite)</option>
                    <option value="fa-filter">Accesorio Barista (fa-filter)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('categoryModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-stone-100 text-stone-700 font-semibold">Cancelar</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#2C1810] text-white font-bold">Crear Categoría</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterInvTable() {
    const q = (document.getElementById('invSearch').value || '').toLowerCase().trim();
    document.querySelectorAll('.inv-row').forEach(r => {
        r.style.display = (!q || r.dataset.search.includes(q)) ? '' : 'none';
    });
}

function openProductModal(prod = null) {
    document.getElementById('productModalTitle').textContent = prod ? 'Editar Producto #' + prod.id : 'Nuevo Producto';
    document.getElementById('prodId').value = prod ? prod.id : 0;
    document.getElementById('prodCodigo').value = prod ? prod.codigo : 'CAF-' + Math.floor(100 + Math.random() * 900);
    document.getElementById('prodCategoria').value = prod ? prod.categoria_id : 1;
    document.getElementById('prodNombre').value = prod ? prod.nombre : '';
    document.getElementById('prodDescripcion').value = prod ? (prod.descripcion || '') : '';
    document.getElementById('prodOrigen').value = prod ? (prod.origen || 'Huila, Colombia') : 'Huila, Colombia';
    document.getElementById('prodTueste').value = prod ? (prod.tueste || 'Medio') : 'Medio';
    document.getElementById('prodPrecio').value = prod ? prod.precio : 15000;
    document.getElementById('prodCosto').value = prod ? (prod.costo || 6000) : 6000;
    document.getElementById('prodStock').value = prod ? prod.stock : 25;
    document.getElementById('prodStockMin').value = prod ? prod.stock_minimo : 5;
    document.getElementById('prodImagen').value = prod ? (prod.imagen_url || '') : 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80';
    document.getElementById('prodDestacado').checked = prod ? Boolean(prod.destacado) : false;
    document.getElementById('productModal').classList.remove('hidden');
}

function openStockModal(id, nombre, stockActual) {
    document.getElementById('stockProdId').value = id;
    document.getElementById('stockModalProdName').textContent = nombre + ' (Stock actual: ' + stockActual + ' uds)';
    document.getElementById('stockModal').classList.remove('hidden');
}
</script>

