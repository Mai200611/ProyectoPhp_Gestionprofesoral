 <?php
declare(strict_types=1);

/**
 * Docente.php
 *
 * Modelo clásico de la tabla `docente` (5_data_model.md §3.6).
 *
 * Es el ENCABEZADO del maestro-detalle: sus dependencias (estudios,
 * intereses, redes, evaluaciones, reconocimientos, experiencia) NO viven
 * dentro de esta clase; se manejan en su propio modelo y en el repositorio.
 * Aquí solo están los campos propios de la fila `docente`.
 *
 * Llave: `cedula` es natural (la manda el cliente), por eso es `string` y no
 * `?string`: nunca está «pendiente de asignar».
 *
 * Nota importante (Artículo 11): `linea_investigacion_principal` NO se calcula
 * aquí a partir de los estudios. La base lo mueve con su trigger; la API lo
 * lee. Si alguien lo manda en el body, la lista blanca del repositorio lo deja
 * pasar solo cuando corresponde al PUT/POST del docente, pero el cálculo no se
 * hace en PHP.
 */
class Docente
{
    private string $cedula;
    private string $nombres;
    private string $apellidos;
    private string $genero;
    private string $cargo;
    private string $fechaNacimiento;
    private string $correo;
    private string $telefono;
    private string $urlCvlac;
    private string $fechaActualizacion;
    private string $escalafon;
    private string $perfil;
    private string $catMinciencia;
    private string $nacionalidad;
    private bool $activo;
    private int $lineaInvestigacionPrincipal;

    public function __construct(
        string $cedula,
        string $nombres,
        string $apellidos,
        string $genero,
        string $cargo,
        string $fechaNacimiento,
        string $correo,
        string $telefono,
        string $urlCvlac,
        string $fechaActualizacion,
        string $escalafon,
        string $perfil,
        string $catMinciencia,
        string $nacionalidad,
        bool $activo,
        int $lineaInvestigacionPrincipal
    ) {
        $this->cedula = $cedula;
        $this->nombres = $nombres;
        $this->apellidos = $apellidos;
        $this->genero = $genero;
        $this->cargo = $cargo;
        $this->fechaNacimiento = $fechaNacimiento;
        $this->correo = $correo;
        $this->telefono = $telefono;
        $this->urlCvlac = $urlCvlac;
        $this->fechaActualizacion = $fechaActualizacion;
        $this->escalafon = $escalafon;
        $this->perfil = $perfil;
        $this->catMinciencia = $catMinciencia;
        $this->nacionalidad = $nacionalidad;
        $this->activo = $activo;
        $this->lineaInvestigacionPrincipal = $lineaInvestigacionPrincipal;
    }

    public function obtenerCedula(): string { return $this->cedula; }
    public function obtenerNombres(): string { return $this->nombres; }
    public function obtenerApellidos(): string { return $this->apellidos; }
    public function obtenerGenero(): string { return $this->genero; }
    public function obtenerCargo(): string { return $this->cargo; }
    public function obtenerFechaNacimiento(): string { return $this->fechaNacimiento; }
    public function obtenerCorreo(): string { return $this->correo; }
    public function obtenerTelefono(): string { return $this->telefono; }
    public function obtenerUrlCvlac(): string { return $this->urlCvlac; }
    public function obtenerFechaActualizacion(): string { return $this->fechaActualizacion; }
    public function obtenerEscalafon(): string { return $this->escalafon; }
    public function obtenerPerfil(): string { return $this->perfil; }
    public function obtenerCatMinciencia(): string { return $this->catMinciencia; }
    public function obtenerNacionalidad(): string { return $this->nacionalidad; }
    public function obtenerActivo(): bool { return $this->activo; }
    public function obtenerLineaInvestigacionPrincipal(): int { return $this->lineaInvestigacionPrincipal; }

    public function establecerNombres(string $nombres): void { $this->nombres = $nombres; }
    public function establecerApellidos(string $apellidos): void { $this->apellidos = $apellidos; }
    public function establecerGenero(string $genero): void { $this->genero = $genero; }
    public function establecerCargo(string $cargo): void { $this->cargo = $cargo; }
    public function establecerFechaNacimiento(string $fechaNacimiento): void { $this->fechaNacimiento = $fechaNacimiento; }
    public function establecerCorreo(string $correo): void { $this->correo = $correo; }
    public function establecerTelefono(string $telefono): void { $this->telefono = $telefono; }
    public function establecerUrlCvlac(string $urlCvlac): void { $this->urlCvlac = $urlCvlac; }
    public function establecerFechaActualizacion(string $fechaActualizacion): void { $this->fechaActualizacion = $fechaActualizacion; }
    public function establecerEscalafon(string $escalafon): void { $this->escalafon = $escalafon; }
    public function establecerPerfil(string $perfil): void { $this->perfil = $perfil; }
    public function establecerCatMinciencia(string $catMinciencia): void { $this->catMinciencia = $catMinciencia; }
    public function establecerNacionalidad(string $nacionalidad): void { $this->nacionalidad = $nacionalidad; }
    public function establecerActivo(bool $activo): void { $this->activo = $activo; }
    public function establecerLineaInvestigacionPrincipal(int $linea): void { $this->lineaInvestigacionPrincipal = $linea; }

    /**
     * Devuelve la representación del docente como arreglo asociativo,
     * con las claves en snake_case tal como viajan en el JSON de la API.
     */
    public function toArray(): array
    {
        return [
            'cedula' => $this->cedula,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'genero' => $this->genero,
            'cargo' => $this->cargo,
            'fecha_nacimiento' => $this->fechaNacimiento,
            'correo' => $this->correo,
            'telefono' => $this->telefono,
            'url_cvlac' => $this->urlCvlac,
            'fecha_actualizacion' => $this->fechaActualizacion,
            'escalafon' => $this->escalafon,
            'perfil' => $this->perfil,
            'cat_minciencia' => $this->catMinciencia,
            'nacionalidad' => $this->nacionalidad,
            'activo' => $this->activo,
            'linea_investigacion_principal' => $this->lineaInvestigacionPrincipal,
        ];
    }
}