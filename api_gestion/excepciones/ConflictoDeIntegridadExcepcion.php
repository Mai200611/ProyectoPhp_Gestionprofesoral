<?php
declare(strict_types=1);

/**
 * ConflictoDeIntegridadExcepcion.php
 *
 * Excepción que representa un 409 (Conflicto): «su petición está bien
 * escrita, pero no cabe en los datos que hay».
 *
 * Es distinta de una NoEncontradoExcepcion (404) y de una PDOException (500):
 * aquí el cliente no se equivocó al escribir el body, y el sistema no se
 * rompió; simplemente chocó con una regla de integridad que defiende la base
 * de datos (llave duplicada, FK inexistente, o fila con dependencias).
 *
 * Nadie fuera de los repositorios debería construirla con los números de
 * error del motor: esa traducción vive en repositorios/errores_de_integridad.php.
 *
 * Esta clase hereda de Exception y guarda, además del mensaje, un «detalle»
 * opcional que el controlador incluye en la respuesta JSON.
 */
class ConflictoDeIntegridadExcepcion extends Exception
{
    /**
     * Explicación adicional, en español, de por qué se produjo el conflicto.
     * Puede ser vacía; el contrato la devuelve como "detalle".
     */
    private string $detalle;

    /**
     * @param string $mensaje  Mensaje principal, apto para el usuario final.
     * @param string $detalle  Explicación adicional (opcional).
     */
    public function __construct(string $mensaje, string $detalle = '')
    {
        parent::__construct($mensaje);
        $this->detalle = $detalle;
    }

    /**
     * Devuelve la explicación adicional del conflicto.
     */
    public function obtenerDetalle(): string
    {
        return $this->detalle;
    }
}