<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es una EXCEPCIÓN propia del proyecto, especializada en representar la
 *   situación "el recurso que buscas no existe".
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el canal por el que la capa de servicios le dice al controlador
 *   que un identificador no encontró nada. En vez de devolver null y
 *   obligar al controlador a revisarlo, el servicio LANZA esta excepción,
 *   y el controlador la captura y la traduce a un 404.
 *
 *   Esa separación es importante:
 *     - El servicio NO conoce HTTP: no sabe qué es un 404. Solo sabe que
 *       "el recurso no existe".
 *     - El controlador SÍ conoce HTTP y decide que eso equivale a un 404.
 *     - El repositorio, por su parte, devuelve null cuando no encuentra
 *       nada; es el servicio quien convierte ese null en esta excepción.
 *
 * ¿Por qué hereda de Exception y no de RuntimeException?
 *   Porque "no encontrado" es una condición que el programa espera y
 *   maneja (la captura el controlador); no es un fallo de programación.
 *   Heredar directamente de Exception deja claro que es una excepción
 *   de negocio, no un error interno.
 *
 * ¿Cómo se usará?
 *   Servicio:
 *       $programa = $this->repositorio->obtenerPorId($id);
 *       if ($programa === null) {
 *           throw new NoEncontradoExcepcion(
 *               "Programa no encontrado."
 *           );
 *       }
 *
 *   Controlador:
 *       try {
 *           $programa = $this->servicio->obtenerPorId($id);
 *       } catch (NoEncontradoExcepcion $e) {
 *           // responder 404 con {estado, mensaje, detalle}
 *       }
 * ---------------------------------------------------------------------------
 */
class NoEncontradoExcepcion extends Exception
{
    /**
     * Constructor.
     *
     * Recibe el mensaje en español que el controlador usará como campo
     * `mensaje` en la respuesta JSON del 404. Por ejemplo:
     *   "Programa no encontrado."
     *   "Área de conocimiento no encontrada."
     *
     * El tercer parámetro (código) y el cuarto (excepción previa) se
     * aceptan como en la clase base Exception, por si más adelante se
     * necesita encadenar. En la v1 se usan solo el mensaje y, a veces,
     * el código interno.
     */
    public function __construct(
        string $mensaje = 'Recurso no encontrado.',
        int $codigo = 0,
        ?Throwable $anterior = null
    ) {
        parent::__construct($mensaje, $codigo, $anterior);
    }
}