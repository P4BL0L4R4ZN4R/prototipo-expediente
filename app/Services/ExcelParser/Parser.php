<?php

namespace App\Services\ExcelParser;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

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
        $inicio = microtime(true);

        // Log::info('[Parser] ═════════ INICIO PARSE ═════════', [
        //     'path' => $path,
        // ]);

        // ── 1. Leer Excel
        $rows = Excel::toArray([], $path)[0];

        // Log::info('[Parser] Excel leído', [
        //     'filas_totales' => count($rows),
        //     'columnas_max'  => count($rows[0] ?? []),
        // ]);

        // ── 2. Header
        $contexto = $this->header->extract($rows);

        // Log::info('[Parser] → Header extraído', [
        //     'empresa_factura' => $contexto['empresa_factura'] ?? null,
        //     'cliente'         => $contexto['cliente'] ?? null,
        //     'servicio'        => $contexto['servicio'] ?? null,
        //     'objetivo'        => isset($contexto['objetivo']) 
        //                             ? substr($contexto['objetivo'], 0, 80) . '...' 
        //                             : null,
        // ]);

        // ── 3. FixedData
        $duros = $this->fixedData->extract($rows);

        // Log::info('[Parser] → FixedData extraído', [
        //     'fecha_inicio'  => $duros['fecha_inicio'] ?? null,
        //     'fecha_termino' => $duros['fecha_termino'] ?? null,
        // ]);

        // ── 4. Header row (fila donde empiezan conceptos/facturas)
        $headerRow = $this->findHeaderRow($rows);

        // Log::info('[Parser] → Header row encontrado', [
        //     'fila'    => $headerRow,
        //     'headers' => array_map(
        //         fn($v) => trim((string) $v),
        //         $rows[$headerRow] ?? []
        //     ),
        // ]);

        // ── 5. Conceptos
        $conceptos = $this->concepts->extract($rows, $headerRow);

        // Log::info('[Parser] → Conceptos extraídos', [
        //     'total' => count($conceptos),
        //     'muestra' => array_slice(array_map(fn($c) => [
        //         'orden'    => $c['orden'],
        //         'concepto' => substr($c['concepto'], 0, 60) . '...',
        //         // 'proceso'  => substr($c['proceso'], 0, 60) . '...',
        //     ], $conceptos), 0, 3),
        // ]);

        // ── 6. Facturas
        $facturas = $this->invoices->extract($rows, $headerRow);

        // Log::info('[Parser] → Facturas extraídas', [
        //     'total' => count($facturas),
        //     'por_concepto' => collect($facturas)
        //         ->groupBy('concepto_orden')
        //         ->map(fn($grupo) => count($grupo))
        //         ->all(),
        //     'muestra' => array_slice($facturas, 0, 3),
        // ]);

        // ── 7. Cálculos
        $calculos = $this->calc->calculate($facturas);

        // Log::info('[Parser] → Cálculos realizados', [
        //     'total_programa' => $calculos['total_programa'] ?? null,
        //     'fecha_legal'    => $calculos['fecha_legal'] ?? null,
        // ]);

        // ── 8. Construir IA
        $ia = $this->buildIa($contexto, $conceptos);

        // Log::info('[Parser] → IA construida', [
        //     'keys'            => array_keys($ia),
        //     'total_conceptos' => count($ia['conceptos'] ?? []),
        // ]);

        // ── 9. Construir DATA
        $data = $this->buildData($duros, $conceptos, $facturas, $calculos);

        // Log::info('[Parser] → DATA construida', [
        //     'keys'                => array_keys($data),
        //     'total_conceptos'     => count($data['conceptos'] ?? []),
        //     'total_facturas'      => collect($data['conceptos'] ?? [])
        //                                 ->sum(fn($c) => count($c['facturas'] ?? [])),
        //     'fecha_inicio'        => $data['fecha_inicio'],
        //     'fecha_termino'       => $data['fecha_termino'],
        //     'calculos'            => $data['calculos'],
        // ]);

        // ── 10. Resumen final
        $duracion = round((microtime(true) - $inicio) * 1000, 2);

        // Log::info('[Parser] ═════════ FIN PARSE ═════════', [
        //     'duracion_ms' => $duracion,
        //     'ia_bytes'    => strlen(json_encode($ia)),
        //     'data_bytes'  => strlen(json_encode($data)),
        // ]);

        return [
            'ia'   => $ia,
            'data' => $data,
        ];
    }

    /**
     * IA: solo lo que se usa en los Word y en los prompts de IA.
     */
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
                // 'proceso'  => $c['proceso'],
            ], $conceptos),
        ];
    }

    /**
     * DATA: solo lo que se usa en cálculos y Word.
     */
    private function buildData(array $duros, array $conceptos, array $facturas, array $calculos): array
    {
        // Agrupar facturas por concepto
        $conceptosDuros = array_map(function ($c) use ($facturas) {
            $orden = $c['orden'];

            $facturasDelConcepto = array_values(array_filter(
                $facturas,
                fn($f) => $f['concepto_orden'] === $orden
            ));

            // Limpiar cada factura: quitar concepto_orden (redundante)
            $facturasLimpias = array_map(function ($f) {
                unset($f['concepto_orden']);
                return $f;
            }, $facturasDelConcepto);

            return [
                'orden'    => $orden,
                'facturas' => $facturasLimpias,
            ];
        }, $conceptos);

        // Verificar facturas huérfanas (sin concepto válido)
        $this->detectarFacturasHuerfanas($facturas, $conceptos);

        return [
            'fecha_inicio'  => $this->normalizarFecha($duros['fecha_inicio'] ?? null),
            'fecha_termino' => $this->normalizarFecha($duros['fecha_termino'] ?? null),
            'conceptos'     => $conceptosDuros,
            'calculos'      => [
                'total_programa' => $calculos['total_programa'] ?? 0,
                'fecha_legal'    => $calculos['fecha_legal'] ?? null,
            ],
        ];
    }

    /**
     * Normaliza una fecha. Si el valor no parece fecha, devuelve null.
     */
    private function normalizarFecha(?string $fecha): ?string
    {
        if (!$fecha) return null;

        $fecha = trim($fecha);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)
            && !preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}$/', $fecha)
            && !preg_match('/^\d{1,2}-\d{1,2}-\d{2,4}$/', $fecha)) {

            // Log::warning('[Parser] Fecha rechazada por formato inválido', [
            //     'valor_original' => $fecha,
            // ]);

            return null;
        }

        try {
            return \Carbon\Carbon::parse($fecha)->format('Y-m-d');
        } catch (\Throwable $e) {
            // Log::warning('[Parser] Fecha rechazada por Carbon', [
            //     'valor' => $fecha,
            //     'error' => $e->getMessage(),
            // ]);
            return null;
        }
    }

    /**
     * Detecta facturas que no corresponden a ningún concepto.
     */
    private function detectarFacturasHuerfanas(array $facturas, array $conceptos): void
    {
        $ordenesValidos = array_column($conceptos, 'orden');

        $huerfanas = array_filter($facturas, function ($f) use ($ordenesValidos) {
            return !in_array($f['concepto_orden'], $ordenesValidos, true);
        });

        if (!empty($huerfanas)) {
            // Log::warning('[Parser] Facturas sin concepto válido', [
            //     'total'     => count($huerfanas),
            //     'conceptos' => $ordenesValidos,
            //     'huerfanas' => array_map(fn($f) => [
            //         'concepto_orden' => $f['concepto_orden'],
            //         'total_factura'  => $f['total_factura'],
            //         'fecha'          => $f['fecha'],
            //     ], array_values($huerfanas)),
            // ]);
        }
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