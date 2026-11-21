<?php
/**
 * Gestión de Inventario, Kardex y Alertas de Stock - API REST
 * GET  /api/inventario              -> Resumen de existencias, valuación y alertas críticas
 * GET  /api/inventario/kardex       -> Historial inmutable de movimientos
 * POST /api/inventario/movimientos  -> Registrar movimiento manual (entrada, salida, ajuste)
 * Requiere rol: admin
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_middleware.php';

$db = getApiDb();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = getJsonInput();
$subAction = $_GET['sub'] ?? ($uriSegments[1] ?? 'resumen');

// Todos los endpoints de inventario requieren rol Admin
ApiAuth::requireRole(['admin']);

switch ($method) {
    case 'GET':
        if ($subAction === 'kardex' || isset($_GET['kardex'])) {
            $movimientos = $db->select('movimientos_inventario', ['select' => '*', 'order' => 'id.desc']);
            $productos = $db->select('productos');
            $mapaProd = [];
            foreach ($productos as $p) {
                $mapaProd[(int)$p['id']] = $p;
            }

            foreach ($movimientos as &$m) {
                $pId = (int)$m['producto_id'];
                $m['producto_nombre'] = $mapaProd[$pId]['nombre'] ?? 'Producto Desconocido';
                $m['producto_codigo'] = $mapaProd[$pId]['codigo'] ?? 'N/A';
            }

            apiResponse(true, $movimientos, 'Kardex de movimientos obtenido.', 200, [
                'total_movimientos' => count($movimientos)
            ]);
        } else {
            // Resumen de inventario y alertas
            $productos = $db->select('productos', ['select' => '*', 'order' => 'stock.asc']);
            $criticos = [];
            $totalStock = 0;
            $valorCostoTotal = 0.0;
            $valorVentaTotal = 0.0;

            foreach ($productos as $p) {
                $stock = (int)($p['stock'] ?? 0);
                $min = (int)($p['stock_minimo'] ?? 5);
                $costo = (float)($p['costo'] ?? 0);
                $precio = (float)($p['precio'] ?? 0);

                $totalStock += $stock;
                $valorCostoTotal += ($costo * $stock);
                $valorVentaTotal += ($precio * $stock);

                if ($stock <= $min) {
                    $criticos[] = [
                        'id'           => (int)$p['id'],
                        'codigo'       => $p['codigo'],
                        'nombre'       => $p['nombre'],
                        'stock'        => $stock,
                        'stock_minimo' => $min,
                        'deficiencia'  => ($min - $stock)
                    ];
                }
            }

            apiResponse(true, [
                'resumen' => [
                    'total_productos'     => count($productos),
                    'total_unidades'      => $totalStock,
                    'productos_criticos'  => count($criticos),
                    'valuacion_costo_cop' => $valorCostoTotal,
                    'valuacion_venta_cop' => $valorVentaTotal,
                    'margen_potencial'    => ($valorVentaTotal > 0) ? round((($valorVentaTotal - $valorCostoTotal) / $valorVentaTotal) * 100, 1) : 0
                ],
                'alertas_stock_critico'   => $criticos
            ], 'Estado del inventario evaluado exitosamente.');
        }
        break;

    case 'POST':
        // Registrar movimiento de inventario (Entrada de compra, Merma o Ajuste)
        $productoId = (int)($input['producto_id'] ?? 0);
        $tipo = strtolower(trim($input['tipo'] ?? 'entrada'));
        $cantidad = (int)($input['cantidad'] ?? 0);
        $motivo = trim($input['motivo'] ?? 'Ajuste manual de inventario');

        if ($productoId <= 0) {
            apiError('Debes indicar un "producto_id" válido.', 422);
        }
        if ($cantidad <= 0) {
            apiError('La "cantidad" debe ser un número entero mayor a 0.', 422);
        }
        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            apiError('El "tipo" debe ser "entrada", "salida" o "ajuste".', 422);
        }

        $prod = $db->findById('productos', $productoId);
        if (!$prod) {
            apiError("Producto con ID {$productoId} no encontrado.", 404);
        }

        $stockAnt = (int)$prod['stock'];
        $stockNuevo = match($tipo) {
            'entrada' => $stockAnt + $cantidad,
            'salida'  => max(0, $stockAnt - $cantidad),
            'ajuste'  => $cantidad // En ajuste absoluto, la cantidad es el nuevo stock
        };

        if ($tipo === 'ajuste') {
            $cantidadMov = abs($stockNuevo - $stockAnt);
        } else {
            $cantidadMov = $cantidad;
        }

        // Actualizar stock en productos
        $db->update('productos', $productoId, ['stock' => $stockNuevo]);

        // Registrar en Kardex
        $kardexRes = $db->insert('movimientos_inventario', [
            'producto_id'    => $productoId,
            'tipo'           => $tipo,
            'cantidad'       => $cantidadMov,
            'stock_anterior' => $stockAnt,
            'stock_nuevo'    => $stockNuevo,
            'motivo'         => $motivo
        ]);

        apiResponse(true, [
            'producto_id'    => $productoId,
            'producto_nombre'=> $prod['nombre'],
            'tipo'           => $tipo,
            'cantidad'       => $cantidadMov,
            'stock_anterior' => $stockAnt,
            'stock_nuevo'    => $stockNuevo,
            'motivo'         => $motivo
        ], 'Movimiento de inventario asentado en Kardex con éxito.', 201);
        break;

    default:
        apiError("Método '{$method}' no soportado para inventario.", 405);
}
