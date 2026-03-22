<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\PublicQuestionController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('questions/browse', [PublicQuestionController::class, 'browse'])
    ->name('questions.browse');

Route::middleware(['auth'])->group(function () {
    Route::post('questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('questions/{question}', [PublicQuestionController::class, 'show'])
        ->name('questions.show');
    Route::post('votes/{voteable_type}/{voteable_id}', [VoteController::class, 'toggle'])
        ->name('votes.toggle');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('api/provinces/{province}/cities', [ProvinceController::class, 'cities'])
        ->name('api.provinces.cities');

    Route::resource('questions', QuestionController::class)
        ->only(['index', 'edit', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
