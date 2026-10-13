<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\CallTokenController;

Route::post('/rooms', [RoomController::class, 'store']);
Route::get('/rooms', [RoomController::class, 'index']);

Route::post('/call-token', [
    CallTokenController::class,
    'generate'
]);