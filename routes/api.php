<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\{
    ExpedienteController,
    Secciones\CotizacionController,
    Secciones\CalendarioController,
    Secciones\PropuestaController,
    Secciones\EntregableController,
    ZipController,
};
use App\Http\Controllers\Word\{
    CotizacionesWordController,
    CalendarioWordController,
    ResumenWordController,
    AcuseWordController,
    EntregableWordController,
    PropuestaWordController,
};

Route::prefix('{plaza}/prototipo/excel')
    ->middleware(['tenant.auth'])
    ->group(function () {
        // ── Rutas específicas primero (sin {id} numérico) ─────────
        Route::get('/',                 [ExpedienteController::class, 'index']);
        Route::post('/upload',          [ExpedienteController::class, 'upload']);


        Route::get('/word/cotizacion/final/{id}',   [CotizacionesWordController::class, 'exportFinal']);
        Route::get('/word/cotizacion/inicial/{id}', [CotizacionesWordController::class, 'exportInicial']);
        Route::get('/word/calendario/{id}',         [CalendarioWordController::class, 'export']);
        Route::get('/word/resumen/{id}',            [ResumenWordController::class, 'export']);
        Route::get('/word/acuse/{id}',              [AcuseWordController::class, 'export']);

        Route::get('/calendario/{id}', [CalendarioController::class, 'show']);

        Route::prefix('zip')->group(function () {
            Route::post('/{id}',            [ZipController::class, 'solicitar'])->whereNumber('id');
            Route::get('/{jobId}/status',   [ZipController::class, 'estado']);
            Route::get('/{jobId}/download', [ZipController::class, 'descargar']);
        });

        Route::prefix('propuesta/{id}')->group(function () {
            Route::post('texto-propuesta',       [PropuestaController::class, 'textoPropuesta'])->middleware('throttle:20,1');
            Route::post('introduccion',          [PropuestaController::class, 'introduccion'])->middleware('throttle:20,1');
            Route::post('problematica',          [PropuestaController::class, 'problematica'])->middleware('throttle:20,1');
            Route::post('objetivo-general',      [PropuestaController::class, 'objetivoGeneral'])->middleware('throttle:20,1');
            Route::post('objetivos-especificos', [PropuestaController::class, 'objetivosEspecificos'])->middleware('throttle:20,1');
            Route::post('metodologia',           [PropuestaController::class, 'metodologia'])->middleware('throttle:20,1');
            Route::post('generar-todo',          [PropuestaController::class, 'generarTodo'])->middleware('throttle:5,1');
            Route::get('word',                   [PropuestaWordController::class, 'export']);
        });

        Route::prefix('entregable/{id}')->group(function () {
            Route::post('introduccion',          [EntregableController::class, 'introduccion'])->middleware('throttle:20,1');
            Route::post('problematica',          [EntregableController::class, 'problematica'])->middleware('throttle:20,1');
            Route::post('desarrollo-conceptos',  [EntregableController::class, 'desarrolloConceptos'])->middleware('throttle:20,1');
            Route::post('finalizacion-servicio', [EntregableController::class, 'finalizacionServicio'])->middleware('throttle:20,1');
            Route::post('retrospectiva',         [EntregableController::class, 'retrospectiva'])->middleware('throttle:20,1');
            Route::get('word',                   [EntregableWordController::class, 'export']);
        });

        // ── Rutas con {id} numérico AL FINAL ──────────────────────
        Route::get('/{id}',           [ExpedienteController::class, 'show']);
        Route::get('/{id}/ia',        [ExpedienteController::class, 'ia']);
        Route::get('/{id}/data',      [ExpedienteController::class, 'data']);
        Route::get('/{id}/conceptos', [CotizacionController::class, 'conceptos']);
        Route::get('/{id}/download',  [ExpedienteController::class, 'download']);
        Route::delete('/{id}',        [ExpedienteController::class, 'destroy']);
        Route::patch('/{id}',         [ExpedienteController::class, 'update']);
    });


    //Tokens
    // Merida  18|070ba6c4f8b918ef979cbb6e5efb4be807932d06
    // CDMX    16|2fb5dc4efefcbde1c5d46a116e2777ba8bd45431


    // TEMPORAL: solo en local. La condición de entorno lo protege en prod.
    // TODO (I-13): reemplazar por comando `php artisan service-token:create {plaza}`.
    if (app()->environment('local')) {
        Route::post('/_dev/token', function (\Illuminate\Http\Request $request) {
            $plaza = $request->input('plaza', 'test00');

            // Validar que la plaza exista antes de configurar la conexión
            $config = config("database.connections.{$plaza}");
            abort_if($config === null, 404, "Plaza '{$plaza}' no configurada");

            config(['database.connections.tenant' => $config, 'database.default' => 'tenant']);
            \DB::purge('tenant');

            $plain = bin2hex(random_bytes(20));
            $hash  = hash('sha256', $plain);

            $id = \DB::table('personal_access_tokens')->insertGetId([
                'tokenable_type' => 'App\\Models\\User',
                'tokenable_id'   => 1,
                'name'           => 'dev-' . now()->timestamp . '-' . random_int(1000, 9999),
                'token'          => $hash,
                'abilities'      => json_encode(['*']),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return response()->json([
                'token' => $id . '|' . $plain,
                'plaza' => $plaza,
            ]);
        })->middleware('throttle:10,1');
    }