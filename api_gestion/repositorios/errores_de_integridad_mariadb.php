<?php
declare(strict_types=1);

require_once __DIR__ . '/conflictos.php';

/**
 * Traduce un PDOException de MariaDB a la excepción de dominio.
 */
function traducirErrorMariaDB(PDOException $error, string $ficha): never
{
    $codigo = 0;
    if (isset($error->errorInfo[1]) && is_numeric($error->errorInfo[1])) {
        $codigo = (int) $error->errorInfo[1];
    }

    if ($codigo === 1062) {
        $msg = conflictoLlaveDuplicada($ficha);
        throw new ConflictoDeIntegridadExcepcion($msg[0], $msg[1]);
    }

    if ($codigo === 1452) {
        $msg = conflictoReferenciaRota($ficha);
        throw new ConflictoDeIntegridadExcepcion($msg[0], $msg[1]);
    }

    if ($codigo === 1451) {
        $msg = conflictoTieneDependientes($ficha);
        throw new ConflictoDeIntegridadExcepcion($msg[0], $msg[1]);
    }

    throw $error;
}