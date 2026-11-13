<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// REQUIRES
// Solo necesitamos las interfaces, la excepción, el modelo y el servicio
// de Programa. En particular, NO incluimos ningún repositorio MariaDB ni
// la clase Conexion: la gracia de esta prueba es demostrar que el servicio
// funciona SIN base de datos, con un repositorio falso.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../servicios/IServicioPrograma.php';
require_once __DIR__ . '/../servicios/ServicioPrograma.php';
require_once __DIR__ . '/../repositorios/IRepositorioPrograma.php';
require_once __DIR__ . '/../modelos/Programa.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la PRUEBA DE CAPAS: un script que demuestra que el ServicioPrograma
 *   depende de la INTERFAZ del repositorio y no de la clase concreta
 *   MariaDB. Es el criterio 6 de aceptación de la spec.
 *
 * ¿Cómo lo demuestra?
 *   Implementa un repositorio FALSO en memoria (esta clase de abajo,
 *   RepositorioProgramaEnMemoria) que cumple la misma interfaz
 *   IRepositorioPrograma que el repositorio MariaDB, pero guarda los
 *   datos en un array de PHP. Después instancia el servicio con ese
 *   repositorio falso — que es exactamente la operación que un
 *   contenedor de inyección de dependencias haría — y ejercita el ciclo
 *   crear/listar/eliminar sin tocar MariaDB.
 *
 * ¿Cómo se corre?
 *   Desde la raíz del proyecto (con o sin Docker, no importa: esta prueba
 *   NO toca la base):
 *
 *       php api_gestion/pruebas/prueba_capas.php
 *
 *   O dentro del contenedor de la API (Fase 6):
 *
 *       docker compose exec api-gestion php pruebas/prueba_capas.php
 *
 * ¿Qué debe salir?
 *   Cada línea con [OK]. Si sale alguna con [FALLO], las capas quedaron
 *   mal cortadas y hay que revisar los archivos de servicios o
 *   repositorios.
 * ---------------------------------------------------------------------------
 */

// ===========================================================================
// REPOSITORIO FALSO EN MEMORIA
// ===========================================================================
// Esta clase implementa la MISMA interfaz que el repositorio MariaDB, pero
// guarda todo en un array. No hay PDO, no hay SQL, no hay base de datos.
//
// El hecho de que el servicio acepte esta clase sin problema es la prueba
// objetiva de que está programado contra la interfaz y no contra la
// implementación concreta.
// ===========================================================================
class RepositorioProgramaEnMemoria implements IRepositorioPrograma
{
    /** @var array<int, Programa> */
    private array $programas = [];

    /** Cuántas veces se ha llamado a cada método. Útil para diagnóstico. */
    public int $llamadasCrear = 0;
    public int $llamadasObtenerTodos = 0;
    public int $llamadasEliminar = 0;

    public function obtenerTodos(int $limite): array
    {
        $this->llamadasObtenerTodos++;
        // Devuelve hasta $limite elementos del array.
        return array_slice(array_values($this->programas), 0, $limite);
    }

    public function obtenerPorId(int $id): ?Programa
    {
        return $this->programas[$id] ?? null;
    }

    public function crear(Programa $programa): bool
    {
        $this->llamadasCrear++;
        // Guarda el objeto indexado por su id. Si ya existe, falla.
        if (isset($this->programas[$programa->getId()])) {
            return false;
        }
        $this->programas[$programa->getId()] = $programa;
        return true;
    }

    public function actualizar(int $id, array $datos): int
    {
        if (!isset($this->programas[$id])) {
            return 0;
        }
        // Modifica los campos que vengan en $datos.
        $p = $this->programas[$id];
        if (array_key_exists('nombre', $datos)) {
            $p->setNombre((string) $datos['nombre']);
        }
        if (array_key_exists('ciudad', $datos)) {
            $p->setCiudad((string) $datos['ciudad']);
        }
        // (Para la prueba basta con dos campos; los demás los maneja el
        // repositorio real.)
        return 1;
    }

    public function eliminar(int $id): int
    {
        $this->llamadasEliminar++;
        if (!isset($this->programas[$id])) {
            return 0;
        }
        unset($this->programas[$id]);
        return 1;
    }
}

// ===========================================================================
// PEQUEÑO ARNÉS DE PRUEBA
// ===========================================================================
// Tres funciones auxiliares para no repetir código: una para registrar un
// resultado, otra para los encabezados y otra para lanzar la excepción de
// fallo si algo no cuadra.
// ===========================================================================

function afirmar(bool $condicion, string $descripcion): void
{
    if ($condicion) {
        echo "[OK]    {$descripcion}\n";
    } else {
        echo "[FALLO] {$descripcion}\n";
        $GLOBALS['huboFallos'] = true;
    }
}

function titulo(string $texto): void
{
    echo "\n=== {$texto} ===\n";
}

$huboFallos = false;

// ===========================================================================
// PREPARACIÓN
// ===========================================================================
// Instanciamos el repositorio falso y el servicio real, inyectando el
// falso. A partir de aquí, el servicio no sabe (ni puede saber) que no
// hay MariaDB detrás.
// ===========================================================================
$repositorioFalso = new RepositorioProgramaEnMemoria();
$servicio = new ServicioPrograma($repositorioFalso);

// ===========================================================================
// PRUEBA 1: listar cuando está vacío
// ===========================================================================
titulo('Prueba 1: listar con el repositorio vacío');

$vacio = $servicio->obtenerTodos(100);
afirmar($vacio === [], 'obtenerTodos devuelve un array vacío');

// ===========================================================================
// PRUEBA 2: crear y volver a listar
// ===========================================================================
titulo('Prueba 2: crear un programa');

$ok = $servicio->crear([
    'id'                  => 10101,
    'nombre'              => 'Administración de Empresas',
    'tipo'                => 'Programa Académico',
    'nivel'               => 'Pregrado',
    'fecha_creacion'      => 'sin dato',
    'fecha_cierre'        => null,
    'numero_cohortes'     => 'sin dato',
    'cant_graduados'      => 'sin dato',
    'fecha_actualizacion' => 'sin dato',
    'ciudad'              => 'Bogotá',
    'facultad'            => 101,
]);

afirmar($ok === true, 'crear devuelve true');
afirmar($repositorioFalso->llamadasCrear === 1, 'el repositorio recibió exactamente una llamada a crear');

$todos = $servicio->obtenerTodos(100);
afirmar(count($todos) === 1, 'obtenerTodos devuelve ahora 1 programa');
afirmar($todos[0] instanceof Programa, 'el elemento devuelto es un Programa');
afirmar($todos[0]->getNombre() === 'Administración de Empresas', 'el nombre quedó bien guardado');
afirmar($todos[0]->getCiudad() === 'Bogotá', 'la ciudad quedó bien guardada');
afirmar($todos[0]->getFechaCierre() === null, 'fecha_cierre se conservó como null');

// ===========================================================================
// PRUEBA 3: obtener por id
// ===========================================================================
titulo('Prueba 3: obtener por id');

$uno = $servicio->obtenerPorId(10101);
afirmar($uno->getId() === 10101, 'obtenerPorId devuelve el programa con id correcto');

// ===========================================================================
// PRUEBA 4: actualizar (PATCH parcial)
// ===========================================================================
titulo('Prueba 4: actualizar solo la ciudad (PATCH)');

$filas = $servicio->actualizar(10101, ['ciudad' => 'Medellín']);
afirmar($filas === 1, 'actualizar devuelve 1 fila afectada');

$despues = $servicio->obtenerPorId(10101);
afirmar($despues->getCiudad() === 'Medellín', 'la ciudad cambió');
afirmar($despues->getNombre() === 'Administración de Empresas', 'el nombre NO cambió (fue un PATCH)');

// ===========================================================================
// PRUEBA 5: obtener algo que no existe → NoEncontradoExcepcion
// ===========================================================================
titulo('Prueba 5: obtener un id inexistente lanza NoEncontradoExcepcion');

$lanzo = false;
try {
    $servicio->obtenerPorId(999999);
} catch (NoEncontradoExcepcion $e) {
    $lanzo = true;
    afirmar($e->getMessage() === 'Programa no encontrado.', 'el mensaje de la excepción es el esperado');
}
afirmar($lanzo, 'la excepción NoEncontradoExcepcion fue lanzada');

// ===========================================================================
// PRUEBA 6: límite inválido → InvalidArgumentException
// ===========================================================================
titulo('Prueba 6: límite <= 0 lanza InvalidArgumentException');

$lanzo = false;
try {
    $servicio->obtenerTodos(0);
} catch (InvalidArgumentException $e) {
    $lanzo = true;
}
afirmar($lanzo, 'la excepción InvalidArgumentException fue lanzada');

// ===========================================================================
// PRUEBA 7: PATCH con body vacío → InvalidArgumentException
// ===========================================================================
titulo('Prueba 7: actualizar con body vacío lanza InvalidArgumentException');

$lanzo = false;
try {
    $servicio->actualizar(10101, []);
} catch (InvalidArgumentException $e) {
    $lanzo = true;
}
afirmar($lanzo, 'la excepción InvalidArgumentException fue lanzada');

// ===========================================================================
// PRUEBA 8: eliminar
// ===========================================================================
titulo('Prueba 8: eliminar');

$borradas = $servicio->eliminar(10101);
afirmar($borradas === 1, 'eliminar devuelve 1 fila eliminada');
afirmar($repositorioFalso->llamadasEliminar === 1, 'el repositorio recibió la llamada a eliminar');

$quedan = $servicio->obtenerTodos(100);
afirmar($quedan === [], 'ya no quedan programas');

// ===========================================================================
// PRUEBA 9: eliminar algo que no existe → NoEncontradoExcepcion
// ===========================================================================
titulo('Prueba 9: eliminar un id inexistente lanza NoEncontradoExcepcion');

$lanzo = false;
try {
    $servicio->eliminar(999999);
} catch (NoEncontradoExcepcion $e) {
    $lanzo = true;
}
afirmar($lanzo, 'la excepción NoEncontradoExcepcion fue lanzada');

// ===========================================================================
// RESUMEN
// ===========================================================================
echo "\n";
if ($huboFallos) {
    echo "=======================================\n";
    echo "  RESULTADO: HAY FALLOS. Revisar.\n";
    echo "=======================================\n";
    exit(1);
}

echo "=======================================\n";
echo "  RESULTADO: TODO OK.\n";
echo "  El servicio funciona SIN MariaDB:\n";
echo "  las capas quedaron bien cortadas.\n";
echo "=======================================\n";
exit(0);