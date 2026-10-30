<?php
/**
 * LA CASA DEL BUEN CAFÉ — Sistema Integral PHP + Supabase
 * Módulos: Tienda en Línea, Punto de Venta (POS), Pedidos, Inventario CRUD, Clientes y Supabase Auth
 */

require_once __DIR__ . '/includes/actions.php';

$tab = $_GET['tab'] ?? 'dashboard';
if ($tab === 'login') {
    $tab = 'configuracion';
}

// Consultas a Supabase PostgreSQL (o almacenamiento local híbrido si no se ha conectado aún)
$categorias     = $db->select('categorias', ['select' => '*', 'order' => 'id.asc']);
$productos      = $db->select('productos', ['select' => '*', 'order' => 'id.asc']);
$clientes       = $db->select('clientes', ['select' => '*', 'order' => 'id.asc']);
$pedidos        = $db->select('pedidos', ['select' => '*', 'order' => 'id.asc']);
$detallePedidos = $db->select('detalle_pedidos', ['select' => '*', 'order' => 'id.asc']);
$movimientos    = $db->select('movimientos_inventario', ['select' => '*', 'order' => 'id.asc']);

// Cálculo de indicadores KPI
$totalVentas = 0;
$pedidosPendientes = 0;
foreach ($pedidos as $p) {
    if (($p['estado'] ?? '') !== 'cancelado') {
        $totalVentas += (float)($p['total'] ?? 0);
    }
    if (in_array($p['estado'] ?? '', ['pendiente', 'preparando'], true)) {
        $pedidosPendientes++;
    }
}

$productosStockBajo = array_values(array_filter($productos, function ($prod) {
    return (int)($prod['stock'] ?? 0) <= (int)($prod['stock_minimo'] ?? 5);
}));

$navItems = [
    'dashboard'     => ['label' => 'Dashboard',       'icon' => 'fa-chart-pie'],
    'tienda'        => ['label' => 'Tienda Online',   'icon' => 'fa-store'],
    'pos'           => ['label' => 'Punto de Venta',  'icon' => 'fa-cash-register'],
    'pedidos'       => ['label' => 'Pedidos',         'icon' => 'fa-bell-concierge', 'badge' => $pedidosPendientes],
    'inventario'    => ['label' => 'Inventario CRUD', 'icon' => 'fa-boxes-stacked',  'badge' => count($productosStockBajo)],
    'clientes'      => ['label' => 'Clientes',        'icon' => 'fa-users'],
    'configuracion' => ['label' => 'MySQL & Supabase', 'icon' => 'fa-database'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Casa del Buen Café — App PHP + XAMPP MySQL & Supabase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8F5F0; }
    </style>
</head>
<body class="min-h-screen flex flex-col text-stone-800">

    <!-- Barra Superior -->
    <header class="bg-[#1E110B] text-white sticky top-0 z-40 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16">
                <!-- Logo y Marca -->
                <a href="index.php?tab=dashboard" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-stone-950 flex items-center justify-center text-lg font-black shadow">
                        <i class="fa-solid fa-mug-hot"></i>
                    </div>
                    <div>
                        <span class="font-extrabold text-base tracking-tight block leading-none">LA CASA DEL BUEN CAFÉ</span>
                        <span class="text-[11px] text-amber-300/80 font-medium">PHP + XAMPP MySQL & Supabase ERP</span>
                    </div>
                </a>

                <!-- Estado de Conexión XAMPP MySQL / Supabase y Usuario -->
                <div class="flex items-center gap-3">
                    <a href="index.php?tab=configuracion"
                       class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border transition <?= ($db->isMySQLActive() || $db->isConnected()) ? 'bg-emerald-950/80 border-emerald-500/40 text-emerald-300' : 'bg-amber-950/60 border-amber-500/40 text-amber-300' ?>">
                        <span class="w-2 h-2 rounded-full <?= ($db->isMySQLActive() || $db->isConnected()) ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' ?>"></span>
                        <?= htmlspecialchars($db->getActiveEngineName()) ?>
                    </a>

                    <?php if (!empty($_SESSION['user'])): ?>
                        <div class="flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-xl text-xs">
                            <i class="fa-solid fa-user-check text-amber-400"></i>
                            <span class="font-semibold"><?= htmlspecialchars($_SESSION['user']['nombre']) ?></span>
                        </div>
                    <?php else: ?>
                        <a href="index.php?tab=configuracion" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold transition">
                            <i class="fa-solid fa-right-to-bracket mr-1"></i> Iniciar Sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Navegación de Módulos -->
            <nav class="flex items-center gap-1 overflow-x-auto py-2 border-t border-white/10">
                <?php foreach ($navItems as $key => $item): ?>
                    <?php $active = ($tab === $key); ?>
                    <a href="index.php?tab=<?= $key ?>"
                       class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center gap-2 <?= $active ? 'bg-amber-500 text-stone-950 shadow' : 'text-stone-300 hover:bg-white/10 hover:text-white' ?>">
                        <i class="fa-solid <?= $item['icon'] ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                        <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold <?= $active ? 'bg-stone-950 text-amber-400' : 'bg-red-500 text-white' ?>">
                                <?= (int)$item['badge'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-6">
        <!-- Mensajes Flash -->
        <?php if ($flash): ?>
            <?php
            $alertStyle = match($flash['type']) {
                'success' => 'bg-emerald-50 border-emerald-300 text-emerald-900',
                'error'   => 'bg-red-50 border-red-300 text-red-900',
                'warning' => 'bg-amber-50 border-amber-300 text-amber-900',
                default   => 'bg-blue-50 border-blue-300 text-blue-900'
            };
            ?>
            <div class="mb-5 p-4 rounded-2xl border <?= $alertStyle ?> flex items-center justify-between shadow-sm text-xs sm:text-sm font-medium">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-info text-base"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-stone-400 hover:text-stone-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php
        $viewFile = __DIR__ . '/views/' . basename($tab) . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            include __DIR__ . '/views/dashboard.php';
        }
        ?>
    </main>

    <!-- Pie de Página -->
    <footer class="bg-white border-t border-stone-200/80 py-4 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between text-xs text-stone-400 gap-2">
            <p>© <?= date('Y') ?> <strong>La Casa del Buen Café</strong> — Aplicación Web en PHP + Supabase (PostgreSQL & REST API).</p>
            <p>Módulos: Catálogo Online · POS Barra · Pedidos · Inventario Kardex · Clientes · Auth</p>
        </div>
    </footer>

</body>
</html>

