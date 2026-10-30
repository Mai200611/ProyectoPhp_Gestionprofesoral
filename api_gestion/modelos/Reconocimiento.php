<?php
declare(strict_types=1);

/**
 * Reconocimiento.php
 *
 * Modelo clásico de la tabla `reconocimiento` (5_data_model.md §3.11).
 *
 * Ficha hija de `docente`: cada fila es un reconocimiento que un docente
 * recibió. Su llave la genera la base (`id` AUTO_INCREMENT), por eso se
 * declara `?int` (3_plan §4.2). El cliente no la manda al crear; la API se
 * la devuelve.
 *
 * La FK `docente` se guarda tal cual (string). Que exista o no lo decide el
 * motor; el rechazo se traduce a 409 en el repositorio (Artículo 11).
 */
class Reconocimiento
{
    private ?int $id;
    private string $tipo;
    private string $fecha;
    private string $institucion;
    private string $nombre;
    private string $ambito;
    private bool $activo;
    private string $docente;

    public function __construct(
        ?int $id,
        string $tipo,
        string $fecha,
        string $institucion,
        string $nombre,
        string $ambito,
        bool $activo,
        string $docente
    ) {
        $this->id = $id;
        $this->tipo = $tipo;
        $this->fecha = $fecha;
        $this->institucion = $institucion;
        $this->nombre = $nombre;
        $this->ambito = $ambito;
        $this->activo = $activo;
        $this->docente = $docente;
    }

    public function obtenerId(): ?int { return $this->id; }
    public function obtenerTipo(): string { return $this->tipo; }
    public function obtenerFecha(): string { return $this->fecha; }
    public function obtenerInstitucion(): string { return $this->institucion; }
    public function obtenerNombre(): string { return $this->nombre; }
    public function obtenerAmbito(): string { return $this->ambito; }
    public function obtenerActivo(): bool { return $this->activo; }
    public function obtenerDocente(): string { return $this->docente; }

    public function establecerId(?int $id): void { $this->id = $id; }
    public function establecerTipo(string $tipo): void { $this->tipo = $tipo; }
    public function establecerFecha(string $fecha): void { $this->fecha = $fecha; }
    public function establecerInstitucion(string $institucion): void { $this->institucion = $institucion; }
    public function establecerNombre(string $nombre): void { $this->nombre = $nombre; }
    public function establecerAmbito(string $ambito): void { $this->ambito = $ambito; }
    public function establecerActivo(bool $activo): void { $this->activo = $activo; }
    public function establecerDocente(string $docente): void { $this->docente = $docente; }

    /**
     * Representación del reconocimiento como arreglo asociativo, con las
     * claves en snake_case tal como viajan en el JSON de la API.
     *
     * Si todavía no se ha guardado, `id` sale como null: es la verdad.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'fecha' => $this->fecha,
            'institucion' => $this->institucion,
            'nombre' => $this->nombre,
            'ambito' => $this->ambito,
            'activo' => $this->activo,
            'docente' => $this->docente,
        ];
    }
}