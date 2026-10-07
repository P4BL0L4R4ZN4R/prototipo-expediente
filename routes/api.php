<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



use App\Http\Controllers\GeminiController;
use App\Http\Controllers\Api\{
    WordController,
    ExcelController,
    CotizacionController,
    CalendarioController,
    PropuestaController,
    EntregableController,
    ZipController,

    // pruebas
    TestPropuestaController,
};
use App\Http\Controllers\Word\{
    CotizacionesWordController,
    CalendarioWordController,
    ResumenWordController,
    AcuseWordController,
    EntregableWordController,
    PropuestaWordController,
};



Route::prefix('prototipo/excel')->group(function () {

    // =========================================================
    // EXPEDIENTES (Excel)
    // =========================================================
    Route::get('/',                    [ExcelController::class, 'index']);
    Route::post('/upload',             [ExcelController::class, 'upload']);
    Route::get('/migrar-existente',    [ExcelController::class, 'migrarExistentes']);
    Route::get('/{id}',                [ExcelController::class, 'show']);
    Route::get('/{id}/ia',             [ExcelController::class, 'ia']);
    Route::get('/{id}/data',           [ExcelController::class, 'data']);
    Route::get('/{id}/conceptos',      [CotizacionController::class, 'conceptos']);
    Route::get('/{id}/download',       [ExcelController::class, 'download']);
    Route::delete('/{id}',             [ExcelController::class, 'destroy']);
    Route::patch('/{id}', [ExcelController::class, 'update']);

    // =========================================================
    // WORD: documentos administrativos
    // =========================================================
    Route::get('/word/cotizacion/final/{id}', [CotizacionesWordController::class, 'exportFinal']);
    Route::get('/word/cotizacion/inicial/{id}', [CotizacionesWordController::class, 'exportInicial']);
    Route::get('/word/calendario/{id}', [CalendarioWordController::class, 'export']);
    Route::get('/word/resumen/{id}',    [ResumenWordController::class, 'export']);
    Route::get('/word/acuse/{id}',      [AcuseWordController::class, 'export']);

    // =========================================================
    // PROPUESTA: generación IA + Word
    // =========================================================
    Route::prefix('propuesta/{id}')->group(function () {
        Route::get('texto-propuesta',       [PropuestaController::class, 'textoPropuesta']);
        Route::get('introduccion',          [PropuestaController::class, 'introduccion']);
        Route::get('problematica',          [PropuestaController::class, 'problematica']);
        Route::get('objetivo-general',      [PropuestaController::class, 'objetivoGeneral']);
        Route::get('objetivos-especificos', [PropuestaController::class, 'objetivosEspecificos']);
        Route::get('metodologia',           [PropuestaController::class, 'metodologia']);
        Route::get('generar-todo',          [TestPropuestaController::class, 'generarTodo']);
        Route::get('word',                  [PropuestaWordController::class, 'export']);
    });

    // =========================================================
    // ENTREGABLE: generación IA + Word
    // =========================================================
    Route::prefix('entregable/{id}')->group(function () {
        Route::get('introduccion',          [EntregableController::class, 'introduccion']);
        Route::get('problematica',          [EntregableController::class, 'problematica']);
        Route::get('desarrollo-conceptos',  [EntregableController::class, 'desarrolloConceptos']);
        Route::get('generar-todo',          [EntregableController::class, 'generarTodo']);  // ← nueva
        Route::get('word',                  [EntregableWordController::class, 'export']);
    });

    // =========================================================
    // CALENDARIO (JSON, no Word)
    // =========================================================
    Route::get('/calendario/{id}', [CalendarioController::class, 'show']);


    Route::get('/{id}/zip', [ZipController::class, 'descargar']);
});