<?php
/**
 * Vista: Gestión de Clientes & Club de Fidelización
 * La Casa del Buen Café
 */
?>
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-stone-800 flex items-center gap-2">
                <i class="fa-solid fa-users text-[#6F4E37]"></i> Directorio de Clientes & Fidelización
            </h2>
            <p class="text-xs text-stone-500">Cada compra de $1.000 COP acumula 1 punto para redimir en café en grano o bebidas de barra</p>
        </div>
        <button type="button" onclick="openClientModal()"
                class="px-4 py-2.5 rounded-xl bg-[#2C1810] hover:bg-[#4A2C1D] text-white font-bold text-xs shadow flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Nuevo Cliente
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-stone-50 text-stone-500 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3">ID / Cliente</th>
                        <th class="px-5 py-3">Contacto</th>
                        <th class="px-5 py-3">Dirección habitual</th>
                        <th class="px-5 py-3">Puntos Acumulados</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    <?php foreach ($clientes as $cli): ?>
                        <tr class="hover:bg-stone-50/80">
                            <td class="px-5 py-3.5">
                                <span class="text-xs font-mono text-stone-400">#<?= (int)$cli['id'] ?></span>
                                <p class="font-bold text-stone-800"><?= htmlspecialchars($cli['nombre']) ?></p>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-stone-600">
                                <p><i class="fa-regular fa-envelope mr-1 text-stone-400"></i> <?= htmlspecialchars($cli['email'] ?? '-') ?></p>
                                <p><i class="fa-solid fa-phone mr-1 text-stone-400"></i> <?= htmlspecialchars($cli['telefono'] ?? '-') ?></p>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-stone-600"><?= htmlspecialchars($cli['direccion'] ?? '-') ?></td>
                            <td class="px-5 py-3.5">
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800 inline-flex items-center gap-1">
                                    <i class="fa-solid fa-star text-amber-500"></i> <?= (int)($cli['puntos'] ?? 0) ?> pts
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <button type="button" onclick='openClientModal(<?= json_encode($cli) ?>)'
                                        class="px-3 py-1.5 rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-semibold mr-1">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </button>
                                <?php if ((int)$cli['id'] > 1): ?>
                                    <form method="POST" action="index.php" class="inline" onsubmit="return confirm('¿Eliminar este cliente?')">
                                        <input type="hidden" name="action" value="delete_client">
                                        <input type="hidden" name="id" value="<?= (int)$cli['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear / Editar Cliente -->
<div id="clientModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-stone-200">
        <h3 id="clientModalTitle" class="font-extrabold text-stone-800 text-base">Registrar Cliente</h3>
        <form method="POST" action="index.php" class="space-y-3 mt-4 text-xs">
            <input type="hidden" name="action" value="save_client">
            <input type="hidden" name="id" id="cliId" value="0">

            <div>
                <label class="block font-semibold text-stone-700 mb-1">Nombre Completo *</label>
                <input type="text" name="nombre" id="cliNombre" required class="w-full px-3 py-2 rounded-xl border border-stone-200">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Correo Electrónico</label>
                    <input type="email" name="email" id="cliEmail" class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Teléfono / WhatsApp</label>
                    <input type="text" name="telefono" id="cliTelefono" class="w-full px-3 py-2 rounded-xl border border-stone-200">
                </div>
            </div>
            <div>
                <label class="block font-semibold text-stone-700 mb-1">Dirección de Entrega</label>
                <input type="text" name="direccion" id="cliDireccion" class="w-full px-3 py-2 rounded-xl border border-stone-200">
            </div>
            <div>
                <label class="block font-semibold text-stone-700 mb-1">Puntos de Fidelidad</label>
                <input type="number" name="puntos" id="cliPuntos" value="0" class="w-full px-3 py-2 rounded-xl border border-stone-200">
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-stone-100">
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-stone-100 text-stone-700 font-semibold">Cancelar</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#2C1810] text-white font-bold">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

<script>
function openClientModal(cli = null) {
    document.getElementById('clientModalTitle').textContent = cli ? 'Editar Cliente #' + cli.id : 'Registrar Cliente';
    document.getElementById('cliId').value = cli ? cli.id : 0;
    document.getElementById('cliNombre').value = cli ? cli.nombre : '';
    document.getElementById('cliEmail').value = cli ? (cli.email || '') : '';
    document.getElementById('cliTelefono').value = cli ? (cli.telefono || '') : '';
    document.getElementById('cliDireccion').value = cli ? (cli.direccion || '') : '';
    document.getElementById('cliPuntos').value = cli ? (cli.puntos || 0) : 0;
    document.getElementById('clientModal').classList.remove('hidden');
}
</script>

