<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el MODELO de la tabla `termino_clave`. Representa UNA fila de esa
 *   tabla como un objeto PHP.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los otros modelos: el dato con tipos. NO valida, NO habla con
 *   la BD, NO conoce HTTP. Solo guarda los 2 campos de una fila y sabe
 *   devolverse como arreglo para que la API lo convierta a JSON.
 *
 * ¿Qué columnas representa?
 *   termino         → palabra clave (es la CLAVE PRIMARIA de la tabla)
 *   termino_ingles  → su traducción al inglés
 *
 * Nota sobre la clave primaria: aquí el identificador NO se llama `id`,
 * se llama `termino` y es un string. Por eso este modelo se ve distinto
 * al de AreaConocimiento: no hay setId() ni un campo `id`, y el "nombre"
 * de la fila es el propio término. Es lo que el contrato (6_contracts.md
 * §2) llama un recurso identificado por `termino`.
 *
 * Estilo: "modelo clásico" — propiedades privadas, getters, setters y
 * toArray().
 * ---------------------------------------------------------------------------
 */
class TerminoClave
{
    // -----------------------------------------------------------------------
    // PROPIEDADES PRIVADAS
    // -----------------------------------------------------------------------
    private string $termino;
    private string $terminoIngles;

    // -----------------------------------------------------------------------
    // CONSTRUCTOR
    // Recibe los dos campos y los deja guardados.
    // -----------------------------------------------------------------------
    public function __construct(
        string $termino,
        string $terminoIngles
    ) {
        $this->termino = $termino;
        $this->terminoIngles = $terminoIngles;
    }

    // -----------------------------------------------------------------------
    // GETTERS
    // -----------------------------------------------------------------------
    public function getTermino(): string
    {
        return $this->termino;
    }

    public function getTerminoIngles(): string
    {
        return $this->terminoIngles;
    }

    // -----------------------------------------------------------------------
    // SETTERS
    //
    // Nota didáctica: el `termino` es la clave primaria y NO se modifica al
    // editar (igual que el id en los otros modelos). El único campo
    // editable es `termino_ingles`, así que solo él tiene setter.
    // Esto coincide con el contrato: en PUT y PATCH de termino_clave solo
    // viaja el campo termino_ingles.
    // -----------------------------------------------------------------------
    public function setTerminoIngles(string $terminoIngles): void
    {
        $this->terminoIngles = $terminoIngles;
    }

    // -----------------------------------------------------------------------
    // toArray()
    // Devuelve la fila como arreglo con las claves EXACTAS del JSON.
    // -----------------------------------------------------------------------
    public function toArray(): array
    {
        return [
            'termino'        => $this->termino,
            'termino_ingles' => $this->terminoIngles,
        ];
    }
}