<?php
/**
 * SELECTOR CENTRAL DE LOGIN POR ROL
 * La Casa del Buen Café
 */
require_once __DIR__ . '/includes/actions.php';

// Si ya hay sesión activa, redirigir al portal que corresponda a su rol
if (!empty($_SESSION['user'])) {
    $r = $_SESSION['user']['rol'] ?? '';
    if ($r === 'cliente') {
        header('Location: cliente.php');
        exit;
    }
    $tab = match($r) {
        'admin'   => 'dashboard',
        'cajero'  => 'pos',
        'barista' => 'pedidos',
        default   => 'pos'
    };
    header('Location: admin.php?tab=' . $tab);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Acceso por Roles — La Casa del Buen Café</title>
    <link rel="icon" type="image/jpeg" href="logo.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #120A06; }
        .font-display { font-family: 'Playfair Display', serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .coffee-gradient { background: linear-gradient(135deg, #120A06 0%, #26150E 55%, #3E2417 100%); }
        .gold-gradient { background: linear-gradient(135deg, #E2AA63 0%, #B87333 100%); }
        .card-hover { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.4); }

        /* ESTÁNDAR TIPOGRÁFICO: Títulos 20px | Botones 14px | Párrafos 14px */
        h1, h2, h3, h4, .section-title, .card-title {
            font-size: 20px !important;
            line-height: 1.35 !important;
            font-weight: 700 !important;
        }
        p, .paragraph, .body-text, .text-desc {
            font-size: 14px !important;
            line-height: 1.6 !important;
        }
        button, .btn, a.btn, input[type="submit"], input[type="button"] {
            font-size: 14px !important;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between p-4 sm:p-8 coffee-gradient text-stone-100">

    <!-- Encabezado -->
    <header class="max-w-6xl w-full mx-auto flex items-center justify-between pb-6 border-b border-amber-500/20">
        <a href="index.php" class="flex items-center gap-3.5 group">
            <img src="logo.php" alt="La Casa del Buen Café"
                 class="w-12 h-12 rounded-full object-cover shadow-lg border-2 border-amber-400 group-hover:scale-105 transition">
            <div>
                <span class="font-display font-bold text-lg text-white block leading-none">
                    La Casa del Buen Café
                </span>
                <span class="text-[11px] uppercase tracking-widest text-amber-400 font-semibold block mt-1">
                    Portales de Acceso por Rol
                </span>
            </div>
        </a>

        <a href="index.php" class="px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-amber-300 hover:text-white transition text-xs font-bold flex items-center gap-2">
            <i class="fa-solid fa-store"></i> <span class="hidden sm:inline">Tienda Pública</span>
        </a>
    </header>

    <!-- Contenido Central -->
    <main class="max-w-6xl w-full mx-auto py-8 sm:py-12 space-y-8">
        <div class="text-center max-w-2xl mx-auto space-y-3">
            <span class="px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 text-xs font-extrabold uppercase tracking-widest inline-block">
                <i class="fa-solid fa-users-gear mr-1"></i> Autenticación Independiente
            </span>
            <h1 class="font-display font-bold text-white">Selecciona tu Rol de Acceso</h1>
            <p class="text-stone-300">Cada rol cuenta con su propio portal de login, permisos y herramientas exclusivas según tus funciones.</p>
        </div>

        <?php if ($flash): ?>
            <div class="max-w-md mx-auto p-4 rounded-2xl <?= ($flash['type'] ?? '') === 'error' ? 'bg-red-950/80 border border-red-500/60 text-red-200' : 'bg-emerald-950/80 border border-emerald-500/60 text-emerald-200' ?> text-xs font-semibold shadow-lg">
                <i class="fa-solid <?= ($flash['type'] ?? '') === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?> mr-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Grid de 4 Roles Independientes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- 1. ROL ADMINISTRADOR -->
            <div class="bg-white rounded-3xl p-6 text-stone-800 shadow-2xl border-2 border-amber-500/40 flex flex-col justify-between card-hover">
                <div class="space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-2xl shadow-inner">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-700 block">Nivel Ejecutivo</span>
                        <h2 class="card-title font-display text-stone-900 mt-0.5">Administrador</h2>
                    </div>
                    <p class="text-stone-600 text-xs leading-relaxed">
                        Control total del negocio, reportes financieros, inventario CRUD, base de datos y auditoría de ventas.
                    </p>
                    <ul class="text-xs text-stone-500 space-y-1.5 pt-2 border-t border-stone-100">
                        <li><i class="fa-solid fa-check text-amber-600 mr-1.5"></i> Panel KPIs & Gráficas</li>
                        <li><i class="fa-solid fa-check text-amber-600 mr-1.5"></i> Gestión de Inventario</li>
                        <li><i class="fa-solid fa-check text-amber-600 mr-1.5"></i> Base de Datos MySQL</li>
                    </ul>
                </div>
                <div class="pt-6">
                    <a href="login_admin.php" class="w-full py-3 rounded-2xl coffee-gradient text-white font-extrabold shadow flex items-center justify-center gap-2 hover:opacity-95 transition">
                        <i class="fa-solid fa-right-to-bracket text-amber-400"></i> Entrar como Admin
                    </a>
                </div>
            </div>

            <!-- 2. ROL CAJERO -->
            <div class="bg-white rounded-3xl p-6 text-stone-800 shadow-2xl border-2 border-emerald-500/40 flex flex-col justify-between card-hover">
                <div class="space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl shadow-inner">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-emerald-700 block">Caja & POS</span>
                        <h2 class="card-title font-display text-stone-900 mt-0.5">Cajero / Turno</h2>
                    </div>
                    <p class="text-stone-600 text-xs leading-relaxed">
                        Punto de venta directo, cobros en efectivo/transferencia, facturación rápida y registro de clientes.
                    </p>
                    <ul class="text-xs text-stone-500 space-y-1.5 pt-2 border-t border-stone-100">
                        <li><i class="fa-solid fa-check text-emerald-600 mr-1.5"></i> Terminal de Venta POS</li>
                        <li><i class="fa-solid fa-check text-emerald-600 mr-1.5"></i> Emisión de Facturas</li>
                        <li><i class="fa-solid fa-check text-emerald-600 mr-1.5"></i> Registro de Clientes</li>
                    </ul>
                </div>
                <div class="pt-6">
                    <a href="login_cajero.php" class="w-full py-3 rounded-2xl bg-emerald-800 text-white font-extrabold shadow flex items-center justify-center gap-2 hover:bg-emerald-700 transition">
                        <i class="fa-solid fa-right-to-bracket text-emerald-300"></i> Entrar como Cajero
                    </a>
                </div>
            </div>

            <!-- 3. ROL BARISTA -->
            <div class="bg-white rounded-3xl p-6 text-stone-800 shadow-2xl border-2 border-amber-600/40 flex flex-col justify-between card-hover">
                <div class="space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-900 flex items-center justify-center text-2xl shadow-inner">
                        <i class="fa-solid fa-mug-hot"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-800 block">Barra & Cocina</span>
                        <h2 class="card-title font-display text-stone-900 mt-0.5">Barista / KDS</h2>
                    </div>
                    <p class="text-stone-600 text-xs leading-relaxed">
                        Monitor de comandas en tiempo real, preparación de pedidos de café, bebidas y control de estados.
                    </p>
                    <ul class="text-xs text-stone-500 space-y-1.5 pt-2 border-t border-stone-100">
                        <li><i class="fa-solid fa-check text-amber-700 mr-1.5"></i> Monitor KDS en Vivo</li>
                        <li><i class="fa-solid fa-check text-amber-700 mr-1.5"></i> Órdenes Barra y Web</li>
                        <li><i class="fa-solid fa-check text-amber-700 mr-1.5"></i> Despacho de Pedidos</li>
                    </ul>
                </div>
                <div class="pt-6">
                    <a href="login_barista.php" class="w-full py-3 rounded-2xl bg-amber-900 text-white font-extrabold shadow flex items-center justify-center gap-2 hover:bg-amber-800 transition">
                        <i class="fa-solid fa-right-to-bracket text-amber-300"></i> Entrar como Barista
                    </a>
                </div>
            </div>

            <!-- 4. ROL CLIENTE -->
            <div class="bg-white rounded-3xl p-6 text-stone-800 shadow-2xl border-2 border-amber-300/80 flex flex-col justify-between card-hover">
                <div class="space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-2xl shadow-inner">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-700 block">Fidelización</span>
                        <h2 class="card-title font-display text-stone-900 mt-0.5">Club de Clientes</h2>
                    </div>
                    <p class="text-stone-600 text-xs leading-relaxed">
                        Consulta de puntos acumulados, historial de pedidos, promociones exclusivas y compras directas.
                    </p>
                    <ul class="text-xs text-stone-500 space-y-1.5 pt-2 border-t border-stone-100">
                        <li><i class="fa-solid fa-check text-amber-600 mr-1.5"></i> Saldo de Puntos Club</li>
                        <li><i class="fa-solid fa-check text-amber-600 mr-1.5"></i> Rastreo de Pedidos</li>
                        <li><i class="fa-solid fa-check text-amber-600 mr-1.5"></i> Redención de Premios</li>
                    </ul>
                </div>
                <div class="pt-6">
                    <a href="login_cliente.php" class="w-full py-3 rounded-2xl gold-gradient text-stone-950 font-extrabold shadow flex items-center justify-center gap-2 hover:brightness-105 transition">
                        <i class="fa-solid fa-right-to-bracket text-stone-950"></i> Entrar como Cliente
                    </a>
                </div>
            </div>

        </div>
    </main>

    <!-- Pie de Página -->
    <footer class="max-w-6xl w-full mx-auto pt-6 border-t border-white/10 text-center text-stone-400 text-xs space-y-1">
        <p><strong class="text-stone-200">La Casa del Buen Café</strong> — Neiva, Huila, Colombia</p>
        <p class="text-[11px] text-stone-500">Calle 26 # 16-24 Centro Comercial San Juan Plaza Local 151 Primer Piso · Lunes a Domingo: 9AM - 8PM</p>
    </footer>

</body>
</html>
