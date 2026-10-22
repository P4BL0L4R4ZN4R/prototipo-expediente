<?php

namespace App\Http\Controllers\Word;

use App\Http\Controllers\Api\WordController;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordFieldDispatcher;
use App\Services\Word\WordFieldRegistry;
use App\Services\Word\WordFieldResolver;
use App\Services\Word\WordTemplateService;
use PhpOffice\PhpWord\TemplateProcessor;

class AcuseWordController extends WordController
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
        return 'plantillas/7.ACUSE.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "acuse_{$id}";
    }

    protected function campos(): array
    {
        return WordFieldRegistry::acuse();
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx = new ExpedienteContext($this->expedienteId, $data, $ia);

        // Campos declarativos (hoy solo DIRECCION_CLIENTE)
        $this->inyectarCampos($template, $ctx);

        // Encabezado (custom: inyecta EMPRESA_BRINDA, EMPRESA_RECIBE,
        // NOMBRE_PROGRAMA, TITULO_1)
        $this->tpl->setEncabezadoEmpresa($template, $ctx, 'ACUSE DE RECIBO');
    }
}