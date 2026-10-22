<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerarZipExpediente;
use App\Models\Expediente;
use App\Services\Storage\ExpedienteTextoStorage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ZipController extends Controller
{
    public function __construct(
        private ExpedienteTextoStorage $textoStorage,
    ) {}

    // =========================================================
    // ZIP con los 7 documentos Word (async)
    // =========================================================

    public function solicitar(string $id)
    {
        if (!Expediente::whereKey($id)->exists()) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $jobId = (string) Str::uuid();
        Cache::put("zip:{$jobId}", ['status' => 'pending'], now()->addHour());

        GenerarZipExpediente::dispatch($id, $jobId);

        return response()->json([
            'job_id' => $jobId,
            'status' => Cache::get("zip:{$jobId}")['status'] ?? 'pending',
        ], 202);
    }

    public function estado(string $jobId)
    {
        $estado = Cache::get("zip:{$jobId}");

        if (!$estado) {
            return response()->json(['error' => 'Job no encontrado'], 404);
        }

        unset($estado['path']);
        return response()->json($estado);
    }

    public function descargar(string $jobId)
    {
        $estado = Cache::get("zip:{$jobId}");

        if (!$estado || ($estado['status'] ?? null) !== 'done') {
            return response()->json(['error' => 'ZIP no disponible'], 404);
        }

        $path = $estado['path'] ?? null;
        if (!$path || !file_exists($path)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }

        return response()->download($path, "expediente_{$estado['ok']}.zip");
    }

    // =========================================================
    // ZIP con los JSONs del expediente (sync) — movido de Excel-Controller
    // =========================================================

    public function downloadJson(string $id)
    {
        $expediente = Expediente::find($id);

        if (!$expediente) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        // Asegurar directorio
        $dir = storage_path('app/expedientes');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zipPath = "{$dir}/{$id}.zip";
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['error' => 'No se pudo crear el ZIP'], 500);
        }

        $zip->addFromString(
            'ia.json',
            json_encode($expediente->ia, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $zip->addFromString(
            'data.json',
            json_encode($expediente->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

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

        if (!file_exists($zipPath)) {
            return response()->json(['error' => 'El ZIP no se generó correctamente'], 500);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}