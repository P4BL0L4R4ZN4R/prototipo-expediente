<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Http\Controllers\Api\CalendarioController;
use App\Http\Controllers\Api\WordController;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordTemplateService;

class CalendarioWordController extends WordController
{
    public function __construct(
        private WordTemplateService $tpl,
        private CalendarioController $calendario,
    ) {}

    protected function plantilla(): string { return 'plantillas/4.CALENDARIO-DE-TRABAJO.docx'; }
    protected function nombreArchivo(string $id): string { return "calendario_{$id}"; }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx   = new ExpedienteContext($this->expedienteId, $data, $ia);
        $filas = $this->calendario->filas($data, $ia);
        $fecha = $ctx->calc->fechaCalendario();

        $this->tpl->tablaCalendario($template, 'TABLA_CALENDARIO', $filas);
        $this->tpl->setEncabezadoEmpresa($template, $ctx, 'Calendario de Trabajo');
        $template->setValue('FECHA', $ctx->calc->fechaDocumento($fecha));
    }
}