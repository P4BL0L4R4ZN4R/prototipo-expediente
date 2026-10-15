<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExcelParser\Parser;
use App\Services\ExpedienteTextoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Expediente;

class ExcelController extends Controller
{
    public function __construct(
        private Parser $parser,
        private ExpedienteTextoStorage $textoStorage,
    ) {}

    public function index()
    {
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

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

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

            Expediente::updateOrCreate(
                ['id' => $id],
                [
                    'ia'   => $parsed['ia'],
                    'data' => $parsed['data'],
                ]
            );

            // Opcional: borrar el Excel temporal
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

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        // Hidratar propuesta_ia y entregable: leer los .txt
        $propuesta  = $this->textoStorage->obtenerPropuestaCompleta($id);
        $entregable = $this->textoStorage->obtenerEntregableCompleto($id);

        return response()->json([
            'id'            => $expediente->id,
            'ia'            => $expediente->ia,
            'data'          => $expediente->data,
            'meta'          => $expediente->meta,
            'propuesta_ia'  => $propuesta,
            'entregable'    => $entregable,
            'fecha_propuesta'  => $expediente->fecha_propuesta?->format('Y-m-d'),
            'fecha_entregable' => $expediente->fecha_entregable?->format('Y-m-d'),
        ]);
    }

    public function download(string $id)
    {
        $expediente = Expediente::find($id);

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        // Asegurar directorio
        $dir = storage_path("app/expedientes");
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $zipPath = "{$dir}/{$id}.zip";
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

            // Agregar propuesta y entregable hidratados
            $propuesta  = $this->textoStorage->obtenerPropuestaCompleta($id);
            $entregable = $this->textoStorage->obtenerEntregableCompleto($id);

            $zip->addFromString(
                'propuesta_ia.json',
                json_encode($propuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            $zip->addFromString(
                'entregable.json',
                json_encode($entregable, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
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

        // Borrar carpeta de textos del filesystem
        if (Storage::disk('expedientes')->exists($id)) {
            Storage::disk('expedientes')->deleteDirectory($id);
        }

        // Borrar el registro
        $expediente->delete();

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, string $id)
    {
        $exp = Expediente::find($id);
        if (!$exp) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        // ── META
        if ($request->has('meta')) {
            $meta = array_merge($exp->meta ?? [], $request->input('meta', []));
            unset($meta['estado']);  // Campo muerto
            $exp->meta = $meta;
        }

        // ── PROPUESTA_IA
        if ($request->has('propuesta_ia')) {
            foreach ($request->input('propuesta_ia', []) as $seccion => $val) {
                $texto = $val['texto'] ?? null;
                if ($texto !== null) {
                    $this->textoStorage->guardarSeccion($id, 'propuesta', $seccion, $texto);
                }
            }
        }

        // ── ENTREGABLE
        if ($request->has('entregable')) {
            foreach ($request->input('entregable', []) as $seccion => $val) {
                $texto = $val['texto'] ?? null;
                if ($texto !== null) {
                    $this->textoStorage->guardarSeccion($id, 'entregable', $seccion, $texto);
                }
            }
        }

        // ── FECHAS
        if ($request->has('fecha_propuesta')) {
            $exp->fecha_propuesta = $request->input('fecha_propuesta');
        }
        if ($request->has('fecha_entregable')) {
            $exp->fecha_entregable = $request->input('fecha_entregable');
        }

        $exp->save();

        // Devolver el expediente hidratado
        return response()->json([
            'id'            => $exp->id,
            'ia'            => $exp->ia,
            'data'          => $exp->data,
            'meta'          => $exp->meta,
            'propuesta_ia'  => $this->textoStorage->obtenerPropuestaCompleta($id),
            'entregable'    => $this->textoStorage->obtenerEntregableCompleto($id),
            'fecha_propuesta'  => $exp->fecha_propuesta?->format('Y-m-d'),
            'fecha_entregable' => $exp->fecha_entregable?->format('Y-m-d'),
        ]);
    }
}