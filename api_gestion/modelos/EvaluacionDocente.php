<?php
declare(strict_types=1);

/**
 * EvaluacionDocente.php
 *
 * Modelo clásico de la tabla `evaluacion_docente` (5_data_model.md §3.10).
 *
 * Ficha hija de `docente`: cada fila es la calificación de un docente en un
 * semestre. Su llave la genera la base (`id` AUTO_INCREMENT), por eso se
 * declara `?int` (3_plan §4.2). El cliente no la manda al crear; la API se
 * la devuelve.
 *
 * La FK `docente` se guarda tal cual (string). Que exista o no lo decide el
 * motor; el rechazo se traduce a 409 en el repositorio (Artículo 11).
 */
class EvaluacionDocente
{
    private ?int $id;
    private float $calificacion;
    private string $semestre;
    private bool $activo;
    private string $docente;

    public function __construct(
        ?int $id,
        float $calificacion,
        string $semestre,
        bool $activo,
        string $docente
    ) {
        $this->id = $id;
        $this->calificacion = $calificacion;
        $this->semestre = $semestre;
        $this->activo = $activo;
        $this->docente = $docente;
    }

    public function obtenerId(): ?int { return $this->id; }
    public function obtenerCalificacion(): float { return $this->calificacion; }
    public function obtenerSemestre(): string { return $this->semestre; }
    public function obtenerActivo(): bool { return $this->activo; }
    public function obtenerDocente(): string { return $this->docente; }

    public function establecerId(?int $id): void { $this->id = $id; }
    public function establecerCalificacion(float $calificacion): void { $this->calificacion = $calificacion; }
    public function establecerSemestre(string $semestre): void { $this->semestre = $semestre; }
    public function establecerActivo(bool $activo): void { $this->activo = $activo; }
    public function establecerDocente(string $docente): void { $this->docente = $docente; }

    /**
     * Representación de la evaluación como arreglo asociativo, con las claves
     * en snake_case tal como viajan en el JSON de la API.
     *
     * Si todavía no se ha guardado, `id` sale como null: es la verdad.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'calificacion' => $this->calificacion,
            'semestre' => $this->semestre,
            'activo' => $this->activo,
            'docente' => $this->docente,
        ];
    }
}