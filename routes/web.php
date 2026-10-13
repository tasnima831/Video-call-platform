<?php

use App\Http\Controllers\MeetingAccessController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingMessagesController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordingsController;
use App\Http\Controllers\SummariesController;
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
})->name('welcome');

Route::get('/meeting/{code}', [MeetingController::class, 'show'])
    ->where('code', '[A-Za-z0-9-]+')->name('meeting.show');
Route::post('/meeting/{code}/join', [MeetingController::class, 'join'])
    ->where('code', '[A-Za-z0-9-]+')->middleware('throttle:30,1')->name('meeting.join');
Route::get('/meeting/{code}/status', [MeetingAccessController::class, 'status'])->name('meeting.status');
Route::get('/meeting/{code}/chat', [MeetingMessagesController::class, 'index'])->name('meeting.chat.index');
Route::post('/meeting/{code}/chat', [MeetingMessagesController::class, 'store'])->middleware('throttle:60,1')->name('meeting.chat.store');

Route::post('/meeting/{code}/leave', [MeetingController::class, 'leave'])
    ->where('code', '[A-Za-z0-9-]+')->name('meeting.leave');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::post('/meeting/{code}/end', [MeetingController::class, 'end'])
        ->where('code', '[A-Za-z0-9-]+')->name('meeting.end');
    Route::get('/meeting/{code}/waiting', [MeetingAccessController::class, 'waiting'])->name('meeting.waiting');
    Route::post('/meeting/{code}/participants/{participant}/decision', [MeetingAccessController::class, 'decide'])->name('meeting.participants.decision');
    Route::post('/meeting/{code}/recordings', [RecordingsController::class, 'store'])
        ->where('code', '[A-Za-z0-9-]+')->name('meeting.recordings.store');

    Route::get('/notes', [NotesController::class, 'index'])->name('notes.index');
    Route::put('/notes', [NotesController::class, 'save'])->name('notes.save');
    Route::get('/summaries', [SummariesController::class, 'index'])->name('summaries.index');
    Route::post('/summaries', [SummariesController::class, 'store'])->name('summaries.store');

    Route::get('/plans', [PlansController::class, 'index'])->name('plans.index');
    Route::post('/plans', [PlansController::class, 'choose'])->name('plans.choose');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
