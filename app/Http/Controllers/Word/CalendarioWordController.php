<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\Jc;
use App\Http\Controllers\Api\CalendarioController;
use App\Http\Controllers\Api\WordController; 

class CalendarioWordController extends WordController
{
    public function __construct(
        private CalendarioController $calendario,
    ) {}

    protected function plantilla(): string
    {
        return 'plantillas/4.CALENDARIO-DE-TRABAJO.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "calendario_{$id}";
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        // Trae las filas del CalendarioController (sin calcular nada aquí)
        $filas = $this->calendario->filas($data);

        $template->setComplexBlock('TABLA_CALENDARIO', $this->generarTabla($filas));

        $template->setValue('FECHA',           now()->format('d/m/Y'));
        $template->setValue('TITULO_1',        'Calendario de Trabajo');
        $template->setValue('EMPRESA_RECIBE',  $ia['cliente'] ?? '');
        $template->setValue('EMPRESA_BRINDA',  $ia['empresa_factura'] ?? '');
        $template->setValue('NOMBRE_PROGRAMA', $ia['servicio'] ?? '');
    }

    private function generarTabla(array $filas): Table
    {
        $table = new Table([
            'borderColor' => '000000',
            'borderSize'  => 6,
            'cellMargin'  => 60,
        ]);

        // ============ Encabezados ============
        $table->addRow();

        $table->addCell(800, ['bgColor' => 'D9D9D9'])->addText('#',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        $table->addCell(2000, ['bgColor' => 'D9D9D9'])->addText('INICIO',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        $table->addCell(2000, ['bgColor' => 'D9D9D9'])->addText('FINALIZACIÓN',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        $table->addCell(7200, ['bgColor' => 'D9D9D9'])->addText('ACTIVIDAD A REALIZAR',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
        );

        // ============ Filas ============
        foreach ($filas as $fila) {
            $table->addRow();

            // Columna #
            $table->addCell(800)->addText(
                (string) $fila['numero'],
                ['size' => 11],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
            );

            // Columnas INICIO y FINALIZACIÓN (con vMerge)
            if ($fila['fusionar_fechas']) {
                $table->addCell(2000, ['vMerge' => 'restart'])->addText(
                    $fila['inicio'],
                    ['size' => 11],
                    ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
                );
                $table->addCell(2000, ['vMerge' => 'restart'])->addText(
                    $fila['fin'],
                    ['size' => 11],
                    ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0]
                );
            } else {
                $table->addCell(2000, ['vMerge' => 'continue']);
                $table->addCell(2000, ['vMerge' => 'continue']);
            }

            // Columna ACTIVIDAD
            $table->addCell(7200)->addText(
                $fila['actividad'],
                ['size' => 11],
                ['spaceBefore' => 0, 'spaceAfter' => 0]
            );
        }

        return $table;
    }
        
}