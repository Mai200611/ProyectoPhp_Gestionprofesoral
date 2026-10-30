<?php
declare(strict_types=1);

/**
 * ApoyoProfesoral.php
 *
 * Modelo clásico de la tabla `apoyo_profesoral` (5_data_model.md §3.15).
 *
 * Ficha hija de `estudios_realizados`: cada fila es un apoyo institucional
 * que un estudio recibió. Su llave primaria es `estudios` SOLA (no es
 * compuesta, aunque el §8 de 6_contracts la etiquete como `(id_estudio,
 * tipo_apoyo)` — eso es una etiqueta didáctica, no la PK real). Gana
 * 5_data_model.md (instrucción 8 de la petición): la tabla tiene UN apoyo
 * por estudio, y por eso el `estudios` es PK y FK a la vez.
 *
 * Consecuencias que no se ven aquí pero sí en el resto del sistema:
 *   - El cliente manda el `estudios` en el body del POST; es la llave.
 *   - No puede existir dos filas con el mismo `estudios`; lo defiende la base.
 *   - El PUT y el DELETE reciben el `estudios` en la ruta.
 *
 * `con_apoyo` es TINYINT en la base (0/1). Aquí se modela como `int` porque
 * es un indicador numérico, no un booleano de negocio.
 */
class ApoyoProfesoral
{
    private int $estudios;
    private int $conApoyo;
    private string $institucion;
    private string $tipo;
    private bool $activo;

    public function __construct(
        int $estudios,
        int $conApoyo,
        string $institucion,
        string $tipo,
        bool $activo
    ) {
        $this->estudios = $estudios;
        $this->conApoyo = $conApoyo;
        $this->institucion = $institucion;
        $this->tipo = $tipo;
        $this->activo = $activo;
    }

    public function obtenerEstudios(): int { return $this->estudios; }
    public function obtenerConApoyo(): int { return $this->conApoyo; }
    public function obtenerInstitucion(): string { return $this->institucion; }
    public function obtenerTipo(): string { return $this->tipo; }
    public function obtenerActivo(): bool { return $this->activo; }

    public function establecerConApoyo(int $conApoyo): void { $this->conApoyo = $conApoyo; }
    public function establecerInstitucion(string $institucion): void { $this->institucion = $institucion; }
    public function establecerTipo(string $tipo): void { $this->tipo = $tipo; }
    public function establecerActivo(bool $activo): void { $this->activo = $activo; }

    /**
     * Representación del apoyo como arreglo asociativo, con las claves en
     * snake_case tal como viajan en el JSON de la API.
     */
    public function toArray(): array
    {
        return [
            'estudios' => $this->estudios,
            'con_apoyo' => $this->conApoyo,
            'institucion' => $this->institucion,
            'tipo' => $this->tipo,
            'activo' => $this->activo,
        ];
    }
}