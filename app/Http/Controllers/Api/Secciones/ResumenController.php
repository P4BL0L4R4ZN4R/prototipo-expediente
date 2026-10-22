<?php

namespace App\Http\Controllers\Api\Secciones;

use App\Http\Controllers\Controller;
use App\Services\Secciones\ResumenService;

class ResumenController extends Controller
{
    public function __construct(
        private ResumenService $resumen,
    ) {}

    public function show(string $id)
    {
        try {
            return response()->json($this->resumen->generar($id));
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Expediente no encontrado' => 404,
                'Expediente incompleto'    => 422,
                default                    => 422,
            };
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }
}