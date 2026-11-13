<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el MODELO de la tabla `red`. Representa UNA fila de esa tabla como
 *   un objeto PHP.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los otros modelos: el dato con tipos. NO valida, NO habla con
 *   la BD, NO conoce HTTP. Solo guarda los 4 campos de una fila y sabe
 *   devolverse como arreglo para que la API lo convierta a JSON.
 *
 * ¿Qué columnas representa?
 *   idr    → identificador de la red (INTEGER, es la CLAVE PRIMARIA)
 *   nombre → nombre de la red académica
 *   url    → dirección web de la red (se guarda como texto, NO se visita)
 *   pais   → país de la red
 *
 * Nota sobre la clave primaria: aquí el identificador se llama `idr`, no
 * `id`. Es un int, pero el nombre propio de la columna se respeta en toda
 * la API: el contrato dice `/api/red/{idr}`, y el modelo lo refleja.
 *
 * Nota sobre `url`: es solo un campo de texto. La v1 NO visita la URL,
 * NO la valida conectándose al sitio, NO descarga nada de ella. Es un
 * dato más de la fila.
 *
 * Estilo: "modelo clásico" — propiedades privadas, getters, setters y
 * toArray().
 * ---------------------------------------------------------------------------
 */
class Red
{
    // -----------------------------------------------------------------------
    // PROPIEDADES PRIVADAS
    // -----------------------------------------------------------------------
    private int $idr;
    private string $nombre;
    private string $url;
    private string $pais;

    // -----------------------------------------------------------------------
    // CONSTRUCTOR
    // Recibe los cuatro campos y los deja guardados.
    // -----------------------------------------------------------------------
    public function __construct(
        int $idr,
        string $nombre,
        string $url,
        string $pais
    ) {
        $this->idr = $idr;
        $this->nombre = $nombre;
        $this->url = $url;
        $this->pais = $pais;
    }

    // -----------------------------------------------------------------------
    // GETTERS
    // -----------------------------------------------------------------------
    public function getIdr(): int
    {
        return $this->idr;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPais(): string
    {
        return $this->pais;
    }

    // -----------------------------------------------------------------------
    // SETTERS
    //
    // Nota didáctica: el `idr` es la clave primaria y NO se modifica al
    // editar. Los otros tres campos sí son editables, así que tienen
    // setter.
    // -----------------------------------------------------------------------
    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setPais(string $pais): void
    {
        $this->pais = $pais;
    }

    // -----------------------------------------------------------------------
    // toArray()
    // Devuelve la fila como arreglo con las claves EXACTAS del JSON.
    // El `idr` se fuerza a int para que viaje como número.
    // -----------------------------------------------------------------------
    public function toArray(): array
    {
        return [
            'idr'    => $this->idr,
            'nombre' => $this->nombre,
            'url'    => $this->url,
            'pais'   => $this->pais,
        ];
    }
}