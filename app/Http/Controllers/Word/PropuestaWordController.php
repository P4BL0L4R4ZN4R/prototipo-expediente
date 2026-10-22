<?php

namespace App\Http\Controllers\Word;

use App\Http\Controllers\Api\WordController;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordFieldDispatcher;
use App\Services\Word\WordFieldRegistry;
use App\Services\Word\WordFieldResolver;
use App\Services\Word\WordTemplateService;
use PhpOffice\PhpWord\TemplateProcessor;

class PropuestaWordController extends WordController
{
    public function __construct(
        private WordTemplateService $tpl,
        WordFieldResolver $resolver,
        WordFieldDispatcher $dispatcher,
    ) {
        parent::__construct($resolver, $dispatcher);
    }

    protected function plantilla(): string
    {
        return 'plantillas/1.PROPUESTA.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "propuesta_{$id}";
    }

    protected function campos(): array
    {
        return WordFieldRegistry::propuesta();
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx = new ExpedienteContext($this->expedienteId, $data, $ia);

        // 1. Campos declarativos: TEXTO_PROPUESTA, INTRODUCCION, PROBLEMATICA,
        //    OBJETIVO_GENERAL, OBJETIVOS_ESPECIFICOS, METODOLOGIA, FECHA,
        //    DIRECCION_CLIENTE
        $this->inyectarCampos($template, $ctx);

        // 2. Encabezado custom
        $this->tpl->setEncabezadoEmpresa($template, $ctx);
    }
}