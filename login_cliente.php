<?php
/**
 * PORTAL DE LOGIN: CLIENTE / CLUB DEL BUEN CAFÉ
 * Acceso al estado de fidelización, puntos y pedidos del cliente
 */
require_once __DIR__ . '/includes/actions.php';

// Si ya tiene sesión activa como cliente, llevarlo directo a su portal
if (!empty($_SESSION['user']) && ($_SESSION['user']['rol'] ?? '') === 'cliente') {
    header('Location: cliente.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Club del Buen Café — Acceso Clientes</title>
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
<body class="min-h-screen flex items-center justify-center p-4 coffee-gradient relative overflow-hidden text-stone-800">
    <img src="https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=1600&q=80"
         alt="" class="absolute inset-0 w-full h-full object-cover opacity-20 pointer-events-none">

    <div class="relative z-10 max-w-md w-full bg-white rounded-3xl shadow-2xl border-2 border-amber-400/60 p-7 sm:p-8 space-y-6">
        <div class="text-center space-y-2">
            <div class="relative inline-block">
                <img src="logo.php" alt="La Casa del Buen Café"
                     class="w-24 h-24 rounded-full object-cover mx-auto shadow-xl border-2 border-amber-400">
                <span class="absolute bottom-0 right-0 bg-amber-500 text-stone-950 p-1.5 rounded-full text-xs shadow" title="Club del Buen Café">
                    <i class="fa-solid fa-award"></i>
                </span>
            </div>
            <span class="text-[11px] font-extrabold uppercase tracking-widest text-amber-700 block pt-1">
                <i class="fa-solid fa-star mr-1"></i> Fidelización & Beneficios
            </span>
            <h1 class="font-display font-bold text-stone-900">Club del Buen Café</h1>
            <p class="text-stone-500">Consulta tus puntos acumulados, redime cafés gratis y revisa el estado de tus órdenes</p>
        </div>

        <?php if ($flash): ?>
            <div class="p-3.5 rounded-2xl <?= ($flash['type'] ?? '') === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-800' ?> text-xs font-semibold">
                <i class="fa-solid <?= ($flash['type'] ?? '') === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?> mr-1.5"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Acceso Rápido 1-Clic para Cliente Demo -->
        <form method="POST" action="includes/actions.php">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="portal_rol" value="cliente">
            <input type="hidden" name="email" value="estefania@ejemplo.com">
            <input type="hidden" name="password" value="password">
            <button type="submit" class="w-full p-3.5 rounded-2xl coffee-gradient text-white hover:opacity-95 transition text-left flex items-center justify-between shadow-md group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <div>
                        <span class="block font-extrabold text-xs text-white">Ingreso Rápido Cliente Demo</span>
                        <span class="block text-[11px] text-amber-300">estefania@ejemplo.com (150 Pts)</span>
                    </div>
                </div>
                <i class="fa-solid fa-arrow-right text-amber-400 group-hover:translate-x-1 transition"></i>
            </button>
        </form>

        <div class="flex items-center gap-3 my-2 text-stone-400 text-xs">
            <hr class="flex-1 border-stone-200">
            <span>o ingresa con tu correo</span>
            <hr class="flex-1 border-stone-200">
        </div>

        <!-- Formulario de Acceso / Registro de Cliente -->
        <form method="POST" action="includes/actions.php" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="portal_rol" value="cliente">
            <input type="hidden" name="return_to" value="login_cliente.php">

            <div>
                <label class="block font-bold text-stone-700 text-xs mb-1">Tu Correo Electrónico</label>
                <div class="relative">
                    <i class="fa-solid fa-envelope absolute left-3.5 top-3.5 text-stone-400 text-xs"></i>
                    <input type="email" name="email" required placeholder="tucorreo@ejemplo.com"
                           class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-stone-200 focus:outline-none focus:border-amber-500 text-xs font-medium">
                </div>
                <span class="text-[11px] text-stone-400 mt-1 block">Si es tu primera vez, se creará tu membresía automáticamente con 50 puntos de bienvenida.</span>
            </div>

            <div>
                <label class="block font-bold text-stone-700 text-xs mb-1">Contraseña o PIN (Opcional)</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3.5 top-3.5 text-stone-400 text-xs"></i>
                    <input type="password" name="password" value="password"
                           class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-stone-200 focus:outline-none focus:border-amber-500 text-xs font-medium">
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl gold-gradient text-stone-950 font-extrabold shadow-md hover:brightness-105 transition">
                <i class="fa-solid fa-award mr-1.5"></i> Entrar a Mi Club del Café
            </button>
        </form>

        <!-- Navegación entre Portales y Tienda -->
        <div class="pt-4 border-t border-stone-200 text-center space-y-2.5">
            <p class="text-xs text-stone-500 font-semibold">¿Eres parte del equipo de trabajo?</p>
            <div class="flex items-center justify-center gap-2 flex-wrap text-xs">
                <a href="login_admin.php" class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-900 font-bold hover:bg-amber-100 transition">
                    <i class="fa-solid fa-crown mr-1"></i> Admin
                </a>
                <a href="login_cajero.php" class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-bold hover:bg-emerald-100 transition">
                    <i class="fa-solid fa-cash-register mr-1"></i> Cajero
                </a>
                <a href="login_barista.php" class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-900 font-bold hover:bg-amber-100 transition">
                    <i class="fa-solid fa-mug-hot mr-1"></i> Barista
                </a>
            </div>
            <div class="pt-2">
                <a href="index.php" class="text-xs font-bold text-amber-800 hover:underline inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-store"></i> Volver a la Tienda Pública
                </a>
            </div>
        </div>
    </div>
</body>
</html>
