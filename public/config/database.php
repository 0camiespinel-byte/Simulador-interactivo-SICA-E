<?php
/**
 * SICA-E · Conexión a Base de Datos MySQL con PDO
 * Institución Educativa Brighton Pamplona
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $pdo = null;

    private static function env(string $key, string $default = ''): string {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }

    public static function connect() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = self::env('DB_HOST', 'localhost');
        $port = self::env('DB_PORT', '3306');
        $db = self::env('DB_NAME', 'sica_e');
        $user = self::env('DB_USER', 'root');
        $pass = self::env('DB_PASSWORD', '');
        $charset = self::env('DB_CHARSET', 'utf8mb4');
        $dsn = self::env(
            'DB_DSN',
            "mysql:host={$host};port={$port};dbname={$db};charset={$charset}"
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            // En Vercel la base de datos debe ser externa y ya debe existir.
            self::$pdo = new PDO($dsn, $user, $pass, $options);

            // Verificar si la tabla users tiene datos
            $checkTable = self::$pdo->query("SHOW TABLES LIKE 'users'");
            if (!$checkTable->fetch()) {
                if (filter_var(getenv('DB_AUTO_INIT') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
                    self::initializeDatabase(self::$pdo);
                } else {
                    throw new RuntimeException(
                        'La tabla users no existe. Importa database/schema.sql y vuelve a intentarlo.'
                    );
                }
            }

            return self::$pdo;
        } catch (Throwable $e) {
            die("<div style='font-family:sans-serif;max-width:600px;margin:50px auto;padding:25px;border:1px solid #e2e8f0;border-radius:12px;background:#fff5f5;color:#991b1b;'>
                <h3 style='margin-top:0;'>⚠️ Error de conexión a la Base de Datos</h3>
                <p>No se pudo conectar a la base de datos configurada.</p>
                <p style='font-size:13px;background:#fee2e2;padding:10px;border-radius:6px;word-break:break-all;'>" . htmlspecialchars($e->getMessage()) . "</p>
                <p style='font-size:14px;'><strong>Solución:</strong> verifica DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASSWORD en las variables de entorno de Vercel.</p>
            </div>");
        }
    }

    /**
     * Auto-inicializa las tablas y datos semilla desde schema.sql
     */
    private static function initializeDatabase($pdo) {
        $sqlFile = getenv('SCHEMA_PATH') ?: ROOT_PATH . '/database/schema.sql';
        if (!file_exists($sqlFile)) {
            $sqlFile = dirname(ROOT_PATH) . '/database/schema.sql';
        }
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
        }
    }
}

/**
 * Función helper global para obtener la conexión PDO
 */
function getDB() {
    return Database::connect();
}
