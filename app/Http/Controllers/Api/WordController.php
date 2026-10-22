<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordFieldDispatcher;
use App\Services\Word\WordFieldResolver;
use PhpOffice\PhpWord\TemplateProcessor;

abstract class WordController extends Controller
{
    protected string $expedienteId;

    public function __construct(
        protected WordFieldResolver   $resolver,
        protected WordFieldDispatcher $dispatcher,
    ) {}

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

    /**
     * El hijo declara QUÉ campos usa.
     * Por defecto, ninguno. Los hijos que quieran campos
     * declarativos sobreescriben este método.
     */
    protected function campos(): array
    {
        return [];
    }

    /**
     * Inyecta todos los campos declarados en campos().
     * Resuelve cada uno con WordFieldResolver y lo inyecta
     * con WordFieldDispatcher.
     */
    protected function inyectarCampos(
        TemplateProcessor $template,
        ExpedienteContext $ctx
    ): void {
        foreach ($this->campos() as $placeholder => $config) {
            $texto = $this->resolver->resolver($ctx, $config);

            $this->dispatcher->inyectar(
                $template,
                $placeholder,
                $texto,
                $config['metodo'] ?? 'bloque'
            );
        }
    }

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