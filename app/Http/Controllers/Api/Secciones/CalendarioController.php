<?php

namespace App\Http\Controllers\Api\Secciones;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Services\Secciones\Calendario\Generador;
// use Illuminate\Support\Facades\Log;

class CalendarioController extends Controller
{
    public function __construct(
        private Generador $calendario,
    ) {}

    public function show(string $id)
    {
        $exp = Expediente::find($id);

        if (!$exp) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        return response()->json([
            'id'    => $id,
            'filas' => $this->calendario->filas($exp->data ?? [], $exp->ia ?? []),
        ]);
    }
}