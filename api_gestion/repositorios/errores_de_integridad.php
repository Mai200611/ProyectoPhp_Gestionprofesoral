<?php
declare(strict_types=1);

/**
 * errores_de_integridad.php
 *
 * ÚNICO archivo del proyecto con derecho a conocer los números de error de
 * integridad de MariaDB (1062, 1452, 1451). Su papel es traducir el veredicto
 * del motor a una ConflictoDeIntegridadExcepcion con el motivo en español.
 *
 * ¿Por qué vive en repositorios/ y no en servicios/?
 * Porque es la única capa que sabe qué motor hay detrás. Si esta traducción
 * viviera en el servicio, cambiar de motor (PostgreSQL o SQL Server en v3/v4)
 * obligaría a tocar el negocio, que es justo lo que la arquitectura por capas
 * evita. Ver 3_plan.md §4.1 y el Artículo 11 de la constitución.
 *
 * Códigos de MariaDB que se traducen:
 *   1062 → llave duplicada (llave primaria o única ya existe).
 *   1452 → FK que apunta a algo inexistente (registro padre no existe).
 *   1451 → fila referenciada por otras (borrado bloqueado por dependencias).
 *
 * Cualquier otro código se RELANZA: que el controlador lo convierta en 500,
 * porque no sabemos qué significa y no debemos inventar un mensaje.
 *
 * Detalle aprendido probando: $e->getCode() de PDO devuelve el SQLSTATE
 * genérico ('23000' para TODAS las violaciones de integridad) y con eso no se
 * distingue una cosa de otra. El número útil está en $e->errorInfo[1].
 */

/**
 * Traduce un PDOException de integridad a la excepción de dominio
 * correspondiente. Si el código no es de los conocidos, relanza el original.
 *
 * @param PDOException $error   La excepción capturada del motor.
 * @param string       $ficha   Nombre legible de la ficha, para el mensaje
 *                              (p. ej. "el docente", "el estudio").
 * @return never                Siempre lanza una excepción.
 */
function traducirErrorDeIntegridad(PDOException $error, string $ficha): never
{
    // El número que distingue los casos está en errorInfo[1], no en getCode().
    $codigo = 0;
    if (isset($error->errorInfo[1]) && is_numeric($error->errorInfo[1])) {
        $codigo = (int) $error->errorInfo[1];
    }

    if ($codigo === 1062) {
        // Llave primaria o única duplicada.
        throw new ConflictoDeIntegridadExcepcion(
            "Ya existe {$ficha} con esa llave.",
            "La llave que intentó usar ya está registrada en el sistema."
        );
    }

    if ($codigo === 1452) {
        // Llave foránea que apunta a un registro inexistente.
        throw new ConflictoDeIntegridadExcepcion(
            "Alguno de los códigos a los que apunta {$ficha} no existe.",
            "Verifique que las fichas relacionadas sigan existiendo."
        );
    }

    if ($codigo === 1451) {
        // La fila tiene dependencias y no se puede borrar.
        throw new ConflictoDeIntegridadExcepcion(
            "No se puede eliminar {$ficha} porque hay otras fichas que dependen de ella.",
            "Elimine primero las fichas dependientes o cancele la operación."
        );
    }

    // Código no reconocido: no inventamos un mensaje. Que suba como 500.
    throw $error;
}