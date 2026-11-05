<?php
/**
 * Conexión Directa con XAMPP MySQL / MariaDB (PDO y MySQLi)
 * Base de datos: la_casa_del_buen_cafe
 */

define('MYSQL_HOST', '127.0.0.1');
define('MYSQL_PORT', '3306');
define('MYSQL_DB',   'la_casa_del_buen_cafe');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');

/**
 * Obtiene una conexión PDO activa a XAMPP MySQL
 * Crea automáticamente la base de datos y las tablas si aún no existen.
 */
function obtenerConexionMySQL(array $customConfig = []): ?PDO {
    $host = $customConfig['host'] ?? MYSQL_HOST;
    $port = $customConfig['port'] ?? MYSQL_PORT;
    $db   = $customConfig['database'] ?? MYSQL_DB;
    $user = $customConfig['user'] ?? MYSQL_USER;
    $pass = $customConfig['password'] ?? MYSQL_PASS;

    try {
        // 1. Conectar al servidor MySQL de XAMPP
        $dsnServer = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsnServer, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 4,
        ]);

        // 2. Asegurar que la base de datos exista
        $dbSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $db);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbSafe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbSafe}`");

        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Detecta automáticamente la IPv4 local (Wi-Fi / LAN) del equipo para conectar celulares y tablets
 */
function obtenerIpLocalRed(): string {
    // Si ya se está accediendo mediante una IP de red en el navegador, usar esa misma
    $hostActual = $_SERVER['HTTP_HOST'] ?? '';
    if (!empty($hostActual) && strpos($hostActual, 'localhost') === false && strpos($hostActual, '127.0.0.1') === false) {
        return explode(':', $hostActual)[0];
    }

    // Intentar detectar mediante socket UDP sin enviar tráfico
    if (function_exists('socket_create')) {
        $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($sock && @socket_connect($sock, '8.8.8.8', 53)) {
            @socket_getsockname($sock, $ip);
            @socket_close($sock);
            if (!empty($ip) && $ip !== '127.0.0.1') {
                return $ip;
            }
        }
    }

    $hostName = gethostname();
    if ($hostName) {
        $ips = gethostbynamel($hostName);
        if (is_array($ips)) {
            foreach ($ips as $ip) {
                if (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0 || strpos($ip, '172.') === 0) {
                    return $ip;
                }
            }
            if (!empty($ips[0]) && $ips[0] !== '127.0.0.1') {
                return $ips[0];
            }
        }
    }

    return '127.0.0.1';
}


