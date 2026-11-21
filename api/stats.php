<?php
/**
 * Analítica en Tiempo Real, KPIs y Métricas KDS - API REST
 * GET /api/stats/dashboard -> KPIs financieros y gráficos gerenciales (Requiere rol: admin)
 * GET /api/stats/kds       -> Estado de cola en cocina y barra (Requiere rol: barista, cajero, admin)
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_middleware.php';

$db = getApiDb();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$sub = $_GET['sub'] ?? ($uriSegments[1] ?? 'dashboard');

if ($method !== 'GET') {
    apiError('Método no permitido. Se requiere GET.', 405);
}

switch ($sub) {
    case 'kds':
        ApiAuth::requireRole(['barista', 'cajero', 'admin']);

        $pedidos = $db->getOrdersWithDetails(100);
        $hoy = date('Y-m-d');

        $pendientes = [];
        $preparando = [];
        $completadosHoy = 0;

        foreach ($pedidos as $p) {
            $estado = strtolower($p['estado'] ?? '');
            $fecha = substr($p['created_at'] ?? '', 0, 10);

            if ($estado === 'pendiente') {
                $pendientes[] = [
                    'id'             => (int)$p['id'],
                    'folio'          => $p['folio'],
                    'tipo'           => $p['tipo'],
                    'cliente_nombre' => $p['cliente_nombre'],
                    'total_items'    => count($p['items'] ?? []),
                    'hora'           => substr($p['created_at'] ?? '', 11, 5)
                ];
            } elseif ($estado === 'preparando') {
                $preparando[] = [
                    'id'             => (int)$p['id'],
                    'folio'          => $p['folio'],
                    'tipo'           => $p['tipo'],
                    'cliente_nombre' => $p['cliente_nombre'],
                    'total_items'    => count($p['items'] ?? []),
                    'hora'           => substr($p['created_at'] ?? '', 11, 5)
                ];
            } elseif ($estado === 'completado' && $fecha === $hoy) {
                $completadosHoy++;
            }
        }

        apiResponse(true, [
            'conteo' => [
                'pendientes'      => count($pendientes),
                'preparando'      => count($preparando),
                'completados_hoy' => $completadosHoy,
                'total_activas'   => count($pendientes) + count($preparando)
            ],
            'cola_pendientes' => $pendientes,
            'cola_preparando' => $preparando
        ], 'Cola KDS sincronizada.');
        break;

    case 'dashboard':
    default:
        ApiAuth::requireRole(['admin']);

        $pedidos = $db->getOrdersWithDetails(200);
        $clientes = $db->select('clientes');
        $productos = $db->select('productos');
        $detalles = $db->select('detalle_pedidos');

        $hoy = date('Y-m-d');
        $mesActual = date('Y-m');

        $ventasTotal = 0.0;
        $ventasHoy = 0.0;
        $ventasMes = 0.0;
        $porCanal = ['pos' => 0.0, 'tienda_online' => 0.0];
        $porPago = ['efectivo' => 0.0, 'tarjeta' => 0.0, 'transferencia' => 0.0];

        foreach ($pedidos as $p) {
            if (($p['estado'] ?? '') === 'cancelado') continue;

            $monto = (float)($p['total'] ?? 0);
            $ventasTotal += $monto;

            $fecha = substr($p['created_at'] ?? '', 0, 10);
            if ($fecha === $hoy) {
                $ventasHoy += $monto;
            }
            if (substr($p['created_at'] ?? '', 0, 7) === $mesActual) {
                $ventasMes += $monto;
            }

            $tipo = strtolower($p['tipo'] ?? 'pos');
            $porCanal[$tipo] = ($porCanal[$tipo] ?? 0.0) + $monto;

            $metodo = strtolower($p['metodo_pago'] ?? 'efectivo');
            $porPago[$metodo] = ($porPago[$metodo] ?? 0.0) + $monto;
        }

        // Top 5 Productos más vendidos
        $ventasPorProd = [];
        foreach ($detalles as $d) {
            $pId = (int)($d['producto_id'] ?? 0);
            $cant = (int)($d['cantidad'] ?? 0);
            $sub = (float)($d['subtotal'] ?? 0);
            if (!isset($ventasPorProd[$pId])) {
                $ventasPorProd[$pId] = [
                    'producto_id' => $pId,
                    'nombre'      => $d['nombre_producto'] ?? 'Item',
                    'unidades'    => 0,
                    'ingresos'    => 0.0
                ];
            }
            $ventasPorProd[$pId]['unidades'] += $cant;
            $ventasPorProd[$pId]['ingresos'] += $sub;
        }

        usort($ventasPorProd, fn($a, $b) => $b['unidades'] <=> $a['unidades']);
        $top5 = array_slice($ventasPorProd, 0, 5);

        $totalPedidosValidos = count(array_filter($pedidos, fn($p) => ($p['estado'] ?? '') !== 'cancelado'));
        $ticketPromedio = $totalPedidosValidos > 0 ? round($ventasTotal / $totalPedidosValidos, 2) : 0;

        apiResponse(true, [
            'kpis' => [
                'ventas_totales_cop' => $ventasTotal,
                'ventas_hoy_cop'     => $ventasHoy,
                'ventas_mes_cop'     => $ventasMes,
                'total_pedidos'      => count($pedidos),
                'ticket_promedio_cop'=> $ticketPromedio,
                'total_clientes'     => count($clientes),
                'total_productos'    => count($productos)
            ],
            'canales'           => $porCanal,
            'metodos_pago'      => $porPago,
            'top_productos'     => $top5
        ], 'Métricas del negocio consolidadas.');
        break;
}
