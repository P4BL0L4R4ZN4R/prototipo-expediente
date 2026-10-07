<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Models\Expediente;


class ResumenController extends Controller
{
 
    public function show(string $id): JsonResponse
    {
        $this->validarId($id);

        $exp = Expediente::find($id);

        if (!$exp) {
            return response()->json(['error' => 'Expediente no encontrado'], 404);
        }

        $ia   = $exp->ia   ?? [];
        $data = $exp->data ?? [];

        if (empty($ia) || empty($data)) {
            return response()->json(['error' => 'Expediente incompleto'], 404);
        }

        Log::info('RESUMEN · datos recibidos', [
            'id'   => $id,
            'ia'   => $ia,
            'data' => $data,
        ]);

        return response()->json([
            'id'       => $id,
            'general'  => $this->datosGenerales($data),
            'admin'    => $this->datosAdministrativos($data),
            'calculos' => $this->calculos($data),
            'ia'       => $ia,
        ]);
    }

    // ---------------------------------------------------------
    // Normalización
    // ---------------------------------------------------------

    private function datosGenerales(array $data): array
    {
        return [
            'empresa_factura' => $data['empresa_factura'] ?? null,
            'cliente'         => $data['cliente'] ?? null,
            'servicio'        => $data['servicio'] ?? null,
            'objetivo'        => $data['objetivo'] ?? null,
            'conceptos'       => array_map(
                fn ($c) => [
                    'orden'    => $c['orden']    ?? null,
                    'concepto' => $c['concepto'] ?? null,
                    'proceso'  => $c['proceso']  ?? null,
                ],
                $data['conceptos'] ?? []
            ),
        ];
    }

    private function datosAdministrativos(array $data): array
    {
        return [
            'fecha_inicio'  => $data['fecha_inicio']  ?? null,
            'fecha_termino' => $data['fecha_termino'] ?? null,
        ];
    }

    private function calculos(array $data): array
    {
        return [
            'total_programa' => $data['calculos']['total_programa'] ?? null,
            'fecha_legal'    => $data['calculos']['fecha_legal']    ?? null,
        ];
    }

    // ---------------------------------------------------------
    // Utilidades
    // ---------------------------------------------------------

    private function validarId(string $id): void
    {
        abort_unless(
            preg_match('/^exp_\d{8}_\d{6}$/', $id),
            404,
            'ID de expediente inválido'
        );
    }
}


