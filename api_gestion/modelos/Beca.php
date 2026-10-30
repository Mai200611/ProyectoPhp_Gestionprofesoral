<?php
declare(strict_types=1);

/**
 * Beca.php
 *
 * Modelo clásico de la tabla `beca` (5_data_model.md §3.16).
 *
 * Ficha hija de `estudios_realizados`: cada fila es una beca que un estudio
 * recibió. Su llave primaria es `estudios` SOLA (no es compuesta, aunque el
 * §8 de 6_contracts la etiquete como `(id_estudio, tipo_beca)` — eso es una
 * etiqueta didáctica, no la PK real). Gana 5_data_model.md (instrucción 8
 * de la petición): la tabla tiene UNA beca por estudio, y por eso el
 * `estudios` es PK y FK a la vez.
 *
 * Consecuencias que no se ven aquí pero sí en el resto del sistema:
 *   - El cliente manda el `estudios` en el body del POST; es la llave.
 *   - No puede existir dos filas con el mismo `estudios`; lo defiende la base.
 *   - El PUT y el DELETE reciben el `estudios` en la ruta.
 *
 * `fecha_fin` es opcional en la tabla (puede ser NULL): la beca puede seguir
 * vigente. Por eso se declara `?string`.
 */
class Beca
{
    private int $estudios;
    private string $tipo;
    private string $institucion;
    private string $fechaInicio;
    private ?string $fechaFin;
    private bool $activo;

    public function __construct(
        int $estudios,
        string $tipo,
        string $institucion,
        string $fechaInicio,
        ?string $fechaFin,
        bool $activo
    ) {
        $this->estudios = $estudios;
        $this->tipo = $tipo;
        $this->institucion = $institucion;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->activo = $activo;
    }

    public function obtenerEstudios(): int { return $this->estudios; }
    public function obtenerTipo(): string { return $this->tipo; }
    public function obtenerInstitucion(): string { return $this->institucion; }
    public function obtenerFechaInicio(): string { return $this->fechaInicio; }
    public function obtenerFechaFin(): ?string { return $this->fechaFin; }
    public function obtenerActivo(): bool { return $this->activo; }

    public function establecerTipo(string $tipo): void { $this->tipo = $tipo; }
    public function establecerInstitucion(string $institucion): void { $this->institucion = $institucion; }
    public function establecerFechaInicio(string $fechaInicio): void { $this->fechaInicio = $fechaInicio; }
    public function establecerFechaFin(?string $fechaFin): void { $this->fechaFin = $fechaFin; }
    public function establecerActivo(bool $activo): void { $this->activo = $activo; }

    /**
     * Representación de la beca como arreglo asociativo, con las claves en
     * snake_case tal como viajan en el JSON de la API.
     */
    public function toArray(): array
    {
        return [
            'estudios' => $this->estudios,
            'tipo' => $this->tipo,
            'institucion' => $this->institucion,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'activo' => $this->activo,
        ];
    }
}