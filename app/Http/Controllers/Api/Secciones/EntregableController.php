<?php

namespace App\Http\Controllers\Api\Secciones;

use App\Http\Controllers\Controller;
use App\Services\Secciones\Orquestador;
// use App\Services\Secciones\Cotizacion\Generador;
// use Illuminate\Support\Facades\Log;

class EntregableController extends Controller
{
    public function introduccion(string $id)         { return $this->generar($id, 'introduccion'); }
    public function problematica(string $id)         { return $this->generar($id, 'problematica'); }
    public function desarrolloConceptos(string $id)  { return $this->generar($id, 'desarrollo_conceptos'); }
    public function finalizacionServicio(string $id) { return $this->generar($id, 'finalizacion_servicio'); }
    public function retrospectiva(string $id)        { return $this->generar($id, 'retrospectiva'); }

    private function generar(string $id, string $seccion)
    {
        try {
            return response()->json(Orquestador::generar('entregable', $id, $seccion));
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Expediente no encontrado' => 404,
                'Expediente incompleto'    => 500,
                default                    => 422,
            };
            return response()->json(['error' => $e->getMessage()], $status);
        } 
    }
}