<?php
/**
 * Router Central / Front Controller - API REST
 * La Casa del Buen Café
 * 
 * Enruta peticiones hacia los controladores correspondientes:
 *  - /api/auth/*         -> api/auth.php
 *  - /api/productos/*    -> api/productos.php
 *  - /api/pedidos/*      -> api/pedidos.php
 *  - /api/clientes/*     -> api/clientes.php
 *  - /api/categorias/*   -> api/categorias.php
 *  - /api/inventario/*   -> api/inventario.php
 *  - /api/stats/*        -> api/stats.php
 *  - /api (o /api/info)  -> Catálogo y OpenAPI Specification
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_middleware.php';

// 1. Extraer la ruta solicitada
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Obtener la parte después de /api/
$path = '';
if (!empty($_GET['endpoint'])) {
    $path = trim($_GET['endpoint'], '/');
} else {
    $uriPath = parse_url($requestUri, PHP_URL_PATH);
    $apiPos = strpos($uriPath, '/api');
    if ($apiPos !== false) {
        $path = substr($uriPath, $apiPos + 4);
    }
    $path = trim($path, '/');
    if (str_starts_with($path, 'index.php')) {
        $path = trim(substr($path, 9), '/');
    }
}

$segments = array_values(array_filter(explode('/', $path)));
$resource = strtolower($segments[0] ?? '');
$globalParam = $segments[1] ?? null;
$subParam = $segments[2] ?? null;

// Exponer variables a los controladores incluidos
$uriSegments = $segments;
if ($globalParam !== null) {
    if (!isset($_GET['id'])) $_GET['id'] = $globalParam;
    if (!isset($_GET['action'])) $_GET['action'] = $globalParam;
    if (!isset($_GET['sub'])) $_GET['sub'] = $globalParam;
}
if ($subParam !== null) {
    if (!isset($_GET['sub'])) $_GET['sub'] = $subParam;
}

// 2. Ruta Raíz: Información del Sistema y Especificación de la API
if (empty($resource) || $resource === 'info' || $resource === 'openapi.json') {
    $db = getApiDb();
    $currentUser = ApiAuth::getCurrentUser();

    $apiSpec = [
        'nombre'      => 'API RESTful Oficial — La Casa del Buen Café',
        'version'     => '2.5.0-dev',
        'estandar'    => 'RESTful JSON / OpenAPI 3.0 Compatible',
        'ubicacion'   => 'Calle 26 # 16-24 Centro Comercial San Juan Plaza Local 151 Primer Piso, Neiva-Huila',
        'servidor'    => [
            'motor_db'   => $db->getActiveEngineName(),
            'php_version'=> PHP_VERSION,
            'hora_local' => date('Y-m-d H:i:s'),
            'docs_url'   => 'http://localhost/La_casa_del_buen_cafe/api/docs.php'
        ],
        'usuario_autenticado' => $currentUser ?: 'Invitado Público',
        'modulos_disponibles' => [
            'autenticacion' => [
                'base'      => '/api/auth',
                'endpoints' => [
                    'POST /api/auth/login'    => 'Autenticación multirrol (admin, cajero, barista, cliente) -> Retorna Bearer Token',
                    'POST /api/auth/register' => 'Registro de nuevos socios (con 50 pts gratis) o personal interno',
                    'GET  /api/auth/me'       => 'Perfil y permisos del usuario autenticado vía Bearer Token',
                    'POST /api/auth/logout'   => 'Cierre de sesión y revocación'
                ]
            ],
            'productos' => [
                'base'      => '/api/productos',
                'endpoints' => [
                    'GET    /api/productos'      => 'Listar productos con filtros (categoria_id, search, activo, limit, page)',
                    'GET    /api/productos/{id}' => 'Detalle de producto por ID o código SKU',
                    'POST   /api/productos'      => 'Crear producto (Requiere admin)',
                    'PUT    /api/productos/{id}' => 'Actualizar producto (Requiere admin)',
                    'PATCH  /api/productos/{id}' => 'Actualización parcial / stock (Requiere admin)',
                    'DELETE /api/productos/{id}' => 'Eliminar producto (Requiere admin)'
                ]
            ],
            'pedidos' => [
                'base'      => '/api/pedidos',
                'endpoints' => [
                    'GET    /api/pedidos'             => 'Listar pedidos (con filtros por estado, tipo, folio y carga de ítems)',
                    'GET    /api/pedidos/{id}'        => 'Consultar pedido individual por ID o Folio (ORD-XXXX, POS-XXXX)',
                    'POST   /api/pedidos'             => 'Crear orden (valida stock, descuenta inventario, genera Kardex, puntos)',
                    'PATCH  /api/pedidos/{id}/estado' => 'Actualizar comanda KDS (pendiente -> preparando -> completado)',
                    'DELETE /api/pedidos/{id}'        => 'Cancelar pedido y reponer existencias en stock'
                ]
            ],
            'clientes' => [
                'base'      => '/api/clientes',
                'endpoints' => [
                    'GET    /api/clientes'              => 'Listado de socios del Club de Fidelización (cajero, admin)',
                    'GET    /api/clientes/{id}'         => 'Detalle de cliente y sus compras',
                    'POST   /api/clientes'              => 'Registrar nuevo cliente',
                    'PUT    /api/clientes/{id}'         => 'Actualizar datos de cliente',
                    'DELETE /api/clientes/{id}'         => 'Eliminar cliente (admin)',
                    'GET    /api/clientes/{id}/puntos'  => 'Consultar saldo de puntos, nivel y recompensas',
                    'POST   /api/clientes/{id}/canjear' => 'Canjear puntos por café de cortesía o repostería'
                ]
            ],
            'categorias' => [
                'base'      => '/api/categorias',
                'endpoints' => [
                    'GET    /api/categorias'      => 'Listar familias de productos con conteo de activos',
                    'GET    /api/categorias/{id}' => 'Detalle de categoría y sus productos asociados',
                    'POST   /api/categorias'      => 'Crear categoría (admin)',
                    'PUT    /api/categorias/{id}' => 'Actualizar categoría (admin)',
                    'DELETE /api/categorias/{id}' => 'Eliminar categoría (admin)'
                ]
            ],
            'inventario' => [
                'base'      => '/api/inventario',
                'endpoints' => [
                    'GET  /api/inventario'             => 'Resumen de stock, alertas de punto de reorden y valuación COP (admin)',
                    'GET  /api/inventario/kardex'      => 'Historial inmutable de movimientos de almacén (admin)',
                    'POST /api/inventario/movimientos' => 'Registrar ajuste manual (entrada, salida, merma) con Kardex (admin)'
                ]
            ],
            'estadisticas' => [
                'base'      => '/api/stats',
                'endpoints' => [
                    'GET /api/stats/dashboard' => 'KPIs de ventas, ventas hoy, ticket promedio y Top 5 productos (admin)',
                    'GET /api/stats/kds'       => 'Cola en tiempo real de cocina y barra (barista, cajero, admin)'
                ]
            ]
        ]
    ];

    apiResponse(true, $apiSpec, 'Bienvenido a la API REST de La Casa del Buen Café.');
}

// 3. Despacho a Controladores Modulares
switch ($resource) {
    case 'auth':
        require __DIR__ . '/auth.php';
        break;

    case 'productos':
        require __DIR__ . '/productos.php';
        break;

    case 'pedidos':
        require __DIR__ . '/pedidos.php';
        break;

    case 'clientes':
        require __DIR__ . '/clientes.php';
        break;

    case 'categorias':
        require __DIR__ . '/categorias.php';
        break;

    case 'inventario':
        require __DIR__ . '/inventario.php';
        break;

    case 'stats':
        require __DIR__ . '/stats.php';
        break;

    default:
        apiError("Recurso '{$resource}' no encontrado en la API REST.", 404, [
            'recursos_validos' => ['auth', 'productos', 'pedidos', 'clientes', 'categorias', 'inventario', 'stats'],
            'docs_url' => 'http://localhost/La_casa_del_buen_cafe/api/docs.php'
        ]);
}
