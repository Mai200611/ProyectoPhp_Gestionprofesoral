<?php
declare(strict_types=1);

/**
 * conexion.php
 *
 * Fábrica de conexión a MariaDB. Lee las credenciales de las variables de
 * entorno inyectadas por Docker Compose y devuelve una instancia de PDO
 * lista para usarse con prepared statements.
 *
 * Si por alguna razón las variables de entorno no existen (por ejemplo,
 * al correr pruebas locales sin Docker), usa los valores por defecto de
 * la Constitución adaptados a los puertos del compose.
 */

function obtenerConexion(): PDO
{
    $dsn = getenv('DB_DSN');
    if ($dsn === false || $dsn === '') {
        $dsn = 'mysql:host=mariadb;port=3306;dbname=bdgestion_mariadb_local;charset=utf8mb4';
    }

    $usuario = getenv('DB_USUARIO');
    if ($usuario === false || $usuario === '') {
        $usuario = 'paradigmas';
    }

    $clave = getenv('DB_CLAVE');
    if ($clave === false || $clave === '') {
        $clave = 'paradigmas123';
    }

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $usuario, $clave, $opciones);
    } catch (PDOException $e) {
        // Si la conexión falla, respondemos un JSON de error y terminamos.
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'estado' => 500,
            'mensaje' => 'No se pudo conectar a la base de datos.',
            'detalle' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}