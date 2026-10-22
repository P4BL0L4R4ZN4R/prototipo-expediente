<?php

namespace App\Services\Secciones\Propuesta;

use App\Models\Expediente;
use Illuminate\Support\Facades\Log;

class BatchPropuesta
{
    public function generarTodo(string $id): array
    {
        $exp = Expediente::find($id);
        if (!$exp) {
            throw new \RuntimeException('Expediente no encontrado');
        }

        @set_time_limit(600);
        @ini_set('max_execution_time', '600');

        $resultados = [];
        $inicio = microtime(true);

        foreach (Orquestador::propuesta() as $seccionObj) {
            $seccion = $seccionObj->nombre();
            $t0 = microtime(true);

            try {
                $resultado = Orquestador::generar('propuesta', $id, $seccion);

                $resultados[$seccion] = [
                    'ok'        => $resultado['ok'] ?? false,
                    'proveedor' => $resultado['proveedor'] ?? null,
                    'modelo'    => $resultado['modelo'] ?? null,
                    'tiempo_ms' => $resultado['tiempo_ms'] ?? null,
                    'error'     => $resultado['error'] ?? null,
                    'texto_len' => isset($resultado['respuesta']) ? strlen($resultado['respuesta']) : 0,
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

        return [
            'id'        => $id,
            'todas_ok'  => collect($resultados)->every(fn($r) => $r['ok'] === true),
            'total_s'   => round(microtime(true) - $inicio, 2),
            'secciones' => $resultados,
        ];
    }
}