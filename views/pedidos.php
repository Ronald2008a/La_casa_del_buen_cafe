<?php
/**
 * Vista: Gestión de Pedidos (Tienda Online y Barra POS)
 * La Casa del Buen Café
 */

// Agrupar detalles por pedido_id
$detallesPorPedido = [];
foreach ($detallePedidos as $det) {
    $pid = (int)($det['pedido_id'] ?? 0);
    $detallesPorPedido[$pid][] = $det;
}
?>
<div class="space-y-5">
    <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-stone-800 flex items-center gap-2">
                <i class="fa-solid fa-bell-concierge text-[#6F4E37]"></i> Centro de Pedidos & Comandas
            </h2>
            <p class="text-xs text-stone-500">Gestiona el flujo de preparación de pedidos web y órdenes en mesa/barra</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="filterOrdersByStatus('all')" class="order-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#2C1810] text-white">
                Todos (<?= count($pedidos) ?>)
            </button>
            <button type="button" onclick="filterOrdersByStatus('pendiente')" class="order-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100">
                Pendientes
            </button>
            <button type="button" onclick="filterOrdersByStatus('preparando')" class="order-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-800 hover:bg-amber-100">
                En Preparación
            </button>
            <button type="button" onclick="filterOrdersByStatus('completado')" class="order-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100">
                Completados
            </button>
        </div>
    </div>

    <!-- Tarjetas de Pedidos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach (array_reverse($pedidos) as $ped): ?>
            <?php
            $pid = (int)$ped['id'];
            $itemsPed = $detallesPorPedido[$pid] ?? [];
            $est = $ped['estado'] ?? 'completado';
            $borderClass = match($est) {
                'pendiente'  => 'border-l-4 border-l-blue-500',
                'preparando' => 'border-l-4 border-l-amber-500',
                'completado' => 'border-l-4 border-l-emerald-500',
                default      => 'border-l-4 border-l-red-500'
            };
            ?>
            <div class="order-card bg-white rounded-2xl border border-stone-200/80 <?= $borderClass ?> shadow-sm p-5 flex flex-col justify-between gap-4"
                 data-status="<?= htmlspecialchars($est) ?>">
                <div>
                    <div class="flex items-start justify-between gap-2 pb-3 border-b border-stone-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-extrabold text-base text-stone-900"><?= htmlspecialchars($ped['folio']) ?></span>
                                <?php if (($ped['tipo'] ?? 'pos') === 'tienda_online'): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700">
                                        <i class="fa-solid fa-globe mr-1"></i> Tienda Online
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800">
                                        <i class="fa-solid fa-cash-register mr-1"></i> POS Barra
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-stone-500 mt-1">
                                <i class="fa-regular fa-user mr-1"></i> <strong><?= htmlspecialchars($ped['cliente_nombre'] ?? 'Cliente Mostrador') ?></strong>
                                · Pago: <?= strtoupper(htmlspecialchars($ped['metodo_pago'] ?? 'efectivo')) ?>
                            </p>
                            <?php if (!empty($ped['direccion_entrega'])): ?>
                                <p class="text-xs text-stone-500">
                                    <i class="fa-solid fa-location-dot mr-1 text-stone-400"></i> <?= htmlspecialchars($ped['direccion_entrega']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-extrabold text-[#2C1810]">$<?= number_format((float)$ped['total'], 0, ',', '.') ?></span>
                            <span class="block text-[11px] text-stone-400">
                                <?= !empty($ped['created_at']) ? date('d/m H:i', strtotime($ped['created_at'])) : 'Hoy' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Productos del Pedido -->
                    <div class="py-3 space-y-1.5 text-xs">
                        <?php if (empty($itemsPed)): ?>
                            <p class="text-stone-400 italic">Sin desglose registrado</p>
                        <?php else: ?>
                            <?php foreach ($itemsPed as $it): ?>
                                <div class="flex justify-between text-stone-700">
                                    <span><strong class="text-stone-900"><?= (int)$it['cantidad'] ?>x</strong> <?= htmlspecialchars($it['nombre_producto']) ?></span>
                                    <span class="font-semibold">$<?= number_format((float)$it['subtotal'], 0, ',', '.') ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($ped['notas'])): ?>
                            <div class="mt-2 p-2 rounded-lg bg-amber-50/70 text-amber-900 text-[11px]">
                                <i class="fa-solid fa-note-sticky mr-1"></i> <strong>Nota:</strong> <?= htmlspecialchars($ped['notas']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botonera de Cambio Rápido de Estado -->
                <div class="pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                    <span class="text-xs font-bold uppercase text-stone-500">Estado: <strong class="text-stone-800"><?= htmlspecialchars($est) ?></strong></span>
                    <form method="POST" action="index.php" class="flex items-center gap-1.5">
                        <input type="hidden" name="action" value="update_order_status">
                        <input type="hidden" name="pedido_id" value="<?= $pid ?>">
                        <button type="submit" name="estado" value="pendiente"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold <?= $est === 'pendiente' ? 'bg-blue-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-blue-50' ?>">
                            Pendiente
                        </button>
                        <button type="submit" name="estado" value="preparando"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold <?= $est === 'preparando' ? 'bg-amber-500 text-stone-950' : 'bg-stone-100 text-stone-600 hover:bg-amber-50' ?>">
                            Preparando
                        </button>
                        <button type="submit" name="estado" value="completado"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold <?= $est === 'completado' ? 'bg-emerald-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-emerald-50' ?>">
                            Completado
                        </button>
                        <button type="submit" name="estado" value="cancelado"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold <?= $est === 'cancelado' ? 'bg-red-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-red-50' ?>">
                            Cancelar
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function filterOrdersByStatus(status) {
    document.querySelectorAll('.order-card').forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

