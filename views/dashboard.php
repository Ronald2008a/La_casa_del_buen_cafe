<?php
/**
 * Vista: Panel Administrativo (Dashboard)
 * La Casa del Buen Café
 */
?>
<div class="space-y-6">
    <!-- Banner de Bienvenida -->
    <div class="bg-gradient-to-r from-[#2C1810] via-[#4A2C1D] to-[#6F4E37] rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-400/30 mb-2">
                <i class="fa-solid fa-seedling"></i> Café 100% Colombiano de Origen
            </span>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Panel General — La Casa del Buen Café</h1>
            <p class="text-amber-100/80 text-sm mt-1">
                Monitorea tus ventas de tienda online y barra POS, inventario de café en grano y pedidos en curso.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="index.php?tab=pos" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-stone-950 font-semibold rounded-xl shadow transition flex items-center gap-2 text-sm">
                <i class="fa-solid fa-cash-register"></i> Abrir Caja POS
            </a>
            <a href="index.php?tab=tienda" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition flex items-center gap-2 text-sm">
                <i class="fa-solid fa-store"></i> Ver Tienda Online
            </a>
        </div>
    </div>

    <!-- Tarjetas KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Ingresos Totales</p>
                <p class="text-2xl font-extrabold text-stone-800 mt-1">$<?= number_format($totalVentas, 0, ',', '.') ?></p>
                <span class="text-xs text-emerald-600 font-medium inline-flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-circle-check"></i> <?= count($pedidos) ?> pedidos registrados
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Pedidos Activos</p>
                <p class="text-2xl font-extrabold text-stone-800 mt-1"><?= $pedidosPendientes ?></p>
                <span class="text-xs text-amber-600 font-medium inline-flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-clock"></i> Pendientes / En preparación
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl">
                <i class="fa-solid fa-bell-concierge"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Catálogo & Stock</p>
                <p class="text-2xl font-extrabold text-stone-800 mt-1"><?= count($productos) ?> referencias</p>
                <?php if (count($productosStockBajo) > 0): ?>
                    <a href="index.php?tab=inventario" class="text-xs text-red-600 font-semibold inline-flex items-center gap-1 mt-1 hover:underline">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?= count($productosStockBajo) ?> con stock bajo
                    </a>
                <?php else: ?>
                    <span class="text-xs text-stone-500 inline-flex items-center gap-1 mt-1">
                        <i class="fa-solid fa-boxes-stacked"></i> Stock óptimo
                    </span>
                <?php endif; ?>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-stone-100 text-[#6F4E37] flex items-center justify-center text-xl">
                <i class="fa-solid fa-mug-hot"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Clientes Fidelizados</p>
                <p class="text-2xl font-extrabold text-stone-800 mt-1"><?= count($clientes) ?></p>
                <a href="index.php?tab=clientes" class="text-xs text-[#6F4E37] font-medium inline-flex items-center gap-1 mt-1 hover:underline">
                    <i class="fa-solid fa-award"></i> Club del Buen Café
                </a>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-800 flex items-center justify-center text-xl">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Últimos Pedidos -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-stone-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-stone-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-stone-800">Pedidos Recientes (POS & Tienda Online)</h2>
                    <p class="text-xs text-stone-500">Últimas transacciones registradas en el sistema</p>
                </div>
                <a href="index.php?tab=pedidos" class="text-xs font-semibold text-[#6F4E37] hover:underline">
                    Ver todos <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-stone-50 text-stone-500 text-xs uppercase">
                        <tr>
                            <th class="px-5 py-3">Folio</th>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">Canal</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <?php foreach (array_slice(array_reverse($pedidos), 0, 6) as $ped): ?>
                            <tr class="hover:bg-stone-50/80">
                                <td class="px-5 py-3.5 font-mono font-bold text-stone-800"><?= htmlspecialchars($ped['folio']) ?></td>
                                <td class="px-5 py-3.5 text-stone-700"><?= htmlspecialchars($ped['cliente_nombre'] ?? 'Cliente Mostrador') ?></td>
                                <td class="px-5 py-3.5">
                                    <?php if (($ped['tipo'] ?? 'pos') === 'tienda_online'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                            <i class="fa-solid fa-globe mr-1"></i> Online
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-800">
                                            <i class="fa-solid fa-cash-register mr-1"></i> Barra POS
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 font-bold text-stone-800">$<?= number_format((float)$ped['total'], 0, ',', '.') ?></td>
                                <td class="px-5 py-3.5">
                                    <?php
                                    $est = $ped['estado'] ?? 'completado';
                                    $badgeStyle = match($est) {
                                        'completado' => 'bg-emerald-100 text-emerald-800',
                                        'preparando' => 'bg-amber-100 text-amber-800',
                                        'pendiente'  => 'bg-blue-100 text-blue-800',
                                        default      => 'bg-red-100 text-red-800'
                                    };
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase <?= $badgeStyle ?>">
                                        <?= htmlspecialchars($est) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alertas de Stock Bajo y Estado Supabase -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-stone-800 flex items-center gap-2">
                        <i class="fa-solid fa-boxes-packing text-amber-600"></i> Alertas de Reabastecimiento
                    </h3>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-bold"><?= count($productosStockBajo) ?></span>
                </div>
                <?php if (empty($productosStockBajo)): ?>
                    <p class="text-sm text-stone-500 py-4 text-center">Todos los productos superan el stock mínimo configurado.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($productosStockBajo as $prodBajo): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-red-50/70 border border-red-100">
                                <div>
                                    <p class="text-sm font-semibold text-stone-800"><?= htmlspecialchars($prodBajo['nombre']) ?></p>
                                    <p class="text-xs text-stone-500">Código: <?= htmlspecialchars($prodBajo['codigo']) ?> · Mín: <?= (int)$prodBajo['stock_minimo'] ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-extrabold text-red-600"><?= (int)$prodBajo['stock'] ?> uds</span>
                                    <a href="index.php?tab=inventario" class="block text-[11px] font-semibold text-[#6F4E37] hover:underline">Reabastecer</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tarjeta Estado de Conexión XAMPP MySQL & Supabase -->
            <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm p-5">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-stone-800 flex items-center gap-2">
                        <i class="fa-solid fa-database text-emerald-600"></i> Base de Datos Activa
                    </h3>
                    <?php if ($db->isMySQLActive()): ?>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">XAMPP MYSQL ACTIVO</span>
                    <?php elseif ($db->isConnected()): ?>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">SUPABASE ACTIVO</span>
                    <?php else: ?>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800">INICIAR MYSQL EN XAMPP</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-stone-500 leading-relaxed mb-4">
                    <?php if ($db->isMySQLActive()): ?>
                        Conectado directamente a <strong>XAMPP MySQL</strong> (base de datos <code>la_casa_del_buen_cafe</code> en <code>127.0.0.1:3306</code>). Todas las tablas y registros se guardan en tiempo real en MySQL.
                    <?php else: ?>
                        Inicia el servicio <strong>MySQL</strong> en tu panel de control de <strong>XAMPP</strong> para guardar directamente en <code>la_casa_del_buen_cafe</code>.
                    <?php endif; ?>
                </p>
                <div class="space-y-2">
                    <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=la_casa_del_buen_cafe" target="_blank"
                       class="w-full py-2 px-3 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-xs flex items-center justify-center gap-2 transition">
                        <i class="fa-solid fa-table"></i> Ver Base de Datos en phpMyAdmin
                    </a>
                    <a href="index.php?tab=configuracion" class="w-full py-2 px-3 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-800 font-semibold text-xs flex items-center justify-center gap-2 transition">
                        <i class="fa-solid fa-plug"></i> Configurar XAMPP MySQL & Supabase
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

