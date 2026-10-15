<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Http\Controllers\Api\WordController;
use App\Http\Controllers\Api\CotizacionController;
use App\Models\Expediente;
use App\Services\ExpedienteTextoStorage;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordTemplateService;
use Illuminate\Support\Facades\Log;

class EntregableWordController extends WordController
{
    public function __construct(
        private WordTemplateService $tpl,
        private CotizacionController $cotizacion,
        private ExpedienteTextoStorage $textoStorage,
    ) {}

    protected function plantilla(): string { return 'plantillas/5.ENTREGABLE.docx'; }
    protected function nombreArchivo(string $id): string { return "entregable_{$id}"; }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx = new ExpedienteContext($this->expedienteId, $data, $ia);

        $this->inyectarEncabezado($template, $ctx);
        $this->inyectarIntroduccion($template, $ctx);
        $this->inyectarProblematica($template, $ctx);
        $this->inyectarDesarrolloConceptos($template, $ctx);
        $this->inyectarObjetivos($template, $ctx);
        $this->inyectarCierre($template, $ctx);
        $this->inyectarTablaPagos($template, $data, $ia);
        $this->inyectarRetrospectiva($template, $ctx);
    }

    // =========================================================
    // ENCABEZADO
    // =========================================================

    private function inyectarEncabezado(TemplateProcessor $template, ExpedienteContext $ctx): void
    {
        $this->tpl->setEncabezadoEmpresa($template, $ctx);
        $template->setValue('FECHA', $ctx->calc->periodoServicio());
    }

    // =========================================================
    // SECCIONES DE TEXTO
    // =========================================================

    private function inyectarIntroduccion(TemplateProcessor $template, ExpedienteContext $ctx): void
    {
        $seccion = $this->textoStorage->obtenerSeccion($ctx->id, 'entregable', 'introduccion');
        $this->tpl->bloque($template, 'INTRODUCCION', $seccion['texto'] ?? '');
    }

    private function inyectarProblematica(TemplateProcessor $template, ExpedienteContext $ctx): void
    {
        $seccion = $this->textoStorage->obtenerSeccion($ctx->id, 'entregable', 'problematica');
        $this->tpl->bloque($template, 'PROBLEMATICA', $seccion['texto'] ?? '');
    }

    private function inyectarDesarrolloConceptos(TemplateProcessor $template, ExpedienteContext $ctx): void
    {
        $seccion = $this->textoStorage->obtenerSeccion($ctx->id, 'entregable', 'desarrollo_conceptos');
        $this->tpl->conNegritas($template, 'DESARROLLO_CONCEPTOS', $seccion['texto'] ?? '');
    }

    // =========================================================
    // HEREDADAS DE LA PROPUESTA
    // =========================================================

    private function inyectarObjetivos(TemplateProcessor $template, ExpedienteContext $ctx): void
    {
        $propuesta = $this->textoStorage->obtenerPropuestaCompleta($ctx->id);

        $this->tpl->bloque($template, 'OBJETIVO_GENERAL', $propuesta['objetivo_general']['texto'] ?? '');
        $this->tpl->lista ($template, 'OBJETIVOS_ESPECIFICOS', $propuesta['objetivos_especificos']['texto'] ?? '');
    }

    // =========================================================
    // CIERRE DEL SERVICIO
    // =========================================================

    private function inyectarCierre(TemplateProcessor $template, ExpedienteContext $ctx): void
    {
        $cierre = $this->construirCierre($ctx);
        // $this->tpl->bloque($template, 'FINALIZACION_SERVICIO', '"' . $cierre . '"');
        $template->setValue('FINALIZACION_SERVICIO', $cierre);
    }


    private function construirCierre(ExpedienteContext $ctx): string
    {
        // El .txt ya tiene el cierre completo (lo armó Api\EntregableController)
        $seccionCierre = $this->textoStorage->obtenerSeccion($ctx->id, 'entregable', 'finalizacion_servicio');
        $cierre = trim($seccionCierre['texto'] ?? '');

        // Si no existe, armar un default
        if ($cierre === '') {
            $empresaRecibe  = $ctx->empresaRecibe();
            $nombrePrograma = $ctx->servicio();

            $cierre = "{$empresaRecibe}., doy por terminada la prestación de servicio profesional "
                . "mediante el cual obtuve un documento que proporciona {$nombrePrograma}. "
                . "Dicho documento me fue proporcionado en Mérida, Yucatán, México.";
        }

        return $cierre;
    }


    // =========================================================
    // TABLA DE PAGOS
    // =========================================================

    private function inyectarTablaPagos(TemplateProcessor $template, array $data, array $ia): void
    {
        try {
            $info = $this->cotizacion->tablaFinal($data, $ia);

            $this->tpl->tablaPagos(
                $template,
                'CONTROL_PAGOS',
                $info['grupos'],
                $info['total'],
                $info['total_letras'],
                true,   // conFechas
                false,  // conTotalLetras
                false   // conTotal
            );
        } catch (\Throwable $e) {
            Log::warning('ENTREGABLE · CONTROL_PAGOS falló', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
        }
    }


    // =========================================================
    // RETROSPECTIVA
    // =========================================================

    private function inyectarRetrospectiva(TemplateProcessor $template, ExpedienteContext $ctx): void
    {
        $seccion = $this->textoStorage->obtenerSeccion($ctx->id, 'entregable', 'retrospectiva');
        $this->tpl->bloque($template, 'RETROSPECTIVA', $seccion['texto'] ?? '');
    }

    
}