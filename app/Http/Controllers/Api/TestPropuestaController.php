<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use Illuminate\Support\Facades\Log;


class TestPropuestaController extends Controller
{
    private const SECCIONES = [
        'texto_propuesta',
        'introduccion',
        'problematica',
        'objetivo_general',
        'objetivos_especificos',
        'metodologia',
    ];

    public function __construct(
        private PropuestaController $propuesta,
    ) {}

    /**
     * Genera TODAS las secciones de la propuesta de un solo golpe.
     * GET /api/propuesta/{id}/generar-todo
     */
    public function generarTodo(string $id)
    {
        // 1. Verificar expediente en BD
        $exp = Expediente::find($id);

        if (!$exp) {
            return response()->json(['error' => 'Expediente no encontrado'], 404);
        }

        // 2. Subir timeout SOLO para esta request (6 llamadas a IA pueden tardar)
        @set_time_limit(600); // 10 min
        @ini_set('max_execution_time', '600');

        $resultados = [];
        $inicio = microtime(true);

        // 3. Generar sección por sección
        foreach (self::SECCIONES as $seccion) {
            $t0 = microtime(true);

            try {
                // Llamamos al método público correspondiente
                $response = match ($seccion) {
                    'texto_propuesta'       => $this->propuesta->textoPropuesta($id),
                    'introduccion'          => $this->propuesta->introduccion($id),
                    'problematica'          => $this->propuesta->problematica($id),
                    'objetivo_general'      => $this->propuesta->objetivoGeneral($id),
                    'objetivos_especificos' => $this->propuesta->objetivosEspecificos($id),
                    'metodologia'           => $this->propuesta->metodologia($id),
                };

                $json = $response->getData(true);
                $ok   = $json['ok'] ?? false;

                $resultados[$seccion] = [
                    'ok'         => $ok,
                    'proveedor'  => $json['proveedor'] ?? null,
                    'modelo'     => $json['modelo']    ?? null,
                    'tiempo_ms'  => $json['tiempo_ms'] ?? null,
                    'error'      => $json['error']     ?? null,
                    'texto_len'  => isset($json['respuesta']) ? strlen($json['respuesta']) : 0,
                ];

            } catch (\Throwable $e) {
                Log::error('Batch · excepción en sección', [
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

        $todasOk = collect($resultados)->every(fn($r) => $r['ok'] === true);

        return response()->json([
            'id'          => $id,
            'todas_ok'    => $todasOk,
            'total_s'     => $total,
            'secciones'   => $resultados,
        ]);
    }
}