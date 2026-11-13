<?php
declare(strict_types=1);

/**
 * DocenteDepartamento.php
 *
 * Modelo clásico de la tabla puente `docente_departamento` (5_data_model.md §3.8).
 *
 * Es una tabla puente entre `docente` y `programa` (el «departamento»).
 * Por eso su llave primaria es COMPUESTA:
 *
 *     PK = (docente, departamento)
 *
 * Consecuencias que no se ven en el modelo pero sí en el resto del sistema:
 *   - El cliente manda las dos llaves en el body del POST.
 *   - No puede existir dos veces la misma pareja en la tabla; eso lo
 *     defiende la base, no PHP.
 *   - El `PUT` y el `DELETE` reciben las dos llaves en la ruta.
 *
 * Ojo con los nombres de columna (5_data_model gana sobre 6_contracts §8,
 * que usa etiquetas didácticas): las columnas reales son `docente` y
 * `departamento`; el comentario de la tabla dice «departamento» pero el
 * destino real de la FK es `programa.id`.
 */
class DocenteDepartamento
{
    private string $docente;
    private int $departamento;
    private string $dedicacion;
    private string $modalidad;
    private string $fechaIngreso;
    private ?string $fechaSalida;
    private bool $activo;

    public function __construct(
        string $docente,
        int $departamento,
        string $dedicacion,
        string $modalidad,
        string $fechaIngreso,
        ?string $fechaSalida,
        bool $activo
    ) {
        $this->docente = $docente;
        $this->departamento = $departamento;
        $this->dedicacion = $dedicacion;
        $this->modalidad = $modalidad;
        $this->fechaIngreso = $fechaIngreso;
        $this->fechaSalida = $fechaSalida;
        $this->activo = $activo;
    }

    public function obtenerDocente(): string { return $this->docente; }
    public function obtenerDepartamento(): int { return $this->departamento; }
    public function obtenerDedicacion(): string { return $this->dedicacion; }
    public function obtenerModalidad(): string { return $this->modalidad; }
    public function obtenerFechaIngreso(): string { return $this->fechaIngreso; }
    public function obtenerFechaSalida(): ?string { return $this->fechaSalida; }
    public function obtenerActivo(): bool { return $this->activo; }

    public function establecerDedicacion(string $dedicacion): void { $this->dedicacion = $dedicacion; }
    public function establecerModalidad(string $modalidad): void { $this->modalidad = $modalidad; }
    public function establecerFechaIngreso(string $fechaIngreso): void { $this->fechaIngreso = $fechaIngreso; }
    public function establecerFechaSalida(?string $fechaSalida): void { $this->fechaSalida = $fechaSalida; }
    public function establecerActivo(bool $activo): void { $this->activo = $activo; }

    /**
     * Representación de la vinculación como arreglo asociativo, con las
     * claves en snake_case tal como viajan en el JSON de la API.
     */
    public function toArray(): array
    {
        return [
            'docente' => $this->docente,
            'departamento' => $this->departamento,
            'dedicacion' => $this->dedicacion,
            'modalidad' => $this->modalidad,
            'fecha_ingreso' => $this->fechaIngreso,
            'fecha_salida' => $this->fechaSalida,
            'activo' => $this->activo,
        ];
    }
}