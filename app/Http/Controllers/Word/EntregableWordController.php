<?php

namespace App\Http\Controllers\Word;

use App\Services\Secciones\Cotizacion\Generador;
use App\Http\Controllers\Api\WordController;
use App\Services\Storage\ExpedienteTextoStorage;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordFieldDispatcher;
use App\Services\Word\WordFieldRegistry;
use App\Services\Word\WordFieldResolver;
use App\Services\Word\WordTemplateService;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;

class EntregableWordController extends WordController
{
    public function __construct(
        private WordTemplateService $tpl,
        private Generador $cotizacion,
        private ExpedienteTextoStorage $textoStorage,
        WordFieldResolver $resolver,
        WordFieldDispatcher $dispatcher,
    ) {
        parent::__construct($resolver, $dispatcher);
    }

    protected function plantilla(): string
    {
        return 'plantillas/5.ENTREGABLE.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "entregable_{$id}";
    }

    protected function campos(): array
    {
        return WordFieldRegistry::entregable();
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx = new ExpedienteContext($this->expedienteId, $data, $ia);

        // 1. Campos declarativos:
        //    INTRODUCCION, PROBLEMATICA, DESARROLLO_CONCEPTOS, RETROSPECTIVA,
        //    OBJETIVO_GENERAL, OBJETIVOS_ESPECIFICOS, FECHA, DIRECCION_CLIENTE
        $this->inyectarCampos($template, $ctx);

        // 2. Encabezado (custom)
        $this->tpl->setEncabezadoEmpresa($template, $ctx);

        // 3. Cierre (custom por fallback)
        $this->inyectarCierre($template, $ctx);

        // 4. Tabla de pagos (custom porque llama a CotizacionController)
        $this->inyectarTablaPagos($template, $data, $ia);
    }

    // =========================================================
    // CIERRE DEL SERVICIO
    // =========================================================

    private function inyectarCierre(TemplateProcessor $template, ExpedienteContext $ctx): void
    {
        $cierre = $this->construirCierre($ctx);
        $template->setValue('FINALIZACION_SERVICIO', $cierre);
    }

    private function construirCierre(ExpedienteContext $ctx): string
    {
        // El .txt ya tiene el cierre completo (lo armó Api\EntregableController)
        $seccionCierre = $this->textoStorage->obtenerSeccion(
            $ctx->id,
            'entregable',
            'finalizacion_servicio'
        );
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
}