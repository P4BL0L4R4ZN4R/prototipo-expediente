<?php

namespace App\Services\Word;

use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\Jc;
use Carbon\Carbon;

class PagosTableBuilder
{
    public function build(
        array $grupos,
        float $total,
        string $totalLetras,
        bool $conFechas = false,
        bool $conTotalLetras = true,
        bool $conTotal = true,
    ): Table {
        $table = new Table([
            'borderColor' => '000000',
            'borderSize'  => 6,
            'cellMargin'  => 60,
        ]);

        // Encabezados
        $table->addRow();
        $table->addCell(5500)->addText('Conceptos',
            ['bold' => true, 'size' => 12],
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::CENTER]
        );
        $table->addCell(2500)->addText('Monto',
            ['bold' => true, 'size' => 12],
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::CENTER]
        );
        if ($conFechas) {
            $table->addCell(2500)->addText('Fecha',
                ['bold' => true, 'size' => 12],
                ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::CENTER]
            );
        }

        // Filas por grupo
        foreach ($grupos as $grupo) {
            foreach ($grupo['facturas'] as $i => $factura) {
                $table->addRow();

                if ($i === 0) {
                    $table->addCell(5500, ['vMerge' => 'restart'])->addText(
                        $grupo['concepto'],
                        ['size' => 12],
                        ['spaceBefore' => 0, 'spaceAfter' => 0]
                    );
                } else {
                    $table->addCell(5500, ['vMerge' => 'continue']);
                }

                $table->addCell(2500)->addText(
                    '$' . number_format((float) ($factura['total_factura'] ?? 0), 2),
                    ['size' => 12],
                    ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::END]
                );

                if ($conFechas) {
                    $fechaFila = '';
                    if (!empty($factura['fecha'])) {
                        try {
                            $fechaFila = Carbon::parse($factura['fecha'])->format('d/m/Y');
                        } catch (\Throwable) {
                            $fechaFila = $factura['fecha'];
                        }
                    }
                    $table->addCell(2500)->addText(
                        $fechaFila,
                        ['size' => 11],
                        ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::CENTER]
                    );
                }
            }
        }

        // Total
        if ($conTotal) {
            $table->addRow();
            $table->addCell(5500)->addText('Total',
                ['bold' => true, 'size' => 12],
                ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::END]
            );
            $table->addCell(2500)->addText(
                '$' . number_format($total, 2),
                ['bold' => true, 'size' => 12],
                ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::END]
            );
            if ($conFechas) $table->addCell(2500);
        }

        // Total en letras
        if ($conTotalLetras) {
            $cols  = $conFechas ? 3 : 2;
            $ancho = $conFechas ? 10500 : 8000;

            $table->addRow();
            $table->addCell($ancho, ['gridSpan' => $cols])->addText(
                $totalLetras,
                ['size' => 12, 'bold' => true],
                ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::CENTER]
            );
        }

        return $table;
    }
}