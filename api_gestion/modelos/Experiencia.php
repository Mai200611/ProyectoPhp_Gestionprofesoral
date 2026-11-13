<?php
declare(strict_types=1);

/**
 * Experiencia.php
 *
 * Modelo clásico de la tabla `experiecia` (5_data_model.md §3.12).
 *
 * ERRATA HISTÓRICA: la tabla en la base se llama `experiecia` (así, sin la
 * «n»). No se corrige en v2 (3_plan §6). La CLASE, en cambio, sí se llama
 * `Experiencia` en PascalCase correcto, como cualquier otra. Quien lea el
 * código verá `Experiencia`; quien abra la base verá `experiecia`. Se
 * documenta aquí para que nadie crea que es un typo del código.
 *
 * Ficha hija de `docente`: cada fila es un cargo que un docente desempeñó.
 * Su llave la genera la base (`id` AUTO_INCREMENT), por eso se declara
 * `?int` (3_plan §4.2).
 *
 * `fecha_fin` es opcional en la tabla (puede ser NULL): un cargo puede seguir
 * vigente. Por eso se declara `?string`.
 *
 * La FK `docente` se guarda tal cual (string). Que exista o no lo decide el
 * motor; el rechazo se traduce a 409 en el repositorio (Artículo 11).
 */
class Experiencia
{
    private ?int $id;
    private string $nombreCargo;
    private string $institucion;
    private string $tipo;
    private string $fechaInicio;
    private ?string $fechaFin;
    private bool $activo;
    private string $docente;

    public function __construct(
        ?int $id,
        string $nombreCargo,
        string $institucion,
        string $tipo,
        string $fechaInicio,
        ?string $fechaFin,
        bool $activo,
        string $docente
    ) {
        $this->id = $id;
        $this->nombreCargo = $nombreCargo;
        $this->institucion = $institucion;
        $this->tipo = $tipo;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->activo = $activo;
        $this->docente = $docente;
    }

    public function obtenerId(): ?int { return $this->id; }
    public function obtenerNombreCargo(): string { return $this->nombreCargo; }
    public function obtenerInstitucion(): string { return $this->institucion; }
    public function obtenerTipo(): string { return $this->tipo; }
    public function obtenerFechaInicio(): string { return $this->fechaInicio; }
    public function obtenerFechaFin(): ?string { return $this->fechaFin; }
    public function obtenerActivo(): bool { return $this->activo; }
    public function obtenerDocente(): string { return $this->docente; }

    public function establecerId(?int $id): void { $this->id = $id; }
    public function establecerNombreCargo(string $nombreCargo): void { $this->nombreCargo = $nombreCargo; }
    public function establecerInstitucion(string $institucion): void { $this->institucion = $institucion; }
    public function establecerTipo(string $tipo): void { $this->tipo = $tipo; }
    public function establecerFechaInicio(string $fechaInicio): void { $this->fechaInicio = $fechaInicio; }
    public function establecerFechaFin(?string $fechaFin): void { $this->fechaFin = $fechaFin; }
    public function establecerActivo(bool $activo): void { $this->activo = $activo; }
    public function establecerDocente(string $docente): void { $this->docente = $docente; }

    /**
     * Representación de la experiencia como arreglo asociativo, con las
     * claves en snake_case tal como viajan en el JSON de la API.
     *
     * Si todavía no se ha guardado, `id` sale como null: es la verdad.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre_cargo' => $this->nombreCargo,
            'institucion' => $this->institucion,
            'tipo' => $this->tipo,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'activo' => $this->activo,
            'docente' => $this->docente,
        ];
    }
}