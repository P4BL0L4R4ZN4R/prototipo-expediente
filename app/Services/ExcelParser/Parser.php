<?php

namespace App\Services\ExcelParser;

use Maatwebsite\Excel\Facades\Excel;

class Parser
{
    public function __construct(
        private Header $header,
        private Concepts $concepts,
        private Invoices $invoices,
        private FixedData $fixedData,
        private Calc $calc,
    ) {}

    public function parse(string $path): array
    {
        $rows = Excel::toArray([], $path)[0];

        $contexto = $this->header->extract($rows);
        $duros    = $this->fixedData->extract($rows);
        $headerRow = $this->findHeaderRow($rows);
        $conceptos = $this->concepts->extract($rows, $headerRow);
        $facturas  = $this->invoices->extract($rows, $headerRow);
        $calculos  = $this->calc->calculate($conceptos, $facturas);

        return [
            'ia'   => $this->buildIa($contexto, $conceptos),
            'data' => $this->buildData($duros, $conceptos, $facturas, $calculos),
        ];
    }

    private function buildIa(array $contexto, array $conceptos): array
    {
        return [
            'empresa_factura' => $contexto['empresa_factura'] ?? '',
            'cliente'         => $contexto['cliente'] ?? '',
            'servicio'        => $contexto['servicio'] ?? '',
            'objetivo'        => $contexto['objetivo'] ?? '',
            'conceptos'       => array_map(fn($c) => [
                'orden'    => $c['orden'],
                'concepto' => $c['concepto'],
                'proceso'  => $c['proceso'],
            ], $conceptos),
        ];
    }

    private function buildData(array $duros, array $conceptos, array $facturas, array $calculos): array
    {
        $conceptosDuros = array_map(function ($c) use ($facturas, $calculos) {
            $orden = $c['orden'];

            return [
                'orden'       => $orden,
                'clave'       => $c['clave'],
                'descripcion' => $c['descripcion'],
                'proceso'     => $c['proceso'],       // 👈 AGREGAR
                'concepto'    => $c['concepto'],      // 👈 AGREGAR
                'costo_p_mes' => $c['costo_p_mes'],
                'facturas'    => array_values(array_filter(
                    $facturas,
                    fn($f) => $f['concepto_orden'] === $orden
                )),
                'total'       => $calculos['total_por_concepto'][(string) $orden] ?? 0,
            ];
        }, $conceptos);

        return [
            'fecha_inicio'  => $duros['fecha_inicio'] ?? null,
            'fecha_termino' => $duros['fecha_termino'] ?? null,
            'costo_p_mes'   => $duros['costo_p_mes'] ?? null,
            'conceptos'     => $conceptosDuros,
            'calculos'      => [
                'total_programa' => $calculos['total_programa'] ?? 0,
                'fecha_legal'    => $calculos['fecha_legal'] ?? null,
            ],
        ];
    }

    private function findHeaderRow(array $rows): int
    {
        foreach ($rows as $i => $row) {
            $lower = array_map(fn($v) => strtolower(trim((string) $v)), $row);

            if (
                in_array('concepto', $lower) &&
                in_array('total factura ($)', $lower) &&
                in_array('fecha', $lower)
            ) {
                return $i;
            }
        }

        throw new \RuntimeException('Header row not found');
    }
}