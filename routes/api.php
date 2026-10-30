<?php

use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

Route::name('api.')->middleware(['auth:sanctum', 'active', 'throttle:api', CheckAbilities::class.':links:manage'])->group(function () {
    Route::apiResource('links', LinkController::class);
});
