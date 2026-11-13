<?php
declare(strict_types=1);

class Conexion
{
    private static ?PDO $instancia = null;

    public static function obtener(): PDO
    {
        if (self::$instancia !== null) return self::$instancia;
        $motor = getenv('DB_MOTOR') ?: 'mariadb';
        
        if ($motor === 'postgres') {
            $dsn = getenv('POSTGRES_DSN'); $usuario = getenv('POSTGRES_USUARIO'); $clave = getenv('POSTGRES_CLAVE');
        } else {
            $dsn = getenv('MARIADB_DSN'); $usuario = getenv('MARIADB_USUARIO'); $clave = getenv('MARIADB_CLAVE');
        }

        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($motor === 'mariadb' && defined('PDO::MYSQL_ATTR_FOUND_ROWS')) {
            $opciones[PDO::MYSQL_ATTR_FOUND_ROWS] = true;
        }

        self::$instancia = new PDO((string)$dsn, (string)$usuario, (string)$clave, $opciones);
        return self::$instancia;
    }
}