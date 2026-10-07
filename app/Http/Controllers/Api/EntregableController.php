<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GeminiController;
use App\Services\CalculosExpediente;
use App\Services\PropuestaStorage;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Storage;
use App\Models\Expediente;


class EntregableController extends Controller
{
    private const RAMA_DEFAULT = 'Tecnologías de la información';
    private const TEMA_DEFAULT = 'Almacenamiento de información digital';

    public function __construct(
        private GeminiController $gemini,
        private PropuestaStorage $propuesta,
    ) {}

    // =========================================================
    // RUTAS
    // =========================================================

    public function introduccion(string $id)
    {
        return $this->generarSeccion($id, 'introduccion');
    }

    public function problematica(string $id)
    {
        return $this->generarSeccion($id, 'problematica');
    }

    public function desarrolloConceptos(string $id)
    {
        return $this->generarSeccion($id, 'desarrollo_conceptos');
    }

    // =========================================================
    // LÓGICA CENTRAL
    // =========================================================

    private function generarSeccion(string $id, string $seccion)
    {

        $exp = Expediente::find($id);
        if (!$exp) {
            return response()->json(['error' => 'Expediente no encontrado'], 404);
        }

        $ia   = $exp->ia   ?? [];
        $data = $exp->data ?? [];
        $meta = $exp->meta ?? [];

        if (empty($ia) || empty($data)) {
            return response()->json(['error' => 'Expediente incompleto'], 500);
        }

        if (empty($meta['tema']) || empty($meta['area'])) {
            return response()->json([
                'error' => 'Faltan "tema" y "area". Guárdalos antes de generar.',
            ], 422);
        }


        $calc = new CalculosExpediente($data);
        $ctx  = $this->construirContexto($id, $calc);

        $prompt = $this->construirPrompt($seccion, $ia, $ctx, $meta);

        
        $resultado = $this->gemini->generar($prompt);

        Log::info('Entregable · generación', [
            'id'        => $id,
            'seccion'   => $seccion,
            'ok'        => $resultado['ok'],
            'proveedor' => $resultado['proveedor'],
            'modelo'    => $resultado['modelo'],
            'tiempo_ms' => $resultado['tiempo_ms'],
        ]);

        $this->guardarSeccion($id, $seccion, [
            'ok'          => $resultado['ok'],
            'texto'       => $resultado['texto'],
            'proveedor'   => $resultado['proveedor'],
            'modelo'      => $resultado['modelo'],
            'tiempo_ms'   => $resultado['tiempo_ms'],
            'generado_en' => now()->toIso8601String(),
        ]);

        return response()->json([
            'id'             => $id,
            'seccion'        => $seccion,
            'ok'             => $resultado['ok'],
            'proveedor'      => $resultado['proveedor'],
            'modelo'         => $resultado['modelo'],
            'usado_fallback' => $resultado['usado_fallback'],
            'error_gemini'   => $resultado['error_gemini'],
            'tiempo_ms'      => $resultado['tiempo_ms'],
            'prompt'         => $prompt,
            'respuesta'      => $resultado['texto'],
            'error'          => $resultado['error'],
            'metadata'       => [
                'periodo_servicio' => $ctx['periodo_servicio'],
                'anio_reporte'     => $ctx['anio_reporte'],
                'semanas_totales'  => $ctx['semanas_totales'],
            ],
        ]);
    }

    private function construirContexto(string $id, CalculosExpediente $calc): array
    {
        $prop = $this->propuesta->leerConsolidado($id);

        return [
            'periodo_servicio'       => $calc->periodoServicio(),
            'anio_reporte'           => $calc->anioReporte(),
            'semanas_totales'        => $calc->semanasTotales(),
            'objetivo_general'       => $prop['objetivo_general']      ?? '',
            'objetivos_especificos'  => $prop['objetivos_especificos'] ?? '',
            'problematica_propuesta' => $prop['problematica']          ?? '',
            'periodos_por_concepto'  => $calc->periodosPorConcepto(),
        ];
    }

    private function guardarSeccion(string $id, string $seccion, array $payload): void
    {

        $exp = Expediente::find($id);
        if (!$exp) return;

        $entregable = $exp->entregable ?? [];

        $entregable[$seccion] = [
            'ok'          => $payload['ok']          ?? false,
            'texto'       => $payload['texto']       ?? '',
            'proveedor'   => $payload['proveedor']   ?? null,
            'modelo'      => $payload['modelo']      ?? null,
            'tiempo_ms'   => $payload['tiempo_ms']   ?? null,
            'generado_en' => $payload['generado_en'] ?? now()->toIso8601String(),
        ];

        $exp->entregable = $entregable;
        $exp->save();
    }
    

    private function leerSeccion(string $id, string $seccion): array
    {
        $exp = \App\Models\Expediente::find($id);
        if (!$exp) return [];

        $entregable = $exp->entregable ?? [];
        return $entregable[$seccion] ?? [];
    }

    public function generarTodo(string $id)
    {
        // Subir timeout: 3 llamadas a IA pueden tardar
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');

        $secciones = ['introduccion', 'problematica', 'desarrollo_conceptos'];
        $resultados = [];
        $inicio = microtime(true);

        foreach ($secciones as $seccion) {
            $t0 = microtime(true);
            try {
                $response = $this->generarSeccion($id, $seccion);
                $json = $response->getData(true);

                $resultados[$seccion] = [
                    'ok'        => $json['ok'] ?? false,
                    'proveedor' => $json['proveedor'] ?? null,
                    'modelo'    => $json['modelo'] ?? null,
                    'tiempo_ms' => $json['tiempo_ms'] ?? null,
                    'error'     => $json['error'] ?? null,
                ];
            } catch (\Throwable $e) {
                \Log::error('Entregable batch · excepción', [
                    'id'      => $id,
                    'seccion' => $seccion,
                    'msg'     => $e->getMessage(),
                ]);

                $resultados[$seccion] = [
                    'ok'    => false,
                    'error' => $e->getMessage(),
                ];
            }
            $resultados[$seccion]['duracion_s'] = round(microtime(true) - $t0, 2);
        }

        $total = round(microtime(true) - $inicio, 2);
        $todasOk = collect($resultados)->every(fn($r) => ($r['ok'] ?? false) === true);

        return response()->json([
            'id'        => $id,
            'todas_ok'  => $todasOk,
            'total_s'   => $total,
            'secciones' => $resultados,
        ]);
    }

    // =========================================================
    // PROMPTS
    // =========================================================

    private function construirPrompt(string $seccion, array $ia, array $ctx, array $meta = []): string
    {
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
        ];

        $plantilla = match ($seccion) {
            'introduccion'         => $this->promptIntroduccion(),
            'problematica'         => $this->promptProblematica(),
            'desarrollo_conceptos' => $this->promptDesarrolloConceptos(),
            default                => '',
        };

        return strtr($plantilla, $variables);
    }

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

    private function promptIntroduccion(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta la INTRODUCCIÓN de un ENTREGABLE retrospectivo (informe de cierre de servicios ya concluidos).

        CONTEXTO:
        - Servicio: {{SERVICIO}}
        - Cliente: {{CLIENTE}}
        - Objetivo general: {{OBJETIVO_GENERAL}}
        - Objetivos específicos: {{OBJETIVOS_ESPECIFICOS}}
        - Periodo de servicio: {{PERIODO_SERVICIO}}
        - Año de reporte: {{ANIO_REPORTE}}

        REGLAS DE CONTENIDO:
        - Redacta en PASADO (el servicio ya se prestó).
        - Menciona explícitamente el periodo: "{{PERIODO_SERVICIO}}".
        - Explica el propósito del proyecto y su justificación.
        - Anticipa brevemente los temas que se desarrollarán en el documento.
        - Refleja impacto organizacional y operativo del servicio concluido.

        REGLAS DE COHERENCIA:
        - Pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Prohibido usar términos de otras disciplinas o consultoría genérica: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - Sin adjetivos grandilocuentes.

        REGLAS DE FORMATO:
        - Un solo párrafo continuo.
        - Sin subtítulos, sin listas, sin viñetas.
        - Tono ejecutivo e impersonal.
        - No inventes fechas ni cifras.
        - No menciones inteligencia artificial.

        Entrega únicamente el párrafo final.
        PROMPT;
    }

    private function promptProblematica(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta la PROBLEMÁTICA de un ENTREGABLE retrospectivo, a partir de la problemática original de la propuesta.

        PROBLEMÁTICA ORIGINAL:
        "{{PROBLEMATICA_PROPUESTA}}"

        CONTEXTO:
        - Servicio: {{SERVICIO}}
        - Cliente: {{CLIENTE}}
        - Objetivo general: {{OBJETIVO_GENERAL}}
        - Periodo de servicio: {{PERIODO_SERVICIO}}

        REGLAS DE CONTENIDO:
        - Mantén el mismo problema central de la propuesta original.
        - Redacta en PASADO: el problema fue atendido durante el servicio.
        - Inicia EXACTAMENTE con: "{{CLIENTE}} enfrentaba..."
        - Describe las deficiencias que existían antes del servicio.
        - Cierra justificando la necesidad del servicio prestado.

        REGLAS DE COHERENCIA:
        - Pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Prohibido usar términos de otras disciplinas: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.

        REGLAS DE FORMATO:
        - Un solo párrafo continuo.
        - Sin subtítulos, sin listas, sin viñetas.
        - Tono ejecutivo e impersonal.
        - No inventes fechas ni cifras.
        - No menciones inteligencia artificial.

        Entrega únicamente el párrafo final.
        PROMPT;
    }

    private function promptDesarrolloConceptos(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta el DESARROLLO DE CONCEPTOS de un ENTREGABLE retrospectivo. Desarrolla TODOS los conceptos, cada uno como un apartado independiente.

        CONTEXTO:
        - Servicio: {{SERVICIO}}
        - Cliente: {{CLIENTE}}
        - Objetivo general: {{OBJETIVO_GENERAL}}
        - Objetivos específicos: {{OBJETIVOS_ESPECIFICOS}}
        - Periodo de servicio: {{PERIODO_SERVICIO}}
        - Año de reporte: {{ANIO_REPORTE}}

        CONCEPTOS DEL PROGRAMA (con su periodo asignado):
        {{CONCEPTOS}}

        REGLAS DE CONTENIDO:
        - El número de apartados DEBE ser EXACTAMENTE igual al número de conceptos listados.
        - Está prohibido fusionar, omitir o resumir conceptos.
        - Cada apartado describe: qué se analizó, qué se desarrolló, qué se implementó, qué se optimizó, y qué beneficio generó.
        - Menciona el periodo correspondiente en cada apartado (usando el texto "Semana X" o "Semana X a Semana Y").
        - Redacta en PASADO.

        REGLAS DE COHERENCIA:
        - Pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Prohibido usar términos de otras disciplinas: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.

        REGLAS DE FORMATO:
        - Inicia EXACTAMENTE con: "A continuación, se presentan los temas desarrollados durante el servicio:"
        - Después, cada concepto con la estructura: **NOMBRE DEL CONCEPTO EN MAYÚSCULAS.** Desarrollo del concepto (3-5 párrafos).
        - Cada apartado separado por un salto de línea.
        - Sin viñetas, sin numeración en los subtítulos.
        - Tono ejecutivo e impersonal.
        - No inventes fechas exactas ni cifras.
        - No menciones inteligencia artificial.

        Entrega únicamente el desarrollo completo.
        PROMPT;
    }
}