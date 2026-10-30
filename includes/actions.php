<?php
/**
 * Procesador de Acciones POST (Pedidos, POS, Inventario CRUD, Clientes, Auth y Configuración Supabase)
 * La Casa del Buen Café
 */

require_once __DIR__ . '/../config/supabase.php';

$db = new SupabaseService();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirectTab = $_POST['redirect_tab'] ?? 'dashboard';

    switch ($action) {
        // 0. CONFIGURACIÓN Y CONEXIÓN XAMPP MYSQL
        case 'save_mysql_config':
            $host = trim($_POST['mysql_host'] ?? '127.0.0.1');
            $port = trim($_POST['mysql_port'] ?? '3306');
            $database = trim($_POST['mysql_database'] ?? 'la_casa_del_buen_cafe');
            $user = trim($_POST['mysql_user'] ?? 'root');
            $pass = $_POST['mysql_password'] ?? '';

            if ($db->saveMySQLConfig($host, $port, $database, $user, $pass)) {
                $init = $db->initializeMySQLSchema();
                setFlash('success', "¡Conectado exitosamente a XAMPP MySQL ({$database})! " . ($init['message'] ?? ''));
            } else {
                setFlash('error', 'No se pudo conectar a XAMPP MySQL. Verifica que el servicio MySQL esté iniciado en XAMPP Control Panel. Detalle: ' . $db->getMySQLError());
            }
            header('Location: index.php?tab=configuracion');
            exit;

        case 'init_mysql_schema':
            if ($db->connectMySQL()) {
                $res = $db->initializeMySQLSchema();
                setFlash($res['ok'] ? 'success' : 'error', $res['message']);
            } else {
                setFlash('error', 'El servicio MySQL de XAMPP no responde: ' . $db->getMySQLError());
            }
            header('Location: index.php?tab=configuracion');
            exit;

        // 1. CONFIGURACIÓN DE SUPABASE
        case 'save_supabase_config':
            $url = trim($_POST['supabase_url'] ?? '');
            $key = trim($_POST['supabase_key'] ?? '');
            if (empty($url) || empty($key)) {
                setFlash('error', 'Por favor ingresa tanto el Project URL como el API Key de Supabase.');
            } else {
                $db->saveCredentials($url, $key);
                $test = $db->testConnection();
                if ($test['ok']) {
                    setFlash('success', '¡Conectado exitosamente a Supabase PostgreSQL! (' . $test['message'] . ')');
                } else {
                    setFlash('warning', 'Credenciales guardadas. Nota: ' . $test['message']);
                }
            }
            header('Location: index.php?tab=configuracion');
            exit;

        case 'disconnect_supabase':
            $db->disconnect();
            setFlash('info', 'Se desconectó Supabase. Ahora trabajando en Modo Local de Respaldo.');
            header('Location: index.php?tab=configuracion');
            exit;

        // 2. AUTENTICACIÓN SUPABASE AUTH / SESIÓN
        case 'login':
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $res = $db->signIn($email, $password);
            if ($res['ok']) {
                setFlash('success', 'Bienvenido(a) a La Casa del Buen Café, ' . ($_SESSION['user']['nombre'] ?? $email));
                header('Location: index.php?tab=dashboard');
            } else {
                setFlash('error', $res['error'] ?? 'Error al iniciar sesión');
                header('Location: index.php?tab=login');
            }
            exit;

        case 'register':
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rol = $_POST['rol'] ?? 'admin';
            $res = $db->signUp($nombre, $email, $password, $rol);
            if ($res['ok']) {
                setFlash('success', $res['message'] ?? 'Cuenta registrada con éxito.');
                header('Location: index.php?tab=dashboard');
            } else {
                setFlash('error', $res['error'] ?? 'No se pudo registrar el usuario');
                header('Location: index.php?tab=login');
            }
            exit;

        case 'logout':
            unset($_SESSION['user'], $_SESSION['supabase_access_token']);
            setFlash('info', 'Sesión cerrada correctamente.');
            header('Location: index.php?tab=tienda');
            exit;

        // 3. CREAR PEDIDO (DESDE POS O TIENDA EN LÍNEA)
        case 'create_order':
            $tipo = $_POST['tipo'] ?? 'pos';
            $clienteId = (int)($_POST['cliente_id'] ?? 1);
            $clienteNombre = trim($_POST['cliente_nombre'] ?? 'Cliente Mostrador');
            $metodoPago = $_POST['metodo_pago'] ?? 'efectivo';
            $direccion = trim($_POST['direccion_entrega'] ?? ($tipo === 'pos' ? 'Entrega en barra' : ''));
            $notas = trim($_POST['notas'] ?? '');
            $itemsJson = $_POST['items_json'] ?? '[]';
            $items = json_decode($itemsJson, true);

            if (empty($items) || !is_array($items)) {
                setFlash('error', 'El carrito está vacío. Agrega al menos un producto.');
                header('Location: index.php?tab=' . urlencode($redirectTab));
                exit;
            }

            // Obtener cliente si se seleccionó ID
            $clientes = $db->select('clientes');
            foreach ($clientes as $c) {
                if ((int)$c['id'] === $clienteId && empty($_POST['cliente_nombre'])) {
                    $clienteNombre = $c['nombre'];
                    break;
                }
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += ((float)$item['precio'] * (int)$item['cantidad']);
            }
            $impuestos = 0; // Precios con IVA incluido
            $total = $subtotal + $impuestos;
            $prefijo = ($tipo === 'pos') ? 'POS-' : 'WEB-';
            $folio = $prefijo . rand(1000, 9999);

            $estadoInicial = ($tipo === 'pos') ? 'completado' : 'pendiente';

            $pedidoRes = $db->insert('pedidos', [
                'folio' => $folio,
                'cliente_id' => $clienteId > 0 ? $clienteId : null,
                'cliente_nombre' => $clienteNombre,
                'tipo' => $tipo,
                'metodo_pago' => $metodoPago,
                'subtotal' => $subtotal,
                'impuestos' => $impuestos,
                'total' => $total,
                'estado' => $estadoInicial,
                'direccion_entrega' => $direccion,
                'notas' => $notas
            ]);

            if ($pedidoRes['ok']) {
                $pedidoId = (int)($pedidoRes['data']['id'] ?? rand(100, 999));
                $productosActuales = $db->select('productos');
                $mapaProductos = [];
                foreach ($productosActuales as $p) {
                    $mapaProductos[(int)$p['id']] = $p;
                }

                foreach ($items as $item) {
                    $prodId = (int)$item['id'];
                    $cant = (int)$item['cantidad'];
                    $precioUnit = (float)$item['precio'];

                    $db->insert('detalle_pedidos', [
                        'pedido_id' => $pedidoId,
                        'producto_id' => $prodId,
                        'nombre_producto' => $item['nombre'],
                        'cantidad' => $cant,
                        'precio_unitario' => $precioUnit,
                        'subtotal' => $precioUnit * $cant
                    ]);

                    // Descontar stock y registrar en kardex
                    if (isset($mapaProductos[$prodId])) {
                        $stockAnt = (int)$mapaProductos[$prodId]['stock'];
                        $stockNuevo = max(0, $stockAnt - $cant);
                        $db->update('productos', $prodId, ['stock' => $stockNuevo]);
                        $db->insert('movimientos_inventario', [
                            'producto_id' => $prodId,
                            'tipo' => 'venta',
                            'cantidad' => $cant,
                            'stock_anterior' => $stockAnt,
                            'stock_nuevo' => $stockNuevo,
                            'motivo' => 'Venta ' . $folio
                        ]);
                    }
                }

                // Sumar puntos de fidelidad (1 punto por cada $1.000 COP)
                if ($clienteId > 1) {
                    foreach ($clientes as $c) {
                        if ((int)$c['id'] === $clienteId) {
                            $nuevosPuntos = ((int)($c['puntos'] ?? 0)) + (int)floor($total / 1000);
                            $db->update('clientes', $clienteId, ['puntos' => $nuevosPuntos]);
                            break;
                        }
                    }
                }

                $_SESSION['last_receipt'] = [
                    'folio' => $folio,
                    'fecha' => date('d/m/Y H:i'),
                    'cliente' => $clienteNombre,
                    'tipo' => $tipo,
                    'metodo_pago' => $metodoPago,
                    'items' => $items,
                    'total' => $total,
                    'notas' => $notas
                ];

                setFlash('success', "¡Pedido {$folio} registrado con éxito por $" . number_format($total, 0, ',', '.') . "!");
            } else {
                setFlash('error', 'Error al guardar el pedido: ' . ($pedidoRes['error'] ?? 'Desconocido'));
            }

            header('Location: index.php?tab=' . urlencode($redirectTab));
            exit;

        // 4. ACTUALIZAR ESTADO DE PEDIDO
        case 'update_order_status':
            $pedidoId = (int)($_POST['pedido_id'] ?? 0);
            $nuevoEstado = $_POST['estado'] ?? 'completado';
            if ($pedidoId > 0) {
                $db->update('pedidos', $pedidoId, ['estado' => $nuevoEstado]);
                setFlash('success', "Estado del pedido #{$pedidoId} actualizado a '" . strtoupper($nuevoEstado) . "'.");
            }
            header('Location: index.php?tab=pedidos');
            exit;

        // 5. CRUD DE PRODUCTOS (INVENTARIO)
        case 'save_product':
            $id = (int)($_POST['id'] ?? 0);
            $data = [
                'categoria_id' => (int)($_POST['categoria_id'] ?? 1),
                'codigo' => strtoupper(trim($_POST['codigo'] ?? ('CAF-' . rand(100, 999)))),
                'nombre' => trim($_POST['nombre'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'origen' => trim($_POST['origen'] ?? 'Huila, Colombia'),
                'tueste' => trim($_POST['tueste'] ?? 'Medio'),
                'precio' => (float)($_POST['precio'] ?? 0),
                'costo' => (float)($_POST['costo'] ?? 0),
                'stock' => (int)($_POST['stock'] ?? 0),
                'stock_minimo' => (int)($_POST['stock_minimo'] ?? 5),
                'imagen_url' => trim($_POST['imagen_url'] ?? 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80'),
                'destacado' => isset($_POST['destacado']),
                'activo' => true
            ];

            if ($id > 0) {
                $res = $db->update('productos', $id, $data);
                setFlash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Producto actualizado correctamente.' : ($res['error'] ?? 'Error'));
            } else {
                $res = $db->insert('productos', $data);
                setFlash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Nuevo producto agregado al catálogo.' : ($res['error'] ?? 'Error'));
            }
            header('Location: index.php?tab=inventario');
            exit;

        case 'delete_product':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->delete('productos', $id);
                setFlash('info', 'Producto eliminado del catálogo.');
            }
            header('Location: index.php?tab=inventario');
            exit;

        case 'adjust_stock':
            $productoId = (int)($_POST['producto_id'] ?? 0);
            $tipoMov = $_POST['tipo_movimiento'] ?? 'entrada';
            $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
            $motivo = trim($_POST['motivo'] ?? 'Ajuste manual de inventario');

            $productos = $db->select('productos');
            foreach ($productos as $p) {
                if ((int)$p['id'] === $productoId) {
                    $stockAnt = (int)$p['stock'];
                    if ($tipoMov === 'entrada') {
                        $stockNuevo = $stockAnt + $cantidad;
                    } elseif ($tipoMov === 'salida') {
                        $stockNuevo = max(0, $stockAnt - $cantidad);
                    } else {
                        $stockNuevo = $cantidad; // ajuste exacto
                    }

                    $db->update('productos', $productoId, ['stock' => $stockNuevo]);
                    $db->insert('movimientos_inventario', [
                        'producto_id' => $productoId,
                        'tipo' => $tipoMov,
                        'cantidad' => $cantidad,
                        'stock_anterior' => $stockAnt,
                        'stock_nuevo' => $stockNuevo,
                        'motivo' => $motivo
                    ]);
                    setFlash('success', "Stock de '{$p['nombre']}' actualizado de {$stockAnt} a {$stockNuevo} unidades.");
                    break;
                }
            }
            header('Location: index.php?tab=inventario');
            exit;

        // 6. CRUD DE CATEGORÍAS
        case 'save_category':
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $icono = trim($_POST['icono'] ?? 'fa-mug-hot');
            if (!empty($nombre)) {
                $db->insert('categorias', [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'icono' => $icono,
                    'activo' => true
                ]);
                setFlash('success', "Categoría '{$nombre}' creada.");
            }
            header('Location: index.php?tab=inventario');
            exit;

        // 7. CRUD DE CLIENTES
        case 'save_client':
            $id = (int)($_POST['id'] ?? 0);
            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
                'puntos' => (int)($_POST['puntos'] ?? 0)
            ];
            if ($id > 0) {
                $res = $db->update('clientes', $id, $data);
                setFlash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Cliente actualizado.' : ($res['error'] ?? 'Error'));
            } else {
                $res = $db->insert('clientes', $data);
                setFlash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Cliente registrado exitosamente.' : ($res['error'] ?? 'Error'));
            }
            header('Location: index.php?tab=clientes');
            exit;

        case 'delete_client':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 1) {
                $db->delete('clientes', $id);
                setFlash('info', 'Cliente eliminado.');
            } else {
                setFlash('warning', 'El Cliente Mostrador predeterminado no se puede eliminar.');
            }
            header('Location: index.php?tab=clientes');
            exit;
    }
}

