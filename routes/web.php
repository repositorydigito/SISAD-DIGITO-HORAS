<?php

use App\Http\Controllers\RedirectController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login', 301);

// Ruta de prueba web
Route::get('/test-web', function() {
    return response()->json(['message' => 'Ruta web funcionando correctamente']);
});

// Ruta para exportación de proyectos
Route::get('/admin/resources/projects/export', [ProjectExportController::class, 'export'])
    ->name('filament.admin.resources.projects.export')
    ->middleware(['web', 'auth']);

// Ruta para descargar plantilla de time entries
Route::get('/admin/resources/time-entries/template', [TemplateController::class, 'downloadTimeEntriesTemplate'])
    ->name('time-entries.template')
    ->middleware(['web', 'auth']);
