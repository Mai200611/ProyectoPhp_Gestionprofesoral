<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el MODELO de la tabla `linea_investigacion`. Representa UNA fila de
 *   esa tabla como un objeto PHP.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los otros modelos: el dato con tipos. NO valida, NO habla con
 *   la BD, NO conoce HTTP. Solo guarda los 3 campos de una fila y sabe
 *   devolverse como arreglo para que la API lo convierta a JSON.
 *
 * ¿Qué columnas representa?
 *   id          → identificador autoincremental (INTEGER, lo genera la BD)
 *   nombre      → nombre de la línea de investigación
 *   descripcion → texto que explica su temática o propósito
 *
 * Nota sobre el id: a diferencia de `area_conocimiento` (que tiene un id
 * tipo VARCHAR con valores legibles como '1A01'), aquí el id es un entero
 * autoincremental generado por MariaDB. Por eso es `int` y en el POST de
 * creación NO se envía: la BD lo inventa. El contrato lo refleja así.
 *
 * Estilo: "modelo clásico" — propiedades privadas, getters, setters y
 * toArray().
 * ---------------------------------------------------------------------------
 */
class LineaInvestigacion
{
    // -----------------------------------------------------------------------
    // PROPIEDADES PRIVADAS
    // -----------------------------------------------------------------------
    private int $id;
    private string $nombre;
    private string $descripcion;

    // -----------------------------------------------------------------------
    // CONSTRUCTOR
    // Recibe los tres campos y los deja guardados. El `id` va primero para
    // respetar el orden natural de la tabla.
    // -----------------------------------------------------------------------
    public function __construct(
        int $id,
        string $nombre,
        string $descripcion
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
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

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    // -----------------------------------------------------------------------
    // SETTERS
    //
    // Nota didáctica: el `id` es la clave primaria y NO se modifica al
    // editar. Solo los campos editables (nombre, descripcion) tienen
    // setter. Esto coincide con el contrato: en PUT y PATCH de
    // linea_investigacion solo viajan nombre y descripcion.
    // -----------------------------------------------------------------------
    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    // -----------------------------------------------------------------------
    // toArray()
    // Devuelve la fila como arreglo con las claves EXACTAS del JSON.
    // El `id` se fuerza a int para que el JSON lo traiga como número y no
    // como texto — el driver de MariaDB a veces devuelve los enteros como
    // string y este es el punto donde se corrige.
    // -----------------------------------------------------------------------
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
        ];
    }
}