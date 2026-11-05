<?php
/**
 * Vista: Directorio de Clientes & Club de Fidelización (Modo Solo Visualización)
 * La Casa del Buen Café
 */

// Calcular compras por cliente para mostrar en la vista de solo lectura
$comprasPorCliente = [];
foreach ($pedidos as $ped) {
    $cid = (int)($ped['cliente_id'] ?? 0);
    if (!isset($comprasPorCliente[$cid])) {
        $comprasPorCliente[$cid] = ['cantidad' => 0, 'total' => 0];
    }
    if (($ped['estado'] ?? '') !== 'cancelado') {
        $comprasPorCliente[$cid]['cantidad']++;
        $comprasPorCliente[$cid]['total'] += (float)($ped['total'] ?? 0);
    }
}
?>
<div class="space-y-6">
    <div class="glass-card rounded-2xl p-5 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-xl font-bold text-stone-900 flex items-center gap-2">
                <i class="fa-solid fa-users text-[#6F4E37]"></i> Directorio de Clientes & Fidelización
            </h2>
            <p class="text-xs text-stone-500">Consulta de clientes registrados y puntos acumulados (1 punto por cada $1.000 COP en compras)</p>
        </div>
        <span class="px-3.5 py-1.5 rounded-xl bg-amber-100 text-amber-900 font-bold text-xs flex items-center gap-2">
            <i class="fa-solid fa-eye"></i> Modo Solo Lectura (<?= count($clientes) ?> clientes)
        </span>
    </div>

    <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-stone-100/80 text-stone-500 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3">ID / Cliente</th>
                        <th class="px-5 py-3">Contacto</th>
                        <th class="px-5 py-3">Dirección Habitual</th>
                        <th class="px-5 py-3">Pedidos Realizados</th>
                        <th class="px-5 py-3">Puntos Acumulados</th>
                        <th class="px-5 py-3 text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200/60">
                    <?php foreach ($clientes as $cli): ?>
                        <?php
                        $cid = (int)$cli['id'];
                        $resumen = $comprasPorCliente[$cid] ?? ['cantidad' => 0, 'total' => 0];
                        ?>
                        <tr class="hover:bg-amber-50/40 transition">
                            <td class="px-5 py-3.5">
                                <span class="text-xs font-mono text-stone-400">#<?= $cid ?></span>
                                <p class="font-bold text-stone-900"><?= htmlspecialchars($cli['nombre']) ?></p>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-stone-600">
                                <p><i class="fa-regular fa-envelope mr-1 text-stone-400"></i> <?= htmlspecialchars($cli['email'] ?? '-') ?></p>
                                <p><i class="fa-solid fa-phone mr-1 text-stone-400"></i> <?= htmlspecialchars($cli['telefono'] ?? '-') ?></p>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-stone-600"><?= htmlspecialchars($cli['direccion'] ?? '-') ?></td>
                            <td class="px-5 py-3.5 text-xs">
                                <span class="font-bold text-stone-800"><?= $resumen['cantidad'] ?> compras</span>
                                <span class="block text-[11px] text-stone-400 font-mono">$<?= number_format($resumen['total'], 0, ',', '.') ?></span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800 inline-flex items-center gap-1">
                                    <i class="fa-solid fa-star text-amber-500"></i> <?= (int)($cli['puntos'] ?? 0) ?> pts
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <button type="button" onclick='openClientViewModal(<?= json_encode($cli) ?>, <?= json_encode($resumen) ?>)'
                                        class="px-3 py-1.5 rounded-xl bg-stone-100 hover:bg-amber-100 text-stone-800 text-xs font-bold transition inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-eye text-amber-700"></i> Ver Ficha
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Solo Lectura: Ver Ficha del Cliente -->
<div id="clientViewModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-stone-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-stone-100">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl gold-gradient text-stone-950 flex items-center justify-center font-extrabold">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div>
                    <h3 id="viewCliNombre" class="font-display font-bold text-stone-900 text-lg leading-tight">Cliente</h3>
                    <span id="viewCliId" class="text-xs font-mono text-stone-400">#0</span>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('clientViewModal').classList.add('hidden')" class="text-stone-400 hover:text-stone-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs bg-stone-50 p-4 rounded-2xl border border-stone-200/70">
            <div class="flex justify-between">
                <span class="text-stone-500 font-semibold">Correo Electrónico:</span>
                <span id="viewCliEmail" class="font-bold text-stone-800">-</span>
            </div>
            <div class="flex justify-between">
                <span class="text-stone-500 font-semibold">Teléfono / WhatsApp:</span>
                <span id="viewCliTelefono" class="font-bold text-stone-800">-</span>
            </div>
            <div class="flex justify-between">
                <span class="text-stone-500 font-semibold">Dirección Registrada:</span>
                <span id="viewCliDireccion" class="font-bold text-stone-800 text-right">-</span>
            </div>
            <div class="flex justify-between pt-2 border-t border-stone-200">
                <span class="text-stone-500 font-semibold">Total Compras:</span>
                <span id="viewCliCompras" class="font-mono font-bold text-stone-900">0</span>
            </div>
            <div class="flex justify-between">
                <span class="text-stone-500 font-semibold">Puntos Club del Café:</span>
                <span id="viewCliPuntos" class="font-mono font-extrabold text-amber-700">0 pts</span>
            </div>
        </div>

        <div class="flex justify-end pt-1">
            <button type="button" onclick="document.getElementById('clientViewModal').classList.add('hidden')"
                    class="px-5 py-2.5 rounded-xl coffee-gradient text-amber-300 font-bold text-xs">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
function openClientViewModal(cli, resumen) {
    document.getElementById('viewCliId').textContent = 'Socio #' + cli.id;
    document.getElementById('viewCliNombre').textContent = cli.nombre || '-';
    document.getElementById('viewCliEmail').textContent = cli.email || '-';
    document.getElementById('viewCliTelefono').textContent = cli.telefono || '-';
    document.getElementById('viewCliDireccion').textContent = cli.direccion || '-';
    document.getElementById('viewCliCompras').textContent = (resumen.cantidad || 0) + ' ($' + Number(resumen.total || 0).toLocaleString('es-CO') + ')';
    document.getElementById('viewCliPuntos').textContent = (cli.puntos || 0) + ' pts';
    document.getElementById('clientViewModal').classList.remove('hidden');
}
</script>
