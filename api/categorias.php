<?php
/**
 * CRUD de Categorías de Menú - API REST
 * GET    /api/categorias          -> Listar categorías
 * GET    /api/categorias/{id}     -> Detalle de categoría
 * POST   /api/categorias          -> Crear categoría (Requiere rol: admin)
 * PUT    /api/categorias/{id}     -> Actualizar categoría (Requiere rol: admin)
 * DELETE /api/categorias/{id}     -> Eliminar categoría (Requiere rol: admin)
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_middleware.php';

$db = getApiDb();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = getJsonInput();
$idParam = $_GET['id'] ?? ($uriSegments[1] ?? null);

switch ($method) {
    case 'GET':
        if ($idParam !== null && $idParam !== '') {
            $cat = $db->findById('categorias', (int)$idParam);
            if (!$cat) {
                apiError("Categoría con ID '{$idParam}' no encontrada.", 404);
            }
            $prods = $db->selectWhere('productos', ['categoria_id' => (int)$cat['id'], 'activo' => 1]);
            $cat['total_productos'] = count($prods);
            $cat['productos'] = $prods;
            apiResponse(true, $cat, 'Categoría obtenida con éxito.');
        } else {
            $categorias = $db->select('categorias', ['select' => '*', 'order' => 'id.asc']);
            $productos = $db->select('productos');
            
            $conteoPorCat = [];
            foreach ($productos as $p) {
                if ((int)($p['activo'] ?? 1) === 1) {
                    $cId = (int)($p['categoria_id'] ?? 0);
                    $conteoPorCat[$cId] = ($conteoPorCat[$cId] ?? 0) + 1;
                }
            }

            foreach ($categorias as &$c) {
                $c['total_productos'] = $conteoPorCat[(int)$c['id']] ?? 0;
            }

            apiResponse(true, $categorias, 'Categorías de menú obtenidas.', 200, [
                'total' => count($categorias)
            ]);
        }
        break;

    case 'POST':
        ApiAuth::requireRole(['admin']);

        $nombre = trim($input['nombre'] ?? '');
        if (empty($nombre)) {
            apiError('El campo "nombre" de la categoría es obligatorio.', 422);
        }

        $res = $db->insert('categorias', [
            'nombre'      => $nombre,
            'descripcion' => trim($input['descripcion'] ?? ''),
            'icono'       => trim($input['icono'] ?? 'fa-mug-hot'),
            'activo'      => isset($input['activo']) ? (int)$input['activo'] : 1
        ]);

        if (!$res['ok']) {
            apiError('Error al crear categoría: ' . ($res['error'] ?? ''), 500);
        }

        apiResponse(true, $res['data'], 'Categoría creada exitosamente.', 201);
        break;

    case 'PUT':
    case 'PATCH':
        ApiAuth::requireRole(['admin']);

        if (empty($idParam)) {
            apiError('Debes indicar el ID de la categoría a actualizar.', 400);
        }

        $cat = $db->findById('categorias', (int)$idParam);
        if (!$cat) {
            apiError("Categoría '{$idParam}' no encontrada.", 404);
        }

        $id = (int)$cat['id'];
        $cambios = [];
        foreach (['nombre', 'descripcion', 'icono', 'activo'] as $c) {
            if (array_key_exists($c, $input)) {
                $cambios[$c] = ($c === 'activo') ? (int)$input[$c] : trim((string)$input[$c]);
            }
        }

        if (empty($cambios)) {
            apiError('No se indicaron cambios.', 422);
        }

        $res = $db->update('categorias', $id, $cambios);
        if (!$res['ok']) {
            apiError('Error al actualizar: ' . ($res['error'] ?? ''), 500);
        }

        apiResponse(true, $db->findById('categorias', $id), 'Categoría actualizada correctamente.');
        break;

    case 'DELETE':
        ApiAuth::requireRole(['admin']);

        if (empty($idParam)) {
            apiError('Debes indicar el ID de la categoría a eliminar.', 400);
        }

        $cat = $db->findById('categorias', (int)$idParam);
        if (!$cat) {
            apiError("Categoría '{$idParam}' no encontrada.", 404);
        }

        $id = (int)$cat['id'];
        $res = $db->delete('categorias', $id);
        if (!$res['ok']) {
            apiError('No se pudo eliminar categoría: ' . ($res['error'] ?? ''), 500);
        }

        apiResponse(true, ['id' => $id], 'Categoría eliminada.');
        break;

    default:
        apiError("Método '{$method}' no soportado para categorías.", 405);
}
