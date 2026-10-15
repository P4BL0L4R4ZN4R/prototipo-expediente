<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Http\Controllers\Api\WordController;
use App\Services\ExpedienteTextoStorage;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordTemplateService;

class PropuestaWordController extends WordController
{
    public function __construct(
        private WordTemplateService $tpl,
        private ExpedienteTextoStorage $textoStorage,
    ) {}

    protected function plantilla(): string { return 'plantillas/1.PROPUESTA.docx'; }
    protected function nombreArchivo(string $id): string { return "propuesta_{$id}"; }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx = new ExpedienteContext($this->expedienteId, $data, $ia);
        $propuesta = $this->textoStorage->obtenerPropuestaCompleta($ctx->id);

        $fechaPropuesta = $ctx->calc->fechaPropuesta();

        $this->tpl->setEncabezadoEmpresa($template, $ctx);
        $template->setValue('FECHA', $ctx->calc->fechaDocumento($fechaPropuesta));

        $this->tpl->bloque     ($template, 'TEXTO_PROPUESTA',       $propuesta['texto_propuesta']['texto'] ?? '');
        $this->tpl->bloque     ($template, 'INTRODUCCION',          $propuesta['introduccion']['texto'] ?? '');
        $this->tpl->bloque     ($template, 'PROBLEMATICA',          $propuesta['problematica']['texto'] ?? '');
        $this->tpl->bloque     ($template, 'OBJETIVO_GENERAL',      $propuesta['objetivo_general']['texto'] ?? '');
        $this->tpl->lista      ($template, 'OBJETIVOS_ESPECIFICOS', $propuesta['objetivos_especificos']['texto'] ?? '');
        $this->tpl->conNegritas($template, 'METODOLOGIA',           $propuesta['metodologia']['texto'] ?? '');
    }
}