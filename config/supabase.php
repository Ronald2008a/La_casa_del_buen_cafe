<?php
/**
 * Gestor de Base de Datos Unificado: XAMPP MySQL (PDO) + Supabase (PostgreSQL REST & Auth)
 * La Casa del Buen Café
 */

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    @session_start();
}

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/conexion_mysql.php';

class SupabaseService {
    private string $url = '';
    private string $key = '';
    private bool $isSupabaseConfigured = false;
    private bool $isMySQLConnected = false;
    private ?PDO $pdo = null;
    private string $mysqlError = '';
    private array $mysqlConfig = [
        'host'     => '127.0.0.1',
        'port'     => '3306',
        'database' => 'la_casa_del_buen_cafe',
        'user'     => 'root',
        'password' => ''
    ];

    private string $credentialsFile;
    private string $mysqlConfigFile;
    private string $localDataFile;

    private array $allowedTables = [
        'categorias',
        'productos',
        'clientes',
        'usuarios',
        'pedidos',
        'detalle_pedidos',
        'movimientos_inventario'
    ];

    public function __construct() {
        $this->credentialsFile = __DIR__ . '/credentials.json';
        $this->mysqlConfigFile = __DIR__ . '/mysql_credentials.json';
        $this->localDataFile   = __DIR__ . '/../data/local_store.json';

        // 1. Cargar configuración personalizada de MySQL si existe
        if (file_exists($this->mysqlConfigFile)) {
            $savedMysql = json_decode(file_get_contents($this->mysqlConfigFile), true);
            if (is_array($savedMysql)) {
                $this->mysqlConfig = array_merge($this->mysqlConfig, $savedMysql);
            }
        }

        // 2. Intentar conexión directa con XAMPP MySQL
        $this->connectMySQL();

        // 3. Cargar configuración de Supabase si existe
        if (file_exists($this->credentialsFile)) {
            $creds = json_decode(file_get_contents($this->credentialsFile), true);
            if (!empty($creds['supabase_url']) && !empty($creds['supabase_key'])) {
                $this->url = rtrim(trim($creds['supabase_url']), '/');
                $this->key = trim($creds['supabase_key']);
            }
        }

        if (empty($this->url) && getenv('SUPABASE_URL')) {
            $this->url = rtrim(getenv('SUPABASE_URL'), '/');
        }
        if (empty($this->key) && getenv('SUPABASE_ANON_KEY')) {
            $this->key = getenv('SUPABASE_ANON_KEY');
        }

        $this->isSupabaseConfigured = (!empty($this->url) && !empty($this->key) && strpos($this->url, 'supabase.co') !== false);
        $this->ensureLocalStore();
    }

    /**
     * Conecta con XAMPP MySQL y crea tablas/datos automáticamente si no existen
     */
    public function connectMySQL(): bool {
        try {
            $host = $this->mysqlConfig['host'];
            $port = $this->mysqlConfig['port'];
            $db   = preg_replace('/[^a-zA-Z0-9_]/', '', $this->mysqlConfig['database']);
            $user = $this->mysqlConfig['user'];
            $pass = $this->mysqlConfig['password'];

            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 3,
            ]);

            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->pdo->exec("USE `{$db}`");

            // Verificar si ya existen todas las tablas y las 9 categorías actualizadas
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'movimientos_inventario'");
            if ($stmt->rowCount() === 0) {
                $this->initializeMySQLSchema();
            } else {
                $checkCat = $this->pdo->query("SELECT COUNT(*) FROM `categorias` WHERE `nombre` = 'Platanitos Verdes'")->fetchColumn();
                if ((int)$checkCat === 0) {
                    $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $this->pdo->exec("TRUNCATE TABLE `productos`");
                    $this->pdo->exec("TRUNCATE TABLE `categorias`");
                    $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $this->initializeMySQLSchema();
                }
            }

            $this->isMySQLConnected = true;
            $this->mysqlError = '';
            return true;
        } catch (Throwable $e) {
            $this->isMySQLConnected = false;
            $this->pdo = null;
            $this->mysqlError = $e->getMessage();
            return false;
        }
    }

    /**
     * Ejecuta el script database/mysql_schema.sql en XAMPP MySQL sentencia por sentencia
     */
    public function initializeMySQLSchema(): array {
        if (!$this->pdo) {
            return ['ok' => false, 'message' => 'No hay conexión activa con el servidor MySQL de XAMPP.'];
        }
        try {
            $sqlPath = __DIR__ . '/../database/mysql_schema.sql';
            if (!file_exists($sqlPath)) {
                return ['ok' => false, 'message' => 'No se encontró database/mysql_schema.sql'];
            }
            $sql = file_get_contents($sqlPath);
            // Eliminar líneas de comentarios SQL y dividir por punto y coma
            $lines = explode("\n", $sql);
            $cleanSql = '';
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || strpos($trimmed, '--') === 0) {
                    continue;
                }
                $cleanSql .= $line . "\n";
            }

            $statements = array_filter(array_map('trim', explode(';', $cleanSql)));
            foreach ($statements as $stmtSql) {
                if (!empty($stmtSql)) {
                    $this->pdo->exec($stmtSql);
                }
            }

            // Insertar pedidos de ejemplo si pedidos está vacío
            $countPed = (int)$this->pdo->query("SELECT COUNT(*) FROM `pedidos`")->fetchColumn();
            if ($countPed === 0) {
                $this->pdo->exec("
                    INSERT INTO `pedidos` (`folio`, `cliente_id`, `cliente_nombre`, `tipo`, `metodo_pago`, `subtotal`, `impuestos`, `total`, `estado`, `direccion_entrega`, `notas`)
                    VALUES
                    ('WEB-1001', 2, 'Estefanía Olaya', 'tienda_online', 'tarjeta', 59000, 0, 59000, 'completado', 'Calle 85 #14-20, Bogotá', 'Molienda media para filtro'),
                    ('POS-1002', 1, 'Cliente Mostrador', 'pos', 'efectivo', 20500, 0, 20500, 'preparando', 'Consumo en Barra - Mesa 4', 'Sin azúcar')
                ");
                $this->pdo->exec("
                    INSERT INTO `detalle_pedidos` (`pedido_id`, `producto_id`, `nombre_producto`, `cantidad`, `precio_unitario`, `subtotal`)
                    VALUES
                    (1, 1, 'Reserva Especial Huila 500g', 1, 42000, 42000),
                    (1, 4, 'Cappuccino Artesanal 8oz', 2, 8500, 17000),
                    (2, 6, 'Filtrado V60 de Origen', 1, 11000, 11000),
                    (2, 9, 'Croissant de Almendras', 1, 9500, 9500)
                ");
                $this->pdo->exec("
                    INSERT INTO `movimientos_inventario` (`producto_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`, `motivo`)
                    VALUES
                    (1, 'entrada', 35, 0, 35, 'Lote inicial tostión octubre')
                ");
            }

            return ['ok' => true, 'message' => 'Tablas y catálogo inicial creados correctamente en XAMPP MySQL (' . $this->mysqlConfig['database'] . ').'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Error al inicializar esquema MySQL: ' . $e->getMessage()];
        }
    }

    public function saveMySQLConfig(string $host, string $port, string $database, string $user, string $password): bool {
        $this->mysqlConfig = [
            'host'     => trim($host) ?: '127.0.0.1',
            'port'     => trim($port) ?: '3306',
            'database' => trim($database) ?: 'la_casa_del_buen_cafe',
            'user'     => trim($user) ?: 'root',
            'password' => $password
        ];
        if (!is_dir(dirname($this->mysqlConfigFile))) {
            mkdir(dirname($this->mysqlConfigFile), 0777, true);
        }
        file_put_contents($this->mysqlConfigFile, json_encode($this->mysqlConfig, JSON_PRETTY_PRINT));
        return $this->connectMySQL();
    }

    public function isMySQLActive(): bool {
        return $this->isMySQLConnected;
    }

    public function getMySQLConfig(): array {
        return $this->mysqlConfig;
    }

    public function getMySQLError(): string {
        return $this->mysqlError;
    }

    public function isConnected(): bool {
        return $this->isSupabaseConfigured;
    }

    public function getActiveEngineName(): string {
        if ($this->isMySQLConnected && $this->isSupabaseConfigured) {
            return 'XAMPP MySQL + Supabase Híbrido';
        }
        if ($this->isMySQLConnected) {
            return 'XAMPP MySQL (' . $this->mysqlConfig['database'] . ')';
        }
        if ($this->isSupabaseConfigured) {
            return 'Supabase Cloud PostgreSQL';
        }
        return 'Respaldo Local JSON (Inicia MySQL en XAMPP)';
    }

    public function getUrl(): string {
        return $this->url;
    }

    public function getMaskedKey(): string {
        if (empty($this->key)) return '';
        return substr($this->key, 0, 14) . '...' . substr($this->key, -8);
    }

    public function saveCredentials(string $url, string $key): bool {
        $url = rtrim(trim($url), '/');
        $key = trim($key);
        $data = [
            'supabase_url' => $url,
            'supabase_key' => $key,
            'updated_at' => date('c')
        ];
        if (!is_dir(dirname($this->credentialsFile))) {
            mkdir(dirname($this->credentialsFile), 0777, true);
        }
        $saved = file_put_contents($this->credentialsFile, json_encode($data, JSON_PRETTY_PRINT)) !== false;
        if ($saved) {
            $this->url = $url;
            $this->key = $key;
            $this->isSupabaseConfigured = (!empty($this->url) && !empty($this->key));
        }
        return $saved;
    }

    public function disconnect(): void {
        if (file_exists($this->credentialsFile)) {
            unlink($this->credentialsFile);
        }
        $this->url = '';
        $this->key = '';
        $this->isSupabaseConfigured = false;
    }

    private function request(string $method, string $endpoint, array $queryParams = [], ?array $body = null, array $extraHeaders = []): array {
        $fullUrl = $this->url . $endpoint;
        if (!empty($queryParams)) {
            $fullUrl .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init($fullUrl);
        $token = $_SESSION['supabase_access_token'] ?? $this->key;

        $headers = array_merge([
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ], $extraHeaders);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'status' => 0, 'error' => $error, 'data' => []];
        }

        $decoded = json_decode($response, true);
        $ok = ($httpCode >= 200 && $httpCode < 300);

        return [
            'ok' => $ok,
            'status' => $httpCode,
            'data' => is_array($decoded) ? $decoded : [],
            'error' => !$ok ? ($decoded['message'] ?? $decoded['error_description'] ?? $response) : null
        ];
    }

    public function testConnection(): array {
        if (!$this->isSupabaseConfigured) {
            return ['ok' => false, 'message' => 'Credenciales de Supabase no configuradas.'];
        }
        $res = $this->request('GET', '/rest/v1/categorias', ['select' => 'id,nombre', 'limit' => 1]);
        if ($res['ok']) {
            return ['ok' => true, 'message' => 'Conexión exitosa con Supabase PostgreSQL.'];
        }
        return [
            'ok' => false,
            'message' => 'Conectado al servidor, pero falta ejecutar el script SQL en Supabase. Detalle: ' . ($res['error'] ?? 'HTTP ' . $res['status'])
        ];
    }

    // ========================================================================
    // OPERACIONES CRUD (XAMPP MySQL -> Supabase REST -> Respaldo JSON)
    // ========================================================================

    public function select(string $table, array $params = ['select' => '*', 'order' => 'id.asc']): array {
        if (!in_array($table, $this->allowedTables, true)) {
            return [];
        }

        // 1. Si XAMPP MySQL está conectado, consultar MySQL directamente
        if ($this->isMySQLConnected && $this->pdo) {
            try {
                $dir = (isset($params['order']) && strpos($params['order'], 'desc') !== false) ? 'DESC' : 'ASC';
                $stmt = $this->pdo->query("SELECT * FROM `{$table}` ORDER BY `id` {$dir}");
                return $stmt->fetchAll();
            } catch (Throwable $e) {
                // Continuar al siguiente motor si ocurre algún error puntual
            }
        }

        // 2. Si Supabase está configurado, consultar Supabase
        if ($this->isSupabaseConfigured) {
            $res = $this->request('GET', '/rest/v1/' . $table, $params);
            if ($res['ok']) {
                return $res['data'];
            }
        }

        // 3. Fallback local JSON
        $store = $this->readLocalStore();
        $rows = $store[$table] ?? [];
        if (isset($params['order']) && strpos($params['order'], 'desc') !== false) {
            $rows = array_reverse($rows);
        }
        return array_values($rows);
    }

    public function insert(string $table, array $record): array {
        if (!in_array($table, $this->allowedTables, true)) {
            return ['ok' => false, 'error' => 'Tabla no permitida'];
        }

        // 1. Insertar en XAMPP MySQL si está conectado
        if ($this->isMySQLConnected && $this->pdo) {
            try {
                $cols = [];
                $placeholders = [];
                $values = [];
                foreach ($record as $col => $val) {
                    $colClean = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
                    $cols[] = "`{$colClean}`";
                    $placeholders[] = ":{$colClean}";
                    if (is_bool($val)) {
                        $values[":{$colClean}"] = $val ? 1 : 0;
                    } else {
                        $values[":{$colClean}"] = $val;
                    }
                }
                $sql = "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($values);
                $record['id'] = (int)$this->pdo->lastInsertId();

                // Si además está conectado Supabase, replicar en la nube en segundo plano
                if ($this->isSupabaseConfigured) {
                    $copy = $record;
                    unset($copy['id']);
                    $this->request('POST', '/rest/v1/' . $table, [], $copy);
                }

                return ['ok' => true, 'data' => $record];
            } catch (Throwable $e) {
                return ['ok' => false, 'error' => 'Error MySQL: ' . $e->getMessage()];
            }
        }

        // 2. Insertar en Supabase si está configurado
        if ($this->isSupabaseConfigured) {
            $res = $this->request('POST', '/rest/v1/' . $table, [], $record);
            if ($res['ok'] && !empty($res['data'][0])) {
                return ['ok' => true, 'data' => $res['data'][0]];
            }
            return ['ok' => false, 'error' => $res['error'] ?? 'Error al insertar en Supabase'];
        }

        // 3. Fallback JSON
        $store = $this->readLocalStore();
        if (!isset($store[$table])) {
            $store[$table] = [];
        }
        $maxId = 0;
        foreach ($store[$table] as $r) {
            if (($r['id'] ?? 0) > $maxId) $maxId = (int)$r['id'];
        }
        $record['id'] = $maxId + 1;
        if (!isset($record['created_at'])) {
            $record['created_at'] = date('c');
        }
        $store[$table][] = $record;
        $this->writeLocalStore($store);
        return ['ok' => true, 'data' => $record];
    }

    public function update(string $table, int $id, array $changes): array {
        if (!in_array($table, $this->allowedTables, true)) {
            return ['ok' => false, 'error' => 'Tabla no permitida'];
        }

        // 1. Actualizar en XAMPP MySQL si está conectado
        if ($this->isMySQLConnected && $this->pdo) {
            try {
                $sets = [];
                $values = [':_id' => $id];
                foreach ($changes as $col => $val) {
                    $colClean = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
                    $sets[] = "`{$colClean}` = :{$colClean}";
                    $values[":{$colClean}"] = is_bool($val) ? ($val ? 1 : 0) : $val;
                }
                $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE `id` = :_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($values);

                if ($this->isSupabaseConfigured) {
                    $this->request('PATCH', '/rest/v1/' . $table, ['id' => 'eq.' . $id], $changes);
                }

                return ['ok' => true, 'data' => $changes];
            } catch (Throwable $e) {
                return ['ok' => false, 'error' => 'Error MySQL: ' . $e->getMessage()];
            }
        }

        // 2. Actualizar en Supabase
        if ($this->isSupabaseConfigured) {
            $res = $this->request('PATCH', '/rest/v1/' . $table, ['id' => 'eq.' . $id], $changes);
            if ($res['ok']) {
                return ['ok' => true, 'data' => $res['data'][0] ?? $changes];
            }
            return ['ok' => false, 'error' => $res['error'] ?? 'Error al actualizar en Supabase'];
        }

        // 3. Fallback JSON
        $store = $this->readLocalStore();
        if (isset($store[$table])) {
            foreach ($store[$table] as &$row) {
                if ((int)$row['id'] === $id) {
                    $row = array_merge($row, $changes);
                    $this->writeLocalStore($store);
                    return ['ok' => true, 'data' => $row];
                }
            }
        }
        return ['ok' => false, 'error' => 'Registro no encontrado'];
    }

    public function delete(string $table, int $id): array {
        if (!in_array($table, $this->allowedTables, true)) {
            return ['ok' => false, 'error' => 'Tabla no permitida'];
        }

        // 1. Eliminar en XAMPP MySQL
        if ($this->isMySQLConnected && $this->pdo) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM `{$table}` WHERE `id` = :id");
                $stmt->execute([':id' => $id]);

                if ($this->isSupabaseConfigured) {
                    $this->request('DELETE', '/rest/v1/' . $table, ['id' => 'eq.' . $id]);
                }

                return ['ok' => true];
            } catch (Throwable $e) {
                return ['ok' => false, 'error' => 'Error MySQL: ' . $e->getMessage()];
            }
        }

        // 2. Eliminar en Supabase
        if ($this->isSupabaseConfigured) {
            $res = $this->request('DELETE', '/rest/v1/' . $table, ['id' => 'eq.' . $id]);
            return ['ok' => $res['ok'], 'error' => $res['error']];
        }

        // 3. Fallback JSON
        $store = $this->readLocalStore();
        if (isset($store[$table])) {
            $store[$table] = array_values(array_filter($store[$table], fn($r) => (int)$r['id'] !== $id));
            $this->writeLocalStore($store);
            return ['ok' => true];
        }
        return ['ok' => false, 'error' => 'No encontrado'];
    }

    // ========================================================================
    // AUTENTICACIÓN (MySQL + Supabase Auth)
    // ========================================================================

    public function signIn(string $email, string $password): array {
        if ($this->isMySQLConnected && $this->pdo) {
            $stmt = $this->pdo->prepare("SELECT * FROM `usuarios` WHERE `email` = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $u = $stmt->fetch();
            if ($u) {
                $_SESSION['user'] = [
                    'email'  => $u['email'],
                    'nombre' => $u['nombre'],
                    'rol'    => $u['rol'] ?? 'admin',
                    'modo'   => 'XAMPP MySQL'
                ];
                return ['ok' => true];
            }
        }

        if ($this->isSupabaseConfigured) {
            $res = $this->request('POST', '/auth/v1/token', ['grant_type' => 'password'], [
                'email' => $email,
                'password' => $password
            ]);
            if ($res['ok'] && isset($res['data']['access_token'])) {
                $_SESSION['supabase_access_token'] = $res['data']['access_token'];
                $_SESSION['user'] = [
                    'email' => $res['data']['user']['email'] ?? $email,
                    'nombre' => $res['data']['user']['user_metadata']['nombre'] ?? explode('@', $email)[0],
                    'rol' => $res['data']['user']['user_metadata']['rol'] ?? 'admin',
                    'modo' => 'Supabase Auth'
                ];
                return ['ok' => true];
            }
            return ['ok' => false, 'error' => $res['error'] ?? 'Credenciales inválidas en Supabase Auth'];
        }

        if (!empty($email) && !empty($password)) {
            $_SESSION['user'] = [
                'email' => $email,
                'nombre' => ($email === 'admin@casadelbuencafe.com') ? 'Administrador Principal' : ucfirst(explode('@', $email)[0]),
                'rol' => 'admin',
                'modo' => 'Sesión Local'
            ];
            return ['ok' => true];
        }
        return ['ok' => false, 'error' => 'Ingresa correo y contraseña'];
    }

    public function signUp(string $nombre, string $email, string $password, string $rol = 'admin'): array {
        if ($this->isMySQLConnected && $this->pdo) {
            $this->insert('usuarios', [
                'nombre'        => $nombre,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'rol'           => $rol,
                'activo'        => 1
            ]);
            $_SESSION['user'] = [
                'email'  => $email,
                'nombre' => $nombre,
                'rol'    => $rol,
                'modo'   => 'XAMPP MySQL'
            ];
            return ['ok' => true, 'message' => 'Usuario registrado en XAMPP MySQL exitosamente.'];
        }

        if ($this->isSupabaseConfigured) {
            $res = $this->request('POST', '/auth/v1/signup', [], [
                'email' => $email,
                'password' => $password,
                'data' => ['nombre' => $nombre, 'rol' => $rol]
            ]);
            if ($res['ok']) {
                return ['ok' => true, 'message' => 'Usuario registrado en Supabase Auth exitosamente.'];
            }
            return ['ok' => false, 'error' => $res['error'] ?? 'Error al registrar en Supabase Auth'];
        }

        $_SESSION['user'] = [
            'email' => $email,
            'nombre' => $nombre,
            'rol' => $rol,
            'modo' => 'Sesión Local'
        ];
        return ['ok' => true, 'message' => 'Usuario creado y sesión iniciada.'];
    }

    private function ensureLocalStore(): void {
        if (file_exists($this->localDataFile)) {
            return;
        }
        if (!is_dir(dirname($this->localDataFile))) {
            mkdir(dirname($this->localDataFile), 0777, true);
        }
        file_put_contents($this->localDataFile, json_encode(['categorias' => [], 'productos' => [], 'clientes' => [], 'pedidos' => [], 'detalle_pedidos' => [], 'movimientos_inventario' => []]));
    }

    private function readLocalStore(): array {
        if (!file_exists($this->localDataFile)) {
            return [];
        }
        $data = json_decode(file_get_contents($this->localDataFile), true);
        return is_array($data) ? $data : [];
    }

    private function writeLocalStore(array $data): void {
        file_put_contents($this->localDataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
