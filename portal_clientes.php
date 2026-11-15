<?php
/**
 * PORTAL ESPECIAL PARA CLIENTES
 * Club del Buen Café — Fidelización, Tarjeta VIP, Puntos, Catálogo & Seguimiento de Órdenes
 * La Casa del Buen Café
 */
require_once __DIR__ . '/includes/actions.php';

$user = $_SESSION['user'] ?? null;
$esClienteAutenticado = ($user && ($user['rol'] ?? '') === 'cliente');

// Si no está autenticado como cliente pero tiene sesión de empleado, permitir salir o advertir
if ($user && ($user['rol'] ?? '') !== 'cliente') {
    $rolEmpleado = $user['rol'];
}

$clienteActual = null;
$puntos = 0;
$telefono = '';
$direccion = 'Neiva, Huila';
$clienteId = 0;
$misPedidos = [];

if ($esClienteAutenticado) {
    $clienteEmail = strtolower($user['email'] ?? '');
    $clienteNombre = $user['nombre'] ?? 'Cliente Distinguido';

    // Obtener datos frescos del cliente desde la base de datos o local store
    $todosClientes = $db->select('clientes');
    foreach ($todosClientes as $c) {
        if (strtolower($c['email'] ?? '') === $clienteEmail) {
            $clienteActual = $c;
            break;
        }
    }
    $puntos = (int)($clienteActual['puntos'] ?? $user['puntos'] ?? 50);
    $telefono = $clienteActual['telefono'] ?? $user['telefono'] ?? '';
    $direccion = $clienteActual['direccion'] ?? $user['direccion'] ?? 'Calle 26 # 16-24, Neiva-Huila';
    $clienteId = (int)($clienteActual['id'] ?? $user['id'] ?? 0);

    // Obtener pedidos del cliente
    $todosPedidos = $db->select('pedidos', ['order' => 'id.desc']);
    $detallePedidos = $db->select('detalle_pedidos');

    $detallesPorPedido = [];
    foreach ($detallePedidos as $det) {
        $pid = (int)($det['pedido_id'] ?? 0);
        $detallesPorPedido[$pid][] = $det;
    }

    foreach ($todosPedidos as $p) {
        $pClienteId = (int)($p['cliente_id'] ?? 0);
        $pNombre = strtolower(trim($p['cliente_nombre'] ?? ''));
        if (($clienteId > 0 && $pClienteId === $clienteId) || ($pNombre !== '' && $pNombre === strtolower(trim($clienteNombre)))) {
            $misPedidos[] = $p;
        }
    }
}

// Catálogo de productos destacados para socios
$productos = $db->select('productos', ['select' => '*', 'order' => 'destacado.desc, id.asc']);
$productosSocio = array_slice($productos, 0, 8);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Portal Especial para Clientes — Club del Buen Café</title>
    <link rel="icon" type="image/jpeg" href="logo.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8F5F0; }
        .font-display { font-family: 'Playfair Display', serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .coffee-gradient { background: linear-gradient(135deg, #120A06 0%, #26150E 55%, #3E2417 100%); }
        .gold-gradient { background: linear-gradient(135deg, #E2AA63 0%, #B87333 100%); }
        .card-vip {
            background: linear-gradient(135deg, #1A0D07 0%, #2C1810 50%, #4A2818 100%);
            border: 2px solid rgba(226, 170, 99, 0.5);
            box-shadow: 0 20px 35px -10px rgba(18, 10, 6, 0.4);
        }
        .card-hover { transition: all 0.22s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 14px 24px -10px rgba(28, 16, 11, 0.12); }

        /* ESTÁNDAR TIPOGRÁFICO ESTRICTO: Títulos 20px | Botones 14px | Párrafos 14px */
        h1, h2, h3, h4, .section-title, .card-title, .modal-title {
            font-size: 20px !important;
            line-height: 1.35 !important;
            font-weight: 700 !important;
        }
        p, .paragraph, .body-text, .text-desc {
            font-size: 14px !important;
            line-height: 1.6 !important;
        }
        button, .btn, a.btn, input[type="submit"], input[type="button"], .tab-btn {
            font-size: 14px !important;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-stone-800">

    <!-- Cintillo Superior VIP -->
    <div class="bg-[#120A06] text-amber-200/90 text-xs py-2 px-4 border-b border-amber-500/20">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold uppercase tracking-wider text-[11px]">
                    <i class="fa-solid fa-award mr-1"></i> Club del Buen Café
                </span>
                <span class="text-stone-300 text-xs hidden sm:inline">1 Punto por cada $1.000 COP en compras en línea o en tienda física</span>
            </div>
            <div class="flex items-center gap-3 text-xs font-semibold">
                <a href="index.php" class="text-amber-300 hover:text-white transition flex items-center gap-1">
                    <i class="fa-solid fa-store"></i> Tienda Pública
                </a>
                <span class="text-white/20">|</span>
                <a href="portal_privado.php" class="text-stone-400 hover:text-amber-300 transition flex items-center gap-1">
                    <i class="fa-solid fa-lock"></i> Acceso Personal Privado
                </a>
            </div>
        </div>
    </div>

    <!-- Encabezado del Portal de Clientes -->
    <header class="coffee-gradient text-white sticky top-0 z-40 shadow-xl border-b border-amber-500/20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
            <a href="portal_clientes.php" class="flex items-center gap-3.5 group">
                <img src="logo.php" alt="La Casa del Buen Café"
                     class="w-12 h-12 rounded-full object-cover shadow-lg border-2 border-amber-400 group-hover:scale-105 transition">
                <div>
                    <span class="font-display font-bold text-lg text-white block leading-none">
                        Portal Especial de Clientes
                    </span>
                    <span class="text-[11px] text-amber-400 font-semibold block mt-1 tracking-wider uppercase">
                        La Casa del Buen Café · Neiva, Huila
                    </span>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <?php if ($esClienteAutenticado): ?>
                    <div class="hidden sm:flex items-center gap-2 bg-white/10 px-3.5 py-1.5 rounded-xl border border-white/15 text-xs">
                        <i class="fa-solid fa-award text-amber-400"></i>
                        <div>
                            <span class="font-bold text-white block leading-none"><?= htmlspecialchars($clienteNombre) ?></span>
                            <span class="text-[10px] text-amber-300"><?= number_format($puntos, 0, ',', '.') ?> Puntos</span>
                        </div>
                    </div>
                    <a href="index.php" class="px-3.5 py-2 rounded-xl gold-gradient text-stone-950 font-extrabold text-xs shadow hover:brightness-105 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-bag-shopping"></i> <span class="hidden sm:inline">Comprar en Tienda</span>
                    </a>
                    <form method="POST" action="includes/actions.php" class="inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="px-3 py-2 rounded-xl bg-red-500/20 hover:bg-red-500/30 border border-red-400/30 text-red-200 text-xs font-bold transition flex items-center gap-1.5" title="Cerrar Sesión">
                            <i class="fa-solid fa-power-off"></i> <span class="hidden sm:inline">Salir</span>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="#ingreso" class="px-4 py-2 rounded-xl gold-gradient text-stone-950 font-extrabold text-xs shadow hover:brightness-105 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión de Cliente
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Contenido del Portal -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 space-y-8">

        <?php if ($flash): ?>
            <div class="p-4 rounded-2xl bg-stone-900 text-white border-l-4 border-amber-400 flex items-center justify-between shadow-lg text-xs sm:text-sm font-semibold">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-amber-400 text-lg"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-white/60 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!$esClienteAutenticado): ?>
            <!-- ============================================================== -->
            <!-- VISTA DE INGRESO / REGISTRO AL PORTAL DE CLIENTES              -->
            <!-- ============================================================== -->
            <div id="ingreso" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Beneficios del Club -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="space-y-2">
                        <span class="px-3 py-1 rounded-full bg-amber-200 text-amber-900 text-xs font-bold uppercase tracking-wider inline-block">
                            <i class="fa-solid fa-crown mr-1"></i> Beneficios de Membresía
                        </span>
                        <h1 class="font-display font-bold text-stone-900 text-2xl sm:text-3xl">
                            Bienvenido a tu Espacio Exclusivo de Café
                        </h1>
                        <p class="text-stone-600">
                            Como miembro del Club de La Casa del Buen Café, cada compra en nuestra barra del C.C. San Juan Plaza o en la tienda online te premia con bebidas de autor, tazas de cortesía y descuentos especiales.
                        </p>
                    </div>

                    <!-- 3 Tarjetas de Beneficio -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm space-y-2 card-hover">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-award"></i>
                            </div>
                            <h2 class="card-title text-stone-900 font-bold">Puntos con Cada Café</h2>
                            <p class="text-stone-500 text-xs">Acumula 1 punto por cada $1.000 COP y canjéalos por productos reales.</p>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm space-y-2 card-hover">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-mug-hot"></i>
                            </div>
                            <h2 class="card-title text-stone-900 font-bold">Café Gratis a los 100 Pts</h2>
                            <p class="text-stone-500 text-xs">Llega a 100 puntos y reclama un café de origen 8oz sin costo.</p>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm space-y-2 card-hover">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-bell-concierge"></i>
                            </div>
                            <h2 class="card-title text-stone-900 font-bold">Seguimiento en Vivo</h2>
                            <p class="text-stone-500 text-xs">Mira en tiempo real cuando el barista empiece a preparar tu orden.</p>
                        </div>
                    </div>

                    <!-- Acceso Rápido 1-Clic para Probar Clientes Demo -->
                    <div class="bg-amber-50/80 p-5 rounded-2xl border border-amber-200 space-y-3">
                        <span class="text-xs font-bold text-amber-900 uppercase tracking-wider block">
                            <i class="fa-solid fa-bolt mr-1 text-amber-600"></i> Acceso Rápido 1-Clic (Clientes Registrados):
                        </span>
                        <div class="flex flex-wrap gap-2.5">
                            <form method="POST" action="includes/actions.php" class="inline">
                                <input type="hidden" name="action" value="login">
                                <input type="hidden" name="portal_rol" value="cliente">
                                <input type="hidden" name="email" value="estefania@ejemplo.com">
                                <input type="hidden" name="password" value="password">
                                <button type="submit" class="px-3.5 py-2 rounded-xl bg-white hover:bg-amber-100 text-stone-900 border border-amber-300 font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-check text-amber-600"></i> Estefanía Olaya (150 Pts)
                                </button>
                            </form>
                            <form method="POST" action="includes/actions.php" class="inline">
                                <input type="hidden" name="action" value="login">
                                <input type="hidden" name="portal_rol" value="cliente">
                                <input type="hidden" name="email" value="carlos.mendoza@ejemplo.com">
                                <input type="hidden" name="password" value="password">
                                <button type="submit" class="px-3.5 py-2 rounded-xl bg-white hover:bg-amber-100 text-stone-900 border border-amber-300 font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-check text-amber-600"></i> Carlos Mendoza (85 Pts)
                                </button>
                            </form>
                            <form method="POST" action="includes/actions.php" class="inline">
                                <input type="hidden" name="action" value="login">
                                <input type="hidden" name="portal_rol" value="cliente">
                                <input type="hidden" name="email" value="laura.valencia@ejemplo.com">
                                <input type="hidden" name="password" value="password">
                                <button type="submit" class="px-3.5 py-2 rounded-xl bg-white hover:bg-amber-100 text-stone-900 border border-amber-300 font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-check text-amber-600"></i> Laura Valencia (220 Pts)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Acceso / Registro -->
                <div class="lg:col-span-5 bg-white rounded-3xl p-7 shadow-xl border-2 border-amber-400/50 space-y-5">
                    <div class="text-center space-y-1.5 pb-2 border-b border-stone-100">
                        <img src="logo.php" alt="La Casa del Buen Café" class="w-16 h-16 rounded-full mx-auto border-2 border-amber-400 shadow">
                        <h2 class="card-title font-display text-stone-900">Entrar a Mi Cuenta</h2>
                        <p class="text-xs text-stone-500">Ingresa tu correo para consultar puntos o registrarte automáticamente</p>
                    </div>

                    <form method="POST" action="includes/actions.php" class="space-y-4">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="portal_rol" value="cliente">
                        <input type="hidden" name="return_to" value="portal_clientes.php">

                        <div>
                            <label class="block font-bold text-stone-700 text-xs mb-1">Tu Correo Electrónico</label>
                            <div class="relative">
                                <i class="fa-solid fa-envelope absolute left-3.5 top-3.5 text-stone-400 text-xs"></i>
                                <input type="email" name="email" required placeholder="tucorreo@ejemplo.com"
                                       class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-stone-200 focus:outline-none focus:border-amber-500 text-xs font-medium">
                            </div>
                            <span class="text-[11px] text-stone-400 mt-1 block">Si no tienes cuenta, te registramos al instante con 50 puntos de bienvenida.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-stone-700 text-xs mb-1">Contraseña o Clave</label>
                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-3.5 top-3.5 text-stone-400 text-xs"></i>
                                <input type="password" name="password" value="password" required
                                       class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-stone-200 focus:outline-none focus:border-amber-500 text-xs font-medium">
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-xl gold-gradient text-stone-950 font-extrabold shadow hover:brightness-105 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-award"></i> Ingresar a Mi Portal de Socio
                        </button>
                    </form>

                    <div class="text-center pt-2 border-t border-stone-100 text-xs">
                        <a href="index.php" class="font-bold text-amber-800 hover:underline inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-store"></i> Volver a la Tienda Pública
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ============================================================== -->
            <!-- VISTA DEL CLIENTE AUTENTICADO                                  -->
            <!-- ============================================================== -->

            <!-- Tarjeta VIP Digital del Club del Buen Café -->
            <div class="card-vip rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 opacity-10 text-9xl">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/50 text-amber-300 text-xs font-bold uppercase tracking-wider inline-block">
                                <i class="fa-solid fa-gem mr-1"></i> Membresía VIP Club
                            </span>
                            <span class="text-xs text-stone-400 font-mono">ID: CBC-<?= str_pad($clienteId, 4, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <h1 class="font-display font-bold text-white text-2xl sm:text-3xl">
                            <?= htmlspecialchars($clienteNombre) ?>
                        </h1>
                        <p class="text-stone-300 text-xs sm:text-sm flex flex-wrap gap-4">
                            <span><i class="fa-solid fa-envelope text-amber-400 mr-1.5"></i> <?= htmlspecialchars($clienteEmail) ?></span>
                            <span><i class="fa-solid fa-phone text-amber-400 mr-1.5"></i> <?= htmlspecialchars($telefono ?: 'Registrar teléfono') ?></span>
                            <span><i class="fa-solid fa-location-dot text-amber-400 mr-1.5"></i> <?= htmlspecialchars($direccion) ?></span>
                        </p>
                    </div>

                    <!-- Contador de Puntos -->
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center min-w-[220px] shadow-lg">
                        <span class="text-[11px] font-bold text-amber-300 uppercase tracking-wider block">Saldo de Puntos</span>
                        <span class="font-display text-4xl sm:text-5xl font-black text-amber-400 block my-1">
                            <?= number_format($puntos, 0, ',', '.') ?>
                        </span>
                        <span class="text-[11px] text-stone-300 block">Nivel: <?= $puntos >= 200 ? 'Oro Diamante' : ($puntos >= 100 ? 'Plata Especial' : 'Bronce Cafetero') ?></span>
                    </div>
                </div>

                <!-- Barra de Progreso y Metas de Fidelización -->
                <div class="mt-6 pt-6 border-t border-white/10 space-y-3">
                    <?php
                    $metaPuntos = 100;
                    $porcentaje = min(100, round(($puntos / $metaPuntos) * 100));
                    ?>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-stone-300 font-semibold">
                            <i class="fa-solid fa-mug-hot text-amber-400 mr-1.5"></i> Meta Actual: <strong>Café de Origen 8oz Gratis</strong> (100 pts)
                        </span>
                        <span class="text-amber-400 font-bold"><?= $puntos ?> / 100 Pts (<?= $porcentaje ?>%)</span>
                    </div>
                    <div class="w-full bg-white/20 rounded-full h-3 overflow-hidden">
                        <div class="gold-gradient h-3 rounded-full transition-all duration-500" style="width: <?= $porcentaje ?>%"></div>
                    </div>

                    <!-- Premios Disponibles para Canje -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div class="p-3 rounded-xl bg-white/10 border border-white/10 text-xs flex items-center justify-between">
                            <div>
                                <span class="font-bold text-white block">☕ Café de Origen 8oz</span>
                                <span class="text-[11px] text-stone-300">100 Puntos</span>
                            </div>
                            <?php if ($puntos >= 100): ?>
                                <span class="px-2 py-1 rounded bg-emerald-500 text-stone-950 font-bold text-[10px]">¡Disponible!</span>
                            <?php else: ?>
                                <span class="text-stone-400 text-[10px]">Faltan <?= 100 - $puntos ?> pts</span>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 rounded-xl bg-white/10 border border-white/10 text-xs flex items-center justify-between">
                            <div>
                                <span class="font-bold text-white block">🍰 Torta o Empanada</span>
                                <span class="text-[11px] text-stone-300">150 Puntos</span>
                            </div>
                            <?php if ($puntos >= 150): ?>
                                <span class="px-2 py-1 rounded bg-emerald-500 text-stone-950 font-bold text-[10px]">¡Disponible!</span>
                            <?php else: ?>
                                <span class="text-stone-400 text-[10px]">Faltan <?= 150 - $puntos ?> pts</span>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 rounded-xl bg-white/10 border border-white/10 text-xs flex items-center justify-between">
                            <div>
                                <span class="font-bold text-white block">📦 Café 250g Grano</span>
                                <span class="text-[11px] text-stone-300">300 Puntos</span>
                            </div>
                            <?php if ($puntos >= 300): ?>
                                <span class="px-2 py-1 rounded bg-emerald-500 text-stone-950 font-bold text-[10px]">¡Disponible!</span>
                            <?php else: ?>
                                <span class="text-stone-400 text-[10px]">Faltan <?= 300 - $puntos ?> pts</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Seguimiento de Pedidos y Catálogo Directo -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- Mis Pedidos y Estado en Tiempo Real (8 columnas) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-stone-200 shadow-sm space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-stone-200">
                            <div>
                                <h2 class="card-title font-display text-stone-900 flex items-center gap-2">
                                    <i class="fa-solid fa-receipt text-amber-700"></i> Mis Pedidos & Estado en Barra
                                </h2>
                                <p class="text-stone-500 text-xs">Seguimiento en vivo de tus compras y órdenes preparadas por nuestro barista</p>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-900 text-xs font-bold">
                                <?= count($misPedidos) ?> pedido(s)
                            </span>
                        </div>

                        <?php if (empty($misPedidos)): ?>
                            <div class="text-center py-10 space-y-3">
                                <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center text-2xl mx-auto">
                                    <i class="fa-solid fa-bag-shopping"></i>
                                </div>
                                <h3 class="font-display font-bold text-stone-800">No tienes pedidos activos</h3>
                                <p class="text-stone-500 text-xs max-w-sm mx-auto">Explora nuestro catálogo para pedir tu café favorito y acumular puntos al instante.</p>
                                <a href="index.php#catalogo" class="inline-block mt-2 px-5 py-2.5 rounded-xl gold-gradient text-stone-950 font-extrabold text-xs shadow hover:brightness-105 transition">
                                    Comprar en la Tienda
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($misPedidos as $ped): ?>
                                    <?php
                                    $pid = (int)$ped['id'];
                                    $items = $detallesPorPedido[$pid] ?? [];
                                    $estado = $ped['estado'] ?? 'pendiente';
                                    $badge = match($estado) {
                                        'pendiente'  => ['bg' => 'bg-blue-100 text-blue-800 border-blue-200', 'text' => 'Recibido en cola', 'icon' => 'fa-clock'],
                                        'preparando' => ['bg' => 'bg-amber-100 text-amber-800 border-amber-200 animate-pulse', 'text' => 'Barista Preparando en Barra', 'icon' => 'fa-mug-hot'],
                                        'completado' => ['bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'text' => 'Listo para recoger / Entregado', 'icon' => 'fa-circle-check'],
                                        default      => ['bg' => 'bg-stone-100 text-stone-700 border-stone-200', 'text' => ucfirst($estado), 'icon' => 'fa-info']
                                    };
                                    ?>
                                    <div class="p-5 rounded-2xl border border-stone-200/90 bg-stone-50/50 hover:bg-white transition shadow-sm space-y-3">
                                        <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-stone-200">
                                            <div>
                                                <span class="font-mono font-bold text-stone-900 text-sm">#<?= htmlspecialchars($ped['folio'] ?? 'ORD-' . $pid) ?></span>
                                                <span class="block text-[11px] text-stone-400">
                                                    <?= !empty($ped['created_at']) ? date('d/m/Y - H:i', strtotime($ped['created_at'])) : 'Hoy' ?>
                                                    · Tipo: <?= ($ped['tipo'] ?? 'tienda_online') === 'tienda_online' ? 'Tienda Online' : 'POS Barra' ?>
                                                </span>
                                            </div>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border <?= $badge['bg'] ?> flex items-center gap-1.5 shadow-sm">
                                                <i class="fa-solid <?= $badge['icon'] ?>"></i> <?= $badge['text'] ?>
                                            </span>
                                        </div>

                                        <!-- Desglose de Productos -->
                                        <div class="space-y-1.5 text-xs text-stone-600">
                                            <?php foreach ($items as $it): ?>
                                                <div class="flex justify-between items-center">
                                                    <span><strong><?= (int)$it['cantidad'] ?>x</strong> <?= htmlspecialchars($it['nombre_producto']) ?></span>
                                                    <span class="font-semibold text-stone-800">$<?= number_format((float)$it['subtotal'], 0, ',', '.') ?> COP</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="pt-2.5 border-t border-stone-200 flex items-center justify-between text-xs">
                                            <span class="text-stone-500">Total (<?= strtoupper(htmlspecialchars($ped['metodo_pago'] ?? 'efectivo')) ?>):</span>
                                            <span class="font-bold text-base text-stone-900">$<?= number_format((float)$ped['total'], 0, ',', '.') ?> COP</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Catálogo Rápido para Reordenar (4 columnas) -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-white rounded-3xl p-6 border border-stone-200 shadow-sm space-y-4">
                        <div class="pb-3 border-b border-stone-200">
                            <h2 class="card-title font-display text-stone-900 flex items-center gap-2">
                                <i class="fa-solid fa-fire text-amber-600"></i> Pedir Rápido
                            </h2>
                            <p class="text-xs text-stone-500">Favoritos de socios listos para llevar</p>
                        </div>

                        <div class="space-y-3">
                            <?php foreach ($productosSocio as $pr): ?>
                                <div class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-stone-50 border border-stone-100 transition">
                                    <img src="<?= htmlspecialchars($pr['imagen_url'] ?? 'logo.php') ?>"
                                         alt="<?= htmlspecialchars($pr['nombre']) ?>"
                                         class="w-12 h-12 rounded-xl object-cover border border-stone-200 shadow-sm">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-stone-900 text-xs truncate leading-tight"><?= htmlspecialchars($pr['nombre']) ?></h3>
                                        <span class="text-[11px] text-amber-800 font-bold">$<?= number_format((float)$pr['precio'], 0, ',', '.') ?> COP</span>
                                    </div>
                                    <a href="index.php#catalogo" class="px-2.5 py-1.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-stone-900 font-bold text-xs transition" title="Pedir">
                                        <i class="fa-solid fa-plus"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="pt-3 border-t border-stone-200 text-center">
                            <a href="index.php#catalogo" class="w-full py-2.5 rounded-xl gold-gradient text-stone-950 font-extrabold text-xs shadow hover:brightness-105 transition block">
                                Ver Catálogo Completo
                            </a>
                        </div>
                    </div>

                    <!-- Datos de la Tienda Física -->
                    <div class="bg-[#120A06] text-white rounded-3xl p-6 border border-amber-500/30 space-y-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-400 block">Visítanos en Neiva</span>
                        <h2 class="card-title font-display text-white">La Casa del Buen Café</h2>
                        <p class="text-stone-300 text-xs leading-relaxed">
                            <i class="fa-solid fa-location-dot text-amber-400 mr-1.5"></i> Calle 26 # 16-24 Centro Comercial San Juan Plaza Local 151 Primer Piso, Neiva-Huila
                        </p>
                        <p class="text-stone-400 text-[11px]">
                            <i class="fa-solid fa-clock text-amber-400 mr-1.5"></i> Lunes a Viernes: 9AM - 8PM · Sábados y Domingos: 10AM - 8PM
                        </p>
                    </div>
                </div>

            </div>

        <?php endif; ?>

    </main>

    <!-- Pie de Página -->
    <footer class="bg-[#120A06] text-white py-6 border-t border-amber-500/20 text-center text-xs space-y-1 mt-auto">
        <p><strong class="text-amber-400">La Casa del Buen Café</strong> — Centro Comercial San Juan Plaza Local 151 Primer Piso, Neiva-Huila</p>
        <p class="text-stone-400 text-[11px]">Horario de Atención: Lunes a Domingo de 9:00 AM a 8:00 PM · WhatsApp: +57 310 555 1234</p>
    </footer>

</body>
</html>
