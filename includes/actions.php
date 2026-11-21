<?php
/**
 * Procesador Central de Acciones POST
 * Compartido entre:
 *  - Sitio 1: Tienda Pública de Clientes (index.php)
 *  - Sitio 2: Portal Interno Admin / Trabajador (admin.php)
 */

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/job_queue.php';

$db = new SupabaseService();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function appUrl(string $path = ''): string {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = preg_replace('#[/\\\\]includes$#i', '', $scriptDir);
    $base = rtrim($base, '/\\');
    return ($base ?: '/La_casa_del_buen_cafe') . '/' . ltrim($path, '/');
}

function redirectToPortal(string $tab = 'dashboard', string $portal = 'admin.php'): void {
    if ($portal === 'index.php') {
        header('Location: ' . appUrl('index.php' . ($tab ? '#' . urlencode($tab) : '')));
    } else {
        header('Location: ' . appUrl('admin.php?tab=' . urlencode($tab)));
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirectTab = $_POST['redirect_tab'] ?? 'dashboard';
    $sourcePortal = $_POST['source_portal'] ?? 'admin.php';

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
            redirectToPortal('configuracion', 'admin.php');

        case 'init_mysql_schema':
            if ($db->connectMySQL()) {
                $res = $db->initializeMySQLSchema();
                setFlash($res['ok'] ? 'success' : 'error', $res['message']);
            } else {
                setFlash('error', 'El servicio MySQL de XAMPP no responde: ' . $db->getMySQLError());
            }
            redirectToPortal('configuracion', 'admin.php');

        // 1. AUTENTICACIÓN POR ROL INDEPENDIENTE (ADMIN, CAJERO, BARISTA, CLIENTE)
        case 'login':
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $portalRol = trim($_POST['portal_rol'] ?? $_POST['quick_role'] ?? '');
            $returnTo = trim($_POST['return_to'] ?? '');

            $res = $db->signIn($email, $password, $portalRol ?: null);
            if ($res['ok']) {
                $rolActual = $_SESSION['user']['rol'] ?? 'admin';
                $nombreUser = $_SESSION['user']['nombre'] ?? $email;

                if ($rolActual === 'cliente') {
                    setFlash('success', '¡Hola, ' . htmlspecialchars($nombreUser) . '! Bienvenido a tu Portal Especial de Clientes.');
                    header('Location: ' . appUrl('portal_clientes.php'));
                    exit;
                }

                $tabDestino = match($rolActual) {
                    'admin'   => 'dashboard',
                    'cajero'  => 'pos',
                    'barista' => 'pedidos',
                    default   => 'pos'
                };

                $rolNombres = [
                    'admin'   => 'Administrador General',
                    'cajero'  => 'Cajero de Turno / POS',
                    'barista' => 'Empleado / Barista'
                ];
                setFlash('success', 'Bienvenido(a), ' . $nombreUser . ' [' . ($rolNombres[$rolActual] ?? strtoupper($rolActual)) . ']');
                header('Location: ' . appUrl('portal_privado.php?tab=' . $tabDestino));
                exit;
            } else {
                setFlash('error', $res['error'] ?? 'Error al iniciar sesión');
                if (!empty($returnTo)) {
                    header('Location: ' . $returnTo);
                    exit;
                }
                $redirectPage = match($portalRol) {
                    'admin'   => 'login_admin.php',
                    'cajero'  => 'login_cajero.php',
                    'barista' => 'login_barista.php',
                    'cliente' => 'login_cliente.php',
                    default   => 'login.php'
                };
                header('Location: ' . appUrl($redirectPage));
                exit;
            }

        case 'register':
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rol = $_POST['rol'] ?? 'cajero';
            $res = $db->signUp($nombre, $email, $password, $rol);
            if ($res['ok']) {
                setFlash('success', $res['message'] ?? 'Personal registrado con éxito.');
                redirectToPortal('dashboard', 'admin.php');
            } else {
                setFlash('error', $res['error'] ?? 'No se pudo registrar el usuario');
                redirectToPortal('configuracion', 'admin.php');
            }

        case 'logout':
            $rolAnterior = $_SESSION['user']['rol'] ?? '';
            unset($_SESSION['user']);
            setFlash('info', 'Has cerrado tu sesión de forma segura.');
            if ($rolAnterior === 'cliente') {
                header('Location: ' . appUrl('portal_clientes.php'));
            } else {
                header('Location: ' . appUrl('portal_privado.php'));
            }
            exit;

        // 3. CREAR PEDIDO (DESDE TIENDA PÚBLICA DE CLIENTES O DESDE POS ADMIN)
        case 'create_order':
            $tipo = $_POST['tipo'] ?? 'pos';
            $clienteId = (int)($_POST['cliente_id'] ?? 1);
            $clienteNombre = trim($_POST['cliente_nombre'] ?? 'Cliente Mostrador');
            $clienteTelefono = trim($_POST['cliente_telefono'] ?? '');
            $clienteEmail = trim($_POST['cliente_email'] ?? '');
            $metodoPago = $_POST['metodo_pago'] ?? 'efectivo';
            $direccion = trim($_POST['direccion_entrega'] ?? ($tipo === 'pos' ? 'Entrega en barra' : ''));
            $notas = trim($_POST['notas'] ?? '');
            $itemsJson = $_POST['items_json'] ?? '[]';
            $items = json_decode($itemsJson, true);

            if (empty($items) || !is_array($items)) {
                setFlash('error', 'Tu carrito está vacío. Agrega al menos un producto.');
                redirectToPortal($redirectTab, $sourcePortal);
            }

            $clientes = $db->select('clientes');

            // Si viene de la tienda pública y dejó correo/teléfono, buscar o crear cliente automáticamente
            if ($tipo === 'tienda_online' && !empty($clienteNombre) && $clienteId <= 1) {
                $encontrado = false;
                if (!empty($clienteEmail)) {
                    foreach ($clientes as $c) {
                        if (strtolower($c['email'] ?? '') === strtolower($clienteEmail)) {
                            $clienteId = (int)$c['id'];
                            $encontrado = true;
                            break;
                        }
                    }
                }
                if (!$encontrado && !empty($clienteEmail)) {
                    $nuevoCli = $db->insert('clientes', [
                        'nombre' => $clienteNombre,
                        'email' => $clienteEmail,
                        'telefono' => $clienteTelefono,
                        'direccion' => $direccion,
                        'puntos' => 0
                    ]);
                    if ($nuevoCli['ok'] && !empty($nuevoCli['data']['id'])) {
                        $clienteId = (int)$nuevoCli['data']['id'];
                        $clientes = $db->select('clientes');
                    }
                }
            } else {
                foreach ($clientes as $c) {
                    if ((int)$c['id'] === $clienteId && empty($_POST['cliente_nombre'])) {
                        $clienteNombre = $c['nombre'];
                        break;
                    }
                }
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += ((float)$item['precio'] * (int)$item['cantidad']);
            }
            $impuestos = 0;
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

                $puntosGanados = (int)floor($total / 1000);
                if ($clienteId > 1) {
                    foreach ($clientes as $c) {
                        if ((int)$c['id'] === $clienteId) {
                            $nuevosPuntos = ((int)($c['puntos'] ?? 0)) + $puntosGanados;
                            $db->update('clientes', $clienteId, ['puntos' => $nuevosPuntos]);
                            break;
                        }
                    }
                }

                // Enviar tareas secundarias y notificaciones a la cola de trabajos asíncrona
                JobQueue::push('notify_order', [
                    'folio' => $folio,
                    'cliente' => $clienteNombre,
                    'email' => $clienteEmail,
                    'total' => $total,
                    'items_count' => count($items)
                ]);
                JobQueue::push('refresh_sales_cache', [
                    'pedido_id' => $pedidoId,
                    'total' => $total
                ]);
                JobQueue::processPending(2);

                $_SESSION['last_receipt'] = [
                    'folio' => $folio,
                    'fecha' => date('d/m/Y H:i'),
                    'cliente' => $clienteNombre,
                    'direccion' => $direccion,
                    'tipo' => $tipo,
                    'metodo_pago' => $metodoPago,
                    'items' => $items,
                    'total' => $total,
                    'puntos_ganados' => $puntosGanados,
                    'notas' => $notas
                ];

                setFlash('success', "¡Pedido {$folio} confirmado con éxito por $" . number_format($total, 0, ',', '.') . "!");
            } else {
                setFlash('error', 'Error al guardar el pedido: ' . ($pedidoRes['error'] ?? 'Desconocido'));
            }

            redirectToPortal($redirectTab, $sourcePortal);

        // 4. ACTUALIZAR ESTADO DE PEDIDO
        case 'update_order_status':
            $pedidoId = (int)($_POST['pedido_id'] ?? 0);
            $nuevoEstado = $_POST['estado'] ?? 'completado';
            if ($pedidoId > 0) {
                $db->update('pedidos', $pedidoId, ['estado' => $nuevoEstado]);
                setFlash('success', "Estado de la comanda #{$pedidoId} actualizado a '" . strtoupper($nuevoEstado) . "'.");
            }
            redirectToPortal('pedidos', 'admin.php');

        // 5. CRUD DE PRODUCTOS (INVENTARIO)
        case 'save_product':
            $id = (int)($_POST['id'] ?? 0);
            $imagenUrl = trim($_POST['imagen_url'] ?? '');

            // Si el usuario seleccionó una foto desde su galería / PC
            if (!empty($_FILES['imagen_archivo']['tmp_name']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['imagen_archivo']['tmp_name'];
                $origName = $_FILES['imagen_archivo']['name'] ?? 'foto.jpg';
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $ext = 'jpg';
                }
                $fileName = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;

                $dirHtdocs = 'C:/xampp/htdocs/La_casa_del_buen_cafe/uploads';
                $dirLocal  = __DIR__ . '/../uploads';

                if (!is_dir($dirHtdocs)) {
                    @mkdir($dirHtdocs, 0777, true);
                }
                if (!is_dir($dirLocal)) {
                    @mkdir($dirLocal, 0777, true);
                }

                $destHtdocs = $dirHtdocs . '/' . $fileName;
                $destLocal  = $dirLocal . '/' . $fileName;

                if (@move_uploaded_file($tmpPath, $destHtdocs)) {
                    @copy($destHtdocs, $destLocal);
                    $imagenUrl = 'uploads/' . $fileName;
                } elseif (@move_uploaded_file($tmpPath, $destLocal)) {
                    @copy($destLocal, $destHtdocs);
                    $imagenUrl = 'uploads/' . $fileName;
                }
            }

            if (empty($imagenUrl) || strpos($imagenUrl, 'blob:') === 0) {
                $imagenUrl = 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80';
            }

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
                'imagen_url' => $imagenUrl,
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
            redirectToPortal('inventario', 'admin.php');

        case 'delete_product':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->delete('productos', $id);
                setFlash('info', 'Producto eliminado del catálogo.');
            }
            redirectToPortal('inventario', 'admin.php');

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
                        $stockNuevo = $cantidad;
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
            redirectToPortal('inventario', 'admin.php');

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
            redirectToPortal('inventario', 'admin.php');

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
            redirectToPortal('clientes', 'admin.php');

        case 'delete_client':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 1) {
                $db->delete('clientes', $id);
                setFlash('info', 'Cliente eliminado.');
            } else {
                setFlash('warning', 'El Cliente Mostrador predeterminado no se puede eliminar.');
            }
            redirectToPortal('clientes', 'admin.php');
    }
}
