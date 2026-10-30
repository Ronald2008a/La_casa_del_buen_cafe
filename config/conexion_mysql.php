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

