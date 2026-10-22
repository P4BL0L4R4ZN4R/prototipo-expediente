<?php

namespace App\Services\Secciones\Entregable;

use App\Http\Controllers\Api\IA\IAGenerationController;
use App\Models\Expediente;
use App\Services\CalculosExpediente;
use App\Services\Storage\ExpedienteTextoStorage;
use App\Services\Storage\PropuestaStorage;
use Illuminate\Support\Facades\Log;

class EntregableService
{
    public function __construct(
        private IAGenerationController $iacontext,
        private PropuestaStorage $propuesta,
        private ExpedienteTextoStorage $textoStorage,
        private EntregablePromptBuilder $builder,
        private EntregableParser $parser,
    ) {}

    /**
     * Orquesta la generación de una sección.
     * Devuelve el array de respuesta listo para JSON.
     *
     * @throws \RuntimeException si el expediente no existe o está incompleto.
     */
    public function generar(string $id, string $seccion): array
    {
        $exp = Expediente::find($id);
        if (!$exp) {
            throw new \RuntimeException('Expediente no encontrado');
        }

        $ia   = $exp->ia   ?? [];
        $data = $exp->data ?? [];
        $meta = $exp->meta ?? [];

        if (empty($ia) || empty($data)) {
            throw new \RuntimeException('Expediente incompleto');
        }

        if (empty($meta['tema']) || empty($meta['area'])) {
            throw new \RuntimeException('Faltan "tema" y "area". Guárdalos antes de generar.');
        }

        $calc = new CalculosExpediente($data);
        $ctx  = $this->construirContexto($id, $calc);

        $direccion = $exp->direccion;
        $prompt    = $this->builder->construir($seccion, $ia, $ctx, $meta, $direccion);
        $resultado = $this->iacontext->generar($prompt);

        $textoFinal = $this->parser->parsear(
            $seccion,
            $resultado['texto'] ?? '',
            $id
        );

        $this->persistirSeccion($id, $seccion, $textoFinal);

        return [
            'id'             => $id,
            'seccion'        => $seccion,
            'ok'             => $resultado['ok'],
            'proveedor'      => $resultado['proveedor'],
            'modelo'         => $resultado['modelo'],
            'usado_fallback' => $resultado['usado_fallback'],
            'error_IA'       => $resultado['error_IA'] ?? null,
            'tiempo_ms'      => $resultado['tiempo_ms'],
            'prompt'         => $prompt,
            'respuesta'      => $textoFinal,
            'error'          => $resultado['error'],

            'metadata'       => [
                'periodo_servicio' => $ctx['periodo_servicio'],
                'anio_reporte'     => $ctx['anio_reporte'],
                'semanas_totales'  => $ctx['semanas_totales'],
            ],
        ];
    }

    // =========================================================
    // INTERNO
    // =========================================================

    private function construirContexto(string $id, CalculosExpediente $calc): array
    {
        $prop = $this->propuesta->leerConsolidado($id);

        $ctx = [
            'periodo_servicio'      => $calc->periodoServicio(),
            'anio_reporte'          => $calc->anioReporte(),
            'semanas_totales'       => $calc->semanasTotales(),
            'periodos_por_concepto' => $calc->periodosPorConcepto(),
        ];

        // Campos leídos de la propuesta, según config/entregable.php
        foreach (config('entregable.extraer') as $claveCtx => $campoProp) {
            $ctx[$claveCtx] = $prop[$campoProp] ?? '';
        }

        return $ctx;
    }

    private function persistirSeccion(string $id, string $seccion, string $texto): void
    {
        try {
            $this->textoStorage->guardarSeccion($id, 'entregable', $seccion, $texto);
        } catch (\Throwable $e) {
            Log::error('[EntregableService] Error al guardar sección', [
                'id'      => $id,
                'seccion' => $seccion,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}