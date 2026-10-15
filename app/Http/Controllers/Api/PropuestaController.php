<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GeminiController;
use App\Services\PropuestaPrompts;
use App\Services\PropuestaStorage;
use Illuminate\Support\Facades\Log;
use App\Models\Expediente;
use App\Services\ExpedienteTextoStorage;

class PropuestaController extends Controller
{
    public function __construct(
        private GeminiController $gemini,
        private PropuestaStorage $propuesta,
        private PropuestaPrompts $prompts,
        private ExpedienteTextoStorage $textoStorage,
    ) {}

    // =========================================================
    // RUTAS POR SECCIÓN
    // =========================================================

    public function textoPropuesta(string $id)
    {
        return $this->generarSeccion($id, 'texto_propuesta');
    }

    public function introduccion(string $id)
    {
        return $this->generarSeccion($id, 'introduccion');
    }

    public function problematica(string $id)
    {
        return $this->generarSeccion($id, 'problematica');
    }

    public function objetivoGeneral(string $id)
    {
        return $this->generarSeccion($id, 'objetivo_general');
    }

    public function objetivosEspecificos(string $id)
    {
        return $this->generarSeccion($id, 'objetivos_especificos');
    }

    public function metodologia(string $id)
    {
        return $this->generarSeccion($id, 'metodologia');
    }

    // =========================================================
    // LÓGICA CENTRAL
    // =========================================================

    private function generarSeccion(string $id, string $seccion)
    {
        // 1. Cargar expediente
        $exp = Expediente::find($id);

        if (!$exp) {
            Log::error('[PropuestaController] Expediente no encontrado', ['id' => $id]);
            return response()->json(['error' => 'Expediente no encontrado'], 404);
        }

        $ia   = $exp->ia   ?? [];
        $meta = $exp->meta ?? [];

        if (empty($ia)) {
            Log::error('[PropuestaController] Expediente incompleto: ia vacío', ['id' => $id]);
            return response()->json(['error' => 'Expediente incompleto: ia vacío'], 500);
        }

        // 2. Validar meta
        if (empty($meta['tema']) || empty($meta['area'])) {
            Log::warning('[PropuestaController] Faltan tema/area', ['id' => $id]);
            return response()->json([
                'error' => 'Faltan "tema" y "area". Guárdalos antes de generar.',
            ], 422);
        }

        // 3. Construir prompt
        $rama = $meta['area'];
        $tema = $meta['tema'];


            // Log::info('[PropuestaController] generarSeccion · meta cargado', [
            //     'id'             => $id,
            //     'seccion'        => $seccion,
            //     'meta_completo'  => $meta,
            //     'num_objetivos'  => $meta['num_objetivos'] ?? null,
            //     'tipo_num_obj'   => gettype($meta['num_objetivos'] ?? null),
            //     'tema'           => $meta['tema'] ?? null,
            //     'area'           => $meta['area'] ?? null,
            // ]);

        $prompt = $this->construirPrompt($seccion, $rama, $tema, $ia, $meta);

        // 4. Llamar a IA
        $resultado = $this->gemini->generar($prompt);


        // 5. Guardar sección como archivo .txt
        try {
            $this->textoStorage->guardarSeccion($id, 'propuesta', $seccion, $resultado['texto']);
        } catch (\Throwable $e) {
            Log::error('[PropuestaController] Error al guardar sección', [
                'id'      => $id,
                'seccion' => $seccion,
                'error'   => $e->getMessage(),
            ]);
        }

        // 6. Devolver JSON
        return response()->json([
            'id'             => $id,
            'seccion'        => $seccion,
            'rama'           => $rama,
            'tema'           => $tema,
            'ok'             => $resultado['ok'],
            'proveedor'      => $resultado['proveedor'],
            'modelo'         => $resultado['modelo'],
            'usado_fallback' => $resultado['usado_fallback'],
            'error_gemini'   => $resultado['error_gemini'],
            'tiempo_ms'      => $resultado['tiempo_ms'],
            'prompt'         => $prompt,
            'respuesta'      => $resultado['texto'],
            'error'          => $resultado['error'],
        ]);
    }

    // =========================================================
    // CONSTRUCCIÓN DE PROMPT
    // =========================================================

    private function construirPrompt(string $seccion, string $rama, string $tema, array $ia, array $meta): string
    {
        // Cantidad de objetivos: viene del meta del expediente
        $cantidad = (int) ($meta['num_objetivos'] ?? 5);
        $cantidad = max(2, min($cantidad, 10));

        $variables = [
            '{{RAMA}}'          => $rama,
            '{{TEMA}}'          => $tema,
            '{{SERVICIO}}'      => $ia['servicio']  ?? '',
            '{{OBJETIVO}}'      => $ia['objetivo']  ?? '',
            '{{CLIENTE}}'       => $ia['cliente']   ?? '',
            '{{CONCEPTOS}}'     => $this->formatearConceptos($ia['conceptos'] ?? []),
            '{{NUM_OBJETIVOS}}' => $cantidad,
        ];

        $plantilla = $this->prompts->plantilla($seccion);

        return strtr($plantilla, $variables);
    }

    private function formatearConceptos(array $conceptos): string
    {
        return collect($conceptos)
            ->pluck('concepto')
            ->filter()
            ->values()
            ->implode("\n");
    }
}