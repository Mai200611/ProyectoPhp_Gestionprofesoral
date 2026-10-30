<?php
declare(strict_types=1);

/**
 * EstudioRealizado.php
 *
 * Modelo clásico de la tabla `estudios_realizados` (5_data_model.md §3.7).
 *
 * Es la primera de las fichas cuya llave la genera la base: `id` es
 * AUTO_INCREMENT. Por eso la propiedad se declara `?int` y no `int`
 * (3_plan §4.2): una ficha recién construida y todavía sin guardar NO tiene
 * id, y el tipo debe decir la verdad. Poner `0` habría inventado un valor
 * que significa otra cosa.
 *
 * `docente` es una FK a `docente.cedula`. Aquí se guarda el valor tal cual
 * (string), sin comprobar si existe: la integridad la defiende la base, y el
 * repositorio traduce su rechazo a un 409 (Artículo 11, 4_research D2).
 */
class EstudioRealizado
{
    private ?int $id;
    private string $titulo;
    private string $universidad;
    private string $fecha;
    private string $tipo;
    private string $ciudad;
    private int $insAcreditada;
    private string $metodologia;
    private string $perfilEgresado;
    private string $pais;
    private bool $activo;
    private string $docente;

    public function __construct(
        ?int $id,
        string $titulo,
        string $universidad,
        string $fecha,
        string $tipo,
        string $ciudad,
        int $insAcreditada,
        string $metodologia,
        string $perfilEgresado,
        string $pais,
        bool $activo,
        string $docente
    ) {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->universidad = $universidad;
        $this->fecha = $fecha;
        $this->tipo = $tipo;
        $this->ciudad = $ciudad;
        $this->insAcreditada = $insAcreditada;
        $this->metodologia = $metodologia;
        $this->perfilEgresado = $perfilEgresado;
        $this->pais = $pais;
        $this->activo = $activo;
        $this->docente = $docente;
    }

    public function obtenerId(): ?int { return $this->id; }
    public function obtenerTitulo(): string { return $this->titulo; }
    public function obtenerUniversidad(): string { return $this->universidad; }
    public function obtenerFecha(): string { return $this->fecha; }
    public function obtenerTipo(): string { return $this->tipo; }
    public function obtenerCiudad(): string { return $this->ciudad; }
    public function obtenerInsAcreditada(): int { return $this->insAcreditada; }
    public function obtenerMetodologia(): string { return $this->metodologia; }
    public function obtenerPerfilEgresado(): string { return $this->perfilEgresado; }
    public function obtenerPais(): string { return $this->pais; }
    public function obtenerActivo(): bool { return $this->activo; }
    public function obtenerDocente(): string { return $this->docente; }

    public function establecerId(?int $id): void { $this->id = $id; }
    public function establecerTitulo(string $titulo): void { $this->titulo = $titulo; }
    public function establecerUniversidad(string $universidad): void { $this->universidad = $universidad; }
    public function establecerFecha(string $fecha): void { $this->fecha = $fecha; }
    public function establecerTipo(string $tipo): void { $this->tipo = $tipo; }
    public function establecerCiudad(string $ciudad): void { $this->ciudad = $ciudad; }
    public function establecerInsAcreditada(int $insAcreditada): void { $this->insAcreditada = $insAcreditada; }
    public function establecerMetodologia(string $metodologia): void { $this->metodologia = $metodologia; }
    public function establecerPerfilEgresado(string $perfilEgresado): void { $this->perfilEgresado = $perfilEgresado; }
    public function establecerPais(string $pais): void { $this->pais = $pais; }
    public function establecerActivo(bool $activo): void { $this->activo = $activo; }
    public function establecerDocente(string $docente): void { $this->docente = $docente; }

    /**
     * Representación del estudio como arreglo asociativo, con las claves en
     * snake_case tal como viajan en el JSON de la API.
     *
     * Si todavía no se ha guardado, `id` sale como null: es la verdad.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'universidad' => $this->universidad,
            'fecha' => $this->fecha,
            'tipo' => $this->tipo,
            'ciudad' => $this->ciudad,
            'ins_acreditada' => $this->insAcreditada,
            'metodologia' => $this->metodologia,
            'perfil_egresado' => $this->perfilEgresado,
            'pais' => $this->pais,
            'activo' => $this->activo,
            'docente' => $this->docente,
        ];
    }
}