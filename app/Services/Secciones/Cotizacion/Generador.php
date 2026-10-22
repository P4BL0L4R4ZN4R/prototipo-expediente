<?php

namespace App\Services\Secciones\Cotizacion;

use Illuminate\Support\Collection;

class Generador
{
    public function __construct(
        private DistribuidorIncrementos $distribuidor,
    ) {}

    // =========================================================
    // ENDPOINTS (los usaba CotizacionController::conceptos)
    // =========================================================

    public function conceptos(array $data, array $ia = []): array
    {
        $conceptosPorOrden = $this->mapConceptosPorOrden($ia);

        $filas = collect($data['conceptos'] ?? [])->flatMap(function ($c) use ($conceptosPorOrden) {
            $orden    = $c['orden'] ?? null;
            $concepto = $conceptosPorOrden[$orden] ?? ($c['descripcion'] ?? '');

            return collect($c['facturas'] ?? [])->map(function ($f) use ($concepto) {
                return [
                    'id'       => $f['folio_fiscal'] ?? uniqid(),
                    'concepto' => $concepto,
                    'monto'    => (float) ($f['total_factura'] ?? 0),
                ];
            });
        })->values();

        $total = (float) $filas->sum('monto');

        return [
            'datos'         => $filas,
            'total'         => $total,
            'total_textual' => MontoTextual::convertir($total),
        ];
    }

    // =========================================================
    // TABLAS (las usaban CotizacionesWordController y EntregableWordController)
    // =========================================================

    public function tablaFinal(array $data, array $ia = []): array
    {
        return $this->empaquetarTabla($this->agruparGrupos($data, $ia));
    }

    public function tablaInicial(array $data, array $ia = []): array
    {
        $grupos = $this->agruparGrupos($data, $ia);

        try {
            $grupos = $this->aplicarIncrementoInicial($grupos);
        } catch (\RuntimeException $e) {
            return [
                'grupos'       => [],
                'total'        => 0,
                'total_letras' => '',
                'error'        => $e->getMessage(),
            ];
        }

        return $this->empaquetarTabla($grupos);
    }

    // =========================================================
    // INTERNO
    // =========================================================

    private function mapConceptosPorOrden(array $ia): array
    {
        return collect($ia['conceptos'] ?? [])
            ->keyBy('orden')
            ->map(fn($c) => $c['concepto'] ?? '')
            ->all();
    }

    private function agruparGrupos(array $data, array $ia = []): Collection
    {
        $conceptosPorOrden = $this->mapConceptosPorOrden($ia);

        return collect($data['conceptos'] ?? [])->map(function ($c) use ($conceptosPorOrden) {
            $orden = $c['orden'] ?? null;

            return [
                'concepto' => $conceptosPorOrden[$orden] ?? ($c['descripcion'] ?? ''),
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
            'total_letras' => MontoTextual::convertir($total),
        ];
    }

    private function aplicarIncrementoInicial(Collection $grupos): Collection
    {
        $cantidad = $grupos->sum(fn($g) => count($g['facturas']));
        if ($cantidad === 0) return $grupos;

        $incrementos = $this->distribuidor->distribuir($cantidad, 5000, 30000);

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
}