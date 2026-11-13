<?php
declare(strict_types=1);

/**
 * InteresFuturo.php
 *
 * Modelo clásico de la tabla puente `intereses_futuros` (5_data_model.md §3.9).
 *
 * Une un `docente` con un `termino_clave`. Su llave primaria es COMPUESTA:
 *
 *     PK = (docente, termino_clave)
 *
 * Es la tabla puente más delgada del sistema: solo guarda la relación y el
 * campo `activo`. No hay más datos propios. Por eso no hay setters para las
 * llaves: en un puente, cambiar la llave es crear otra fila, no editar esta.
 *
 * La tabla resumen del §8 de 6_contracts usa la etiqueta `termino` para el
 * campo `termino_clave`; gana 5_data_model.md (instrucción 8 de la petición):
 * la columna real se llama `termino_clave` y así se llama aquí.
 */
class InteresFuturo
{
    private string $docente;
    private string $terminoClave;
    private bool $activo;

    public function __construct(string $docente, string $terminoClave, bool $activo)
    {
        $this->docente = $docente;
        $this->terminoClave = $terminoClave;
        $this->activo = $activo;
    }

    public function obtenerDocente(): string { return $this->docente; }
    public function obtenerTerminoClave(): string { return $this->terminoClave; }
    public function obtenerActivo(): bool { return $this->activo; }

    public function establecerActivo(bool $activo): void { $this->activo = $activo; }

    /**
     * Representación del interés como arreglo asociativo, con las claves en
     * snake_case tal como viajan en el JSON de la API.
     */
    public function toArray(): array
    {
        return [
            'docente' => $this->docente,
            'termino_clave' => $this->terminoClave,
            'activo' => $this->activo,
        ];
    }
}