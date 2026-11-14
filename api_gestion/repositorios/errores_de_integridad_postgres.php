<?php
declare(strict_types=1);

require_once __DIR__ . '/conflictos.php';

/**
 * Traduce un PDOException de PostgreSQL a la excepción de dominio.
 * PostgreSQL usa SQLSTATE 23505 para llave duplicada.
 * PostgreSQL usa SQLSTATE 23503 para llaves foráneas, 
 * y hay que mirar el mensaje para saber si es borrado bloqueado o referencia rota.
 */
function traducirErrorPostgres(PDOException $error, string $ficha): never
{
    $sqlState = (string) $error->getCode();
    $mensaje  = $error->getMessage();

    if ($sqlState === '23505') {
        $msg = conflictoLlaveDuplicada($ficha);
        throw new ConflictoDeIntegridadExcepcion($msg[0], $msg[1]);
    }

    if ($sqlState === '23503') {
        // Si el mensaje dice "update or delete on table", estamos intentando borrar un padre que tiene hijos.
        if (str_contains($mensaje, 'update or delete on table')) {
            $msg = conflictoTieneDependientes($ficha);
        } else {
            // Si dice "insert or update on table", estamos apuntando a un padre que no existe.
            $msg = conflictoReferenciaRota($ficha);
        }
        throw new ConflictoDeIntegridadExcepcion($msg[0], $msg[1]);
    }

    throw $error;
}