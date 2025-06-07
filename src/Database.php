<?php
class Database
{
    private static $pdo;    public static function connect()
    {
        if (!self::$pdo) {
            $config = include __DIR__ . '/../config.php';
            $db = $config['db'];
            $dsn = "mysql:host={$db['host']};dbname={$db['database']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $db['username'], $db['password']);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }
}
?>
