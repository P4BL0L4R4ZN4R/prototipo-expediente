<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Http\Controllers\Api\WordController;
use App\Services\ExpedienteTextoStorage;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordTemplateService;

class ResumenWordController extends WordController
{
    public function __construct(
        private WordTemplateService $tpl,
        private ExpedienteTextoStorage $textoStorage,
    ) {}

    protected function plantilla(): string { return 'plantillas/6.RESUMEN-EJECUTIVO.docx'; }
    protected function nombreArchivo(string $id): string { return "resumen_ejecutivo_{$id}"; }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx = new ExpedienteContext($this->expedienteId, $data, $ia);

        $propuesta = $this->textoStorage->obtenerPropuestaCompleta($ctx->id);

        $this->tpl->setEncabezadoEmpresa($template, $ctx, 'Resumen Ejecutivo');

        $template->setValue('OBJETIVO', $propuesta['objetivo_general']['texto'] ?? '');
        $template->setValue('CIUDAD',   $ctx->ciudad());
        $template->setValue('RANGO_FECHA', $ctx->calc->periodoServicio());

        // Lista de conceptos
        $conceptos = $ctx->conceptosIA();
        if (empty($conceptos)) {
            $template->deleteBlock('LISTA_PROCESOS');
        } else {
            $replacements = [];
            foreach ($conceptos as $c) {
                $replacements[] = ['item_proceso' => $c['concepto'] ?? ''];
            }
            $template->cloneBlock('LISTA_PROCESOS', 0, true, false, $replacements);
        }
    }
}