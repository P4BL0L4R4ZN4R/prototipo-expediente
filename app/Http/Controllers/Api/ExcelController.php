<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExcelParser\Parser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Expediente;

class ExcelController extends Controller
{
    public function __construct(
        private Parser $parser,
    ) {}


    public function index()
    {
        $t0 = microtime(true);
        return response()->json(
            Expediente::query()
                ->orderByDesc('created_at')
                ->get(['id'])
        );
    }


    public function ia(string $id)
    {
        $expediente = Expediente::find($id);

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        return response()->json($expediente->ia);
    }

    public function data(string $id)
    {

        $expediente = Expediente::find($id);
        $t0 = microtime(true);

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        Log::info('Tiempo total endpoint', [
            'id' => $id,
            'ms' => (microtime(true) - $t0) * 1000,
        ]);

        return response()->json($expediente->data);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $pathExcel = $request->file('excel')->store('excels');

            $parsed = $this->parser->parse(
                Storage::path($pathExcel)
            );

            $id = 'exp_' . now()->format('Ymd_His');

            // 🆕 Solo BD, sin disco
            Expediente::updateOrCreate(
                ['id' => $id],
                [
                    'ia'   => $parsed['ia'],
                    'data' => $parsed['data'],
                ]
            );

            // Opcional: borrar el Excel temporal si ya no lo necesitas
            // Storage::delete($pathExcel);

            return response()->json(['id' => $id], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Error al procesar',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }


    public function show(string $id)
    {
        $expediente = Expediente::find($id);
        $t0 = microtime(true);

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        Log::info('Tiempo total endpoint', [
            'id' => $id,
            'ms' => (microtime(true) - $t0) * 1000,
        ]);

        return response()->json([
            'id'            => $expediente->id,
            'ia'            => $expediente->ia,
            'data'          => $expediente->data,
            'meta'          => $expediente->meta,
            'propuesta_ia'  => $expediente->propuesta_ia,
            'entregable'    => $expediente->entregable,
        ]);
    }

    public function download(string $id)
    {
        $expediente = Expediente::find($id);

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $zipPath = storage_path("app/expedientes/{$id}.zip");
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $zip->addFromString(
                'ia.json',
                json_encode($expediente->ia, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            $zip->addFromString(
                'data.json',
                json_encode($expediente->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }


    public function destroy(string $id)
    {
        $expediente = Expediente::find($id);

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $expediente->delete();

        return response()->json(['ok' => true]);
    }

    // private function shape(array $data): array
    // {
    //     return [

    //         // =========================================================
    //         // JSON 1 - DATOS GENERALES
    //         // =========================================================

    //         'empresa_factura' => $data['empresa_factura'] ?? null,
    //         'cliente'         => $data['cliente'] ?? null,
    //         'servicio'        => $data['servicio'] ?? null,
    //         'objetivo'        => $data['objetivo'] ?? null,

    //         'conceptos' => array_map(function ($concepto) {
    //             return [
    //                 'orden'    => $concepto['orden'] ?? null,
    //                 'concepto' => $concepto['concepto'] ?? null,
    //                 'proceso'  => $concepto['proceso'] ?? null,

    //                 // Disponible si posteriormente se necesita:
    //                 // 'clave'       => $concepto['clave'] ?? null,
    //                 // 'descripcion' => $concepto['descripcion'] ?? null,
    //                 // 'costo_p_mes' => $concepto['costo_p_mes'] ?? null,
    //                 // 'total'       => $concepto['total'] ?? null,

    //                 // NO persistir por ahora:
    //                 // 'facturas' => ...
    //             ];
    //         }, $data['conceptos'] ?? []),


            

    //         // =========================================================
    //         // JSON 2 - DATOS ADMINISTRATIVOS / FINANCIEROS
    //         // =========================================================

    //         'fecha_inicio'  => $data['fecha_inicio'] ?? null,
    //         'fecha_termino' => $data['fecha_termino'] ?? null,

    //         // Disponible si posteriormente se necesita:
    //         // 'costo_p_mes' => $data['costo_p_mes'] ?? null,


    //         // =========================================================
    //         // FACTURAS
    //         // =========================================================
    //         // Por ahora NO se almacenan.
    //         // Se dejan documentadas aquí para poder activarlas después.
    //         //
    //         // 'facturas' => [
    //         //     [
    //         //         'concepto_orden' => ...,
    //         //         'total_factura'  => ...,
    //         //         'no_factura'     => ...,
    //         //         'folio_fiscal'   => ...,
    //         //         'fecha'          => ...,
    //         //         'observaciones'  => ...,
    //         //     ]
    //         // ],


    //         // =========================================================
    //         // CÁLCULOS
    //         // =========================================================

    //         'calculos' => [
    //             'total_programa' => $data['calculos']['total_programa'] ?? null,
    //             'fecha_legal'    => $data['calculos']['fecha_legal'] ?? null,

    //             // Disponible posteriormente:
    //             // 'otro_calculo' => $data['calculos']['otro_calculo'] ?? null,
    //         ],
    //     ];
    // }

    //Proximo a eliminar
    public function migrarExistentes()
    {
        $dirs = Storage::directories('expedientes');
        $count = 0;

        foreach ($dirs as $dir) {
            $id = basename($dir);

            $leer = fn($path) => Storage::exists($path)
                ? json_decode(Storage::get($path), true)
                : null;

            $ia   = $leer("{$dir}/ia.json");
            $data = $leer("{$dir}/data.json");

            if (!$ia && !$data) continue;

            Expediente::updateOrCreate(
                ['id' => $id],
                ['ia' => $ia, 'data' => $data]
            );

            $count++;
        }

        return response()->json(['migrados' => $count]);
    }


    public function update(Request $request, string $id)
    {
        $exp = Expediente::find($id);
        if (!$exp) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        if ($request->has('meta')) {
            $exp->meta = array_merge($exp->meta ?? [], $request->input('meta', []));
        }

        if ($request->has('propuesta_ia')) {
            $propuesta = $exp->propuesta_ia ?? [];
            foreach ($request->input('propuesta_ia', []) as $seccion => $val) {
                $propuesta[$seccion] = array_merge($propuesta[$seccion] ?? [], $val);
            }
            $exp->propuesta_ia = $propuesta;
        }

        if ($request->has('entregable')) {
            $entregable = $exp->entregable ?? [];
            foreach ($request->input('entregable', []) as $seccion => $val) {
                $entregable[$seccion] = array_merge($entregable[$seccion] ?? [], $val);
            }
            $exp->entregable = $entregable;
        }

        $exp->save();

        return response()->json($exp);
    }
        

}