<?php

namespace App\Services\Secciones\Cotizacion;

use RuntimeException;

class DistribuidorIncrementos
{
    /**
     * Reparte un incremento total entre $cantidad facturas,
     * devolviendo un array de incrementos distintos entre sí.
     *
     * @throws RuntimeException si no se puede repartir con los límites dados.
     */
    public function distribuir(int $cantidad, int $min, int $max): array
    {
        $sumaMinDistintos = intdiv($cantidad * ($cantidad + 1), 2);
        $minFactible      = max($min, $sumaMinDistintos);

        if ($minFactible > $max) {
            throw new RuntimeException(
                "No se pueden repartir incrementos distintos entre {$cantidad} facturas con máximo {$max}."
            );
        }

        $incrementoTotal = random_int($minFactible, $max);

        $limiteBase = max(
            $cantidad,
            min(intdiv($incrementoTotal, max(1, $cantidad)), $cantidad * 20)
        );

        $rango = range(1, $limiteBase);
        shuffle($rango);
        $incrementos = array_slice($rango, 0, $cantidad);
        sort($incrementos);

        $restante     = $incrementoTotal - array_sum($incrementos);
        $aumentoComun = intdiv($restante, $cantidad);
        $residuo      = $restante % $cantidad;

        $incrementos = array_map(fn($x) => $x + $aumentoComun, $incrementos);

        for ($j = $cantidad - $residuo; $j < $cantidad; $j++) {
            $incrementos[$j]++;
        }

        shuffle($incrementos);

        return $incrementos;
    }
}