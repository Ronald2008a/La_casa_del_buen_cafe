<?php
/**
 * CRUD y Flujo Operativo de Pedidos & Comandas (POS & KDS) - API REST
 * GET    /api/pedidos               -> Listar pedidos (con filtros por estado, tipo, folio)
 * GET    /api/pedidos/{id}          -> Consultar pedido por ID o Folio (con desglose de ítems)
 * POST   /api/pedidos               -> Crear pedido (descuenta inventario, Kardex, puntos Club)
 * PATCH  /api/pedidos/{id}/estado   -> Cambiar estado de preparación (KDS: pendiente, preparando, completado)
 * DELETE /api/pedidos/{id}          -> Cancelar orden (reintegra existencias a inventario)
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_middleware.php';

$db = getApiDb();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = getJsonInput();
$idParam = $_GET['id'] ?? ($uriSegments[1] ?? null);
$subAction = $_GET['sub'] ?? ($uriSegments[2] ?? null);

switch ($method) {
    case 'GET':
        if ($idParam !== null && $idParam !== '') {
            // Consultar pedido individual por ID numérico o Folio alfanumérico
            $pedido = null;
            if (is_numeric($idParam)) {
                $pedido = $db->findById('pedidos', (int)$idParam);
            }
            if (!$pedido) {
                $pedido = $db->findOneBy('pedidos', 'folio', strtoupper(trim($idParam)));
            }

            if (!$pedido) {
                apiError("Pedido con folio o ID '{$idParam}' no encontrado.", 404);
            }

            // Cargar ítems correspondientes al pedido
            $items = $db->selectWhere('detalle_pedidos', ['pedido_id' => (int)$pedido['id']]);
            $pedido['items'] = $items;
            $pedido['total_items'] = array_sum(array_column($items, 'cantidad'));

            // Si hay cliente vinculado, cargar datos de fidelización
            if (!empty($pedido['cliente_id'])) {
                $cliente = $db->findById('clientes', (int)$pedido['cliente_id']);
                $pedido['cliente'] = $cliente ? [
                    'id'     => $cliente['id'],
                    'nombre' => $cliente['nombre'],
                    'email'  => $cliente['email'],
                    'puntos' => (int)($cliente['puntos'] ?? 0)
                ] : null;
            }

            apiResponse(true, $pedido, 'Pedido consultado exitosamente.');
        } else {
            // Listado de pedidos (con control de acceso por rol)
            $currentUser = ApiAuth::getCurrentUser();
            $estadoFiltro = trim($_GET['estado'] ?? '');
            $tipoFiltro = trim($_GET['tipo'] ?? '');
            $folioFiltro = trim($_GET['folio'] ?? '');
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));

            // Eager-loading de todos los pedidos con sus ítems en 1 o 2 consultas optimizadas
            $pedidos = $db->getOrdersWithDetails($limit * 2);

            // Filtrado
            $resultado = array_filter($pedidos, function($p) use ($currentUser, $estadoFiltro, $tipoFiltro, $folioFiltro) {
                // Si es un cliente común autenticado, solo ve sus propias comandas
                if ($currentUser && ($currentUser['rol'] ?? '') === 'cliente') {
                    $propioId = (int)$currentUser['id'];
                    $propioEmail = strtolower($currentUser['email'] ?? '');
                    $pedidoClienteId = (int)($p['cliente_id'] ?? 0);
                    $clienteObj = $p['cliente_real_nombre'] ?? '';
                    if ($pedidoClienteId !== $propioId) {
                        return false;
                    }
                }

                if (!empty($estadoFiltro) && strtolower($p['estado'] ?? '') !== strtolower($estadoFiltro)) {
                    return false;
                }
                if (!empty($tipoFiltro) && strtolower($p['tipo'] ?? '') !== strtolower($tipoFiltro)) {
                    return false;
                }
                if (!empty($folioFiltro) && !str_contains(strtoupper($p['folio'] ?? ''), strtoupper($folioFiltro))) {
                    return false;
                }
                return true;
            });

            apiResponse(true, array_values(array_slice($resultado, 0, $limit)), 'Listado de pedidos recuperado.', 200, [
                'total'   => count($resultado),
                'limit'   => $limit,
                'filtros' => [
                    'estado' => $estadoFiltro,
                    'tipo'   => $tipoFiltro,
                    'folio'  => $folioFiltro
                ]
            ]);
        }
        break;

    case 'POST':
        // Creación de Pedido / Facturación POS / Venta Web
        $tipo = in_array(strtolower($input['tipo'] ?? ''), ['pos', 'tienda_online'], true) ? strtolower($input['tipo']) : 'tienda_online';
        $metodoPago = in_array(strtolower($input['metodo_pago'] ?? ''), ['efectivo', 'tarjeta', 'transferencia'], true) ? strtolower($input['metodo_pago']) : 'efectivo';
        $clienteNombre = trim($input['cliente_nombre'] ?? 'Cliente Mostrador');
        $clienteEmail = trim(strtolower($input['cliente_email'] ?? ''));
        $clienteTelefono = trim($input['cliente_telefono'] ?? '');
        $direccionEntrega = trim($input['direccion_entrega'] ?? ($tipo === 'pos' ? 'Entrega en barra San Juan Plaza Local 151' : 'Neiva, Huila'));
        $notas = trim($input['notas'] ?? '');
        $items = $input['items'] ?? [];

        if (empty($items) || !is_array($items)) {
            apiError('El pedido debe incluir una lista no vacía de productos en "items".', 422);
        }

        // Determinar o registrar cliente
        $clienteId = !empty($input['cliente_id']) ? (int)$input['cliente_id'] : null;
        if (!$clienteId && !empty($clienteEmail)) {
            $cliExistente = $db->findOneBy('clientes', 'email', $clienteEmail);
            if ($cliExistente) {
                $clienteId = (int)$cliExistente['id'];
                $clienteNombre = $cliExistente['nombre'];
            } else {
                $insCli = $db->insert('clientes', [
                    'nombre'    => $clienteNombre,
                    'email'     => $clienteEmail,
                    'telefono'  => $clienteTelefono,
                    'direccion' => $direccionEntrega,
                    'puntos'    => 50 // Bono de bienvenida
                ]);
                if ($insCli['ok']) {
                    $clienteId = (int)$insCli['data']['id'];
                }
            }
        }

        // Validar existencias de inventario y calcular subtotales
        $todosProductos = $db->select('productos');
        $catalogo = [];
        foreach ($todosProductos as $p) {
            $catalogo[(int)$p['id']] = $p;
        }

        $itemsProcesados = [];
        $subtotal = 0.0;

        foreach ($items as $idx => $it) {
            $pId = (int)($it['producto_id'] ?? $it['id'] ?? 0);
            $cant = max(1, (int)($it['cantidad'] ?? 1));

            if (!isset($catalogo[$pId])) {
                apiError("Producto con ID {$pId} en el índice {$idx} no existe en el catálogo.", 422);
            }

            $prod = $catalogo[$pId];
            if ((int)($prod['activo'] ?? 1) === 0) {
                apiError("El producto '{$prod['nombre']}' se encuentra actualmente pausado o inactivo.", 422);
            }

            // Validar stock disponible
            $stockActual = (int)($prod['stock'] ?? 0);
            if ($stockActual < $cant) {
                apiError("Stock insuficiente para '{$prod['nombre']}'. Disponible: {$stockActual}, solicitado: {$cant}.", 409, [
                    'producto_id'      => $pId,
                    'stock_disponible' => $stockActual,
                    'solicitado'       => $cant
                ]);
            }

            $precioUnit = (float)($it['precio'] ?? $prod['precio']);
            $itemSubtotal = $precioUnit * $cant;
            $subtotal += $itemSubtotal;

            $itemsProcesados[] = [
                'producto_id'     => $pId,
                'nombre_producto' => $prod['nombre'],
                'cantidad'        => $cant,
                'precio_unitario' => $precioUnit,
                'subtotal'        => $itemSubtotal,
                'stock_actual'    => $stockActual
            ];
        }

        $impuestos = 0.0;
        $total = $subtotal + $impuestos;
        $prefijo = ($tipo === 'pos') ? 'POS-' : 'WEB-';
        $folio = $prefijo . strtoupper(substr(md5(uniqid()), 0, 6));
        $estadoInicial = ($tipo === 'pos') ? 'completado' : 'pendiente';

        // 1. Insertar Cabecera de Pedido
        $pedidoRes = $db->insert('pedidos', [
            'folio'             => $folio,
            'cliente_id'        => $clienteId,
            'cliente_nombre'    => $clienteNombre,
            'tipo'              => $tipo,
            'metodo_pago'       => $metodoPago,
            'subtotal'          => $subtotal,
            'impuestos'         => $impuestos,
            'total'             => $total,
            'estado'            => $estadoInicial,
            'direccion_entrega' => $direccionEntrega,
            'notas'             => $notas
        ]);

        if (!$pedidoRes['ok']) {
            apiError('Error al crear el pedido: ' . ($pedidoRes['error'] ?? ''), 500);
        }

        $pedidoId = (int)$pedidoRes['data']['id'];

        // 2. Insertar Detalle de Ítems, descontar Stock y registrar Kardex
        foreach ($itemsProcesados as $itProc) {
            $db->insert('detalle_pedidos', [
                'pedido_id'       => $pedidoId,
                'producto_id'     => $itProc['producto_id'],
                'nombre_producto' => $itProc['nombre_producto'],
                'cantidad'        => $itProc['cantidad'],
                'precio_unitario' => $itProc['precio_unitario'],
                'subtotal'        => $itProc['subtotal']
            ]);

            $stockNuevo = max(0, $itProc['stock_actual'] - $itProc['cantidad']);
            $db->update('productos', $itProc['producto_id'], ['stock' => $stockNuevo]);

            $db->insert('movimientos_inventario', [
                'producto_id'    => $itProc['producto_id'],
                'tipo'           => 'venta',
                'cantidad'       => $itProc['cantidad'],
                'stock_anterior' => $itProc['stock_actual'],
                'stock_nuevo'    => $stockNuevo,
                'motivo'         => "Venta {$tipo} folio {$folio}"
            ]);
        }

        // 3. Otorgar Puntos de Fidelización al Socio (1 punto por cada $1.000 COP)
        $puntosGanados = (int)floor($total / 1000);
        if ($clienteId && $puntosGanados > 0) {
            $cliData = $db->findById('clientes', $clienteId);
            if ($cliData) {
                $puntosActuales = (int)($cliData['puntos'] ?? 0);
                $db->update('clientes', $clienteId, [
                    'puntos' => $puntosActuales + $puntosGanados
                ]);
            }
        }

        $pedidoCompleto = $db->findById('pedidos', $pedidoId);
        $pedidoCompleto['items'] = $itemsProcesados;
        $pedidoCompleto['puntos_ganados'] = $puntosGanados;

        apiResponse(true, $pedidoCompleto, '¡Pedido procesado con éxito!', 201);
        break;

    case 'PATCH':
        // Actualizar Estado de Comanda (KDS / Barra / Caja)
        ApiAuth::requireRole(['barista', 'cajero', 'admin']);

        if (empty($idParam)) {
            apiError('Debes indicar el ID o folio del pedido (/api/pedidos/{id}/estado).', 400);
        }

        $pedido = is_numeric($idParam) ? $db->findById('pedidos', (int)$idParam) : $db->findOneBy('pedidos', 'folio', strtoupper(trim($idParam)));
        if (!$pedido) {
            apiError("Pedido '{$idParam}' no encontrado.", 404);
        }

        $nuevoEstado = strtolower(trim($input['estado'] ?? ''));
        $estadosValidos = ['pendiente', 'preparando', 'completado', 'cancelado'];

        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            apiError("Estado inválido. Los estados permitidos son: " . implode(', ', $estadosValidos), 422);
        }

        $id = (int)$pedido['id'];
        $res = $db->update('pedidos', $id, ['estado' => $nuevoEstado]);
        if (!$res['ok']) {
            apiError('Error al actualizar comanda: ' . ($res['error'] ?? ''), 500);
        }

        apiResponse(true, [
            'id'             => $id,
            'folio'          => $pedido['folio'],
            'estado_anterior'=> $pedido['estado'],
            'nuevo_estado'   => $nuevoEstado
        ], "Comanda actualizada a '{$nuevoEstado}' exitosamente.");
        break;

    case 'DELETE':
        // Cancelar pedido y reintegrar inventario
        ApiAuth::requireRole(['cajero', 'admin']);

        if (empty($idParam)) {
            apiError('Indica el ID o Folio del pedido a cancelar.', 400);
        }

        $pedido = is_numeric($idParam) ? $db->findById('pedidos', (int)$idParam) : $db->findOneBy('pedidos', 'folio', strtoupper(trim($idParam)));
        if (!$pedido) {
            apiError("Pedido '{$idParam}' no encontrado.", 404);
        }

        $id = (int)$pedido['id'];

        // Si el pedido no estaba ya cancelado, devolver productos al stock
        if ($pedido['estado'] !== 'cancelado') {
            $items = $db->selectWhere('detalle_pedidos', ['pedido_id' => $id]);
            foreach ($items as $it) {
                $pId = (int)($it['producto_id'] ?? 0);
                $cant = (int)($it['cantidad'] ?? 0);
                if ($pId > 0 && $cant > 0) {
                    $prod = $db->findById('productos', $pId);
                    if ($prod) {
                        $stockAnt = (int)$prod['stock'];
                        $stockRestaurado = $stockAnt + $cant;
                        $db->update('productos', $pId, ['stock' => $stockRestaurado]);
                        $db->insert('movimientos_inventario', [
                            'producto_id'    => $pId,
                            'tipo'           => 'entrada',
                            'cantidad'       => $cant,
                            'stock_anterior' => $stockAnt,
                            'stock_nuevo'    => $stockRestaurado,
                            'motivo'         => "Reversión por cancelación de pedido {$pedido['folio']}"
                        ]);
                    }
                }
            }
        }

        // Marcar como cancelado
        $db->update('pedidos', $id, ['estado' => 'cancelado']);

        apiResponse(true, [
            'id'     => $id,
            'folio'  => $pedido['folio'],
            'estado' => 'cancelado'
        ], 'Pedido cancelado y existencias reintegradas al inventario.', 200);
        break;

    default:
        apiError("Método HTTP '{$method}' no soportado para pedidos.", 405);
}
