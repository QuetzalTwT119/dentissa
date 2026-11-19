<?php

use Illuminate\Support\Facades\Route;

Route::post('/app/login', [\App\Http\Controllers\Api\AppAuthController::class, 'login']);
