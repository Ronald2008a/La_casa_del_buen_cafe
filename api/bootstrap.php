<?php
/**
 * Bootstrap Común para la API REST - La Casa del Buen Café
 * Configura CORS, cabeceras JSON, parseo de cuerpo de petición y respuestas estandarizadas.
 */

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    @session_start();
}

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../config/supabase.php';

// 1. Cabeceras CORS Universales y Tipo de Contenido JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key, Accept');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// 2. Manejo de Preflight OPTIONS para navegadores y clientes frontend
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 3. Instancia Global de Base de Datos
function getApiDb(): SupabaseService {
    static $db = null;
    if ($db === null) {
        $db = new SupabaseService();
    }
    return $db;
}

// 4. Lector y Validador de Cuerpo JSON (php://input)
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return $_POST ?: [];
    }
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return array_merge($_POST ?: [], $decoded);
    }
    return $_POST ?: [];
}

// 5. Formateador de Respuesta JSON Estándar RESTful
function apiResponse(
    bool $ok,
    mixed $data = null,
    string $message = '',
    int $statusCode = 200,
    array $meta = []
): void {
    http_response_code($statusCode);
    
    $response = [
        'ok'        => $ok,
        'status'    => $statusCode,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'data'      => $data
    ];

    if (!empty($meta)) {
        $response['meta'] = $meta;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 6. Manejador de Errores Comunes
function apiError(string $message, int $statusCode = 400, mixed $details = null): void {
    apiResponse(false, $details, $message, $statusCode);
}
