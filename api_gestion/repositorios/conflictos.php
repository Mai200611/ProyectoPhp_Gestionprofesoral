<?php
declare(strict_types=1);

/**
 * conflictos.php
 * Las tres frases de error de integridad, escritas una sola vez.
 */

function conflictoLlaveDuplicada(string $ficha): array {
    return [
        "Ya existe {$ficha} con esa llave.",
        "La llave que intentó usar ya está registrada en el sistema."
    ];
}

function conflictoReferenciaRota(string $ficha): array {
    return [
        "Alguno de los códigos a los que apunta {$ficha} no existe.",
        "Verifique que las fichas relacionadas sigan existiendo."
    ];
}

function conflictoTieneDependientes(string $ficha): array {
    return [
        "No se puede eliminar {$ficha} porque hay otras fichas que dependen de ella.",
        "Elimine primero las fichas dependientes o cancele la operación."
    ];
}