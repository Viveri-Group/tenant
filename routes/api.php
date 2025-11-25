<?php

use App\Http\Controllers\ActiveCallClearDownController;
use App\Http\Controllers\APIHealthCheckController;
use App\Http\Controllers\CallerHistoryController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CompetitionStatisticsController;
use App\Http\Controllers\CompetitionStatisticsRangeController;
use App\Http\Controllers\CompetitionStatisticsRoundController;
use App\Http\Controllers\DeansTestController;
use App\Http\Controllers\AudioFileUploadController;
use App\Http\Controllers\CreateEntryController;
use App\Http\Controllers\EntrantsDownloadController;
use App\Http\Controllers\PhoneBookEntryController;
use App\Http\Controllers\PhoneBookLookupEntryController;
use App\Http\Controllers\PhoneLineAvailabilityController;
use App\Http\Controllers\PhoneLineController;
use App\Http\Controllers\CompetitionCheckController;
use App\Http\Controllers\PhoneLineSchedulerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->scopeBindings()->group(function () {
    Route::get('/health-check', APIHealthCheckController::class)->name('api.health-check');

    Route::post('/active-call/competition-check', CompetitionCheckController::class)->name('active-call.competition-check');
    Route::post('/active-call/{activeCall}/create-entry', CreateEntryController::class)->name('active-call.create-entry');
    Route::post('/active-call/{activeCall}/clear-down', ActiveCallClearDownController::class)->name('active-call.clear-down');

    Route::post('/download/competition/{competition}/entrants', EntrantsDownloadController::class)->name('download.entrants');

    Route::get('/phone-book/entry', [PhoneBookEntryController::class, 'index'])->name('phone-book.entry.index');
    Route::get('/phone-book/entry/{phoneBookEntry}', [PhoneBookEntryController::class, 'show'])->name('phone-book.entry.show');

    Route::get('/phone-book/lookup/entry/{phoneBookEntry:phone_number}', PhoneBookLookupEntryController::class)->name('phone-book.lookup.entry');

    Route::get('/competition', [CompetitionController::class, 'index'])->name('competition.index');
    Route::get('/competition/{competition}', [CompetitionController::class, 'show'])->name('competition.show');
    Route::post('/competition/create', [CompetitionController::class, 'store'])->name('competition.create');
    Route::post('/competition/{competition}/update', [CompetitionController::class, 'update'])->name('competition.update');
    Route::delete('/competition/{competition}/delete', [CompetitionController::class, 'destroy'])->name('competition.destroy');

    Route::get('/competition/{competition}/phone-line/{phoneLine}', [PhoneLineController::class, 'show'])->name('phone-line.show');
    Route::post('/competition/{competition}/phone-line/create', [PhoneLineController::class, 'store'])->name('phone-line.create');
    Route::post('/competition/{competition}/phone-line/{phoneLine}/update', [PhoneLineController::class, 'update'])->name('phone-line.update');
    Route::delete('/competition/{competition}/phone-line/{phoneLine}/delete', [PhoneLineController::class, 'destroy'])->name('phone-line.destroy');

    Route::post('/competition/{competition}/file/upload', [AudioFileUploadController::class, 'store'])->name('file.store');
    Route::delete('/competition/{competition}/file/{file}/delete', [AudioFileUploadController::class, 'destroy'])->name('file.destroy');

    Route::post('/phone-line/availability', PhoneLineAvailabilityController::class)->name('phone-line.availability');

    Route::get('/phone-number-schedule/{phoneLineSchedule}', [PhoneLineSchedulerController::class, 'show'])->name('phone-number-schedule.show');
    Route::post('/phone-number-schedule', [PhoneLineSchedulerController::class, 'index'])->name('phone-number-schedule.index');
    Route::post('/phone-number-schedule/create', [PhoneLineSchedulerController::class, 'store'])->name('phone-number-schedule.create');
    Route::post('/phone-number-schedule/{phoneLineSchedule}/update', [PhoneLineSchedulerController::class, 'update'])->name('phone-number-schedule.update');
    Route::delete('/phone-number-schedule/{phoneLineSchedule}/delete', [PhoneLineSchedulerController::class, 'destroy'])->name('phone-number-schedule.delete');

    Route::get('/competition/{competition}/statistics/active-round', CompetitionStatisticsController::class)->name('competition.statistics.active-round');
    Route::post('/competition/{competition}/statistics/range', CompetitionStatisticsRangeController::class)->name('competition.statistics.range');
    Route::post('/statistics/round/{competitionDraw:round_hash}', CompetitionStatisticsRoundController::class)->name('competition.statistics.round');

    Route::post('/caller/history', CallerHistoryController::class)->name('caller.history');

    if (app()->isLocal()) {
        Route::get('/deans-test', DeansTestController::class)->name('deans-test');
    }
});
