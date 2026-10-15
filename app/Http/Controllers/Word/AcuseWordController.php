<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Http\Controllers\Api\WordController;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordTemplateService;

class AcuseWordController extends WordController
{
    public function __construct(
        private WordTemplateService $tpl,
    ) {}

    protected function plantilla(): string { return 'plantillas/7.ACUSE.docx'; }
    protected function nombreArchivo(string $id): string { return "acuse_{$id}"; }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx = new ExpedienteContext($this->expedienteId, $data, $ia);
        $this->tpl->setEncabezadoEmpresa($template, $ctx, 'ACUSE DE RECIBO');
    }
}