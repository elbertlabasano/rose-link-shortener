<?php

use App\Http\Controllers\KeycloakController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'login')->name('login');
    Route::get('/auth/keycloak/redirect', [KeycloakController::class, 'redirect'])->middleware('throttle:20,1')->name('sso.redirect');
    Route::get('/auth/keycloak/callback', [KeycloakController::class, 'callback'])->middleware('throttle:20,1')->name('sso.callback');
});
Route::post('/logout', [KeycloakController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [LinkController::class, 'dashboard'])->name('dashboard');
    Route::post('/dashboard/links', [LinkController::class, 'store'])->middleware('throttle:60,1')->name('links.store');
    Route::get('/dashboard/links/{link}/edit', [LinkController::class, 'edit'])->name('links.edit');
    Route::patch('/dashboard/links/{link}', [LinkController::class, 'update'])->name('links.update');
    Route::delete('/dashboard/links/{link}', [LinkController::class, 'destroy'])->name('links.destroy');
    Route::get('/dashboard/tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::post('/dashboard/tokens', [TokenController::class, 'store'])->middleware('throttle:10,1')->name('tokens.store');
    Route::delete('/dashboard/tokens/{token}', [TokenController::class, 'destroy'])->name('tokens.destroy');
});
Route::get('/{code}', [LinkController::class, 'redirect'])->where('code', '[a-z0-9][a-z0-9_-]{2,39}')->name('short.redirect');
