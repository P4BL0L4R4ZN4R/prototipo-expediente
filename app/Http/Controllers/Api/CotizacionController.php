<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;  
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Storage;   
use App\Models\Expediente;
use Illuminate\Support\Collection;


class CotizacionController extends Controller
{

    public function __construct(
        private ExcelController $excel,
    ) {}

    
    public function conceptos(string $id)
    {
        $exp = Expediente::find($id);

        if (!$exp) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $data = $exp->data ?? [];
        $ia   = $exp->ia ?? [];

        // Mapa orden → proceso/nombre
        $procesosPorOrden = collect($ia['conceptos'] ?? [])
            ->keyBy('orden')
            ->map(fn($c) => $c['proceso'] ?? $c['concepto'] ?? '')
            ->all();

        $filas = collect($data['conceptos'] ?? [])->flatMap(function ($c) use ($procesosPorOrden) {
            $orden   = $c['orden'] ?? null;
            $proceso = $procesosPorOrden[$orden] ?? ($c['descripcion'] ?? '');

            return collect($c['facturas'] ?? [])->map(function ($f) use ($proceso) {
                return [
                    'id'       => $f['folio_fiscal'] ?? uniqid(),
                    'concepto' => $proceso,
                    'monto'    => (float) ($f['total_factura'] ?? 0),
                ];
            });
        })->values();

        $total = (float) $filas->sum('monto');

        return response()->json([
            'datos'         => $filas,
            'total'         => $total,
            'total_textual' => $this->numeroALetras($total),
        ]);
    }


    public function numeroALetras(float $numero): string
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

        // Convierte hasta 999
        $convertirCentenas = function (int $n) use ($unidades, $decenas, $centenas): string {
            if ($n === 0)   return '';
            if ($n === 100) return 'CIEN';

            if ($n >= 100) {
                $c = intdiv($n, 100);
                $r = $n % 100;
                return $r > 0
                    ? $centenas[$c] . ' ' . $this->convertirDecenas($r, $unidades, $decenas)
                    : $centenas[$c];
            }

            return $this->convertirDecenas($n, $unidades, $decenas);
        };

        // Convierte hasta 999,999,999
        $convertir = function (int $n) use (&$convertir, $convertirCentenas): string {
            if ($n === 0) return 'CERO';

            // Millones
            if ($n >= 1000000) {
                $millones = intdiv($n, 1000000);
                $resto = $n % 1000000;

                $textoMillones = $millones === 1
                    ? 'UN MILLÓN'
                    : $convertir($millones) . ' MILLONES';

                if ($resto === 0) return $textoMillones;

                return $textoMillones . ' ' . $convertir($resto);
            }

            // Miles
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

    public function convertirDecenas(int $n, array $unidades, array $decenas): string
    {
        if ($n <= 20) return $unidades[$n];
        if ($n < 30)  return 'VEINTI' . strtolower($unidades[$n - 20]);

        $d = intdiv($n, 10);
        $u = $n % 10;
        return $u > 0 ? $decenas[$d] . ' Y ' . $unidades[$u] : $decenas[$d];
    }

        
    public function tablaFinal(array $data, array $ia = []): array
    {
        return $this->empaquetarTabla($this->agruparGrupos($data, $ia));
    }

    public function tablaInicial(array $data, array $ia = []): array
    {
        $grupos = $this->agruparGrupos($data, $ia);
        $grupos = $this->aplicarIncrementoInicial($grupos);

        return $this->empaquetarTabla($grupos);
    }

    private function agruparGrupos(array $data, array $ia = []): Collection
    {
        $procesosPorOrden = collect($ia['conceptos'] ?? [])
            ->keyBy('orden')
            ->map(fn($c) => $c['proceso'] ?? $c['concepto'] ?? '')
            ->all();

        return collect($data['conceptos'] ?? [])->map(function ($c) use ($procesosPorOrden) {
            $orden = $c['orden'] ?? null;

            return [
                'concepto' => $procesosPorOrden[$orden] ?? ($c['descripcion'] ?? ''),
                'facturas' => $c['facturas'] ?? [],
            ];
        })->filter(fn($g) => count($g['facturas']) > 0)->values();
    }



    private function empaquetarTabla(Collection $grupos): array
    {
        $total = (float) $grupos->flatMap(fn($g) => $g['facturas'])
            ->sum(fn($f) => (float) ($f['total_factura'] ?? 0));

        return [
            'grupos'       => $grupos,
            'total'        => $total,
            'total_letras' => $this->numeroALetras($total),
        ];
    }

    private function aplicarIncrementoInicial(Collection $grupos): Collection
    {
        $cantidad = $grupos->sum(fn($g) => count($g['facturas']));
        if ($cantidad === 0) return $grupos;

        $incrementos = $this->distribuirIncremento($cantidad, 5000, 30000);

        $i = 0;
        return $grupos->map(function ($grupo) use (&$i, $incrementos) {
            $grupo['facturas'] = collect($grupo['facturas'])->map(function ($f) use (&$i, $incrementos) {
                $nuevo = (float) ($f['total_factura'] ?? 0) + $incrementos[$i];
                $f['total_factura'] = (float) ceil($nuevo);
                $i++;
                return $f;
            })->all();
            return $grupo;
        });
    }

    private function distribuirIncremento(int $cantidad, int $min, int $max): array
    {
        $sumaMinDistintos = intdiv($cantidad * ($cantidad + 1), 2);
        $minFactible      = max($min, $sumaMinDistintos);

        if ($minFactible > $max) {
            throw new \RuntimeException(
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
