<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExcelParser\Parser;
use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExcelImportController extends Controller
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function upload(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $pathExcel = null;

        try {
            $pathExcel = $request->file('excel')->store('excels');

            $parsed = $this->parser->parse(Storage::path($pathExcel));

            $expediente = Expediente::create([
                'ia'                => $parsed['ia'],
                'data'              => $parsed['data'],
                'estado_expediente' => 'borrador',
                'direccion'         => null,
            ]);

            return response()->json(['id' => $expediente->getKey()], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Error al procesar',
                'detalle' => $e->getMessage(),
            ], 500);
        } finally {
            if ($pathExcel && Storage::exists($pathExcel)) {
                Storage::delete($pathExcel);
            }
        }
    }
}