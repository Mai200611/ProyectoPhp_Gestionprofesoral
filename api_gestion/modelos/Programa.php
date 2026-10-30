<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el MODELO de la tabla `programa`. Representa UNA fila de esa tabla
 *   como un objeto PHP.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los otros modelos: el dato con tipos. NO valida, NO habla con
 *   la BD, NO conoce HTTP. Solo guarda los campos de una fila y sabe
 *   devolverse como arreglo para que la API lo convierta a JSON.
 *
 * ¿Qué columnas representa?
 *   id                   → identificador del programa académico (INTEGER)
 *   nombre               → nombre del programa
 *   tipo                 → tipo (Programa Académico, etc.)
 *   nivel                → nivel (Pregrado, Maestría, etc.)
 *   fecha_creacion       → fecha de creación (VARCHAR en la BD)
 *   fecha_cierre         → fecha de cierre (VARCHAR, admite NULL)
 *   numero_cohortes      → número de cohortes (VARCHAR en la BD)
 *   cant_graduados       → cantidad de graduados (VARCHAR en la BD)
 *   fecha_actualizacion  → fecha de actualización (VARCHAR)
 *   ciudad               → ciudad del programa
 *   facultad             → facultad (INTEGER)
 *
 * Nota didáctica sobre los tipos:
 *   El esquema de la BD guarda fechas y cantidades como VARCHAR. Eso quiere
 *   decir que aquí, en el modelo, también viajan como string — el modelo
 *   respeta el esquema, no lo corrige. La validación de que un valor como
 *   `cant_graduados` sea numérico o tenga un formato esperado vive en el
 *   controlador (Fase 5). El modelo es solo el dato con el tipo que tiene
 *   en la BD.
 *
 * Nota sobre `fecha_cierre` y `cant_graduados`:
 *   Ambos admiten el valor NULL en la BD (los datos de ejemplo traen
 *   "sin dato" o vacío). Por eso se declaran como `?string` — un string
 *   que puede ser null.
 *
 * Estilo: "modelo clásico" — propiedades privadas, getters, setters y
 * toArray().
 * ---------------------------------------------------------------------------
 */
class Programa
{
    // -----------------------------------------------------------------------
    // PROPIEDADES PRIVADAS
    // -----------------------------------------------------------------------
    private int $id;
    private string $nombre;
    private string $tipo;
    private string $nivel;
    private string $fechaCreacion;
    private ?string $fechaCierre;
    private string $numeroCohortes;
    private string $cantGraduados;
    private string $fechaActualizacion;
    private string $ciudad;
    private int $facultad;

    // -----------------------------------------------------------------------
    // CONSTRUCTOR
    // Recibe TODOS los campos de la fila. El orden sigue el de la tabla en
    // el esquema (5_data_model.md §2), para que sea fácil comparar el uno
    // con el otro.
    // -----------------------------------------------------------------------
    public function __construct(
        int $id,
        string $nombre,
        string $tipo,
        string $nivel,
        string $fechaCreacion,
        ?string $fechaCierre,
        string $numeroCohortes,
        string $cantGraduados,
        string $fechaActualizacion,
        string $ciudad,
        int $facultad
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->tipo = $tipo;
        $this->nivel = $nivel;
        $this->fechaCreacion = $fechaCreacion;
        $this->fechaCierre = $fechaCierre;
        $this->numeroCohortes = $numeroCohortes;
        $this->cantGraduados = $cantGraduados;
        $this->fechaActualizacion = $fechaActualizacion;
        $this->ciudad = $ciudad;
        $this->facultad = $facultad;
    }

    // -----------------------------------------------------------------------
    // GETTERS
    // -----------------------------------------------------------------------
    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getNivel(): string
    {
        return $this->nivel;
    }

    public function getFechaCreacion(): string
    {
        return $this->fechaCreacion;
    }

    public function getFechaCierre(): ?string
    {
        return $this->fechaCierre;
    }

    public function getNumeroCohortes(): string
    {
        return $this->numeroCohortes;
    }

    public function getCantGraduados(): string
    {
        return $this->cantGraduados;
    }

    public function getFechaActualizacion(): string
    {
        return $this->fechaActualizacion;
    }

    public function getCiudad(): string
    {
        return $this->ciudad;
    }

    public function getFacultad(): int
    {
        return $this->facultad;
    }

    // -----------------------------------------------------------------------
    // SETTERS
    //
    // Nota didáctica: el `id` es la clave primaria y NO se modifica al
    // editar. Todos los demás campos sí son editables, así que cada uno
    // tiene su setter.
    // -----------------------------------------------------------------------
    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setTipo(string $tipo): void
    {
        $this->tipo = $tipo;
    }

    public function setNivel(string $nivel): void
    {
        $this->nivel = $nivel;
    }

    public function setFechaCreacion(string $fechaCreacion): void
    {
        $this->fechaCreacion = $fechaCreacion;
    }

    public function setFechaCierre(?string $fechaCierre): void
    {
        $this->fechaCierre = $fechaCierre;
    }

    public function setNumeroCohortes(string $numeroCohortes): void
    {
        $this->numeroCohortes = $numeroCohortes;
    }

    public function setCantGraduados(string $cantGraduados): void
    {
        $this->cantGraduados = $cantGraduados;
    }

    public function setFechaActualizacion(string $fechaActualizacion): void
    {
        $this->fechaActualizacion = $fechaActualizacion;
    }

    public function setCiudad(string $ciudad): void
    {
        $this->ciudad = $ciudad;
    }

    public function setFacultad(int $facultad): void
    {
        $this->facultad = $facultad;
    }

    // -----------------------------------------------------------------------
    // toArray()
    // Devuelve la fila como arreglo con las claves EXACTAS del JSON.
    // Los campos numéricos se fuerzan a int para que viajen como números
    // y no como texto — recuerda que el driver de MariaDB a veces entrega
    // los enteros como string.
    // -----------------------------------------------------------------------
    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'nombre'              => $this->nombre,
            'tipo'                => $this->tipo,
            'nivel'               => $this->nivel,
            'fecha_creacion'      => $this->fechaCreacion,
            'fecha_cierre'        => $this->fechaCierre,
            'numero_cohortes'     => $this->numeroCohortes,
            'cant_graduados'      => $this->cantGraduados,
            'fecha_actualizacion' => $this->fechaActualizacion,
            'ciudad'              => $this->ciudad,
            'facultad'            => $this->facultad,
        ];
    }
}