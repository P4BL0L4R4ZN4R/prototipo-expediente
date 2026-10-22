<?php

namespace App\Services\Secciones\Entregable;

use App\Services\Secciones\Prompts\EntregablePrompts;

class EntregablePromptBuilder
{
    public function __construct(
        private EntregablePrompts $prompts,
    ) {}

    /**
     * Construye el prompt final reemplazando las variables.
     */
    public function construir(
        string $seccion,
        array $ia,
        array $ctx,
        array $meta = [],
        ?string $direccion = null,
    ): string {
        $variables = [
            '{{RAMA}}'                   => $meta['area'] ?? 'N/A',
            '{{TEMA}}'                   => $meta['tema'] ?? 'N/A',
            '{{SERVICIO}}'               => $ia['servicio'] ?? '',
            '{{CLIENTE}}'                => $ia['cliente']  ?? '',
            '{{OBJETIVO}}'               => $ia['objetivo'] ?? '',
            '{{PERIODO_SERVICIO}}'       => $ctx['periodo_servicio'],
            '{{ANIO_REPORTE}}'           => (string) $ctx['anio_reporte'],
            '{{OBJETIVO_GENERAL}}'       => $ctx['objetivo_general'],
            '{{OBJETIVOS_ESPECIFICOS}}'  => $ctx['objetivos_especificos'],
            '{{PROBLEMATICA_PROPUESTA}}' => $ctx['problematica_propuesta'],
            '{{CONCEPTOS}}'              => $this->formatearConceptos($ia, $ctx),
            '{{DURACION}}'               => $meta['duracion']  ?? 'periodo completo del servicio',
            '{{MODALIDAD}}'              => $meta['modalidad'] ?? 'no especificada',
            '{{UBICACION}}' => $direccion ?? 'No especificada',
        ];

        $plantilla = $this->prompts->plantilla($seccion);

        return strtr($plantilla, $variables);
    }

    /**
     * Formatea la lista de conceptos con su periodo asignado.
     */
    private function formatearConceptos(array $ia, array $ctx): string
    {
        $periodos = $ctx['periodos_por_concepto'] ?? [];
        $lineas   = [];

        foreach ($ia['conceptos'] ?? [] as $i => $c) {
            $nombre = $c['concepto'] ?? '';
            if ($nombre === '') continue;

            $info    = $periodos[$nombre] ?? null;
            $periodo = $info['periodo_texto'] ?? '';

            $lineas[] = trim(($i + 1) . ". {$nombre}" . ($periodo ? " | {$periodo}" : ''));
        }

        return implode("\n", $lineas);
    }
}