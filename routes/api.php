<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas de prueba y depuración
Route::get('/test', function() {
    return response()->json(['message' => 'API funcionando correctamente']);
});

Route::post('/debug/check-credentials', [DebugController::class, 'checkCredentials']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Proyectos
    Route::get('/projects/statistics', [ProjectController::class, 'statistics']);
    Route::apiResource('projects', ProjectController::class);
});
