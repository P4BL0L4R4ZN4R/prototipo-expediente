<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Http\Controllers\Api\WordController;

class ResumenWordController extends WordController
{
    protected function plantilla(): string
    {
        return 'plantillas/6.RESUMEN-EJECUTIVO.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "resumen_ejecutivo_{$id}";
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        // --- Escalares ---
        $template->setValue('EMPRESA_BRINDA', $ia['empresa_factura'] ?? '');
        $template->setValue('EMPRESA_RECIBE', $ia['cliente']         ?? '');
        $template->setValue('TITULO_1',       'Resumen Ejecutivo');

        $template->setValue('OBJETIVO',     $ia['objetivo'] ?? '');
        $template->setValue('CIUDAD',       $ia['ciudad']   ?? 'Mérida, Yucatán, México');
        $template->setValue('FECHA_INICIO', $this->formatearFecha($data['fecha_inicio']  ?? null));
        $template->setValue('FECHA_FIN',    $this->formatearFecha($data['fecha_termino'] ?? null));

        // --- Lista numerada de procesos ---
        $this->listaProcesos($template, $ia['conceptos'] ?? []);
    }

    private function formatearFecha(?string $fecha): string
    {
        if (!$fecha) return '';

        $meses = ['enero','febrero','marzo','abril','mayo','junio',
                  'julio','agosto','septiembre','octubre','noviembre','diciembre'];

        $ts = strtotime($fecha);
        return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' del ' . date('Y', $ts);
    }

    private function listaProcesos(TemplateProcessor $template, array $conceptos): void
    {
        if (empty($conceptos)) {
            $template->deleteBlock('LISTA_PROCESOS');
            return;
        }

        $replacements = [];
        foreach ($conceptos as $c) {
            $replacements[] = ['item_proceso' => $c['proceso'] ?? ''];
        }

        $template->cloneBlock('LISTA_PROCESOS', 0, true, false, $replacements);
    }
}