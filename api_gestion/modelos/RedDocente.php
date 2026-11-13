<?php
declare(strict_types=1);

/**
 * RedDocente.php
 *
 * Modelo clásico de la tabla puente `red_docente` (5_data_model.md §3.13).
 *
 * Une una `red` con un `docente`. Su llave primaria es COMPUESTA:
 *
 *     PK = (red, docente)
 *
 * Consecuencias que no se ven aquí pero sí en el resto del sistema:
 *   - El cliente manda las dos llaves en el body del POST.
 *   - No puede existir dos veces la misma pareja; lo defiende la base.
 *   - El PUT y el DELETE reciben las dos llaves en la ruta.
 *
 * Ojo con los nombres de columna (5_data_model gana sobre 6_contracts §8,
 * que usa etiquetas didácticas): las columnas reales son `red` y `docente`;
 * 6_contracts §8 las llama `idr` y `cedula`. Gana 5_data_model.md
 * (instrucción 8 de la petición).
 *
 * `fecha_fin` es opcional en la tabla (puede ser NULL): el vínculo puede
 * seguir vigente. Por eso se declara `?string`.
 */
class RedDocente
{
    private int $red;
    private string $docente;
    private string $fechaInicio;
    private ?string $fechaFin;
    private string $actDestacadas;
    private bool $activo;

    public function __construct(
        int $red,
        string $docente,
        string $fechaInicio,
        ?string $fechaFin,
        string $actDestacadas,
        bool $activo
    ) {
        $this->red = $red;
        $this->docente = $docente;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->actDestacadas = $actDestacadas;
        $this->activo = $activo;
    }

    public function obtenerRed(): int { return $this->red; }
    public function obtenerDocente(): string { return $this->docente; }
    public function obtenerFechaInicio(): string { return $this->fechaInicio; }
    public function obtenerFechaFin(): ?string { return $this->fechaFin; }
    public function obtenerActDestacadas(): string { return $this->actDestacadas; }
    public function obtenerActivo(): bool { return $this->activo; }

    public function establecerFechaInicio(string $fechaInicio): void { $this->fechaInicio = $fechaInicio; }
    public function establecerFechaFin(?string $fechaFin): void { $this->fechaFin = $fechaFin; }
    public function establecerActDestacadas(string $actDestacadas): void { $this->actDestacadas = $actDestacadas; }
    public function establecerActivo(bool $activo): void { $this->activo = $activo; }

    /**
     * Representación del vínculo como arreglo asociativo, con las claves en
     * snake_case tal como viajan en el JSON de la API.
     */
    public function toArray(): array
    {
        return [
            'red' => $this->red,
            'docente' => $this->docente,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'act_destacadas' => $this->actDestacadas,
            'activo' => $this->activo,
        ];
    }
}