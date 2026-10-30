<?php
/**
 * Vista: Panel Administrativo (Dashboard Analítico & Visual)
 * La Casa del Buen Café
 */

// Calcular valorización total del inventario y datos para gráficos Chart.js
$valorInventario = 0;
$unidadesTotales = 0;
$stockPorCategoria = [];
$nombresCategorias = [];

foreach ($categorias as $c) {
    $nombresCategorias[(int)$c['id']] = $c['nombre'];
    $stockPorCategoria[(int)$c['id']] = 0;
}

foreach ($productos as $p) {
    $st = (int)($p['stock'] ?? 0);
    $pr = (float)($p['precio'] ?? 0);
    $valorInventario += ($st * $pr);
    $unidadesTotales += $st;
    $cid = (int)($p['categoria_id'] ?? 1);
    $stockPorCategoria[$cid] = ($stockPorCategoria[$cid] ?? 0) + $st;
}

$chartCatLabels = array_values($nombresCategorias);
$chartCatValues = array_values($stockPorCategoria);
?>
<div class="space-y-7">
    <!-- Hero Banner con Imagen de Fondo y Estilo Editorial -->
    <div class="relative rounded-3xl overflow-hidden shadow-2xl border border-amber-500/30">
        <img src="https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=1600&q=80"
             alt="Café de Especialidad"
             class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-[#120A06]/95 via-[#1C100B]/85 to-[#2E1A11]/60"></div>

        <div class="relative z-10 p-7 sm:p-9 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 max-w-2xl">
                <img src="logo.php" alt="La Casa del Buen Café"
                     class="w-24 h-24 rounded-full object-cover shadow-2xl border-2 border-[#E8B78D] shrink-0">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30 backdrop-blur-sm">
                        <i class="fa-solid fa-fire-flame-curved text-amber-400"></i> Tostión Fresca de Especialidad · ERP En Tiempo Real
                    </div>
                    <h1 class="font-display text-3xl sm:text-4xl font-bold text-white tracking-tight leading-tight">
                        Bienvenido a <span class="text-amber-400 italic">La Casa del Buen Café</span>
                    </h1>
                    <p class="text-stone-300 text-sm leading-relaxed">
                        Controla tu barra de espresso POS, catálogo de comercio electrónico, kardex de granos de origen y fidelización de clientes conectado a <strong><?= htmlspecialchars($db->getActiveEngineName()) ?></strong>.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="index.php?tab=pos"
                   class="px-5 py-3 rounded-2xl gold-gradient hover:opacity-95 text-stone-950 font-extrabold shadow-xl shadow-amber-500/20 transition flex items-center gap-2.5 text-sm">
                    <i class="fa-solid fa-cash-register"></i> Abrir Terminal POS
                </a>
                <a href="index.php?tab=tienda"
                   class="px-5 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold border border-white/20 backdrop-blur-md transition flex items-center gap-2.5 text-sm">
                    <i class="fa-solid fa-bag-shopping text-amber-400"></i> Tienda Boutique
                </a>
            </div>
        </div>
    </div>

    <!-- Tarjetas KPI Premium -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- KPI 1: Ventas Totales -->
        <div class="glass-card card-hover rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-stone-400">Ventas Acumuladas</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-stone-900 mt-1 font-mono">
                        $<?= number_format($totalVentas, 0, ',', '.') ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white flex items-center justify-center text-xl shadow-md shadow-emerald-600/20">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-200/60 flex items-center justify-between text-xs">
                <span class="text-emerald-700 font-bold flex items-center gap-1">
                    <i class="fa-solid fa-arrow-trend-up"></i> <?= count($pedidos) ?> órdenes
                </span>
                <span class="text-stone-400">POS + Online</span>
            </div>
        </div>

        <!-- KPI 2: Comandas Activas -->
        <div class="glass-card card-hover rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-stone-400">Comandas en Curso</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-stone-900 mt-1 font-mono">
                        <?= $pedidosPendientes ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center text-xl shadow-md shadow-amber-500/20">
                    <i class="fa-solid fa-bell-concierge"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-200/60 flex items-center justify-between text-xs">
                <a href="index.php?tab=pedidos" class="text-amber-700 font-bold hover:underline flex items-center gap-1">
                    <i class="fa-solid fa-fire-burner"></i> Gestionar barra
                </a>
                <span class="text-stone-400">En preparación</span>
            </div>
        </div>

        <!-- KPI 3: Valorización de Inventario -->
        <div class="glass-card card-hover rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-stone-400">Valor Inventario</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-stone-900 mt-1 font-mono">
                        $<?= number_format($valorInventario, 0, ',', '.') ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#4E2F1E] to-[#1C100B] text-amber-400 flex items-center justify-center text-xl shadow-md">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-200/60 flex items-center justify-between text-xs">
                <?php if (count($productosStockBajo) > 0): ?>
                    <a href="index.php?tab=inventario" class="text-red-600 font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?= count($productosStockBajo) ?> en alerta
                    </a>
                <?php else: ?>
                    <span class="text-emerald-700 font-bold">Stock saludable</span>
                <?php endif; ?>
                <span class="text-stone-400"><?= $unidadesTotales ?> uds totales</span>
            </div>
        </div>

        <!-- KPI 4: Club de Clientes -->
        <div class="glass-card card-hover rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-stone-400">Club del Buen Café</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-stone-900 mt-1 font-mono">
                        <?= count($clientes) ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 to-amber-700 text-white flex items-center justify-center text-xl shadow-md">
                    <i class="fa-solid fa-award"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-200/60 flex items-center justify-between text-xs">
                <a href="index.php?tab=clientes" class="text-[#6F4E37] font-bold hover:underline flex items-center gap-1">
                    <i class="fa-solid fa-user-plus"></i> Ver directorio
                </a>
                <span class="text-stone-400">1 pt / $1.000 COP</span>
            </div>
        </div>
    </div>

    <!-- Sección Visual de Analítica (Chart.js) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 glass-card rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-display font-bold text-lg text-stone-900">Unidades en Stock por Categoría</h2>
                    <p class="text-xs text-stone-500">Distribución actual de inventario en bodega y barra</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900">
                    <i class="fa-solid fa-chart-column mr-1"></i> Tiempo Real
                </span>
            </div>
            <div class="h-64">
                <canvas id="inventoryChart"></canvas>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div>
                <h2 class="font-display font-bold text-lg text-stone-900">Ventas por Canal</h2>
                <p class="text-xs text-stone-500 mb-4">Comparativa Tienda Online vs Barra POS</p>
                <div class="h-48 flex items-center justify-center">
                    <canvas id="channelChart"></canvas>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-4 border-t border-stone-200/60 text-xs">
                <div class="p-3 rounded-xl bg-amber-50/80 border border-amber-200/60">
                    <span class="text-stone-500 block">Tienda Online</span>
                    <strong class="text-sm font-mono text-stone-900">$<?= number_format($ventasOnline, 0, ',', '.') ?></strong>
                </div>
                <div class="p-3 rounded-xl bg-stone-100 border border-stone-200/60">
                    <span class="text-stone-500 block">Barra POS</span>
                    <strong class="text-sm font-mono text-stone-900">$<?= number_format($ventasPos, 0, ',', '.') ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos Destacados (Vitrina Rápida) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display font-bold text-xl text-stone-900">Selección Especial del Maestro Tostador</h2>
                <p class="text-xs text-stone-500">Referencias destacadas en nuestra tienda en línea y barra de especialidad</p>
            </div>
            <a href="index.php?tab=tienda" class="text-xs font-bold text-[#6F4E37] hover:underline flex items-center gap-1">
                Explorar catálogo completo <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php
            $destacados = array_values(array_filter($productos, fn($p) => !empty($p['destacado'])));
            foreach (array_slice($destacados ?: $productos, 0, 4) as $dest):
            ?>
                <div class="glass-card card-hover rounded-2xl overflow-hidden shadow-sm flex flex-col justify-between">
                    <div class="relative h-36 bg-stone-200 overflow-hidden">
                        <img src="<?= htmlspecialchars($dest['imagen_url'] ?: 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80') ?>"
                             alt="<?= htmlspecialchars($dest['nombre']) ?>"
                             class="w-full h-full object-cover hover:scale-110 transition duration-500">
                        <span class="absolute top-2.5 left-2.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold gold-gradient text-stone-950 shadow">
                            <i class="fa-solid fa-star mr-0.5"></i> Origen
                        </span>
                        <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-black/75 text-amber-200">
                            <?= htmlspecialchars($dest['origen'] ?? 'Colombia') ?>
                        </span>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-stone-900 text-sm line-clamp-1"><?= htmlspecialchars($dest['nombre']) ?></h3>
                        <p class="text-[11px] text-stone-500 line-clamp-1 mt-0.5"><?= htmlspecialchars($dest['descripcion'] ?? '') ?></p>
                        <div class="mt-3 pt-2.5 border-t border-stone-200/60 flex items-center justify-between">
                            <span class="font-mono font-extrabold text-base text-[#2E1A11]">$<?= number_format((float)$dest['precio'], 0, ',', '.') ?></span>
                            <a href="index.php?tab=tienda" class="px-2.5 py-1 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-950 text-[11px] font-bold transition">
                                Pedir <i class="fa-solid fa-arrow-right ml-0.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Últimos Pedidos y Alertas de Reabastecimiento -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tabla de Últimos Pedidos -->
        <div class="lg:col-span-2 glass-card rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-stone-200/70 flex items-center justify-between">
                <div>
                    <h2 class="font-display font-bold text-lg text-stone-900">Últimas Transacciones</h2>
                    <p class="text-xs text-stone-500">Órdenes registradas en XAMPP MySQL (`pedidos`)</p>
                </div>
                <a href="index.php?tab=pedidos" class="px-3 py-1.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-xs font-bold text-stone-800 transition">
                    Ver todas <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-stone-100/80 text-stone-500 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3">Folio</th>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">Canal</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200/50">
                        <?php foreach (array_slice(array_reverse($pedidos), 0, 6) as $ped): ?>
                            <tr class="hover:bg-amber-50/40 transition">
                                <td class="px-5 py-3.5 font-mono font-bold text-stone-900"><?= htmlspecialchars($ped['folio']) ?></td>
                                <td class="px-5 py-3.5 font-medium text-stone-700"><?= htmlspecialchars($ped['cliente_nombre'] ?? 'Cliente Mostrador') ?></td>
                                <td class="px-5 py-3.5">
                                    <?php if (($ped['tipo'] ?? 'pos') === 'tienda_online'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100/80 text-indigo-800">
                                            <i class="fa-solid fa-globe mr-1"></i> Web
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-900">
                                            <i class="fa-solid fa-cash-register mr-1"></i> POS
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 font-mono font-bold text-stone-900">$<?= number_format((float)$ped['total'], 0, ',', '.') ?></td>
                                <td class="px-5 py-3.5">
                                    <?php
                                    $est = $ped['estado'] ?? 'completado';
                                    $badgeStyle = match($est) {
                                        'completado' => 'bg-emerald-100 text-emerald-800',
                                        'preparando' => 'bg-amber-100 text-amber-800',
                                        'pendiente'  => 'bg-sky-100 text-sky-800',
                                        default      => 'bg-red-100 text-red-800'
                                    };
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase <?= $badgeStyle ?>">
                                        <?= htmlspecialchars($est) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Columna Derecha: Alertas y Conexión MySQL -->
        <div class="space-y-6">
            <div class="glass-card rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-display font-bold text-base text-stone-900 flex items-center gap-2">
                        <i class="fa-solid fa-boxes-packing text-amber-600"></i> Stock Crítico
                    </h3>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 font-extrabold"><?= count($productosStockBajo) ?></span>
                </div>
                <?php if (empty($productosStockBajo)): ?>
                    <p class="text-xs text-stone-500 py-4 text-center">Todos los granos y productos superan el stock mínimo.</p>
                <?php else: ?>
                    <div class="space-y-2.5">
                        <?php foreach ($productosStockBajo as $prodBajo): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-red-50/90 border border-red-200/70">
                                <div>
                                    <p class="text-xs font-bold text-stone-900"><?= htmlspecialchars($prodBajo['nombre']) ?></p>
                                    <p class="text-[11px] text-stone-500 font-mono"><?= htmlspecialchars($prodBajo['codigo']) ?> · Mín: <?= (int)$prodBajo['stock_minimo'] ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-extrabold text-red-600"><?= (int)$prodBajo['stock'] ?> uds</span>
                                    <a href="index.php?tab=inventario" class="block text-[11px] font-bold text-[#6F4E37] hover:underline">Surtiendo →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Estado XAMPP MySQL & phpMyAdmin -->
            <div class="coffee-gradient text-white rounded-2xl p-5 shadow-xl border border-amber-500/30">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-display font-bold text-base flex items-center gap-2">
                        <i class="fa-solid fa-database text-amber-400"></i> Motor de Datos
                    </h3>
                    <span class="px-2.5 py-0.5 text-[10px] font-extrabold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                        ONLINE
                    </span>
                </div>
                <p class="text-xs text-stone-300 leading-relaxed mb-4">
                    Conectado en tiempo real a <strong><?= htmlspecialchars($db->getActiveEngineName()) ?></strong> en <code>127.0.0.1:3306</code>.
                </p>
                <div class="space-y-2">
                    <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=la_casa_del_buen_cafe" target="_blank"
                       class="w-full py-2.5 px-3 rounded-xl gold-gradient text-stone-950 font-extrabold text-xs flex items-center justify-center gap-2 shadow">
                        <i class="fa-solid fa-table"></i> Inspeccionar en phpMyAdmin
                    </a>
                    <a href="index.php?tab=configuracion"
                       class="w-full py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs flex items-center justify-center gap-2 transition">
                        <i class="fa-solid fa-sliders"></i> Configurar MySQL / Supabase
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const invCtx = document.getElementById('inventoryChart');
    if (invCtx && typeof Chart !== 'undefined') {
        new Chart(invCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartCatLabels) ?>,
                datasets: [{
                    label: 'Unidades Disponibles',
                    data: <?= json_encode($chartCatValues) ?>,
                    backgroundColor: ['#2E1A11', '#6F4E37', '#C88A4B', '#D97706', '#92400E'],
                    borderRadius: 10,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    const chanCtx = document.getElementById('channelChart');
    if (chanCtx && typeof Chart !== 'undefined') {
        new Chart(chanCtx, {
            type: 'doughnut',
            data: {
                labels: ['Tienda Online', 'Terminal POS'],
                datasets: [{
                    data: [<?= (float)$ventasOnline ?>, <?= (float)$ventasPos ?>],
                    backgroundColor: ['#C88A4B', '#2E1A11'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });
    }
});
</script>
