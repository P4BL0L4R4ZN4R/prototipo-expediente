<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Services\Storage\ExpedienteTextoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpedienteController extends Controller
{
    public function __construct(
        private ExpedienteTextoStorage $textoStorage,
        private ExcelImportController $excelImport,
        private ZipController $zip,
    ) {}

    // =========================================================
    // CRUD del expediente
    // =========================================================

    public function index()
    {
        return response()->json(
            Expediente::query()
                ->orderByDesc('created_at')
                ->get(['id'])
        );
    }

    public function show(string $id)
    {
        $expediente = Expediente::find($id);

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

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
            'estado_expediente'  => $expediente->estado_expediente,
            'direccion'          => $expediente->direccion,
        ]);
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

    public function update(Request $request, string $id)
    {
        $exp = Expediente::find($id);
        if (!$exp) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        // ── META
        if ($request->has('meta')) {
            $meta = array_merge($exp->meta ?? [], $request->input('meta', []));
            $exp->meta = $meta;
        }

        // ── DIRECCIÓN
        if ($request->has('direccion')) {
            $request->validate([
                'direccion' => 'nullable|string|max:500',
            ]);
            $exp->direccion = $request->input('direccion');
        }

        // ── ESTADO
        if ($request->has('estado_expediente')) {
            $request->validate([
                'estado_expediente' => 'nullable|string|max:50',
            ]);
            $exp->estado_expediente = $request->input('estado_expediente');
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

        return response()->json([
            'id'            => $exp->id,
            'ia'            => $exp->ia,
            'data'          => $exp->data,
            'meta'          => $exp->meta,
            'propuesta_ia'  => $this->textoStorage->obtenerPropuestaCompleta($id),
            'entregable'    => $this->textoStorage->obtenerEntregableCompleto($id),
            'fecha_propuesta'  => $exp->fecha_propuesta?->format('Y-m-d'),
            'fecha_entregable' => $exp->fecha_entregable?->format('Y-m-d'),
            'estado_expediente'  => $exp->estado_expediente,
            'direccion'          => $exp->direccion,
        ]);
    }

    public function destroy(string $id)
    {
        $expediente = Expediente::find($id);

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        if (Storage::disk('expedientes')->exists($id)) {
            Storage::disk('expedientes')->deleteDirectory($id);
        }

        $expediente->delete();

        return response()->json(['ok' => true]);
    }

    // =========================================================
    // Delegados a otros dominios
    // =========================================================

    public function upload(Request $request)
    {
        return $this->excelImport->upload($request);
    }

    public function download(string $id)
    {
        return $this->zip->downloadJson($id);
    }
}