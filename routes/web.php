<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TestController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// Route::get('/prototipo', function () {
//     return Inertia::render('test/prototipo');
// });


Route::prefix('{plaza}')
    ->whereIn('plaza', ['test00', 'cdmx', 'norte', 'merida'])
    ->group(function () {
        Route::get('/prototipo', function (string $plaza) {
            return Inertia::render('test/prototipo', [
                'plaza' => $plaza,
            ]);
        })->name('prototipo');
    });


Route::get('/dialog-test', function () {
    return Inertia::render('test/dialog-test');
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

use App\Http\Controllers\Api\IA\ImageController;
Route::get('image/test', [ImageController::class, 'probar']);


use App\Http\Controllers\Api\IA\ImageGenerationController;

// Ruta de prueba. Solo abre esto en el navegador.
Route::get('/prueba-imagenes', [ImageGenerationController::class, 'generate']);


// Ruta de prueba dinámica por expediente (la nueva)
Route::get('/test-graficos/{id}', [ImageGenerationController::class, 'generarOrganizadores']);

Route::get('/testgen/{id}', [ImageGenerationController::class, 'testGeneracion']);