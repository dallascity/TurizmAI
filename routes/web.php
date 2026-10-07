<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChatController::class, 'index']);
Route::post('/chat', [ChatController::class, 'send'])->middleware('throttle:30,1');
Route::post('/chat/reset', [ChatController::class, 'reset']);

Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
