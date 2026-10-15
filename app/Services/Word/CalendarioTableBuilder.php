<?php

namespace App\Services\Word;

use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\Jc;

class CalendarioTableBuilder
{
    public function build(array $filas): Table
    {
        $table = new Table([
            'borderColor' => '000000',
            'borderSize'  => 6,
            'cellMargin'  => 60,
        ]);

        // Encabezados
        $table->addRow();
        $table->addCell(800,  ['bgColor' => 'D9D9D9'])->addText('#',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);
        $table->addCell(2000, ['bgColor' => 'D9D9D9'])->addText('INICIO',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);
        $table->addCell(2000, ['bgColor' => 'D9D9D9'])->addText('FINALIZACIÓN',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);
        $table->addCell(7200, ['bgColor' => 'D9D9D9'])->addText('ACTIVIDAD A REALIZAR',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);

        // Filas
        foreach ($filas as $fila) {
            $table->addRow();
            $table->addCell(800)->addText(
                (string) $fila['numero'],
                ['size' => 11],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
            );

            if ($fila['fusionar_fechas']) {
                $table->addCell(2000, ['vMerge' => 'restart'])->addText($fila['inicio'],
                    ['size' => 11],
                    ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);
                $table->addCell(2000, ['vMerge' => 'restart'])->addText($fila['fin'],
                    ['size' => 11],
                    ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]);
            } else {
                $table->addCell(2000, ['vMerge' => 'continue']);
                $table->addCell(2000, ['vMerge' => 'continue']);
            }

            $table->addCell(7200)->addText($fila['actividad'],
                ['size' => 11],
                ['spaceBefore' => 0, 'spaceAfter' => 0]);
        }

        return $table;
    }
}