<?php

namespace App\Http\Controllers\Api\IA;

use App\Http\Controllers\Controller;
use App\Services\IA\IaGenerationService;
use Illuminate\Http\Request;

class IAGenerationController extends Controller
{
    public function __construct(
        private IaGenerationService $iacontext,
    ) {}

    public function generar(string $prompt): array
    {
        return $this->iacontext->generar($prompt);
    }

    public function test(Request $request)
    {
        $request->validate([
            'input' => 'required|string',
        ]);

        $resultado = $this->iacontext->generar($request->input('input'));

        if (!$resultado['ok']) {
            return response()->json(['error' => $resultado['error']], 502);
        }

        return response()->json([
            'texto'          => $resultado['texto'],
            'proveedor'      => $resultado['proveedor'],
            'modelo'         => $resultado['modelo'],
            'usado_fallback' => $resultado['usado_fallback'],
            'tiempo_ms'      => $resultado['tiempo_ms'],
        ]);
    }
}