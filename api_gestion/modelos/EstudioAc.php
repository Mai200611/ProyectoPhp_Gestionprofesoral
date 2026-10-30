<?php
declare(strict_types=1);

/**
 * EstudioAc.php
 *
 * Modelo clásico de la tabla puente `estudios_ac` (5_data_model.md §3.14).
 *
 * Une un `estudios_realizados` con un `area_conocimiento`. Su llave primaria
 * es COMPUESTA:
 *
 *     PK = (estudio, area_conocimiento)
 *
 * Consecuencias que no se ven aquí pero sí en el resto del sistema:
 *   - El cliente manda las dos llaves en el body del POST.
 *   - No puede existir dos veces la misma pareja; lo defiende la base.
 *   - El PUT y el DELETE reciben las dos llaves en la ruta.
 *
 * Ojo con los nombres de columna (5_data_model gana sobre 6_contracts §8,
 * que usa etiquetas didácticas): las columnas reales son `estudio` y
 * `area_conocimiento`; 6_contracts §8 las llama `id_estudio` e `id_area`.
 * Gana 5_data_model.md (instrucción 8 de la petición). Además, en
 * 5_data_model.md la tabla se llama `estudios_ac` (con «s»), aunque el
 * recurso público de la API sea `/api/estudio_ac` (sin «s») según 6_contracts.
 * Se respetan los dos nombres: la tabla en el SQL y el recurso en la ruta.
 *
 * Es la puente más delgada junto con `intereses_futuros`: solo guarda la
 * relación y el campo `activo`.
 */
class EstudioAc
{
    private int $estudio;
    private string $areaConocimiento;
    private bool $activo;

    public function __construct(int $estudio, string $areaConocimiento, bool $activo)
    {
        $this->estudio = $estudio;
        $this->areaConocimiento = $areaConocimiento;
        $this->activo = $activo;
    }

    public function obtenerEstudio(): int { return $this->estudio; }
    public function obtenerAreaConocimiento(): string { return $this->areaConocimiento; }
    public function obtenerActivo(): bool { return $this->activo; }

    public function establecerActivo(bool $activo): void { $this->activo = $activo; }

    /**
     * Representación del vínculo como arreglo asociativo, con las claves en
     * snake_case tal como viajan en el JSON de la API.
     */
    public function toArray(): array
    {
        return [
            'estudio' => $this->estudio,
            'area_conocimiento' => $this->areaConocimiento,
            'activo' => $this->activo,
        ];
    }
}