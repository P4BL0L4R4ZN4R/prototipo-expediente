<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Http\Controllers\Api\CotizacionController;
use App\Http\Controllers\Api\WordController;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordTemplateService;

class CotizacionesWordController extends WordController
{
    protected string $tipo = 'inicial';

    public function __construct(
        private WordTemplateService $tpl,
        private CotizacionController $cotizacion,
    ) {}

    public function exportInicial(string $id)   { return $this->exportar($id, 'inicial'); }
    public function exportFinal(string $id)     { return $this->exportar($id, 'final'); }
    public function exportFinalFechas(string $id){ return $this->exportar($id, 'final_fechas'); }

    protected function exportar(string $id, string $tipo)
    {
        $this->tipo = in_array($tipo, ['inicial','final','final_fechas'], true) ? $tipo : 'inicial';
        return $this->export($id);
    }

    protected function plantilla(): string
    {
        return match ($this->tipo) {
            'final_fechas' => 'plantillas/4.COTIZACION-FINAL-FECHAS.docx',
            'final'        => 'plantillas/3.COTIZACION-FINAL.docx',
            default        => 'plantillas/2.COTIZACION-INICIAL.docx',
        };
    }

    protected function nombreArchivo(string $id): string
    {
        return "cotizacion_{$this->tipo}_{$id}";
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx = new ExpedienteContext($this->expedienteId, $data, $ia);
        $esFinal = in_array($this->tipo, ['final','final_fechas'], true);

        $info = $esFinal
            ? $this->cotizacion->tablaFinal($data, $ia)
            : $this->cotizacion->tablaInicial($data, $ia);

        $fecha = $esFinal
            ? $ctx->calc->fechaCotizacionFinal()
            : $ctx->calc->fechaCotizacionInicial();

        $conFechas = ($this->tipo === 'final_fechas');

        $this->tpl->tablaPagos(
            $template,
            'TABLA_COTIZACION',
            $info['grupos'],
            $info['total'],
            $info['total_letras'],
            $conFechas,   // conFechas
            !$conFechas,  // conTotalLetras
            true          // conTotal
        );

        $this->tpl->setEncabezadoEmpresa($template, $ctx, $esFinal ? 'Cotización Final' : 'Cotización Inicial');
        $template->setValue('FECHA',    $ctx->calc->fechaDocumento($fecha));
        $template->setValue('SERVICIO', $ctx->servicio());
    }
}