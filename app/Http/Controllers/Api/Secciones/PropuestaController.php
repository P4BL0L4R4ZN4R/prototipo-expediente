<?php

namespace App\Http\Controllers\Api\Secciones;

use App\Http\Controllers\Controller;
use App\Services\Secciones\Propuesta\BatchPropuesta;
use App\Services\Secciones\Orquestador;

class PropuestaController extends Controller
{
    public function __construct(
        private BatchPropuesta $batch,
    ) {}

    public function textoPropuesta(string $id)       { return $this->generar($id, 'texto_propuesta'); }
    public function introduccion(string $id)         { return $this->generar($id, 'introduccion'); }
    public function problematica(string $id)         { return $this->generar($id, 'problematica'); }
    public function objetivoGeneral(string $id)      { return $this->generar($id, 'objetivo_general'); }
    public function objetivosEspecificos(string $id) { return $this->generar($id, 'objetivos_especificos'); }
    public function metodologia(string $id)          { return $this->generar($id, 'metodologia'); }

    public function generarTodo(string $id)
    {
        try {
            return response()->json($this->batch->generarTodo($id));
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    private function generar(string $id, string $seccion)
    {
        try {
            return response()->json(Orquestador::generar('propuesta', $id, $seccion));
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