<?php
/**
 * CRUD y Programa de Fidelización de Clientes - API REST
 * GET    /api/clientes                 -> Listar clientes (Requiere rol: cajero, admin)
 * GET    /api/clientes/{id}            -> Detalle de cliente por ID o Email
 * POST   /api/clientes                 -> Crear cliente
 * PUT    /api/clientes/{id}            -> Actualizar datos de cliente
 * DELETE /api/clientes/{id}            -> Eliminar cliente (Requiere rol: admin)
 * GET    /api/clientes/{id}/puntos     -> Monedero de puntos, nivel y recompensas
 * POST   /api/clientes/{id}/canjear    -> Redimir puntos por premio
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
            $cliente = is_numeric($idParam) ? $db->findById('clientes', (int)$idParam) : $db->findOneBy('clientes', 'email', strtolower(trim($idParam)));
            if (!$cliente) {
                apiError("Cliente '{$idParam}' no encontrado.", 404);
            }

            // Sub-acción: consultar puntos y recompensas
            if ($subAction === 'puntos' || isset($_GET['puntos'])) {
                $puntos = (int)($cliente['puntos'] ?? 0);
                $nivel = match(true) {
                    $puntos >= 200 => ['nombre' => 'Oro Diamante', 'color' => '#E2AA63', 'beneficio' => '15% de descuento adicional'],
                    $puntos >= 100 => ['nombre' => 'Plata Especial', 'color' => '#94A3B8', 'beneficio' => '10% de descuento adicional'],
                    default        => ['nombre' => 'Bronce Clásico', 'color' => '#CD7F32', 'beneficio' => 'Acumula 1 pt por cada $1.000 COP']
                };

                $recompensas = [
                    [
                        'id' => 'rec-cafe',
                        'nombre' => 'Café de Origen 8oz Gratis',
                        'puntos_requeridos' => 100,
                        'disponible' => ($puntos >= 100)
                    ],
                    [
                        'id' => 'rec-torta',
                        'nombre' => 'Porción de Torta Artesanal o Empanada Gratis',
                        'puntos_requeridos' => 150,
                        'disponible' => ($puntos >= 150)
                    ],
                    [
                        'id' => 'rec-grano',
                        'nombre' => 'Bolsa Café Especial 250g en Grano Gratis',
                        'puntos_requeridos' => 300,
                        'disponible' => ($puntos >= 300)
                    ]
                ];

                apiResponse(true, [
                    'cliente_id'   => (int)$cliente['id'],
                    'nombre'       => $cliente['nombre'],
                    'email'        => $cliente['email'],
                    'puntos'       => $puntos,
                    'nivel'        => $nivel,
                    'recompensas'  => $recompensas
                ], 'Balance de fidelización obtenido.');
            }

            // Cargar historial de compras recientes del cliente
            $pedidos = $db->selectWhere('pedidos', ['cliente_id' => (int)$cliente['id']], ['order' => 'id.desc', 'limit' => 10]);
            $cliente['ultimos_pedidos'] = $pedidos;

            apiResponse(true, $cliente, 'Detalle de cliente recuperado.');
        } else {
            // Listado de clientes (Requiere cajero o admin)
            ApiAuth::requireRole(['cajero', 'admin']);
            $clientes = $db->select('clientes', ['select' => '*', 'order' => 'puntos.desc']);

            apiResponse(true, $clientes, 'Lista de socios del Club de Café.', 200, [
                'total' => count($clientes)
            ]);
        }
        break;

    case 'POST':
        // Sub-acción: Canjear puntos
        if ($subAction === 'canjear' || isset($_GET['canjear'])) {
            $cliente = is_numeric($idParam) ? $db->findById('clientes', (int)$idParam) : $db->findOneBy('clientes', 'email', strtolower(trim($idParam)));
            if (!$cliente) {
                apiError("Cliente '{$idParam}' no encontrado.", 404);
            }

            $puntosACanjear = (int)($input['puntos'] ?? 100);
            $recompensa = trim($input['recompensa'] ?? 'Café de Origen 8oz');
            $puntosActuales = (int)($cliente['puntos'] ?? 0);

            if ($puntosActuales < $puntosACanjear) {
                apiError("Puntos insuficientes. Tienes {$puntosActuales} pts y se requieren {$puntosACanjear} pts.", 400);
            }

            $nuevosPuntos = $puntosActuales - $puntosACanjear;
            $db->update('clientes', (int)$cliente['id'], ['puntos' => $nuevosPuntos]);

            apiResponse(true, [
                'cliente_id'       => (int)$cliente['id'],
                'recompensa'       => $recompensa,
                'puntos_canjeados' => $puntosACanjear,
                'saldo_restante'   => $nuevosPuntos
            ], "¡Felicitaciones! Has canjeado '{$recompensa}' exitosamente.");
        }

        // Crear nuevo cliente
        $nombre = trim($input['nombre'] ?? '');
        $email = trim(strtolower($input['email'] ?? ''));
        $telefono = trim($input['telefono'] ?? '');
        $direccion = trim($input['direccion'] ?? 'Neiva, Huila');
        $puntosIniciales = isset($input['puntos']) ? (int)$input['puntos'] : 50;

        if (empty($nombre) || empty($email)) {
            apiError('Nombre y email son requeridos para registrar cliente.', 422);
        }

        $existente = $db->findOneBy('clientes', 'email', $email);
        if ($existente) {
            apiError("El correo '{$email}' ya está registrado con otro cliente.", 409);
        }

        $res = $db->insert('clientes', [
            'nombre'    => $nombre,
            'email'     => $email,
            'telefono'  => $telefono,
            'direccion' => $direccion,
            'puntos'    => $puntosIniciales
        ]);

        if (!$res['ok']) {
            apiError('Error al crear cliente: ' . ($res['error'] ?? ''), 500);
        }

        apiResponse(true, $res['data'], 'Cliente registrado satisfactoriamente.', 201);
        break;

    case 'PUT':
    case 'PATCH':
        if (empty($idParam)) {
            apiError('Indica el ID del cliente a actualizar.', 400);
        }

        $cliente = is_numeric($idParam) ? $db->findById('clientes', (int)$idParam) : $db->findOneBy('clientes', 'email', strtolower(trim($idParam)));
        if (!$cliente) {
            apiError("Cliente '{$idParam}' no encontrado.", 404);
        }

        $id = (int)$cliente['id'];
        $cambios = [];
        foreach (['nombre', 'telefono', 'direccion', 'puntos'] as $campo) {
            if (array_key_exists($campo, $input)) {
                $cambios[$campo] = ($campo === 'puntos') ? (int)$input[$campo] : trim((string)$input[$campo]);
            }
        }

        if (empty($cambios)) {
            apiError('No se proporcionaron datos para actualizar.', 422);
        }

        $res = $db->update('clientes', $id, $cambios);
        if (!$res['ok']) {
            apiError('Error al actualizar cliente: ' . ($res['error'] ?? ''), 500);
        }

        apiResponse(true, $db->findById('clientes', $id), 'Cliente actualizado correctamente.');
        break;

    case 'DELETE':
        ApiAuth::requireRole(['admin']);

        if (empty($idParam)) {
            apiError('Indica el ID del cliente a eliminar.', 400);
        }

        $cliente = is_numeric($idParam) ? $db->findById('clientes', (int)$idParam) : $db->findOneBy('clientes', 'email', strtolower(trim($idParam)));
        if (!$cliente) {
            apiError("Cliente '{$idParam}' no encontrado.", 404);
        }

        $id = (int)$cliente['id'];
        $res = $db->delete('clientes', $id);
        if (!$res['ok']) {
            apiError('No se pudo eliminar el cliente: ' . ($res['error'] ?? ''), 500);
        }

        apiResponse(true, ['id' => $id, 'email' => $cliente['email']], 'Cliente eliminado del sistema.');
        break;

    default:
        apiError("Método '{$method}' no soportado para clientes.", 405);
}
