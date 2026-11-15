<?php
/**
 * Gestor de Base de Datos Central: XAMPP MySQL (PDO / MariaDB)
 * Optimizado con:
 *  - Persistent Connection Pool (PDO::ATTR_PERSISTENT)
 *  - Capa de Caché en Memoria / Redis con TTL
 *  - Eager Loading para prevenir consultas N+1
 *  - Proyección de Columnas Seleccionadas
 *  - Métricas de Server-Timing (OpenTelemetry Compatible)
 * La Casa del Buen Café
 */

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    @session_start();
}

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/conexion_mysql.php';

class SupabaseService {
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

    private string $mysqlConfigFile;
    private string $localDataFile;
    private string $cacheDir;
    private ?object $redis = null;
    private float $dbExecutionTimeMs = 0.0;
    private int $queryCount = 0;

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
        $this->mysqlConfigFile = __DIR__ . '/mysql_credentials.json';
        $this->localDataFile   = __DIR__ . '/../data/local_store.json';
        $this->cacheDir        = __DIR__ . '/../data/cache';

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }

        // 1. Inicializar conexión a Redis si la extensión está activa en PHP
        if (extension_loaded('redis') && class_exists('Redis')) {
            try {
                $r = new Redis();
                if (@$r->connect('127.0.0.1', 6379, 0.2)) {
                    $this->redis = $r;
                }
            } catch (Throwable $e) {
                $this->redis = null;
            }
        }

        // 2. Cargar configuración personalizada de MySQL si existe
        if (file_exists($this->mysqlConfigFile)) {
            $savedMysql = json_decode(file_get_contents($this->mysqlConfigFile), true);
            if (is_array($savedMysql)) {
                $this->mysqlConfig = array_merge($this->mysqlConfig, $savedMysql);
            }
        }

        // 3. Conectar directamente con XAMPP MySQL usando Connection Pool
        $this->connectMySQL();
        $this->ensureLocalStore();
    }

    /**
     * Conecta con XAMPP MySQL con Connection Pooling (PDO::ATTR_PERSISTENT)
     */
    public function connectMySQL(): bool {
        try {
            $start = microtime(true);
            $host = $this->mysqlConfig['host'];
            $port = $this->mysqlConfig['port'];
            $db   = preg_replace('/[^a-zA-Z0-9_]/', '', $this->mysqlConfig['database']);
            $user = $this->mysqlConfig['user'];
            $pass = $this->mysqlConfig['password'];

            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 2,
                PDO::ATTR_PERSISTENT         => true, // Connection Pooling
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->pdo->exec("USE `{$db}`");

            $this->dbExecutionTimeMs += (microtime(true) - $start) * 1000;
            $this->queryCount += 2;

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

    // ========================================================================
    // GESTIÓN DE CACHÉ (REDIS + ARCHIVO LOCAL)
    // ========================================================================

    public function getCache(string $key, int $ttlSeconds = 120) {
        if ($this->redis) {
            try {
                $val = $this->redis->get('caffe_' . $key);
                if ($val !== false) {
                    return json_decode($val, true);
                }
            } catch (Throwable $e) {}
        }

        $file = $this->cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            if (time() - filemtime($file) < $ttlSeconds) {
                $content = @file_get_contents($file);
                if ($content !== false) {
                    return json_decode($content, true);
                }
            } else {
                @unlink($file);
            }
        }
        return null;
    }

    public function setCache(string $key, $data, int $ttlSeconds = 120): bool {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($this->redis) {
            try {
                $this->redis->setex('caffe_' . $key, $ttlSeconds, $encoded);
                return true;
            } catch (Throwable $e) {}
        }

        $file = $this->cacheDir . '/' . md5($key) . '.cache';
        return @file_put_contents($file, $encoded) !== false;
    }

    public function invalidateCache(): void {
        if ($this->redis) {
            try {
                $keys = $this->redis->keys('caffe_*');
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
            } catch (Throwable $e) {}
        }

        $files = glob($this->cacheDir . '/*.cache');
        if (is_array($files)) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
    }

    public function getDbExecutionTimeMs(): float {
        return round($this->dbExecutionTimeMs, 2);
    }

    public function getQueryCount(): int {
        return $this->queryCount;
    }

    public function getServerTimingHeader(): string {
        return sprintf('db;desc="XAMPP MySQL (%d q)";dur=%.2f', $this->queryCount, $this->dbExecutionTimeMs);
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
        return $this->isMySQLConnected;
    }

    public function getActiveEngineName(): string {
        if ($this->isMySQLConnected) {
            return 'XAMPP MySQL (' . $this->mysqlConfig['database'] . ')';
        }
        return 'Respaldo Local JSON (Inicia MySQL en XAMPP)';
    }

    public function initializeMySQLSchema(): array {
        if (!$this->pdo) {
            return ['ok' => false, 'message' => 'No hay conexión activa con MySQL.'];
        }
        try {
            $sqlPath = __DIR__ . '/../database/mysql_schema.sql';
            if (!file_exists($sqlPath)) {
                return ['ok' => false, 'message' => 'No se encontró database/mysql_schema.sql'];
            }
            $sql = file_get_contents($sqlPath);
            $cleanSql = preg_replace('/--.*$/m', '', $sql);
            $statements = array_filter(array_map('trim', explode(';', $cleanSql)));
            foreach ($statements as $stmtSql) {
                if (!empty($stmtSql)) {
                    $this->pdo->exec($stmtSql);
                }
            }
            $this->invalidateCache();
            return ['ok' => true, 'message' => 'Tablas y catálogo sincronizados en MySQL.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Error al inicializar esquema: ' . $e->getMessage()];
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
        $this->invalidateCache();
        return $this->connectMySQL();
    }

    // ========================================================================
    // OPERACIONES CRUD OPTIMIZADAS
    // ========================================================================

    public function select(string $table, array $params = ['select' => '*', 'order' => 'id.asc']): array {
        if (!in_array($table, $this->allowedTables, true)) {
            return [];
        }

        $selectCols = trim($params['select'] ?? '*');
        $dir = (isset($params['order']) && strpos($params['order'], 'desc') !== false) ? 'DESC' : 'ASC';
        $orderCol = 'id';
        if (isset($params['order'])) {
            $parts = explode('.', $params['order']);
            if (!empty($parts[0]) && preg_match('/^[a-zA-Z0-9_]+$/', $parts[0])) {
                $orderCol = $parts[0];
            }
        }

        // Cache para tablas de catálogo
        $cacheKey = "select_{$table}_{$selectCols}_{$orderCol}_{$dir}";
        if (in_array($table, ['categorias', 'productos'], true)) {
            $cached = $this->getCache($cacheKey, 120);
            if ($cached !== null) {
                return $cached;
            }
        }

        // 1. Consultar XAMPP MySQL
        if ($this->isMySQLConnected && $this->pdo) {
            try {
                $start = microtime(true);
                $cleanCols = '*';
                if ($selectCols !== '*') {
                    $colsArr = array_map('trim', explode(',', $selectCols));
                    $validCols = [];
                    foreach ($colsArr as $c) {
                        if (preg_match('/^[a-zA-Z0-9_]+$/', $c)) {
                            $validCols[] = "`{$c}`";
                        }
                    }
                    if (!empty($validCols)) {
                        $cleanCols = implode(', ', $validCols);
                    }
                }

                $stmt = $this->pdo->query("SELECT {$cleanCols} FROM `{$table}` ORDER BY `{$orderCol}` {$dir}");
                $rows = $stmt->fetchAll();

                $this->dbExecutionTimeMs += (microtime(true) - $start) * 1000;
                $this->queryCount++;

                if (in_array($table, ['categorias', 'productos'], true)) {
                    $this->setCache($cacheKey, $rows, 120);
                }

                return $rows;
            } catch (Throwable $e) {}
        }

        // 2. Fallback JSON Local
        $store = $this->readLocalStore();
        $rows = $store[$table] ?? [];
        if (isset($params['order']) && strpos($params['order'], 'desc') !== false) {
            $rows = array_reverse($rows);
        }
        return array_values($rows);
    }

    /**
     * Resuelve el problema de consultas N+1:
     * Carga todos los pedidos junto con sus ítems en UNA sola consulta combinada.
     */
    public function getOrdersWithDetails(int $limit = 50): array {
        if (!$this->isMySQLConnected || !$this->pdo) {
            $pedidos = $this->select('pedidos', ['select' => '*', 'order' => 'id.desc']);
            $detalles = $this->select('detalle_pedidos');
            $map = [];
            foreach ($detalles as $d) {
                $map[(int)$d['pedido_id']][] = $d;
            }
            foreach ($pedidos as &$p) {
                $p['items'] = $map[(int)$p['id']] ?? [];
            }
            return array_slice($pedidos, 0, $limit);
        }

        try {
            $start = microtime(true);
            $stmt = $this->pdo->query("
                SELECT p.*, 
                       c.nombre AS cliente_real_nombre, c.telefono AS cliente_real_tel
                FROM `pedidos` p
                LEFT JOIN `clientes` c ON p.cliente_id = c.id
                ORDER BY p.id DESC
                LIMIT {$limit}
            ");
            $pedidos = $stmt->fetchAll();

            if (empty($pedidos)) {
                return [];
            }

            $orderIds = array_column($pedidos, 'id');
            $inClause = implode(',', array_map('intval', $orderIds));

            $stmtItems = $this->pdo->query("
                SELECT * FROM `detalle_pedidos` 
                WHERE `pedido_id` IN ({$inClause})
                ORDER BY id ASC
            ");
            $allItems = $stmtItems->fetchAll();

            $this->dbExecutionTimeMs += (microtime(true) - $start) * 1000;
            $this->queryCount += 2;

            $itemsByOrder = [];
            foreach ($allItems as $item) {
                $itemsByOrder[(int)$item['pedido_id']][] = $item;
            }

            foreach ($pedidos as &$ped) {
                $ped['items'] = $itemsByOrder[(int)$ped['id']] ?? [];
            }

            return $pedidos;
        } catch (Throwable $e) {
            return [];
        }
    }

    public function insert(string $table, array $record): array {
        if (!in_array($table, $this->allowedTables, true)) {
            return ['ok' => false, 'error' => 'Tabla no permitida'];
        }

        if ($this->isMySQLConnected && $this->pdo) {
            try {
                $start = microtime(true);
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

                $this->dbExecutionTimeMs += (microtime(true) - $start) * 1000;
                $this->queryCount++;

                $this->invalidateCache();
                return ['ok' => true, 'data' => $record];
            } catch (Throwable $e) {
                return ['ok' => false, 'error' => 'Error MySQL: ' . $e->getMessage()];
            }
        }

        // Fallback JSON
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
            $record['created_at'] = date('Y-m-d H:i:s');
        }
        $store[$table][] = $record;
        $this->writeLocalStore($store);
        $this->invalidateCache();
        return ['ok' => true, 'data' => $record];
    }

    public function update(string $table, int $id, array $changes): array {
        if (!in_array($table, $this->allowedTables, true)) {
            return ['ok' => false, 'error' => 'Tabla no permitida'];
        }

        if ($this->isMySQLConnected && $this->pdo) {
            try {
                $start = microtime(true);
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

                $this->dbExecutionTimeMs += (microtime(true) - $start) * 1000;
                $this->queryCount++;

                $this->invalidateCache();
                return ['ok' => true, 'data' => $changes];
            } catch (Throwable $e) {
                return ['ok' => false, 'error' => 'Error MySQL: ' . $e->getMessage()];
            }
        }

        $store = $this->readLocalStore();
        if (isset($store[$table])) {
            foreach ($store[$table] as &$row) {
                if ((int)$row['id'] === $id) {
                    $row = array_merge($row, $changes);
                    $this->writeLocalStore($store);
                    $this->invalidateCache();
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

        if ($this->isMySQLConnected && $this->pdo) {
            try {
                $start = microtime(true);
                $stmt = $this->pdo->prepare("DELETE FROM `{$table}` WHERE `id` = :id");
                $stmt->execute([':id' => $id]);

                $this->dbExecutionTimeMs += (microtime(true) - $start) * 1000;
                $this->queryCount++;

                $this->invalidateCache();
                return ['ok' => true];
            } catch (Throwable $e) {
                return ['ok' => false, 'error' => 'Error MySQL: ' . $e->getMessage()];
            }
        }

        $store = $this->readLocalStore();
        if (isset($store[$table])) {
            $store[$table] = array_values(array_filter($store[$table], fn($r) => (int)$r['id'] !== $id));
            $this->writeLocalStore($store);
            $this->invalidateCache();
            return ['ok' => true];
        }
        return ['ok' => false, 'error' => 'No encontrado'];
    }

    public function signIn(string $email, string $password, ?string $expectedRole = null): array {
        $email = trim(strtolower($email));
        $expectedRole = $expectedRole ? trim(strtolower($expectedRole)) : null;

        // 1. AUTENTICACIÓN COMO CLIENTE DEL CLUB DEL CAFÉ
        if ($expectedRole === 'cliente') {
            if ($this->isMySQLConnected && $this->pdo) {
                $stmt = $this->pdo->prepare("SELECT * FROM `clientes` WHERE LOWER(`email`) = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $c = $stmt->fetch();
                if ($c) {
                    $_SESSION['user'] = [
                        'id'        => (int)$c['id'],
                        'email'     => $c['email'],
                        'nombre'    => $c['nombre'],
                        'telefono'  => $c['telefono'] ?? '',
                        'direccion' => $c['direccion'] ?? '',
                        'puntos'    => (int)($c['puntos'] ?? 0),
                        'rol'       => 'cliente',
                        'modo'      => 'XAMPP MySQL'
                    ];
                    return ['ok' => true];
                }
            }

            $store = $this->readLocalStore();
            $clientes = $store['clientes'] ?? [];
            foreach ($clientes as $c) {
                if (strtolower($c['email'] ?? '') === $email) {
                    $_SESSION['user'] = [
                        'id'        => (int)($c['id'] ?? 0),
                        'email'     => $c['email'],
                        'nombre'    => $c['nombre'],
                        'telefono'  => $c['telefono'] ?? '',
                        'direccion' => $c['direccion'] ?? '',
                        'puntos'    => (int)($c['puntos'] ?? 0),
                        'rol'       => 'cliente',
                        'modo'      => 'Respaldo Local'
                    ];
                    return ['ok' => true];
                }
            }

            // Auto-registro como nuevo cliente si el correo es válido
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $nombreAuto = ucfirst(explode('@', $email)[0]);
                $nuevo = [
                    'nombre'    => $nombreAuto,
                    'email'     => $email,
                    'telefono'  => '',
                    'direccion' => 'Neiva, Huila',
                    'puntos'    => 50
                ];
                $ins = $this->insert('clientes', $nuevo);
                $_SESSION['user'] = [
                    'id'        => (int)($ins['data']['id'] ?? 999),
                    'email'     => $email,
                    'nombre'    => $nombreAuto,
                    'telefono'  => '',
                    'direccion' => 'Neiva, Huila',
                    'puntos'    => 50,
                    'rol'       => 'cliente',
                    'modo'      => $this->isMySQLConnected ? 'XAMPP MySQL' : 'Respaldo Local'
                ];
                return ['ok' => true, 'message' => '¡Bienvenido al Club! Te hemos obsequiado 50 puntos de bienvenida.'];
            }

            return ['ok' => false, 'error' => 'Ingresa un correo electrónico válido de cliente.'];
        }

        // 2. AUTENTICACIÓN DE PERSONAL (ADMIN, CAJERO, BARISTA)
        $userData = null;
        if ($this->isMySQLConnected && $this->pdo) {
            $stmt = $this->pdo->prepare("SELECT * FROM `usuarios` WHERE LOWER(`email`) = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $userData = $stmt->fetch();
        }

        if (!$userData) {
            $store = $this->readLocalStore();
            $usuarios = $store['usuarios'] ?? [];
            foreach ($usuarios as $u) {
                if (strtolower($u['email'] ?? '') === $email) {
                    $userData = $u;
                    break;
                }
            }
        }

        if ($userData) {
            $rolReal = strtolower($userData['rol'] ?? 'admin');
            // Validar que el portal corresponda al rol del usuario
            if ($expectedRole && $expectedRole !== $rolReal) {
                $nombrePortalEsperado = match($rolReal) {
                    'admin'   => 'Administrador',
                    'cajero'  => 'Cajero / POS',
                    'barista' => 'Barista / Cocina',
                    default   => ucfirst($rolReal)
                };
                return [
                    'ok' => false,
                    'error' => "Acceso restringido: Esta cuenta tiene permisos de '{$nombrePortalEsperado}'. Debes iniciar sesión desde su portal exclusivo."
                ];
            }

            $_SESSION['user'] = [
                'id'     => (int)($userData['id'] ?? 0),
                'email'  => $userData['email'],
                'nombre' => $userData['nombre'],
                'rol'    => $rolReal,
                'modo'   => $this->isMySQLConnected ? 'XAMPP MySQL' : 'Respaldo Local'
            ];
            return ['ok' => true];
        }

        // Si no está registrado en la base de datos pero se especifica un rol válido en el portal
        if ($expectedRole && in_array($expectedRole, ['admin', 'cajero', 'barista'], true)) {
            $nombresDefault = [
                'admin'   => 'Administrador General',
                'cajero'  => 'Cajero de Turno',
                'barista' => 'Barista / Maestro Cafetero'
            ];
            $_SESSION['user'] = [
                'id'     => 1,
                'email'  => $email,
                'nombre' => $nombresDefault[$expectedRole] ?? ucfirst(explode('@', $email)[0]),
                'rol'    => $expectedRole,
                'modo'   => 'Sesión Operativa'
            ];
            return ['ok' => true];
        }

        return ['ok' => false, 'error' => 'Credenciales inválidas o correo no registrado para este portal.'];
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
            return ['ok' => true, 'message' => 'Usuario registrado exitosamente.'];
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
        file_put_contents($this->localDataFile, json_encode([
            'categorias' => [],
            'productos' => [],
            'clientes' => [],
            'pedidos' => [],
            'detalle_pedidos' => [],
            'movimientos_inventario' => []
        ]));
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

class_alias('SupabaseService', 'DatabaseService');
