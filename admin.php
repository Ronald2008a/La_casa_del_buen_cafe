<?php
/**
 * SITIO WEB #2: PORTAL INTERNO PARA ADMINISTRADOR Y TRABAJADOR (BARISTA / CAJERO)
 * La Casa del Buen Café — ERP, POS, Comandas & Inventario CRUD
 * URL: http://localhost/La_casa_del_buen_cafe/admin.php
 */

require_once __DIR__ . '/includes/actions.php';

$user = $_SESSION['user'] ?? null;

// Si el usuario autenticado es un Cliente, redirigir a su portal especial de cliente
if ($user && ($user['rol'] ?? '') === 'cliente') {
    setFlash('info', 'Bienvenido a tu Portal Especial de Clientes. El portal de personal es exclusivo para colaboradores.');
    header('Location: portal_clientes.php');
    exit;
}

$rolUsuario = $user['rol'] ?? 'admin';
$esAdmin   = ($rolUsuario === 'admin');
$esCajero  = ($rolUsuario === 'cajero');
$esBarista = ($rolUsuario === 'barista');

// Restricción estricta de pestañas según el rol autenticado
if ($esBarista) {
    // El barista únicamente tiene acceso al monitor KDS de Comandas
    $tab = 'pedidos';
} elseif ($esCajero) {
    // El cajero solo puede operar POS, ver pedidos de barra y consultar/registrar clientes
    $tabPermitidas = ['pos', 'pedidos', 'clientes'];
    $tabSolicitada = $_GET['tab'] ?? 'pos';
    $tab = in_array($tabSolicitada, $tabPermitidas, true) ? $tabSolicitada : 'pos';
} elseif ($esAdmin) {
    // El administrador tiene acceso a todos los módulos
    $tab = $_GET['tab'] ?? 'dashboard';
} else {
    $tab = 'dashboard';
}

// Consultas a XAMPP MySQL
$categorias     = $db->select('categorias', ['select' => '*', 'order' => 'id.asc']);
$productos      = $db->select('productos', ['select' => '*', 'order' => 'id.asc']);
$clientes       = $db->select('clientes', ['select' => '*', 'order' => 'id.asc']);
$pedidos        = $db->select('pedidos', ['select' => '*', 'order' => 'id.asc']);
$detallePedidos = $db->select('detalle_pedidos', ['select' => '*', 'order' => 'id.asc']);
$movimientos    = $db->select('movimientos_inventario', ['select' => '*', 'order' => 'id.asc']);

// Cálculo de indicadores KPI
$totalVentas = 0;
$pedidosPendientes = 0;
$ventasOnline = 0;
$ventasPos = 0;
foreach ($pedidos as $p) {
    if (($p['estado'] ?? '') !== 'cancelado') {
        $monto = (float)($p['total'] ?? 0);
        $totalVentas += $monto;
        if (($p['tipo'] ?? 'pos') === 'tienda_online') {
            $ventasOnline += $monto;
        } else {
            $ventasPos += $monto;
        }
    }
    if (in_array($p['estado'] ?? '', ['pendiente', 'preparando'], true)) {
        $pedidosPendientes++;
    }
}

$productosStockBajo = array_values(array_filter($productos, function ($prod) {
    return (int)($prod['stock'] ?? 0) <= (int)($prod['stock_minimo'] ?? 5);
}));

$ipLocalRed = function_exists('obtenerIpLocalRed') ? obtenerIpLocalRed() : '127.0.0.1';
$urlMovilTienda = "http://{$ipLocalRed}/La_casa_del_buen_cafe/";
$urlMovilAdmin  = "http://{$ipLocalRed}/La_casa_del_buen_cafe/admin.php";
$lastOrderIdInicial = !empty($pedidos[0]['id']) ? (int)$pedidos[0]['id'] : 0;

// Menú dinámico según el rol autenticado (Admin ve todo, Cajero ve POS/Comandas/Clientes, Barista ve KDS)
$navItems = [];
if ($esAdmin) {
    $navItems['dashboard'] = ['label' => 'Panel Admin',    'icon' => 'fa-chart-line',     'sub' => 'KPIs & Analítica'];
}
if ($esAdmin || $esCajero) {
    $navItems['pos']        = ['label' => 'Terminal POS',   'icon' => 'fa-cash-register',  'sub' => 'Caja & Facturación'];
}
$navItems['pedidos']    = [
    'label' => ($esBarista ? 'Monitor KDS' : 'Comandas'),
    'icon' => 'fa-bell-concierge',
    'sub' => ($esBarista ? 'Preparación en Barra' : 'Órdenes Web y Barra'),
    'badge' => $pedidosPendientes
];
if ($esAdmin) {
    $navItems['inventario'] = ['label' => 'Inventario CRUD','icon' => 'fa-boxes-stacked',  'sub' => 'Productos & Kardex', 'badge' => count($productosStockBajo)];
}
if ($esAdmin || $esCajero) {
    $navItems['clientes']   = ['label' => 'Clientes',       'icon' => 'fa-users',          'sub' => 'Club & Puntos'];
}
if ($esAdmin) {
    $navItems['configuracion'] = ['label' => 'Base de Datos', 'icon' => 'fa-database',    'sub' => 'XAMPP MySQL / phpMyAdmin'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#120A06">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Portal Admin & Trabajador — La Casa del Buen Café</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="logo.php">
    <link rel="icon" type="image/jpeg" href="logo.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F8F5F0;
        }
        .font-display { font-family: 'Playfair Display', serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 214, 200, 0.75);
        }
        .coffee-gradient { background: linear-gradient(135deg, #120A06 0%, #26150E 55%, #3E2417 100%); }
        .gold-gradient { background: linear-gradient(135deg, #E2AA63 0%, #B87333 100%); }
        .card-hover { transition: all 0.22s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 14px 24px -10px rgba(28, 16, 11, 0.12); }

        /* ========================================================== */
        /* ESTANDARIZACIÓN DE TIPOGRAFÍA:                            */
        /* Títulos: 20px | Botones: 14px | Párrafos: 14px             */
        /* ========================================================== */
        h1, h2, h3, h4, .section-title, .card-title, .modal-title {
            font-size: 20px !important;
            line-height: 1.35 !important;
            font-weight: 700 !important;
        }
        p, .paragraph, .body-text, .text-desc {
            font-size: 14px !important;
            line-height: 1.6 !important;
        }
        button, .btn, a.btn, input[type="submit"], input[type="button"], .button-text {
            font-size: 14px !important;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col pb-16 md:pb-0 text-stone-800">

    <div id="toastContainer" class="fixed bottom-20 md:bottom-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none"></div>

    <?php if (!$user): ?>
        <!-- ================================================================== -->
        <!-- PORTAL MUY PRIVADO PARA PERSONAL (ADMIN, CAJERO, EMPLEADO)        -->
        <!-- ================================================================== -->
        <div class="min-h-screen flex items-center justify-center p-4 coffee-gradient relative overflow-hidden">
            <img src="https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=1600&q=80"
                 alt="" class="absolute inset-0 w-full h-full object-cover opacity-20 pointer-events-none">

            <div class="relative z-10 max-w-xl w-full bg-white rounded-3xl shadow-2xl border-2 border-amber-500/50 p-6 sm:p-8 space-y-6">
                <div class="text-center space-y-2">
                    <img src="logo.php" alt="La Casa del Buen Café"
                         class="w-20 h-20 rounded-full object-cover mx-auto shadow-xl border-2 border-amber-400">
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-red-700 block pt-1">
                        <i class="fa-solid fa-lock mr-1"></i> Área Muy Privada · Solo Personal Autorizado
                    </span>
                    <h1 class="font-display font-bold text-stone-900">Portal Privado de Operaciones</h1>
                    <p class="text-stone-500 text-xs sm:text-sm">Acceso interno y confidencial exclusivo para Administrador, Cajero y Empleado (Barista)</p>
                </div>

                <?php if ($flash): ?>
                    <div class="p-3.5 rounded-2xl <?= ($flash['type'] ?? '') === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-800' ?> text-xs font-semibold">
                        <i class="fa-solid <?= ($flash['type'] ?? '') === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?> mr-1.5"></i>
                        <?= htmlspecialchars($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <!-- Aviso para Clientes: Separación clara -->
                <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-stone-700 text-xs flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-200 text-amber-900 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-award"></i>
                        </div>
                        <span>¿Eres cliente? Tu espacio exclusivo es el <strong>Portal Especial de Clientes</strong>.</span>
                    </div>
                    <a href="portal_clientes.php" class="px-3.5 py-1.5 rounded-xl gold-gradient text-stone-950 font-bold text-xs whitespace-nowrap shadow hover:brightness-105 transition">
                        Portal Clientes →
                    </a>
                </div>

                <!-- 3 Puestos de Trabajo Privados: Admin, Cajero, Empleado/Barista -->
                <div class="space-y-3 pt-1">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-stone-400 text-center">Selecciona tu Puesto de Trabajo</p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                        <!-- 1. Administrador -->
                        <div class="p-4 rounded-2xl border-2 border-amber-500/40 bg-stone-50 hover:bg-white hover:border-amber-500 transition shadow-sm space-y-2.5 flex flex-col justify-between">
                            <div class="space-y-1.5">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-crown"></i>
                                </div>
                                <span class="block font-extrabold text-xs text-stone-900">Administrador</span>
                                <span class="block text-[10px] text-stone-500 leading-tight">Control Total + CRUD + BD</span>
                            </div>
                            <div class="flex items-center gap-1.5 pt-2 border-t border-stone-200/60">
                                <a href="login_admin.php" class="flex-1 py-1.5 rounded-xl coffee-gradient text-white text-[11px] font-bold text-center hover:opacity-95 transition">
                                    Login
                                </a>
                                <form method="POST" action="includes/actions.php" class="inline">
                                    <input type="hidden" name="action" value="login">
                                    <input type="hidden" name="portal_rol" value="admin">
                                    <input type="hidden" name="email" value="admin@casadelbuencafe.com">
                                    <input type="hidden" name="password" value="password">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-900 text-[11px] font-bold transition" title="1-Clic">
                                        <i class="fa-solid fa-bolt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- 2. Cajero -->
                        <div class="p-4 rounded-2xl border-2 border-emerald-500/40 bg-stone-50 hover:bg-white hover:border-emerald-500 transition shadow-sm space-y-2.5 flex flex-col justify-between">
                            <div class="space-y-1.5">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-700 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-cash-register"></i>
                                </div>
                                <span class="block font-extrabold text-xs text-stone-900">Cajero (POS)</span>
                                <span class="block text-[10px] text-stone-500 leading-tight">Caja, Facturación & Cobros</span>
                            </div>
                            <div class="flex items-center gap-1.5 pt-2 border-t border-stone-200/60">
                                <a href="login_cajero.php" class="flex-1 py-1.5 rounded-xl bg-emerald-800 text-white text-[11px] font-bold text-center hover:bg-emerald-700 transition">
                                    Login
                                </a>
                                <form method="POST" action="includes/actions.php" class="inline">
                                    <input type="hidden" name="action" value="login">
                                    <input type="hidden" name="portal_rol" value="cajero">
                                    <input type="hidden" name="email" value="cajero@casadelbuencafe.com">
                                    <input type="hidden" name="password" value="password">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-900 text-[11px] font-bold transition" title="1-Clic">
                                        <i class="fa-solid fa-bolt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- 3. Empleado / Barista -->
                        <div class="p-4 rounded-2xl border-2 border-amber-600/40 bg-stone-50 hover:bg-white hover:border-amber-600 transition shadow-sm space-y-2.5 flex flex-col justify-between">
                            <div class="space-y-1.5">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-900 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-mug-hot"></i>
                                </div>
                                <span class="block font-extrabold text-xs text-stone-900">Empleado / Barista</span>
                                <span class="block text-[10px] text-stone-500 leading-tight">Monitor KDS de Comandas</span>
                            </div>
                            <div class="flex items-center gap-1.5 pt-2 border-t border-stone-200/60">
                                <a href="login_barista.php" class="flex-1 py-1.5 rounded-xl bg-amber-900 text-white text-[11px] font-bold text-center hover:bg-amber-800 transition">
                                    Login
                                </a>
                                <form method="POST" action="includes/actions.php" class="inline">
                                    <input type="hidden" name="action" value="login">
                                    <input type="hidden" name="portal_rol" value="barista">
                                    <input type="hidden" name="email" value="barista@casadelbuencafe.com">
                                    <input type="hidden" name="password" value="password">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-900 text-[11px] font-bold transition" title="1-Clic">
                                        <i class="fa-solid fa-bolt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="text-center pt-3 border-t border-stone-200 flex items-center justify-between text-xs">
                    <a href="index.php" class="font-bold text-amber-800 hover:underline inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-store"></i> Tienda Pública
                    </a>
                    <a href="portal_clientes.php" class="font-bold text-stone-600 hover:text-amber-800 inline-flex items-center gap-1">
                        <i class="fa-solid fa-award text-amber-500"></i> Club de Clientes
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- ================================================================== -->
        <!-- INTERFAZ INTERNA PARA PERSONAL AUTENTICADO                         -->
        <!-- ================================================================== -->
        <header class="coffee-gradient text-white sticky top-0 z-40 shadow-2xl border-b border-amber-500/20">
            <!-- Barra de Estado del Sistema -->
            <div class="bg-black/40 border-b border-white/10 text-[11px] py-1.5 px-4">
                <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-0.5 rounded <?= $esAdmin ? 'bg-amber-400 text-stone-950 font-bold' : ($esCajero ? 'bg-emerald-500 text-white font-bold' : 'bg-amber-700 text-amber-100 font-bold') ?> uppercase text-[11px] flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid <?= $esAdmin ? 'fa-crown' : ($esCajero ? 'fa-cash-register' : 'fa-mug-hot') ?>"></i>
                            <?= $esAdmin ? 'ADMINISTRADOR GENERAL' : ($esCajero ? 'CAJERO DE TURNO (POS)' : 'BARISTA / PREPARACIÓN KDS') ?>
                        </span>
                        <span class="hidden md:inline text-white/20">|</span>
                        <span class="hidden md:inline text-emerald-300 font-semibold">
                            <i class="fa-solid fa-database mr-1"></i> <?= htmlspecialchars($db->getActiveEngineName()) ?>
                        </span>
                        <span class="hidden lg:inline text-white/20">|</span>
                        <span class="hidden lg:inline text-amber-200/80 font-mono">
                            <i class="fa-solid fa-network-wired mr-1"></i> IP Red: <?= htmlspecialchars($ipLocalRed) ?>
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="document.getElementById('deviceModal').classList.remove('hidden')"
                                class="px-2.5 py-0.5 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 hover:bg-amber-500 hover:text-stone-950 font-bold transition flex items-center gap-1.5 text-[11px]">
                            <i class="fa-solid fa-mobile-screen-button"></i> Conectar Celulares / QR
                        </button>
                        <span class="text-white/20">|</span>
                        <a href="login.php" class="text-stone-300 hover:text-white transition flex items-center gap-1 text-[11px]">
                            <i class="fa-solid fa-users-gear text-amber-400"></i> Cambiar Rol
                        </a>
                        <span class="text-white/20">|</span>
                        <a href="index.php" target="_blank" class="text-amber-300 hover:text-white font-bold flex items-center gap-1 text-[11px]">
                            <i class="fa-solid fa-up-right-from-square"></i> Tienda Clientes
                        </a>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between py-3.5">
                    <a href="admin.php" class="flex items-center gap-3.5 group">
                        <img src="logo.php" alt="La Casa del Buen Café"
                             class="w-13 h-13 w-12 h-12 rounded-full object-cover shadow-lg border-2 border-amber-300/70 group-hover:scale-105 transition">
                        <div>
                            <span class="font-display font-bold text-lg sm:text-xl tracking-wide block leading-none text-white">
                                Panel Interno — La Casa del Buen Café
                            </span>
                            <span class="text-[11px] text-amber-400 font-semibold block mt-1">
                                <?= $esAdmin ? 'Administración, Inventario CRUD, POS & Base de Datos' : ($esCajero ? 'Terminal POS, Caja, Facturación & Clientes' : 'Monitor KDS de Preparación en Cocina y Barra') ?>
                            </span>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex items-center gap-2.5 bg-white/10 px-3.5 py-1.5 rounded-xl text-xs">
                            <i class="fa-solid fa-user-check text-amber-400"></i>
                            <div>
                                <p class="font-bold text-white leading-none"><?= htmlspecialchars($user['nombre']) ?></p>
                                <span class="text-[10px] text-amber-300 uppercase"><?= htmlspecialchars($rolUsuario) ?></span>
                            </div>
                        </div>
                        <form method="POST" action="admin.php">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="px-3.5 py-2 rounded-xl bg-red-500/20 hover:bg-red-500/30 border border-red-400/30 text-red-200 text-xs font-bold transition">
                                <i class="fa-solid fa-power-off sm:mr-1"></i> <span class="hidden sm:inline">Salir</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Pestañas del Portal Interno -->
                <nav class="flex items-center gap-1.5 overflow-x-auto py-2.5 border-t border-white/10">
                    <?php foreach ($navItems as $key => $item): ?>
                        <?php $active = ($tab === $key); ?>
                        <a href="admin.php?tab=<?= $key ?>"
                           class="px-4 py-2.5 rounded-xl text-xs transition whitespace-nowrap flex items-center gap-2.5 <?= $active ? 'gold-gradient text-stone-950 font-extrabold shadow-lg' : 'text-stone-300 hover:bg-white/10 hover:text-white font-semibold' ?>">
                            <i class="fa-solid <?= $item['icon'] ?> text-sm <?= $active ? 'text-stone-950' : 'text-amber-400' ?>"></i>
                            <div class="leading-tight">
                                <span class="block"><?= htmlspecialchars($item['label']) ?></span>
                                <span class="hidden lg:block text-[10px] <?= $active ? 'text-stone-900/75 font-semibold' : 'text-stone-400 font-normal' ?>">
                                    <?= htmlspecialchars($item['sub']) ?>
                                </span>
                            </div>
                            <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                                <span class="comandas-badge-count px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $active ? 'bg-stone-950 text-amber-400' : 'bg-red-500 text-white animate-pulse' ?>">
                                    <?= (int)$item['badge'] ?>
                                </span>
                            <?php else: ?>
                                <span class="comandas-badge-count hidden px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-500 text-white animate-pulse">
                                    0
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </header>

        <!-- Contenido Principal del Portal Admin/Trabajador -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-7">
            <?php if ($flash): ?>
                <div class="mb-6 p-4 rounded-2xl bg-stone-900 text-white border-l-4 border-amber-400 flex items-center justify-between shadow-lg text-xs sm:text-sm font-semibold">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-amber-400 text-lg"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-white/60 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php
            $viewFile = __DIR__ . '/views/' . basename($tab) . '.php';
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                include __DIR__ . '/views/pos.php';
            }
            ?>
        </main>

        <!-- Barra de Navegación Inferior Móvil según el Rol -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-[#120A06]/95 backdrop-blur-md text-stone-300 border-t border-amber-500/30 z-30 px-2 py-1.5 flex items-center justify-around shadow-2xl">
            <?php if ($esAdmin): ?>
                <a href="admin.php?tab=dashboard" class="flex flex-col items-center py-1 px-2 text-[11px] font-bold <?= $tab === 'dashboard' ? 'text-amber-400' : 'hover:text-amber-300' ?>">
                    <i class="fa-solid fa-chart-line text-sm mb-0.5"></i>
                    <span>Dashboard</span>
                </a>
            <?php endif; ?>
            <?php if ($esAdmin || $esCajero): ?>
                <a href="admin.php?tab=pos" class="flex flex-col items-center py-1 px-2 text-[11px] font-bold <?= $tab === 'pos' ? 'text-amber-400' : 'hover:text-amber-300' ?>">
                    <i class="fa-solid fa-cash-register text-sm mb-0.5"></i>
                    <span>POS</span>
                </a>
            <?php endif; ?>
            <a href="admin.php?tab=pedidos" class="relative flex flex-col items-center py-1 px-2 text-[11px] font-bold <?= $tab === 'pedidos' ? 'text-amber-400' : 'hover:text-amber-300' ?>">
                <i class="fa-solid fa-bell-concierge text-sm mb-0.5"></i>
                <span><?= $esBarista ? 'Monitor KDS' : 'Comandas' ?></span>
                <span class="comandas-badge-count absolute -top-0.5 right-1 px-1.5 py-0.2 rounded-full bg-red-500 text-white text-[10px] font-mono font-black <?= $pedidosPendientes > 0 ? '' : 'hidden' ?>">
                    <?= $pedidosPendientes ?>
                </span>
            </a>
            <?php if ($esAdmin): ?>
                <a href="admin.php?tab=inventario" class="flex flex-col items-center py-1 px-2 text-[11px] font-bold <?= $tab === 'inventario' ? 'text-amber-400' : 'hover:text-amber-300' ?>">
                    <i class="fa-solid fa-boxes-stacked text-sm mb-0.5"></i>
                    <span>Bodega</span>
                </a>
            <?php endif; ?>
            <button type="button" onclick="document.getElementById('deviceModal').classList.remove('hidden')" class="flex flex-col items-center py-1 px-2 text-[11px] font-bold text-amber-300">
                <i class="fa-solid fa-qrcode text-sm mb-0.5"></i>
                <span>QR / Móvil</span>
            </button>
        </nav>

        <!-- Modal para Conectar Celulares / Tablets / QR desde el Panel Admin -->
        <div id="deviceModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-amber-500/40 text-xs space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-stone-200">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl gold-gradient text-stone-950 flex items-center justify-center font-black text-base">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-stone-900 text-lg leading-tight">Acceso desde Cualquier Dispositivo</h3>
                            <p class="text-[11px] text-stone-500">Conecta celulares, tablets o monitores adicionales a tu servidor XAMPP</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('deviceModal').classList.add('hidden')" class="text-stone-400 hover:text-stone-700">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-center">
                    <!-- QR Tienda de Clientes -->
                    <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200 space-y-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-extrabold text-[10px] uppercase">Para Clientes / Compradores</span>
                        <h4 class="font-bold text-stone-900 text-sm">Tienda en Línea</h4>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($urlMovilTienda) ?>"
                             alt="QR Tienda" class="w-36 h-36 mx-auto rounded-xl border border-stone-200 bg-white p-1.5 shadow-sm">
                        <div class="flex items-center gap-1">
                            <input type="text" readonly value="<?= htmlspecialchars($urlMovilTienda) ?>"
                                   id="urlTiendaInput"
                                   class="w-full px-2 py-1 rounded-lg border border-stone-200 bg-white font-mono text-[11px] text-center text-stone-700">
                            <button type="button" onclick="copiarAlPortapapeles('urlTiendaInput')" title="Copiar enlace"
                                    class="px-2 py-1 rounded-lg bg-stone-200 hover:bg-stone-300 text-stone-700 font-bold">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- QR Portal Admin / Trabajador -->
                    <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200 space-y-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-stone-800 text-amber-300 font-extrabold text-[10px] uppercase">Para Personal / Barista</span>
                        <h4 class="font-bold text-stone-900 text-sm">Terminal POS / Comandas</h4>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($urlMovilAdmin) ?>"
                             alt="QR Admin" class="w-36 h-36 mx-auto rounded-xl border border-stone-200 bg-white p-1.5 shadow-sm">
                        <div class="flex items-center gap-1">
                            <input type="text" readonly value="<?= htmlspecialchars($urlMovilAdmin) ?>"
                                   id="urlAdminInput"
                                   class="w-full px-2 py-1 rounded-lg border border-stone-200 bg-white font-mono text-[11px] text-center text-stone-700">
                            <button type="button" onclick="copiarAlPortapapeles('urlAdminInput')" title="Copiar enlace"
                                    class="px-2 py-1 rounded-lg bg-stone-200 hover:bg-stone-300 text-stone-700 font-bold">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-[11px] text-amber-950 space-y-1.5 leading-relaxed">
                    <p class="font-bold flex items-center gap-1.5 text-xs text-amber-900">
                        <i class="fa-solid fa-wifi text-amber-600"></i> ¿Cómo abrirlo desde cualquier celular o tablet?
                    </p>
                    <ol class="list-decimal pl-4 space-y-1 text-stone-700">
                        <li>Conecta el celular a la <strong>misma red Wi-Fi</strong> que este computador.</li>
                        <li>Abre la cámara del celular y <strong>escanea el código QR</strong> (o escribe la dirección en Chrome/Safari).</li>
                        <li><strong>Monitoreo en vivo:</strong> Cada venta o pedido realizado desde el celular se registrará de inmediato en XAMPP MySQL y sonará una alerta en este PC.</li>
                    </ol>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="button" onclick="document.getElementById('deviceModal').classList.add('hidden')"
                            class="px-5 py-2.5 rounded-xl coffee-gradient text-amber-300 font-bold text-xs">
                        Entendido / Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Barra de Pie de Página con Redes Sociales Centradas -->
        <footer class="bg-[#232221] text-white mt-auto border-t border-white/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-8 py-6 grid grid-cols-1 sm:grid-cols-3 items-center gap-4">
                <div class="text-center sm:text-left text-xs text-stone-300">
                    <p class="font-bold text-white flex items-center justify-center sm:justify-start gap-1.5">
                        <i class="fa-solid fa-location-dot text-amber-400"></i> La Casa del Buen Café
                    </p>
                    <p class="text-[11px] text-stone-400">Calle 26 # 16-24 C.C. San Juan Plaza Local 151 Primer Piso · Neiva-Huila</p>
                </div>
                <div class="flex items-center justify-center gap-6">
                    <a href="https://www.facebook.com/sanjuanplaza" target="_blank" rel="noopener noreferrer"
                       title="Síguenos en Facebook"
                       class="w-9 h-9 rounded-full bg-white flex items-center justify-center hover:scale-110 transition shadow">
                        <i class="fa-brands fa-facebook-f text-[#1877F2] text-lg translate-y-[1px]"></i>
                    </a>
                    <a href="https://www.instagram.com/lacasadel_buencafe/" target="_blank" rel="noopener noreferrer"
                       title="Síguenos en Instagram"
                       class="w-9 h-9 rounded-lg border-2 border-white flex items-center justify-center text-white hover:scale-110 hover:bg-white/10 transition">
                        <i class="fa-brands fa-instagram text-lg"></i>
                    </a>
                </div>
                <div class="text-sm font-medium tracking-wide text-white/95 text-center sm:text-right">
                    © <?= date('Y') ?> - La Casa del Buen Café<sup>®</sup>
                </div>
            </div>
        </footer>
    <?php endif; ?>

    <script>
        // Redirigir automáticamente cualquier enlace o formulario interno hacia admin.php
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('main a[href^="index.php?tab="]').forEach(link => {
                const href = link.getAttribute('href');
                if (href.includes('tab=tienda')) {
                    link.setAttribute('href', 'index.php');
                    link.setAttribute('target', '_blank');
                } else {
                    link.setAttribute('href', href.replace('index.php?tab=', 'admin.php?tab='));
                }
            });
            document.querySelectorAll('main form[action="index.php"]').forEach(form => {
                form.setAttribute('action', 'admin.php');
            });
        });

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto px-4 py-3 rounded-2xl border-l-4 border-amber-400 bg-stone-900 text-white shadow-2xl flex items-center gap-3 text-xs font-semibold';
            toast.innerHTML = `<i class="fa-solid fa-mug-hot text-amber-400"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        function copiarAlPortapapeles(inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.select();
                navigator.clipboard.writeText(input.value);
                showToast('¡Enlace copiado al portapapeles!');
            }
        }

        // ====================================================================
        // MONITOREO EN VIVO DESDE XAMPP CON ALERTAS AUDIBLES Y POLLING
        // ====================================================================
        let lastOrderId = <?= (int)$lastOrderIdInicial ?>;

        function playOrderAlertSound() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const now = audioCtx.currentTime;

                const osc1 = audioCtx.createOscillator();
                const gain1 = audioCtx.createGain();
                osc1.type = 'triangle';
                osc1.frequency.setValueAtTime(523.25, now); // C5
                osc1.frequency.exponentialRampToValueAtTime(783.99, now + 0.15); // G5
                gain1.gain.setValueAtTime(0.4, now);
                gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.45);
                osc1.connect(gain1);
                gain1.connect(audioCtx.destination);
                osc1.start(now);
                osc1.stop(now + 0.45);

                const osc2 = audioCtx.createOscillator();
                const gain2 = audioCtx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(1046.50, now + 0.2); // C6
                gain2.gain.setValueAtTime(0.4, now + 0.2);
                gain2.gain.exponentialRampToValueAtTime(0.01, now + 0.8);
                osc2.connect(gain2);
                gain2.connect(audioCtx.destination);
                osc2.start(now + 0.2);
                osc2.stop(now + 0.8);
            } catch(e) {
                // Silencioso si el navegador no permite audio antes de interacción
            }
        }

        async function monitorLiveXampp() {
            try {
                const res = await fetch('api_live_status.php?_t=' + Date.now());
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.ok && data.ultimo_pedido) {
                    if (lastOrderId > 0 && data.ultimo_pedido.id > lastOrderId) {
                        playOrderAlertSound();
                        showToast(`🔔 ¡NUEVA ORDEN DESDE CELULAR! Folio: ${data.ultimo_pedido.folio} ($${Number(data.ultimo_pedido.total).toLocaleString('es-CO')}) - ${data.ultimo_pedido.cliente_nombre}`);

                        const currentUrl = new URL(window.location.href);
                        const tabActual = currentUrl.searchParams.get('tab') || 'dashboard';
                        if (tabActual === 'pedidos' || tabActual === 'dashboard') {
                            setTimeout(() => {
                                window.location.reload();
                            }, 2200);
                        }
                    }
                    lastOrderId = data.ultimo_pedido.id;

                    // Actualizar badges en vivo
                    document.querySelectorAll('.comandas-badge-count').forEach(badge => {
                        badge.textContent = data.pedidos_pendientes;
                        if (data.pedidos_pendientes > 0) {
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    });
                }
            } catch(err) {
                // Silencioso ante pausas de Apache
            }
        }

        // Ejecutar sondeo cada 5 segundos para monitoreo en tiempo real
        setInterval(monitorLiveXampp, 5000);
    </script>
</body>
</html>

