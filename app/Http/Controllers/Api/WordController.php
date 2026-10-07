<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;
use App\Models\Expediente;

abstract class WordController extends Controller
{
    /** Ruta de la plantilla dentro de storage/app */
    abstract protected function plantilla(): string;

    /** Nombre del archivo de salida (sin .docx) */
    abstract protected function nombreArchivo(string $id): string;

    /** Llena la plantilla con los datos */
    abstract protected function construir(
        TemplateProcessor $template,
        array $data,
        array $ia
    ): void;

    public function export(string $id)
    {
        $this->expedienteId = $id;

        // 1. Cargar expediente desde BD
        $exp = Expediente::find($id);

        if (!$exp) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $data = $exp->data ?? [];
        $ia   = $exp->ia   ?? [];

        if (empty($data) || empty($ia)) {
            return response()->json(['error' => 'Expediente incompleto'], 500);
        }

        Log::info('WORD · datos recibidos', [
            'id'        => $id,
            'clase'     => static::class,
            'plantilla' => $this->plantilla(),
            'ia'        => $ia,
            'data'      => $data,
        ]);

        // 2. Resolver plantilla
        $templatePath = storage_path('app/' . $this->plantilla());

        if (!file_exists($templatePath)) {
            return response()->json([
                'error' => 'Plantilla no encontrada',
                'path'  => $templatePath,
            ], 404);
        }

        // 3. Abrir plantilla y delegar el llenado al hijo
        $template = new TemplateProcessor($templatePath);
        $this->construir($template, $data, $ia);

        // 4. Guardar y descargar
        $filename = $this->nombreArchivo($id) . '.docx';
        $tempPath = storage_path("app/temp/{$filename}");

        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        $template->saveAs($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}