<?php

namespace App\Jobs;

use App\Http\Controllers\Word\AcuseWordController;
use App\Http\Controllers\Word\CalendarioWordController;
use App\Http\Controllers\Word\CotizacionesWordController;
use App\Http\Controllers\Word\EntregableWordController;
use App\Http\Controllers\Word\PropuestaWordController;
use App\Http\Controllers\Word\ResumenWordController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerarZipExpediente implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(
        public string $expedienteId,
        public string $jobId,
    ) {}

    public function handle(): void
    {
        Cache::put("zip:{$this->jobId}", ['status' => 'processing'], now()->addHour());

        $documentos = [
            '1.Propuesta.docx'             => fn() => app(PropuestaWordController::class)->export($this->expedienteId),
            '2.Cotizacion Inicial.docx'    => fn() => app(CotizacionesWordController::class)->exportInicial($this->expedienteId),
            '3.Cotizacion Final.docx'      => fn() => app(CotizacionesWordController::class)->exportFinal($this->expedienteId),
            '4.Calendario de trabajo.docx' => fn() => app(CalendarioWordController::class)->export($this->expedienteId),
            '5.Entregable.docx'            => fn() => app(EntregableWordController::class)->export($this->expedienteId),
            '6.Resumen ejecutivo.docx'     => fn() => app(ResumenWordController::class)->export($this->expedienteId),
            '7.Acuse.docx'                 => fn() => app(AcuseWordController::class)->export($this->expedienteId),
        ];

        $zipPath = Storage::disk('local')->path("tmp/expediente_{$this->expedienteId}_{$this->jobId}.zip");
        @mkdir(dirname($zipPath), 0777, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Cache::put("zip:{$this->jobId}", [
                'status' => 'error',
                'error'  => 'No se pudo crear el ZIP',
            ], now()->addHour());
            return;
        }

        $ok = 0;
        $fallidos = [];

        foreach ($documentos as $nombre => $fn) {
            try {
                $bytes = $this->bytesDe($fn());
                if ($bytes === null || $bytes === '') {
                    $fallidos[] = $nombre;
                    continue;
                }
                $zip->addFromString($nombre, $bytes);
                $ok++;
            } catch (\Throwable $e) {
                $fallidos[] = $nombre;
                Log::error('ZIP job · excepción', [
                    'expediente' => $this->expedienteId,
                    'archivo'    => $nombre,
                    'msg'        => $e->getMessage(),
                ]);
            }
        }

        $zip->close();

        Cache::put("zip:{$this->jobId}", [
            'status'   => 'done',
            'path'     => $zipPath,
            'ok'       => $ok,
            'fallidos' => $fallidos,
        ], now()->addHour());
    }

    public function failed(\Throwable $e): void
    {
        Cache::put("zip:{$this->jobId}", [
            'status' => 'error',
            'error'  => $e->getMessage(),
        ], now()->addHour());
    }

    private function bytesDe($response): ?string
    {
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $path = $response->getFile()->getPathname();
            if (!file_exists($path)) return null;
            $bytes = file_get_contents($path);
            @unlink($path);
            return $bytes;
        }
        if ($response instanceof \Illuminate\Http\Response) {
            return $response->getContent();
        }
        return null;
    }
}