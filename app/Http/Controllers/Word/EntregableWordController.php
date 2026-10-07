<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\TextRun;
use App\Http\Controllers\Api\WordController;
use App\Services\CalculosExpediente;
// use Illuminate\Support\Facades\Storage;
use App\Models\Expediente;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Word\CotizacionesWordController;
use App\Http\Controllers\Api\CotizacionController;


class EntregableWordController extends WordController
{


    public function __construct(
        private CotizacionesWordController $cotizaciones,
        private CotizacionController $cotizacion,
    ) {}


    protected function plantilla(): string
    {
        return 'plantillas/5.ENTREGABLE.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "entregable_{$id}";
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {


        $id = $this->expedienteId;

        // 1. Cargar secciones IA del Entregable
        $introduccion         = $this->leerSeccion($id, 'introduccion');
        $problematica         = $this->leerSeccion($id, 'problematica');
        $desarrolloConceptos  = $this->leerSeccion($id, 'desarrollo_conceptos');

        // 2. Cargar propuesta consolidada (para objetivo general y específicos)
        $propuesta = $this->leerConsolidado($id);

        // 3. Cálculos de fechas
        $calc = new CalculosExpediente($data);

        Log::info('ENTREGABLE WORD · construir() iniciado', [
            'id' => $this->expedienteId,
            'tiene_introduccion'         => !empty($introduccion['texto'] ?? ''),
            'tiene_problematica'         => !empty($problematica['texto'] ?? ''),
            'tiene_desarrollo_conceptos' => !empty($desarrolloConceptos['texto'] ?? ''),
            'objetivo_general_len'       => strlen($propuesta['objetivo_general'] ?? ''),
            'objetivos_especificos_len'  => strlen($propuesta['objetivos_especificos'] ?? ''),
            'periodo_servicio'           => $calc->periodoServicio(),
        ]);


        // 4. Escalares
        $template->setValue('EMPRESA_BRINDA',  $ia['empresa_factura'] ?? '');
        $template->setValue('EMPRESA_RECIBE',  $ia['cliente']         ?? '');
        $template->setValue('NOMBRE_PROGRAMA', $ia['servicio']        ?? '');
        $template->setValue('FECHA',           $calc->periodoServicio());

        // 5. Bloques con formato (párrafos largos)
        $this->inyectarBloque($template, 'INTRODUCCION',
            $introduccion['texto'] ?? '');

        $this->inyectarBloque($template, 'PROBLEMATICA',
            $problematica['texto'] ?? '');

        $this->inyectarBloque($template, 'OBJETIVO_GENERAL',
            $propuesta['objetivo_general'] ?? '');

        $this->inyectarLista($template, 'OBJETIVOS_ESPECIFICOS',
            $propuesta['objetivos_especificos'] ?? '');

        $this->inyectarConNegritas($template, 'DESARROLLO_CONCEPTOS',
            $desarrolloConceptos['texto'] ?? '');

        // 6. Control de pagos (opcional, por si la plantilla lo tiene)
        $total = collect($data['conceptos'] ?? [])
            ->flatMap(fn($c) => $c['facturas'] ?? [])
            ->sum(fn($f) => (float) ($f['total_factura'] ?? 0));

        try {
            $info = $this->cotizacion->tablaFinal($data, $ia);
            $tabla = $this->cotizaciones->armarTablaPagos(
                $info['grupos'],
                $info['total'],
                $info['total_letras']
            );

            $template->setComplexBlock('CONTROL_PAGOS', $tabla);
            $template->setValue('TOTAL',       '$' . number_format($info['total'], 2));
            $template->setValue('TOTAL_LETRA', $info['total_letras']);

            \Log::info('ENTREGABLE WORD · CONTROL_PAGOS inyectado', [
                'total' => $info['total'],
            ]);
        } catch (\Throwable $e) {
            \Log::warning('ENTREGABLE WORD · CONTROL_PAGOS falló', [
                'error' => $e->getMessage(),
            ]);
        }

    }

    // =========================================================
    // CARGA
    // =========================================================

    private function leerSeccion(string $id, string $seccion): array
    {
        $exp = Expediente::find($id);
        if (!$exp) return [];

        $entregable = $exp->entregable ?? [];
        return $entregable[$seccion] ?? [];
    }


    private function leerConsolidado(string $id): array
    {
        $exp = Expediente::find($id);

        $consolidado = [
            'texto_propuesta'       => '',
            'introduccion'          => '',
            'problematica'          => '',
            'objetivo_general'      => '',
            'objetivos_especificos' => '',
            'metodologia'           => '',
        ];

        if (!$exp) return $consolidado;

        $propuestaBD = $exp->propuesta_ia ?? [];

        foreach ($consolidado as $sec => $_) {
            $consolidado[$sec] = $propuestaBD[$sec]['texto'] ?? '';
        }

        return $consolidado;
    }
            

    // =========================================================
    // INYECCIÓN
    // =========================================================

    private function inyectarBloque(TemplateProcessor $t, string $var, string $texto): void
    {
        $texto = $this->limpiar($texto);
        if ($texto === '') { $t->setValue($var, ''); return; }

        $run = new TextRun();
        $lineas = explode("\n", $texto);
        $primera = true;
        foreach ($lineas as $linea) {
            if (!$primera) $run->addTextBreak();
            $run->addText($linea);
            $primera = false;
        }
        $t->setComplexBlock($var, $run);
    }

    private function inyectarLista(TemplateProcessor $t, string $var, string $texto): void
    {
        $texto = $this->limpiar($texto);
        if ($texto === '') { $t->setValue($var, ''); return; }

        $lineas = array_values(array_filter(
            array_map('trim', explode("\n", $texto)),
            fn($l) => $l !== ''
        ));

        $run = new TextRun();
        $primera = true;
        foreach ($lineas as $linea) {
            if (!$primera) $run->addTextBreak();
            $run->addText($linea);
            $primera = false;
        }
        $t->setComplexBlock($var, $run);
    }

    private function inyectarConNegritas(TemplateProcessor $t, string $var, string $texto): void
    {
        $texto = $this->limpiar($texto);
        if ($texto === '') { $t->setValue($var, ''); return; }

        $run = new TextRun();
        $lineas = explode("\n", $texto);
        $primera = true;

        foreach ($lineas as $linea) {
            $linea = rtrim($linea);
            if ($linea === '') {
                if (!$primera) $run->addTextBreak();
                continue;
            }
            if (!$primera) $run->addTextBreak();

            $partes = preg_split('/(\*\*[^*]+\*\*)/u', $linea, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($partes as $parte) {
                if ($parte === '') continue;
                if (preg_match('/^\*\*(.+?)\*\*$/u', $parte, $m)) {
                    $run->addText($m[1], ['bold' => true]);
                } else {
                    $limpio = str_replace('*', '', $parte);
                    if ($limpio !== '') $run->addText($limpio);
                }
            }
            $primera = false;
        }
        $t->setComplexBlock($var, $run);
    }

    private function limpiar(string $texto): string
    {
        $texto = trim($texto);
        if (str_starts_with($texto, '"') && str_ends_with($texto, '"')) {
            $texto = trim(substr($texto, 1, -1));
        }
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto);
        return trim($texto);
    }
}