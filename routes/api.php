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

    // Usuarios
    Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
    Route::get('/users/statistics', [\App\Http\Controllers\Api\UserController::class, 'statistics']);
    // Estadísticas de horas por usuario
    Route::get('/users/time-statistics', [\App\Http\Controllers\Api\UserTimeEntryController::class, 'statistics']);
    Route::get('/users/time-entries', [\App\Http\Controllers\Api\UserTimeEntryController::class, 'index']);
    Route::get('/users/{user}', [\App\Http\Controllers\Api\UserController::class, 'show']);

    // Entidades
    Route::get('/entities', [\App\Http\Controllers\Api\EntityController::class, 'index']);
    Route::get('/entities/statistics', [\App\Http\Controllers\Api\EntityController::class, 'statistics']);
    Route::get('/entities/types', [\App\Http\Controllers\Api\EntityController::class, 'types']);
    Route::get('/entities/business-groups', [\App\Http\Controllers\Api\EntityController::class, 'businessGroups']);
    Route::get('/entities/{entity}', [\App\Http\Controllers\Api\EntityController::class, 'show']);

    // Proyectos
    Route::get('/projects/statistics', [ProjectController::class, 'statistics']);
    Route::apiResource('projects', ProjectController::class);

    // Hitos de proyectos
    Route::apiResource('projects.milestones', \App\Http\Controllers\Api\ProjectMilestoneController::class);
    // Time Entries
    Route::apiResource('time-entries', \App\Http\Controllers\Api\TimeEntryController::class);
   
});
