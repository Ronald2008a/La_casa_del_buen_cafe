<?php
/**
 * SITIO WEB: PORTAL PRIVADO DEL CLIENTE
 * Club del Buen Café — Fidelización, Puntos & Historial de Pedidos
 */
require_once __DIR__ . '/includes/actions.php';

$user = $_SESSION['user'] ?? null;
if (!$user || ($user['rol'] ?? '') !== 'cliente') {
    header('Location: login_cliente.php');
    exit;
}

$clienteEmail = strtolower($user['email'] ?? '');
$clienteNombre = $user['nombre'] ?? 'Cliente';

// Obtener datos frescos del cliente
$todosClientes = $db->select('clientes');
$clienteActual = null;
foreach ($todosClientes as $c) {
    if (strtolower($c['email'] ?? '') === $clienteEmail) {
        $clienteActual = $c;
        break;
    }
}
$puntos = (int)($clienteActual['puntos'] ?? $user['puntos'] ?? 0);
$telefono = $clienteActual['telefono'] ?? $user['telefono'] ?? 'No registrado';
$direccion = $clienteActual['direccion'] ?? $user['direccion'] ?? 'Neiva, Huila';
$clienteId = (int)($clienteActual['id'] ?? $user['id'] ?? 0);

// Obtener pedidos del cliente
$todosPedidos = $db->select('pedidos', ['order' => 'id.desc']);
$detallePedidos = $db->select('detalle_pedidos');

$detallesPorPedido = [];
foreach ($detallePedidos as $det) {
    $pid = (int)($det['pedido_id'] ?? 0);
    $detallesPorPedido[$pid][] = $det;
}

$misPedidos = [];
foreach ($todosPedidos as $p) {
    $pClienteId = (int)($p['cliente_id'] ?? 0);
    $pNombre = strtolower(trim($p['cliente_nombre'] ?? ''));
    if (($clienteId > 0 && $pClienteId === $clienteId) || ($pNombre !== '' && $pNombre === strtolower(trim($clienteNombre)))) {
        $misPedidos[] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Mi Cuenta Club — La Casa del Buen Café</title>
    <link rel="icon" type="image/jpeg" href="logo.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8F5F0; }
        .font-display { font-family: 'Playfair Display', serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .coffee-gradient { background: linear-gradient(135deg, #120A06 0%, #26150E 55%, #3E2417 100%); }
        .gold-gradient { background: linear-gradient(135deg, #E2AA63 0%, #B87333 100%); }
        .glass-card { background: rgba(255, 255, 255, 0.96); border: 1px solid rgba(226, 214, 200, 0.8); }

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
<body class="min-h-screen flex flex-col text-stone-800">

    <!-- Header del Cliente -->
    <header class="coffee-gradient text-white sticky top-0 z-30 shadow-xl border-b border-amber-500/20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
            <a href="index.php" class="flex items-center gap-3 group">
                <img src="logo.php" alt="La Casa del Buen Café"
                     class="w-12 h-12 rounded-full object-cover shadow-lg border-2 border-amber-400 group-hover:scale-105 transition">
                <div>
                    <span class="font-display font-bold text-lg text-white block leading-none">
                        Club del Buen Café
                    </span>
                    <span class="text-[11px] text-amber-400 font-semibold block mt-1">
                        Portal Exclusivo de Socios
                    </span>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <a href="index.php" class="px-3.5 py-2 rounded-xl gold-gradient text-stone-950 font-extrabold text-xs shadow flex items-center gap-1.5 hover:brightness-105 transition">
                    <i class="fa-solid fa-store"></i> <span class="hidden sm:inline">Comprar en Tienda</span>
                </a>
                <form method="POST" action="includes/actions.php">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-red-500/20 hover:bg-red-500/30 border border-red-400/30 text-red-200 text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-power-off"></i> <span class="hidden sm:inline">Salir</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 space-y-8">

        <?php if ($flash): ?>
            <div class="p-4 rounded-2xl bg-emerald-900 text-emerald-100 border border-emerald-500/50 flex items-center justify-between shadow-lg text-xs sm:text-sm font-semibold">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-400 text-lg"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-white/60 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Tarjeta de Membresía Digital -->
        <div class="coffee-gradient rounded-3xl p-6 sm:p-8 text-white shadow-2xl border border-amber-500/40 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 opacity-10 text-9xl">
                <i class="fa-solid fa-award"></i>
            </div>
            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <span class="px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 text-xs font-bold uppercase tracking-wider inline-block">
                        <i class="fa-solid fa-star mr-1"></i> Miembro Distinguido
                    </span>
                    <h1 class="font-display font-bold text-white text-2xl sm:text-3xl">
                        <?= htmlspecialchars($clienteNombre) ?>
                    </h1>
                    <p class="text-stone-300 text-xs sm:text-sm flex flex-wrap gap-4">
                        <span><i class="fa-solid fa-envelope text-amber-400 mr-1.5"></i> <?= htmlspecialchars($clienteEmail) ?></span>
                        <span><i class="fa-solid fa-phone text-amber-400 mr-1.5"></i> <?= htmlspecialchars($telefono) ?></span>
                        <span><i class="fa-solid fa-location-dot text-amber-400 mr-1.5"></i> <?= htmlspecialchars($direccion) ?></span>
                    </p>
                </div>

                <!-- Saldo de Puntos -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center min-w-[200px] shadow-lg">
                    <span class="text-[11px] font-bold text-amber-300 uppercase tracking-wider block">Puntos Acumulados</span>
                    <span class="font-display text-4xl sm:text-5xl font-black text-amber-400 block my-1">
                        <?= number_format($puntos, 0, ',', '.') ?>
                    </span>
                    <span class="text-[11px] text-stone-300 block">1 Punto por cada $1.000 COP</span>
                </div>
            </div>

            <!-- Progreso para Redimir Café Gratis -->
            <div class="mt-6 pt-6 border-t border-white/10 space-y-2">
                <?php
                $metaPuntos = 100;
                $porcentaje = min(100, round(($puntos / $metaPuntos) * 100));
                ?>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-stone-300 font-semibold">
                        <i class="fa-solid fa-mug-hot text-amber-400 mr-1"></i> Meta de Recompensa: <strong>Café de Origen 8oz Gratis</strong> (100 pts)
                    </span>
                    <span class="text-amber-400 font-bold"><?= $puntos ?> / 100 Pts (<?= $porcentaje ?>%)</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-3 overflow-hidden">
                    <div class="gold-gradient h-3 rounded-full transition-all duration-500" style="width: <?= $porcentaje ?>%"></div>
                </div>
                <?php if ($puntos >= 100): ?>
                    <p class="text-emerald-300 font-bold text-xs pt-1">
                        <i class="fa-solid fa-gift mr-1"></i> ¡Felicidades! Tienes puntos suficientes para canjear un café de cortesía en tu próxima visita o pedido.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mis Pedidos Recientes -->
        <div class="glass-card rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-stone-200">
                <div>
                    <h2 class="card-title font-display text-stone-900 flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-amber-700"></i> Mis Pedidos & Compras
                    </h2>
                    <p class="text-stone-500 text-xs">Rastreo de órdenes en tiempo real preparadas en barra</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-900 text-xs font-bold">
                    <?= count($misPedidos) ?> pedido(s) registrado(s)
                </span>
            </div>

            <?php if (empty($misPedidos)): ?>
                <div class="text-center py-12 space-y-3">
                    <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center text-2xl mx-auto">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <h3 class="font-display font-bold text-stone-800">Aún no tienes pedidos registrados</h3>
                    <p class="text-stone-500 text-xs max-w-sm mx-auto">Explora nuestro catálogo con cafés especiales del Huila y repostería artesanal recién horneada.</p>
                    <a href="index.php#catalogo" class="inline-block mt-3 px-5 py-2.5 rounded-xl gold-gradient text-stone-950 font-extrabold text-xs shadow hover:brightness-105 transition">
                        Ver Catálogo en la Tienda
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($misPedidos as $ped): ?>
                        <?php
                        $pid = (int)$ped['id'];
                        $items = $detallesPorPedido[$pid] ?? [];
                        $estado = $ped['estado'] ?? 'pendiente';
                        $statusBadge = match($estado) {
                            'pendiente'  => ['bg' => 'bg-blue-100 text-blue-800 border-blue-200', 'text' => 'Recibido / En cola', 'icon' => 'fa-clock'],
                            'preparando' => ['bg' => 'bg-amber-100 text-amber-800 border-amber-200 animate-pulse', 'text' => 'En Preparación por Barista', 'icon' => 'fa-mug-hot'],
                            'completado' => ['bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'text' => 'Entregado / Listo', 'icon' => 'fa-circle-check'],
                            default      => ['bg' => 'bg-stone-100 text-stone-700 border-stone-200', 'text' => ucfirst($estado), 'icon' => 'fa-info']
                        };
                        ?>
                        <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-sm space-y-3">
                            <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-stone-100">
                                <div>
                                    <span class="font-mono font-bold text-stone-900 text-sm">#<?= htmlspecialchars($ped['folio'] ?? 'ORD-' . $pid) ?></span>
                                    <span class="block text-[11px] text-stone-400">
                                        <?= !empty($ped['created_at']) ? date('d/m/Y - H:i', strtotime($ped['created_at'])) : 'Hoy' ?>
                                    </span>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold border <?= $statusBadge['bg'] ?> flex items-center gap-1.5">
                                    <i class="fa-solid <?= $statusBadge['icon'] ?>"></i> <?= $statusBadge['text'] ?>
                                </span>
                            </div>

                            <!-- Desglose de Productos -->
                            <div class="space-y-1 text-xs text-stone-600">
                                <?php foreach ($items as $it): ?>
                                    <div class="flex justify-between">
                                        <span><strong><?= (int)$it['cantidad'] ?>x</strong> <?= htmlspecialchars($it['nombre_producto']) ?></span>
                                        <span class="font-semibold text-stone-800">$<?= number_format((float)$it['subtotal'], 0, ',', '.') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="pt-2.5 border-t border-stone-100 flex items-center justify-between text-xs">
                                <span class="text-stone-500">Total Pagado (<?= strtoupper(htmlspecialchars($ped['metodo_pago'] ?? 'efectivo')) ?>):</span>
                                <span class="font-bold text-base text-stone-900">$<?= number_format((float)$ped['total'], 0, ',', '.') ?> COP</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-[#120A06] text-white py-6 border-t border-amber-500/20 text-center text-xs space-y-1">
        <p><strong class="text-amber-400">La Casa del Buen Café</strong> — Centro Comercial San Juan Plaza Local 151 Primer Piso, Neiva-Huila</p>
        <p class="text-stone-400 text-[11px]">Horario de Atención: Lunes a Domingo de 9:00 AM a 8:00 PM · WhatsApp: +57 310 555 1234</p>
    </footer>

</body>
</html>
