<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Entregable\EntregableService;
use Illuminate\Support\Facades\Log;

class EntregableController extends Controller
{
    private const SECCIONES = [
        'introduccion',
        'problematica',
        'desarrollo_conceptos',
        'finalizacion_servicio',
        'retrospectiva',
    ];

    public function __construct(
        private EntregableService $service,
    ) {}

    // =========================================================
    // RUTAS INDIVIDUALES
    // =========================================================

    public function introduccion(string $id)
    {
        return $this->generar($id, 'introduccion');
    }

    public function problematica(string $id)
    {
        return $this->generar($id, 'problematica');
    }

    public function desarrolloConceptos(string $id)
    {
        return $this->generar($id, 'desarrollo_conceptos');
    }

    public function finalizacionServicio(string $id)
    {
        return $this->generar($id, 'finalizacion_servicio');
    }

    public function retrospectiva(string $id)
    {
        return $this->generar($id, 'retrospectiva');
    }
    
    // =========================================================
    // MÉTODO COMÚN
    // =========================================================

    private function generar(string $id, string $seccion)
    {
        try {
            $resultado = $this->service->generar($id, $seccion);
            return response()->json($resultado);
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Expediente no encontrado' => 404,
                'Expediente incompleto'    => 500,
                default                    => 422,
            };
            return response()->json(['error' => $e->getMessage()], $status);
        } catch (\Throwable $e) {
            Log::error('[EntregableController] Error', [
                'id'      => $id,
                'seccion' => $seccion,
                'error'   => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Error interno'], 500);
        }
    }
}