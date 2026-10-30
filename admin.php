<?php
/**
 * SITIO WEB #2: PORTAL INTERNO PARA ADMINISTRADOR Y TRABAJADOR (BARISTA / CAJERO)
 * La Casa del Buen Café — ERP, POS, Comandas & Inventario CRUD
 * URL: http://localhost/La_casa_del_buen_cafe/admin.php
 */

require_once __DIR__ . '/includes/actions.php';

// Cambio rápido de perfil (para pruebas ágiles entre Admin y Trabajador)
if (isset($_GET['switch_role'])) {
    $r = ($_GET['switch_role'] === 'cajero') ? 'cajero' : 'admin';
    $_SESSION['user'] = [
        'email'  => ($r === 'admin') ? 'admin@casadelbuencafe.com' : 'caja@casadelbuencafe.com',
        'nombre' => ($r === 'admin') ? 'Administrador Principal' : 'Trabajador / Barista Turno',
        'rol'    => $r,
        'modo'   => 'XAMPP MySQL'
    ];
    header('Location: admin.php?tab=' . ($r === 'admin' ? 'dashboard' : 'pos'));
    exit;
}

$user = $_SESSION['user'] ?? null;
$rolUsuario = $user['rol'] ?? 'admin';
$esAdmin = ($rolUsuario === 'admin');

$tab = $_GET['tab'] ?? ($esAdmin ? 'dashboard' : 'pos');

// Si un trabajador intenta entrar a pestañas exclusivas de admin, redirigir a POS
if (!$esAdmin && in_array($tab, ['dashboard', 'inventario', 'configuracion'], true)) {
    $tab = 'pos';
}

// Consultas a XAMPP MySQL / Supabase
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

// Menú según el rol (Administrador ve todo; Trabajador ve operación de barra y clientes)
$navItems = [];
if ($esAdmin) {
    $navItems['dashboard'] = ['label' => 'Panel Admin',    'icon' => 'fa-chart-line',     'sub' => 'KPIs & Analítica'];
}
$navItems['pos']        = ['label' => 'Terminal POS',   'icon' => 'fa-cash-register',  'sub' => 'Caja & Facturación'];
$navItems['pedidos']    = ['label' => 'Comandas',       'icon' => 'fa-bell-concierge', 'sub' => 'Órdenes Web y Barra', 'badge' => $pedidosPendientes];
if ($esAdmin) {
    $navItems['inventario'] = ['label' => 'Inventario CRUD','icon' => 'fa-boxes-stacked',  'sub' => 'Productos & Kardex', 'badge' => count($productosStockBajo)];
}
$navItems['clientes']   = ['label' => 'Clientes',       'icon' => 'fa-users',          'sub' => 'Club & Puntos'];
if ($esAdmin) {
    $navItems['configuracion'] = ['label' => 'Base de Datos', 'icon' => 'fa-database',    'sub' => 'MySQL & Supabase'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Admin & Trabajador — La Casa del Buen Café</title>
    <link rel="icon" type="image/jpeg" href="logo.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F4EFE8;
        }
        .font-display { font-family: 'Playfair Display', serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass-card {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(214, 199, 182, 0.65);
        }
        .coffee-gradient { background: linear-gradient(135deg, #120A06 0%, #26150E 55%, #3E2417 100%); }
        .gold-gradient { background: linear-gradient(135deg, #E2AA63 0%, #B87333 100%); }
        .card-hover { transition: all 0.25s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 18px 28px -10px rgba(28, 16, 11, 0.12); }
    </style>
</head>
<body class="min-h-screen flex flex-col text-stone-800">

    <div id="toastContainer" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none"></div>

    <?php if (!$user): ?>
        <!-- ================================================================== -->
        <!-- PANTALLA DE LOGIN PARA ADMINISTRADOR Y TRABAJADOR                  -->
        <!-- ================================================================== -->
        <div class="min-h-screen flex items-center justify-center p-4 coffee-gradient relative overflow-hidden">
            <img src="https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=1600&q=80"
                 alt="" class="absolute inset-0 w-full h-full object-cover opacity-20">

            <div class="relative z-10 max-w-md w-full bg-white rounded-3xl shadow-2xl border border-amber-500/40 p-7 sm:p-8 space-y-6">
                <div class="text-center space-y-2">
                    <img src="logo.php" alt="La Casa del Buen Café"
                         class="w-24 h-24 rounded-full object-cover mx-auto shadow-xl border-2 border-[#E8B78D]">
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-amber-700 block pt-1">Intranet Operativa</span>
                    <h1 class="font-display text-2xl font-bold text-stone-900">Portal Admin & Trabajadores</h1>
                    <p class="text-xs text-stone-500">Selecciona tu perfil operativo o ingresa tus credenciales de personal</p>
                </div>

                <?php if ($flash): ?>
                    <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-semibold">
                        <?= htmlspecialchars($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <!-- Botones de Acceso Rápido 1-Clic por Rol -->
                <div class="space-y-2.5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-stone-400 text-center">Acceso Rápido por Rol</p>
                    <div class="grid grid-cols-2 gap-3">
                        <form method="POST" action="admin.php">
                            <input type="hidden" name="action" value="login">
                            <input type="hidden" name="email" value="admin@casadelbuencafe.com">
                            <input type="hidden" name="password" value="password">
                            <input type="hidden" name="quick_role" value="admin">
                            <button type="submit" class="w-full p-3.5 rounded-2xl coffee-gradient text-white hover:opacity-95 transition text-left space-y-1 shadow-md">
                                <i class="fa-solid fa-crown text-amber-400 text-base"></i>
                                <span class="block font-extrabold text-xs">Administrador</span>
                                <span class="block text-[10px] text-stone-300">Control Total + CRUD</span>
                            </button>
                        </form>

                        <form method="POST" action="admin.php">
                            <input type="hidden" name="action" value="login">
                            <input type="hidden" name="email" value="caja@casadelbuencafe.com">
                            <input type="hidden" name="password" value="password">
                            <input type="hidden" name="quick_role" value="cajero">
                            <button type="submit" class="w-full p-3.5 rounded-2xl bg-amber-100 hover:bg-amber-200 text-stone-900 border border-amber-300 transition text-left space-y-1 shadow-sm">
                                <i class="fa-solid fa-cash-register text-amber-800 text-base"></i>
                                <span class="block font-extrabold text-xs">Trabajador / Barista</span>
                                <span class="block text-[10px] text-stone-600">Caja POS + Comandas</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Formulario Clásico -->
                <form method="POST" action="admin.php" class="space-y-3 pt-4 border-t border-stone-200 text-xs">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Correo Corporativo</label>
                        <input type="email" name="email" required value="admin@casadelbuencafe.com"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-stone-200">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Contraseña</label>
                        <input type="password" name="password" required value="password"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-stone-200">
                    </div>
                    <button type="submit" class="w-full py-3 rounded-xl gold-gradient text-stone-950 font-extrabold text-sm shadow">
                        Iniciar Sesión
                    </button>
                </form>

                <div class="text-center pt-2">
                    <a href="index.php" class="text-xs font-bold text-amber-800 hover:underline flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-store"></i> Volver a la Tienda Pública de Clientes
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- ================================================================== -->
        <!-- INTERFAZ INTERNA PARA ADMINISTRADOR / TRABAJADOR                   -->
        <!-- ================================================================== -->
        <header class="coffee-gradient text-white sticky top-0 z-40 shadow-2xl border-b border-amber-500/20">
            <!-- Barra de Estado del Sistema -->
            <div class="bg-black/40 border-b border-white/10 text-[11px] py-1.5 px-4">
                <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-bold uppercase">
                            <i class="fa-solid fa-id-badge mr-1"></i> Modo: <?= $esAdmin ? 'ADMINISTRADOR GENERAL' : 'TRABAJADOR / CAJERO BARISTA' ?>
                        </span>
                        <span class="hidden md:inline text-white/20">|</span>
                        <span class="hidden md:inline text-emerald-300 font-semibold">
                            <i class="fa-solid fa-database mr-1"></i> <?= htmlspecialchars($db->getActiveEngineName()) ?>
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Selector rápido para alternar entre vista Admin y vista Trabajador -->
                        <span class="text-stone-400">Cambiar vista:</span>
                        <a href="admin.php?switch_role=admin" class="px-2 py-0.5 rounded <?= $esAdmin ? 'bg-amber-400 text-stone-950 font-bold' : 'text-stone-300 hover:text-white' ?>">
                            Admin
                        </a>
                        <a href="admin.php?switch_role=cajero" class="px-2 py-0.5 rounded <?= !$esAdmin ? 'bg-amber-400 text-stone-950 font-bold' : 'text-stone-300 hover:text-white' ?>">
                            Trabajador
                        </a>
                        <span class="text-white/20">|</span>
                        <a href="index.php" target="_blank" class="text-amber-300 hover:text-white font-bold flex items-center gap-1">
                            <i class="fa-solid fa-up-right-from-square"></i> Ver Tienda de Clientes
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
                                <?= $esAdmin ? 'Administración, Inventario CRUD, POS & Base de Datos' : 'Operación de Barra POS, Comandas & Clientes' ?>
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
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $active ? 'bg-stone-950 text-amber-400' : 'bg-red-500 text-white' ?>">
                                    <?= (int)$item['badge'] ?>
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
            setTimeout(() => toast.remove(), 2500);
        }
    </script>
</body>
</html>

