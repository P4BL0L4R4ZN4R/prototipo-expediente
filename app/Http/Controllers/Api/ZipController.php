<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Word\CotizacionesWordController;
use App\Http\Controllers\Word\CalendarioWordController;
use App\Http\Controllers\Word\ResumenWordController;
use App\Http\Controllers\Word\AcuseWordController;
use App\Http\Controllers\Word\PropuestaWordController;
use App\Http\Controllers\Word\EntregableWordController;
use App\Models\Expediente;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ZipController extends Controller
{
    public function descargar(string $id)
    {
        $inicio = microtime(true);

        Log::info('ZIP · inicio', ['id' => $id]);

        $exp = Expediente::find($id);
        if (!$exp) {
            Log::warning('ZIP · expediente no encontrado', ['id' => $id]);
            return response()->json(['error' => 'No encontrado'], 404);
        }

        @set_time_limit(600);

        // 👇 Definición de los 7 documentos con su closure generador
        $documentos = [
            '1.Propuesta.docx' => [
                'label' => 'Propuesta',
                'fn'    => fn() => app(PropuestaWordController::class)->export($id),
            ],
            '2.Entregable.docx' => [
                'label' => 'Entregable',
                'fn'    => fn() => app(EntregableWordController::class)->export($id),
            ],
            '3.Cotizacion-Inicial.docx' => [
                'label' => 'Cotización Inicial',
                'fn'    => fn() => app(CotizacionesWordController::class)->exportInicial($id),
            ],
            '4.Cotizacion-Final.docx' => [
                'label' => 'Cotización Final',
                'fn'    => fn() => app(CotizacionesWordController::class)->exportFinal($id),
            ],
            '5.Calendario.docx' => [
                'label' => 'Calendario',
                'fn'    => fn() => app(CalendarioWordController::class)->export($id),
            ],
            '6.Resumen.docx' => [
                'label' => 'Resumen',
                'fn'    => fn() => app(ResumenWordController::class)->export($id),
            ],
            '7.Acuse.docx' => [
                'label' => 'Acuse',
                'fn'    => fn() => app(AcuseWordController::class)->export($id),
            ],
        ];

        $zipPath = storage_path("app/tmp/expediente_{$id}.zip");

        if (!is_dir(dirname($zipPath))) {
            Log::info('ZIP · creando carpeta tmp', ['path' => dirname($zipPath)]);
            mkdir(dirname($zipPath), 0777, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::error('ZIP · no se pudo abrir el archivo ZIP', ['path' => $zipPath]);
            return response()->json(['error' => 'No se pudo crear el ZIP'], 500);
        }

        Log::info('ZIP · generando documentos', [
            'total' => count($documentos),
        ]);

        $ok = 0;
        $fallidos = [];

        foreach ($documentos as $nombre => $doc) {
            $t0 = microtime(true);

            Log::info("ZIP · generando [{$doc['label']}]", [
                'archivo' => $nombre,
            ]);

            try {
                $bytes = $this->bytesDe($doc['fn']);

                $t = round(microtime(true) - $t0, 2);

                if ($bytes === null || $bytes === '') {
                    $fallidos[] = $nombre;
                    Log::warning("ZIP · [{$doc['label']}] sin contenido", [
                        'archivo'  => $nombre,
                        'tiempo_s' => $t,
                    ]);
                    continue;
                }

                $zip->addFromString($nombre, $bytes);
                $ok++;

                Log::info("ZIP · [{$doc['label']}] OK", [
                    'archivo'  => $nombre,
                    'bytes'    => strlen($bytes),
                    'tiempo_s' => $t,
                ]);

            } catch (\Throwable $e) {
                $fallidos[] = $nombre;
                $t = round(microtime(true) - $t0, 2);

                Log::error("ZIP · [{$doc['label']}] excepción", [
                    'archivo'  => $nombre,
                    'tiempo_s' => $t,
                    'msg'      => $e->getMessage(),
                    'file'     => $e->getFile() . ':' . $e->getLine(),
                ]);
            }
        }

        $zip->close();

        $total = round(microtime(true) - $inicio, 2);

        Log::info('ZIP · completado', [
            'id'        => $id,
            'ok'        => $ok,
            'fallidos'  => $fallidos,
            'total_s'   => $total,
            'zip_path'  => $zipPath,
            'zip_bytes' => file_exists($zipPath) ? filesize($zipPath) : 0,
        ]);

        return response()->download($zipPath, "expediente_{$id}.zip")
            ->deleteFileAfterSend(true);
    }

    /**
     * Ejecuta el closure que devuelve un BinaryFileResponse
     * y extrae los bytes del archivo generado.
     */
    private function bytesDe(callable $fn): ?string
    {
        $response = $fn();

        // Log del tipo de respuesta para saber qué nos devolvió
        Log::info('ZIP · tipo de respuesta', [
            'clase' => is_object($response) ? get_class($response) : gettype($response),
        ]);

        // Caso 1: BinaryFileResponse (response()->download(...))
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $path = $response->getFile()->getPathname();

            Log::info('ZIP · BinaryFileResponse', [
                'path'      => $path,
                'existe'    => file_exists($path),
                'size'      => file_exists($path) ? filesize($path) : 0,
            ]);

            if (!file_exists($path)) {
                return null;
            }

            $bytes = file_get_contents($path);
            @unlink($path);
            return $bytes;
        }

        // Caso 2: StreamedResponse (response()->streamDownload(...))
        if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            Log::info('ZIP · StreamedResponse');
            ob_start();
            $response->sendContent();
            $out = ob_get_clean();
            return $out === false ? null : $out;
        }

        // Caso 3: Response normal (texto/binario en content)
        if ($response instanceof \Illuminate\Http\Response) {
            Log::info('ZIP · Response normal');
            return $response->getContent();
        }

        // Caso 4: JsonResponse (error)
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            Log::warning('ZIP · JsonResponse (posible error)', [
                'data' => $response->getData(true),
            ]);
            return null;
        }

        Log::warning('ZIP · tipo de respuesta no manejado', [
            'clase' => is_object($response) ? get_class($response) : gettype($response),
        ]);

        return null;
    }
}