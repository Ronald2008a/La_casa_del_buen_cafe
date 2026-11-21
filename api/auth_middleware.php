<?php
/**
 * Capa de Autenticación y Autorización por Roles (RBAC) - API REST
 * Soporta tokens Bearer firmados criptográficamente (HMAC SHA-256), API Keys y sesiones activas.
 * La Casa del Buen Café
 */

require_once __DIR__ . '/bootstrap.php';

class ApiAuth {
    private const SECRET_KEY = 'cbc_secret_jwt_key_huila_especialidad_2026';
    private const TOKEN_PREFIX = 'CBC_TOKEN.';

    /**
     * Genera un Token Bearer firmado para el usuario autenticado
     */
    public static function generateToken(array $user, int $ttlSeconds = 86400): string {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        $payload = [
            'uid'    => (int)($user['id'] ?? 0),
            'email'  => strtolower(trim($user['email'] ?? '')),
            'nombre' => $user['nombre'] ?? '',
            'rol'    => strtolower(trim($user['rol'] ?? 'cliente')),
            'iat'    => time(),
            'exp'    => time() + $ttlSeconds
        ];

        $b64Header = self::base64UrlEncode(json_encode($header));
        $b64Payload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$b64Header}.{$b64Payload}", self::SECRET_KEY, true);
        $b64Signature = self::base64UrlEncode($signature);

        return self::TOKEN_PREFIX . "{$b64Header}.{$b64Payload}.{$b64Signature}";
    }

    /**
     * Valida y decodifica un Token Bearer
     */
    public static function verifyToken(string $token): ?array {
        if (str_starts_with($token, self::TOKEN_PREFIX)) {
            $token = substr($token, strlen(self::TOKEN_PREFIX));
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$b64Header, $b64Payload, $b64Signature] = $parts;
        $expectedSignature = hash_hmac('sha256', "{$b64Header}.{$b64Payload}", self::SECRET_KEY, true);
        $providedSignature = self::base64UrlDecode($b64Signature);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null; // Firma inválida o alterada
        }

        $payload = json_decode(self::base64UrlDecode($b64Payload), true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() > $payload['exp']) {
            return null; // Token expirado
        }

        return $payload;
    }

    /**
     * Extrae el usuario actual a partir de cabeceras Authorization, parámetros o sesión web
     */
    public static function getCurrentUser(): ?array {
        // 1. Cabecera HTTP Authorization: Bearer <token>
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] 
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
            ?? (function_exists('apache_request_headers') ? (apache_request_headers()['Authorization'] ?? null) : null);

        if ($authHeader && preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            $payload = self::verifyToken($token);
            if ($payload) {
                return [
                    'id'     => $payload['uid'],
                    'email'  => $payload['email'],
                    'nombre' => $payload['nombre'],
                    'rol'    => $payload['rol'],
                    'origen' => 'Bearer Token'
                ];
            }
        }

        // 2. Parámetro en URL o cuerpo ?token=...
        $tokenParam = $_GET['token'] ?? $_POST['token'] ?? null;
        if ($tokenParam) {
            $payload = self::verifyToken($tokenParam);
            if ($payload) {
                return [
                    'id'     => $payload['uid'],
                    'email'  => $payload['email'],
                    'nombre' => $payload['nombre'],
                    'rol'    => $payload['rol'],
                    'origen' => 'Query Token'
                ];
            }
        }

        // 3. Cookie de sesión de API
        if (!empty($_COOKIE['cbc_api_token'])) {
            $payload = self::verifyToken($_COOKIE['cbc_api_token']);
            if ($payload) {
                return [
                    'id'     => $payload['uid'],
                    'email'  => $payload['email'],
                    'nombre' => $payload['nombre'],
                    'rol'    => $payload['rol'],
                    'origen' => 'Cookie Token'
                ];
            }
        }

        // 4. Sesión PHP Web activa ($_SESSION['user'])
        if (!empty($_SESSION['user']['email'])) {
            return [
                'id'     => (int)($_SESSION['user']['id'] ?? 0),
                'email'  => $_SESSION['user']['email'],
                'nombre' => $_SESSION['user']['nombre'] ?? 'Usuario',
                'rol'    => $_SESSION['user']['rol'] ?? 'cliente',
                'origen' => 'Web Session'
            ];
        }

        return null;
    }

    /**
     * Middleware de Autorización: Exige autenticación y valida rol requerido
     */
    public static function requireRole(array|string|null $allowedRoles = null): array {
        $user = self::getCurrentUser();

        if (!$user) {
            apiError(
                'Acceso no autorizado. Se requiere Token Bearer en cabecera Authorization o inicio de sesión previo.',
                401,
                ['code' => 'UNAUTHORIZED', 'hint' => 'Obtén un token enviando POST a /api/auth/login']
            );
        }

        if ($allowedRoles !== null) {
            $roles = is_array($allowedRoles) ? array_map('strtolower', $allowedRoles) : [strtolower($allowedRoles)];
            $userRole = strtolower($user['rol'] ?? '');

            // El rol 'admin' siempre tiene acceso a todo
            if (!in_array($userRole, $roles, true) && $userRole !== 'admin') {
                apiError(
                    'Permisos insuficientes. Tu rol [' . strtoupper($userRole) . '] no tiene acceso a esta operación.',
                    403,
                    [
                        'code' => 'FORBIDDEN',
                        'user_role' => $userRole,
                        'required_roles' => $roles
                    ]
                );
            }
        }

        return $user;
    }

    /**
     * Comprueba si el usuario autenticado tiene un rol determinado sin interrumpir la ejecución
     */
    public static function hasRole(string $role): bool {
        $user = self::getCurrentUser();
        if (!$user) return false;
        if (($user['rol'] ?? '') === 'admin') return true;
        return strtolower($user['rol'] ?? '') === strtolower($role);
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}
