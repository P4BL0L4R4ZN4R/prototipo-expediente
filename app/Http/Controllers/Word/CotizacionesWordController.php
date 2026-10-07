<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\Jc;
use App\Http\Controllers\Api\CotizacionController;
use App\Http\Controllers\Api\WordController;

class CotizacionesWordController extends WordController
{
    public function __construct(
        protected CotizacionController $cotizacion,
    ) {}

    // =========================================================
    // RUTAS — dos puntos de entrada
    // =========================================================

    public function exportInicial(string $id)
    {
        return $this->exportar($id, 'inicial');
    }

    public function exportFinal(string $id)
    {
        return $this->exportar($id, 'final');
    }

    // =========================================================
    // MÉTODOS ABSTRACTOS HEREDADOS (usados por exportar())
    // =========================================================

    /** @var string Se setea antes de llamar a exportar() */
    protected string $tipo = 'inicial';

    protected function plantilla(): string
    {
        return $this->tipo === 'final'
            ? 'plantillas/3.COTIZACION-FINAL.docx'    // ⚠️ ajusta al nombre real
            : 'plantillas/2.COTIZACION-INICIAL.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "cotizacion_{$this->tipo}_{$id}";
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $info = $this->tipo === 'final'
            ? $this->cotizacion->tablaFinal($data, $ia)
            : $this->cotizacion->tablaInicial($data, $ia);

        $tabla = $this->armarTablaPagos(
            $info['grupos'],
            $info['total'],
            $info['total_letras']
        );

        $template->setComplexBlock('TABLA_COTIZACION', $tabla);

        $template->setValue('TITULO_1',       $this->tipo === 'final' ? 'Cotización Final' : 'Cotización Inicial');
        $template->setValue('EMPRESA_BRINDA', $ia['empresa_factura'] ?? '');
        $template->setValue('EMPRESA_RECIBE', $ia['cliente'] ?? '');
        $template->setValue('SERVICIO',       $ia['servicio'] ?? '');
        $template->setValue('FECHA',          now()->format('d/m/Y'));
    }

    // =========================================================
    // LÓGICA INTERNA
    // =========================================================

    /**
     * Setea el tipo y llama al export() heredado de WordController.
     */
    protected function exportar(string $id, string $tipo)
    {
        $this->tipo = in_array($tipo, ['inicial', 'final'], true) ? $tipo : 'inicial';

        return $this->export($id);
    }

    public function armarTablaPagos($grupos, float $total, string $totalLetras): Table
    {
        $table = new Table([
            'borderColor' => '000000',
            'borderSize'  => 6,
            'cellMargin'  => 60,
        ]);

        $table->addRow();
        $table->addCell(8000)->addText(
            'Conceptos',
            ['bold' => true, 'size' => 12],
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::CENTER]
        );
        $table->addCell(3000)->addText(
            'Monto',
            ['bold' => true, 'size' => 12],
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::END]
        );

        foreach ($grupos as $grupo) {
            $facturas = $grupo['facturas'];

            foreach ($facturas as $i => $factura) {
                $table->addRow();

                if ($i === 0) {
                    $table->addCell(8000, ['vMerge' => 'restart'])->addText(
                        $grupo['concepto'],
                        ['size' => 12],
                        ['spaceBefore' => 0, 'spaceAfter' => 0]
                    );
                } else {
                    $table->addCell(8000, ['vMerge' => 'continue']);
                }

                $table->addCell(3000)->addText(
                    '$' . number_format((float) ($factura['total_factura'] ?? 0), 2),
                    ['size' => 12],
                    ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::END]
                );
            }
        }

        $table->addRow();
        $table->addCell(8000)->addText(
            'Total',
            ['bold' => true, 'size' => 12],
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::END]
        );
        $table->addCell(3000)->addText(
            '$' . number_format($total, 2),
            ['bold' => true, 'size' => 12],
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::END]
        );

        $table->addRow();
        $table->addCell(11000, ['gridSpan' => 2])->addText(
            $totalLetras,
            ['size' => 12, 'bold' => true],
            ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => Jc::CENTER]
        );

        return $table;
    }
}