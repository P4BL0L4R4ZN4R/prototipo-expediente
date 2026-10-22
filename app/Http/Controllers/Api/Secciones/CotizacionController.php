<?php

namespace App\Http\Controllers\Api\Secciones;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Services\Secciones\Cotizacion\Generador;
// use Illuminate\Support\Facades\Log;

class CotizacionController extends Controller
{
    public function __construct(
        private Generador $cotizacion,
    ) {}

    public function conceptos(string $id)
    {
        $exp = Expediente::find($id);

        if (!$exp) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        return response()->json(
            $this->cotizacion->conceptos($exp->data ?? [], $exp->ia ?? [])
        );
    }
}