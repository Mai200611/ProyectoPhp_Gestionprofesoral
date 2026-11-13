<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el MODELO de la tabla `area_conocimiento`. Representa UNA fila de esa
 *   tabla como un objeto PHP.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es la pieza más baja y más simple del sistema: el dato con tipos.
 *   - NO valida (eso lo hace el controlador, en la frontera HTTP).
 *   - NO habla con la base de datos (eso lo hace el repositorio).
 *   - NO conoce HTTP, ni JSON, ni rutas.
 *   Su único trabajo es guardar los 4 campos de una fila y saber devolverse
 *   como arreglo para que la API lo pueda convertir a JSON.
 *
 * ¿Qué columnas representa?
 *   id          → identificador legible de la fila (ej. '1A01')
 *   gran_area   → categoría superior (ej. 'Ciencias Naturales')
 *   area        → subcategoría de la gran área (ej. 'Matemáticas')
 *   disciplina  → subcategoría del área (ej. 'Matemáticas puras')
 *
 * Estilo: "modelo clásico" — propiedades privadas, getters, setters y
 * toArray(). Explícito y repetitivo a propósito, para que se lea y se
 * estudie sin trucos (Artículo 1 de la constitución).
 * ---------------------------------------------------------------------------
 */
class AreaConocimiento
{
    // -----------------------------------------------------------------------
    // PROPIEDADES PRIVADAS
    // Cada una corresponde a una columna de la tabla `area_conocimiento`.
    // Son privadas para que solo se puedan leer y escribir a través de los
    // getters y setters de abajo — así el resto del código no depende de
    // cómo se guardan por dentro.
    // -----------------------------------------------------------------------
    private string $id;
    private string $granArea;
    private string $area;
    private string $disciplina;

    // -----------------------------------------------------------------------
    // CONSTRUCTOR
    // Recibe TODOS los campos de la fila y los deja guardados.
    // Se usa tanto al leer de la BD (el repositorio arma el objeto) como al
    // construir uno nuevo para insertar.
    // -----------------------------------------------------------------------
    public function __construct(
        string $id,
        string $granArea,
        string $area,
        string $disciplina
    ) {
        $this->id = $id;
        $this->granArea = $granArea;
        $this->area = $area;
        $this->disciplina = $disciplina;
    }

    // -----------------------------------------------------------------------
    // GETTERS — para leer cada campo desde afuera.
    // -----------------------------------------------------------------------
    public function getId(): string
    {
        return $this->id;
    }

    public function getGranArea(): string
    {
        return $this->granArea;
    }

    public function getArea(): string
    {
        return $this->area;
    }

    public function getDisciplina(): string
    {
        return $this->disciplina;
    }

    // -----------------------------------------------------------------------
    // SETTERS — para modificar cada campo.
    //
    // Nota didáctica: en la v1, el `id` es la clave primaria y NO se
    // modifica (la identidad de la ficha no cambia al editarla). Por eso
    // aquí NO hay setId(): los demás campos sí tienen setter porque sí
    // se pueden editar.
    // -----------------------------------------------------------------------
    public function setGranArea(string $granArea): void
    {
        $this->granArea = $granArea;
    }

    public function setArea(string $area): void
    {
        $this->area = $area;
    }

    public function setDisciplina(string $disciplina): void
    {
        $this->disciplina = $disciplina;
    }

    // -----------------------------------------------------------------------
    // toArray()
    // Devuelve la fila como arreglo asociativo, con las claves EXACTAS que
    // viajan en el JSON (id, gran_area, area, disciplina).
    //
    // Ojo con los nombres: las propiedades PHP van en camelCase
    // ($granArea), pero el JSON que ve el cliente va en snake_case
    // (gran_area). Esta traducción vive aquí y en ningún otro lado.
    // -----------------------------------------------------------------------
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'gran_area'  => $this->granArea,
            'area'       => $this->area,
            'disciplina' => $this->disciplina,
        ];
    }
}