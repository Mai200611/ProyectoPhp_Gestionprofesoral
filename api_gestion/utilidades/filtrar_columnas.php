<?php
declare(strict_types=1);

/**
 * filtrar_columnas.php
 *
 * Utilidad de "lista blanca" para los cuerpos JSON que llegan a la API.
 *
 * ¿Por qué existe esto? (3_plan.md §4.3)
 * Porque el cliente no debe poder mandar campos que la API no acepta.
 * Por ejemplo, en un POST de `estudios_realizados`, el `id` lo genera la
 * base. Si el cliente lo manda, esta función lo descarta antes de que
 * llegue al SQL. Lo mismo con campos calculados por la base (como la línea
 * de investigación del docente) o de auditoría.
 *
 * No lanza error (422) por un campo de más: simplemente lo ignora. Rechazar
 * la petición entera por un campo extra sería demasiado severo.
 */

/**
 * Devuelve solo las claves de $datos que están en la lista $permitidas.
 *
 * @param array<string, mixed> $datos      El cuerpo recibido del cliente.
 * @param array<int, string>   $permitidas Lista blanca de columnas válidas.
 * @return array<string, mixed>
 */
function filtrarColumnas(array $datos, array $permitidas): array
{
    $resultado = [];
    foreach ($permitidas as $columna) {
        if (array_key_exists($columna, $datos)) {
            $resultado[$columna] = $datos[$columna];
        }
    }
    return $resultado;
}