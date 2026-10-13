<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/plans', function () {
    return Inertia::render('Plans');
})->middleware(['auth', 'verified'])->name('plans.index');

Route::get('/meeting/{code}', function (string $code) {
    return Inertia::render('MeetingRoom', ['code' => $code]);
})->where('code', '[A-Za-z0-9-]+')->name('meeting.show');
Route::get('/meetings', function () {
    return Inertia::render('Meetings');
})->middleware(['auth', 'verified'])->name('meetings.index');

Route::get('/notes', function () {
    return Inertia::render('Notes');
})->middleware(['auth', 'verified'])->name('notes.index');

Route::get('/summaries', function () {
    return Inertia::render('Summaries');
})->middleware(['auth', 'verified'])->name('summaries.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


