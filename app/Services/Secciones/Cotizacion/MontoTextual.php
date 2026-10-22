<?php

namespace App\Services\Secciones\Cotizacion;

class MontoTextual
{
    public static function convertir(float $numero): string
    {
        $entero   = (int) floor($numero);
        $centavos = (int) round(($numero - $entero) * 100);

        $unidades = [
            '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE',
            'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE',
            'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
        ];

        $decenas = [
            '', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA',
            'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA',
        ];

        $centenas = [
            '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS',
            'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
        ];

        $convertirCentenas = function (int $n) use ($unidades, $decenas, $centenas): string {
            if ($n === 0)   return '';
            if ($n === 100) return 'CIEN';

            if ($n >= 100) {
                $c = intdiv($n, 100);
                $r = $n % 100;
                return $r > 0
                    ? $centenas[$c] . ' ' . self::convertirDecenas($r, $unidades, $decenas)
                    : $centenas[$c];
            }

            return self::convertirDecenas($n, $unidades, $decenas);
        };

        $convertir = function (int $n) use (&$convertir, $convertirCentenas): string {
            if ($n === 0) return 'CERO';

            if ($n >= 1000000) {
                $millones = intdiv($n, 1000000);
                $resto = $n % 1000000;

                $textoMillones = $millones === 1
                    ? 'UN MILLÓN'
                    : $convertir($millones) . ' MILLONES';

                if ($resto === 0) return $textoMillones;

                return $textoMillones . ' ' . $convertir($resto);
            }

            if ($n >= 1000) {
                $miles = intdiv($n, 1000);
                $resto = $n % 1000;

                $textoMiles = $miles === 1
                    ? 'MIL'
                    : $convertirCentenas($miles) . ' MIL';

                if ($resto === 0) return $textoMiles;

                return $textoMiles . ' ' . $convertirCentenas($resto);
            }

            return $convertirCentenas($n);
        };

        return sprintf('%s PESOS %02d/100 MXN', $convertir($entero), $centavos);
    }

    private static function convertirDecenas(int $n, array $unidades, array $decenas): string
    {
        if ($n <= 20) return $unidades[$n];
        if ($n < 30)  return 'VEINTI' . strtolower($unidades[$n - 20]);

        $d = intdiv($n, 10);
        $u = $n % 10;
        return $u > 0 ? $decenas[$d] . ' Y ' . $unidades[$u] : $decenas[$d];
    }
}