<?php
/**
 * Endpoints de Autenticación y Perfil - API REST
 * POST /api/auth/login     -> Iniciar sesión y obtener Bearer Token
 * POST /api/auth/register  -> Registro de nuevo cliente / socio
 * GET  /api/auth/me        -> Perfil del usuario autenticado
 * POST /api/auth/logout    -> Cierre de sesión y revocación de token
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_middleware.php';

$db = getApiDb();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = getJsonInput();
$action = $_GET['action'] ?? ($uriSegments[1] ?? 'login');

switch ($action) {
    case 'login':
        if ($method !== 'POST') {
            apiError('Método no permitido. Se requiere POST.', 405);
        }

        $email = trim(strtolower($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        $expectedRole = !empty($input['rol']) ? trim(strtolower($input['rol'])) : null;

        if (empty($email)) {
            apiError('El campo "email" es obligatorio.', 422, ['field' => 'email']);
        }

        // Intento de autenticación en la base de datos o almacén
        $authRes = $db->signIn($email, $password, $expectedRole);

        if (!$authRes['ok']) {
            apiError($authRes['error'] ?? 'Credenciales inválidas.', 401, ['email' => $email]);
        }

        $userSession = $_SESSION['user'] ?? [
            'id'     => 1,
            'email'  => $email,
            'nombre' => ucfirst(explode('@', $email)[0]),
            'rol'    => $expectedRole ?: 'cliente'
        ];

        // Generar Token Bearer firmado
        $token = ApiAuth::generateToken($userSession, 86400 * 7); // 7 días de vigencia
        @setcookie('cbc_api_token', $token, [
            'expires'  => time() + (86400 * 7),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        apiResponse(true, [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400 * 7,
            'user'       => [
                'id'        => (int)($userSession['id'] ?? 0),
                'email'     => $userSession['email'],
                'nombre'    => $userSession['nombre'],
                'rol'       => $userSession['rol'],
                'telefono'  => $userSession['telefono'] ?? '',
                'direccion' => $userSession['direccion'] ?? '',
                'puntos'    => (int)($userSession['puntos'] ?? 0)
            ],
            'motor'      => $db->getActiveEngineName()
        ], 'Autenticación exitosa. Token generado correctamente.', 200);
        break;

    case 'register':
        if ($method !== 'POST') {
            apiError('Método no permitido. Se requiere POST.', 405);
        }

        $nombre = trim($input['nombre'] ?? '');
        $email = trim(strtolower($input['email'] ?? ''));
        $telefono = trim($input['telefono'] ?? '');
        $direccion = trim($input['direccion'] ?? 'Neiva, Huila');
        $rol = strtolower(trim($input['rol'] ?? 'cliente'));

        if (empty($nombre) || empty($email)) {
            apiError('Los campos "nombre" y "email" son obligatorios.', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            apiError('El formato de correo electrónico no es válido.', 422);
        }

        // Si es registro de cliente (Club de Fidelización)
        if ($rol === 'cliente') {
            $existente = $db->findOneBy('clientes', 'email', $email);
            if ($existente) {
                apiError('Ya existe un socio registrado con este correo electrónico.', 409);
            }

            $nuevoCliente = [
                'nombre'    => $nombre,
                'email'     => $email,
                'telefono'  => $telefono,
                'direccion' => $direccion,
                'puntos'    => 50 // Bono de bienvenida
            ];

            $res = $db->insert('clientes', $nuevoCliente);
            if (!$res['ok']) {
                apiError('Error al crear el cliente en base de datos: ' . ($res['error'] ?? ''), 500);
            }

            $userCreated = [
                'id'        => (int)($res['data']['id'] ?? 0),
                'nombre'    => $nombre,
                'email'     => $email,
                'telefono'  => $telefono,
                'direccion' => $direccion,
                'puntos'    => 50,
                'rol'       => 'cliente'
            ];

            $token = ApiAuth::generateToken($userCreated);
            apiResponse(true, [
                'token'      => $token,
                'token_type' => 'Bearer',
                'user'       => $userCreated,
                'mensaje_bienvenida' => '¡Bienvenido al Club del Buen Café! Has ganado 50 puntos de bienvenida.'
            ], 'Cliente registrado exitosamente.', 201);
        }

        // Si es registro de personal interno (requiere privilegios de Admin)
        $currentUser = ApiAuth::requireRole(['admin']);
        $password = $input['password'] ?? '123456';
        $resPersonal = $db->signUp($nombre, $email, $password, $rol);

        if (!$resPersonal['ok']) {
            apiError($resPersonal['error'] ?? 'Error al registrar personal.', 500);
        }

        apiResponse(true, [
            'nombre' => $nombre,
            'email'  => $email,
            'rol'    => $rol
        ], 'Usuario operativo registrado con éxito.', 201);
        break;

    case 'me':
        if ($method !== 'GET') {
            apiError('Método no permitido. Se requiere GET.', 405);
        }

        $user = ApiAuth::requireRole(); // Requiere cualquier rol válido
        $detalles = null;

        if ($user['rol'] === 'cliente') {
            $detalles = $db->findOneBy('clientes', 'email', $user['email']);
        } else {
            $detalles = $db->findOneBy('usuarios', 'email', $user['email']);
        }

        apiResponse(true, [
            'user' => [
                'id'        => (int)($detalles['id'] ?? $user['id']),
                'nombre'    => $detalles['nombre'] ?? $user['nombre'],
                'email'     => $detalles['email'] ?? $user['email'],
                'rol'       => $user['rol'],
                'telefono'  => $detalles['telefono'] ?? '',
                'direccion' => $detalles['direccion'] ?? '',
                'puntos'    => (int)($detalles['puntos'] ?? 0),
                'origen_auth' => $user['origen']
            ],
            'permisos' => [
                'pos'        => in_array($user['rol'], ['admin', 'cajero'], true),
                'kds'        => in_array($user['rol'], ['admin', 'cajero', 'barista'], true),
                'inventario' => ($user['rol'] === 'admin'),
                'dashboard'  => ($user['rol'] === 'admin'),
                'pedidos_propios' => true
            ]
        ], 'Perfil obtenido correctamente.');
        break;

    case 'logout':
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['user']);
            session_destroy();
        }
        @setcookie('cbc_api_token', '', [
            'expires' => time() - 3600,
            'path'    => '/'
        ]);
        apiResponse(true, null, 'Sesión finalizada y token revocado correctamente.');
        break;

    default:
        apiError("Ruta de autenticación desconocida: {$action}", 404);
}
