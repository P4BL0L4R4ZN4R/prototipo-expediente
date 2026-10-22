<?php

namespace App\Http\Controllers\Word;

use App\Http\Controllers\Api\WordController;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordFieldDispatcher;
use App\Services\Word\WordFieldRegistry;
use App\Services\Word\WordFieldResolver;
use App\Services\Word\WordTemplateService;
use PhpOffice\PhpWord\TemplateProcessor;

class ResumenWordController extends WordController
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
        return 'plantillas/6.RESUMEN-EJECUTIVO.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "resumen_ejecutivo_{$id}";
    }

    protected function campos(): array
    {
        return WordFieldRegistry::resumen();
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx = new ExpedienteContext($this->expedienteId, $data, $ia);

        // 1. Campos declarativos: OBJETIVO, RANGO_FECHA, DIRECCION_CLIENTE
        $this->inyectarCampos($template, $ctx);

        // 2. Encabezado (custom por TITULO_1)
        $this->tpl->setEncabezadoEmpresa($template, $ctx, 'Resumen Ejecutivo');

        // 3. CIUDAD (custom: viene de $ctx->ciudad() pero no está en el registry
        //    porque no es parte del bloque común)
        $template->setValue('CIUDAD', $ctx->ciudad());

        // 4. Lista de conceptos (custom: usa cloneBlock, no se puede
        //    expresar como método del registry)
        $conceptos = $ctx->conceptosIA();

        if (empty($conceptos)) {
            $template->deleteBlock('LISTA_PROCESOS');
        } else {
            $replacements = array_map(
                fn($c) => ['item_proceso' => $c['concepto'] ?? ''],
                $conceptos
            );
            $template->cloneBlock('LISTA_PROCESOS', 0, true, false, $replacements);
        }
    }
}