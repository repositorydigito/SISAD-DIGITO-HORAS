<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login', 301);

// Ruta de prueba web
Route::get('/test-web', function() {
    return response()->json(['message' => 'Ruta web funcionando correctamente']);
});
