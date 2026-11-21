<?php
/**
 * CRUD de Catálogo de Productos e Inventario - API REST
 * GET    /api/productos          -> Listar productos (con filtros y búsqueda)
 * GET    /api/productos/{id}     -> Obtener producto por ID o Código SKU
 * POST   /api/productos          -> Crear producto (Requiere rol: admin)
 * PUT    /api/productos/{id}     -> Actualizar producto completo (Requiere rol: admin)
 * PATCH  /api/productos/{id}     -> Actualización parcial / stock / precio (Requiere rol: admin)
 * DELETE /api/productos/{id}     -> Eliminar producto (Requiere rol: admin)
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_middleware.php';

$db = getApiDb();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = getJsonInput();
$idParam = $_GET['id'] ?? ($uriSegments[1] ?? null);

// Enrutamiento según el método HTTP
switch ($method) {
    case 'GET':
        if ($idParam !== null && $idParam !== '') {
            // Obtener producto individual por ID numérico o Código SKU
            $producto = null;
            if (is_numeric($idParam)) {
                $producto = $db->findById('productos', (int)$idParam);
            }
            if (!$producto) {
                $producto = $db->findOneBy('productos', 'codigo', $idParam);
            }

            if (!$producto) {
                apiError("Producto con identificador '{$idParam}' no encontrado.", 404);
            }

            // Enriquecer con información de categoría
            if (!empty($producto['categoria_id'])) {
                $categoria = $db->findById('categorias', (int)$producto['categoria_id']);
                $producto['categoria_nombre'] = $categoria['nombre'] ?? 'Sin categoría';
            }

            apiResponse(true, $producto, 'Detalle de producto obtenido con éxito.');
        } else {
            // Listar productos con filtros y paginación
            $categoriaId = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;
            $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
            $destacado = isset($_GET['destacado']) ? (int)$_GET['destacado'] : null;
            $soloActivos = isset($_GET['activo']) ? (int)$_GET['activo'] : 1;
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
            $page = max(1, (int)($_GET['page'] ?? 1));

            $todos = $db->select('productos', ['select' => '*', 'order' => 'id.asc']);
            $categorias = $db->select('categorias');
            $mapaCategorias = [];
            foreach ($categorias as $cat) {
                $mapaCategorias[(int)$cat['id']] = $cat['nombre'];
            }

            // Aplicar filtros en memoria
            $filtrados = array_filter($todos, function($p) use ($categoriaId, $search, $destacado, $soloActivos) {
                if ($soloActivos !== null && (int)($p['activo'] ?? 1) !== $soloActivos) {
                    return false;
                }
                if ($categoriaId !== null && (int)($p['categoria_id'] ?? 0) !== $categoriaId) {
                    return false;
                }
                if ($destacado !== null && (int)($p['destacado'] ?? 0) !== $destacado) {
                    return false;
                }
                if (!empty($search)) {
                    $term = mb_strtolower($search, 'UTF-8');
                    $nombre = mb_strtolower($p['nombre'] ?? '', 'UTF-8');
                    $codigo = mb_strtolower($p['codigo'] ?? '', 'UTF-8');
                    $desc = mb_strtolower($p['descripcion'] ?? '', 'UTF-8');
                    if (!str_contains($nombre, $term) && !str_contains($codigo, $term) && !str_contains($desc, $term)) {
                        return false;
                    }
                }
                return true;
            });

            // Enriquecer con nombre de categoría y formatear números
            $resultado = [];
            foreach ($filtrados as $p) {
                $catId = (int)($p['categoria_id'] ?? 0);
                $p['categoria_nombre'] = $mapaCategorias[$catId] ?? 'General';
                $p['precio'] = (float)($p['precio'] ?? 0);
                $p['costo'] = (float)($p['costo'] ?? 0);
                $p['stock'] = (int)($p['stock'] ?? 0);
                $p['stock_minimo'] = (int)($p['stock_minimo'] ?? 5);
                $p['destacado'] = (int)($p['destacado'] ?? 0);
                $p['activo'] = (int)($p['activo'] ?? 1);
                $p['alerta_stock'] = ($p['stock'] <= $p['stock_minimo']);
                $resultado[] = $p;
            }

            $total = count($resultado);
            $offset = ($page - 1) * $limit;
            $itemsPaginados = array_slice($resultado, $offset, $limit);

            apiResponse(true, $itemsPaginados, 'Catálogo de productos obtenido.', 200, [
                'total'        => $total,
                'page'         => $page,
                'limit'        => $limit,
                'total_pages'  => (int)ceil($total / $limit),
                'filtros'      => [
                    'categoria_id' => $categoriaId,
                    'search'       => $search,
                    'destacado'    => $destacado,
                    'activo'       => $soloActivos
                ]
            ]);
        }
        break;

    case 'POST':
        // Crear producto (Requiere Administrador)
        ApiAuth::requireRole(['admin']);

        $nombre = trim($input['nombre'] ?? '');
        $precio = (float)($input['precio'] ?? 0);
        $codigo = trim($input['codigo'] ?? '');

        if (empty($nombre)) {
            apiError('El campo "nombre" del producto es obligatorio.', 422);
        }
        if ($precio <= 0) {
            apiError('El campo "precio" debe ser un número positivo.', 422);
        }

        // Si no se envía código, generar uno autoincremental
        if (empty($codigo)) {
            $codigo = 'PROD-' . strtoupper(substr(md5(uniqid()), 0, 6));
        }

        // Verificar unicidad del código SKU
        $existente = $db->findOneBy('productos', 'codigo', $codigo);
        if ($existente) {
            apiError("Ya existe un producto con el código SKU '{$codigo}'.", 409);
        }

        $stockInicial = (int)($input['stock'] ?? 0);
        $nuevo = [
            'categoria_id' => !empty($input['categoria_id']) ? (int)$input['categoria_id'] : null,
            'codigo'       => $codigo,
            'nombre'       => $nombre,
            'descripcion'  => trim($input['descripcion'] ?? ''),
            'origen'       => trim($input['origen'] ?? 'Huila, Colombia'),
            'tueste'       => trim($input['tueste'] ?? 'Medio'),
            'precio'       => $precio,
            'costo'        => (float)($input['costo'] ?? ($precio * 0.4)),
            'stock'        => $stockInicial,
            'stock_minimo' => (int)($input['stock_minimo'] ?? 5),
            'imagen_url'   => trim($input['imagen_url'] ?? 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=600&q=80'),
            'destacado'    => !empty($input['destacado']) ? 1 : 0,
            'activo'       => isset($input['activo']) ? ((int)$input['activo']) : 1
        ];

        $res = $db->insert('productos', $nuevo);
        if (!$res['ok']) {
            apiError('Error al guardar el producto: ' . ($res['error'] ?? ''), 500);
        }

        $prodId = (int)($res['data']['id'] ?? 0);

        // Si se cargó con stock inicial, registrar en Kardex
        if ($stockInicial > 0 && $prodId > 0) {
            $db->insert('movimientos_inventario', [
                'producto_id'    => $prodId,
                'tipo'           => 'entrada',
                'cantidad'       => $stockInicial,
                'stock_anterior' => 0,
                'stock_nuevo'    => $stockInicial,
                'motivo'         => 'Inventario inicial al crear producto vía API REST'
            ]);
        }

        apiResponse(true, $res['data'], 'Producto creado exitosamente.', 201);
        break;

    case 'PUT':
    case 'PATCH':
        // Actualizar producto (Requiere Administrador)
        ApiAuth::requireRole(['admin']);

        if (empty($idParam)) {
            apiError('Debes especificar el ID del producto en la URL (/api/productos/{id}).', 400);
        }

        $productoActual = is_numeric($idParam) ? $db->findById('productos', (int)$idParam) : $db->findOneBy('productos', 'codigo', $idParam);
        if (!$productoActual) {
            apiError("Producto con ID '{$idParam}' no encontrado.", 404);
        }

        $id = (int)$productoActual['id'];
        $camposPermitidos = [
            'categoria_id', 'codigo', 'nombre', 'descripcion', 'origen', 'tueste',
            'precio', 'costo', 'stock', 'stock_minimo', 'imagen_url', 'destacado', 'activo'
        ];

        $cambios = [];
        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $input)) {
                if (in_array($campo, ['precio', 'costo'], true)) {
                    $cambios[$campo] = (float)$input[$campo];
                } elseif (in_array($campo, ['categoria_id', 'stock', 'stock_minimo', 'destacado', 'activo'], true)) {
                    $cambios[$campo] = $input[$campo] === null ? null : (int)$input[$campo];
                } else {
                    $cambios[$campo] = trim((string)$input[$campo]);
                }
            }
        }

        if (empty($cambios)) {
            apiError('No se enviaron datos para actualizar.', 422);
        }

        // Si se actualizó el stock manualmente, registrar movimiento en Kardex
        if (isset($cambios['stock']) && $cambios['stock'] !== (int)$productoActual['stock']) {
            $stockAnt = (int)$productoActual['stock'];
            $stockNuevo = (int)$cambios['stock'];
            $diff = $stockNuevo - $stockAnt;
            $db->insert('movimientos_inventario', [
                'producto_id'    => $id,
                'tipo'           => $diff >= 0 ? 'ajuste' : 'salida',
                'cantidad'       => abs($diff),
                'stock_anterior' => $stockAnt,
                'stock_nuevo'    => $stockNuevo,
                'motivo'         => trim($input['motivo_ajuste'] ?? 'Ajuste manual de stock vía API REST')
            ]);
        }

        $res = $db->update('productos', $id, $cambios);
        if (!$res['ok']) {
            apiError('Error al actualizar producto: ' . ($res['error'] ?? ''), 500);
        }

        $actualizado = $db->findById('productos', $id);
        apiResponse(true, $actualizado, 'Producto actualizado correctamente.', 200);
        break;

    case 'DELETE':
        // Eliminar producto (Requiere Administrador)
        ApiAuth::requireRole(['admin']);

        if (empty($idParam)) {
            apiError('Debes especificar el ID del producto a eliminar (/api/productos/{id}).', 400);
        }

        $productoActual = is_numeric($idParam) ? $db->findById('productos', (int)$idParam) : $db->findOneBy('productos', 'codigo', $idParam);
        if (!$productoActual) {
            apiError("Producto con ID '{$idParam}' no encontrado.", 404);
        }

        $id = (int)$productoActual['id'];
        $res = $db->delete('productos', $id);
        if (!$res['ok']) {
            apiError('No se pudo eliminar el producto: ' . ($res['error'] ?? ''), 500);
        }

        apiResponse(true, ['id' => $id, 'codigo' => $productoActual['codigo']], 'Producto eliminado satisfactoriamente.', 200);
        break;

    default:
        apiError("Método HTTP '{$method}' no soportado para este endpoint.", 405);
}
