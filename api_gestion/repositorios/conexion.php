<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el ÚNICO lugar del proyecto donde se abre una conexión PDO a
 *   MariaDB. Todos los repositorios concretos lo usan para obtener su
 *   conexión.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Centraliza la conexión por dos razones:
 *     1. Didáctica: quien lea el código ve las 3 líneas que importan
 *        (DSN, usuario, clave) en un solo sitio.
 *     2. Técnica: si mañana cambia el DSN o algún atributo de PDO, se
 *        cambia aquí y no en 5 repositorios.
 *
 * ¿De dónde salen las credenciales?
 *   De variables de entorno (getenv). La API las recibirá del
 *   docker-compose.yml (Fase 6), y para pruebas locales las pone el
 *   desarrollador en PowerShell antes de arrancar php -S (7_quickstart.md
 *   §1):
 *
 *       $env:DB_DSN     = "mysql:host=localhost;port=13426;dbname=bdgestion_mariadb_local"
 *       $env:DB_USUARIO = "paradigmas"
 *       $env:DB_CLAVE   = "paradigmas123"
 *
 *   Si faltan esas variables, el método lanzará una excepción clara, en
 *   vez de intentar conectarse a un valor por defecto que casi seguro
 *   sería incorrecto.
 *
 * ¿Por qué es una clase con métodos estáticos y no una función suelta?
 *   Porque PHP permite funciones globales, pero una clase con métodos
 *   estáticos mantiene todo el código del proyecto dentro de clases
 *   (convención del Artículo 8) y deja el método claramente namespaced
 *   sin necesidad de un namespace formal.
 *
 * ¿Por qué "perezosa" (lazy)?
 *   Porque la conexión NO se abre cuando se carga el archivo, sino la
 *   PRIMERA vez que un repositorio pide una. Si se abriera al cargar, un
 *   listado simple o un endpoint / de diagnóstico sin tocar la BD
 *   pagarían el costo de abrir la conexión aunque no la necesitaran.
 * ---------------------------------------------------------------------------
 */
class Conexion
{
    /**
     * Conexión reutilizable dentro del mismo proceso PHP.
     * Empieza en null y se llena en la primera llamada a obtener().
     * En cada petición HTTP, PHP arranca un proceso nuevo (o reutiliza
     * uno del servidor embebido), así que esta propiedad no persiste
     * entre peticiones: es por proceso, no global.
     */
    private static ?PDO $conexion = null;

    /**
     * Devuelve una conexión PDO ya configurada. La crea la primera vez
     * y la reutiliza en las llamadas siguientes.
     *
     * Configuración que se aplica (3_plan.md §7):
     *   ERRMODE_EXCEPTION       → los errores de BD salen como excepción,
     *                             no como false silencioso.
     *   ATTR_EMULATE_PREPARES   → false: usa prepared statements REALES
     *                             del motor, no emulados por PDO.
     *   ATTR_DEFAULT_FETCH_MODE → FETCH_ASSOC: las filas llegan como
     *                             arreglos asociativos por defecto.
     *   MYSQL_ATTR_FOUND_ROWS   → true: el UPDATE reporta las filas
     *                             ENCONTRADAS por el WHERE, aunque el
     *                             valor no haya cambiado. Sin esto, un
     *                             PATCH que reescribe el mismo valor
     *                             daría 0 filas afectadas y el contrato
     *                             (6_contracts.md §5) exige 1.
     *
     * @throws RuntimeException si faltan las variables de entorno.
     * @throws PDOException    si la conexión falla (BD apagada, etc.).
     */
    public static function obtener(): PDO
    {
        // Si ya hay una conexión abierta en este proceso, la devuelvo.
        if (self::$conexion !== null) {
            return self::$conexion;
        }

        // Leo las tres variables de entorno. Si alguna falta, aviso con
        // un mensaje claro en lugar de intentar conectarme con basura.
        $dsn     = getenv('DB_DSN');
        $usuario = getenv('DB_USUARIO');
        $clave   = getenv('DB_CLAVE');

        if ($dsn === false || $usuario === false || $clave === false) {
            throw new RuntimeException(
                'Faltan las variables de entorno DB_DSN, DB_USUARIO o '
                . 'DB_CLAVE. Ver 7_quickstart.md §1 para cómo definirlas.'
            );
        }

        // Abro la conexión con la configuración fijada por el plan.
        self::$conexion = new PDO(
            $dsn,
            $usuario,
            $clave,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ]
        );

        return self::$conexion;
    }
}