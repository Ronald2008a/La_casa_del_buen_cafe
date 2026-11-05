<?php
/**
 * API en Vivo para Monitoreo en Tiempo Real desde XAMPP
 * La Casa del Buen Café
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/config/supabase.php';

$db = new SupabaseService();
$pedidos = $db->select('pedidos', ['select' => '*', 'order' => 'id.desc']);
$productos = $db->select('productos', ['select' => 'id,stock,stock_minimo', 'order' => 'id.asc']);

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

$stockBajoCount = 0;
foreach ($productos as $pr) {
    if ((int)($pr['stock'] ?? 0) <= (int)($pr['stock_minimo'] ?? 5)) {
        $stockBajoCount++;
    }
}

$ultimoPedido = !empty($pedidos[0]) ? [
    'id'             => (int)$pedidos[0]['id'],
    'folio'          => $pedidos[0]['folio'] ?? '',
    'cliente_nombre' => $pedidos[0]['cliente_nombre'] ?? 'Cliente',
    'total'          => (float)($pedidos[0]['total'] ?? 0),
    'tipo'           => $pedidos[0]['tipo'] ?? 'pos',
    'estado'         => $pedidos[0]['estado'] ?? 'pendiente',
    'created_at'     => $pedidos[0]['created_at'] ?? date('c')
] : null;

$ipRed = function_exists('obtenerIpLocalRed') ? obtenerIpLocalRed() : '127.0.0.1';

echo json_encode([
    'ok'                 => true,
    'total_pedidos'      => count($pedidos),
    'pedidos_pendientes' => $pedidosPendientes,
    'total_ventas'       => $totalVentas,
    'stock_bajo_count'   => $stockBajoCount,
    'ultimo_pedido'      => $ultimoPedido,
    'mysql_active'       => $db->isMySQLActive(),
    'ip_red'             => $ipRed,
    'timestamp'          => time()
], JSON_UNESCAPED_UNICODE);

