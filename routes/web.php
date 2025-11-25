<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\DashboardController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DBHealthCheckController;
use App\Http\Controllers\DeansWebTestController;
use App\Http\Controllers\LogClearDownController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\OrganisationsController;
use App\Http\Controllers\Web\ActiveCallsController;
use App\Http\Controllers\Web\ApiRequestLogsController;
use App\Http\Controllers\Web\CompetitionClearStatsController;
use App\Http\Controllers\Web\CompetitionController;
use App\Http\Controllers\Web\DocsCallFlowController;
use App\Http\Controllers\Web\FailedEntriesController;
use App\Http\Controllers\Web\MaxCapacityCallLogsController;
use App\Http\Controllers\Web\OrphanedActiveCallsController;
use App\Http\Controllers\Web\ParticipantsController;
use App\Http\Controllers\Web\ShoutRequestLogsController;
use App\Http\Controllers\Web\WebPhoneBookEntriesController;
use App\Http\Controllers\Web\WebPhoneLineScheduleController;
use App\Models\PhoneBookEntry;

Route::get('/health-check', HealthCheckController::class)->name('health-check');

Route::get('/db-health-check', DBHealthCheckController::class)->middleware('auth.basic')->name('web.db-health-check');

Route::prefix('web')->middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/organisations', [OrganisationsController::class, 'index'])->name('web.organisations.index');


    Route::get('/active-calls/', [ActiveCallsController::class, 'index'])->name('web.active-calls.index');
    Route::get('/orphan-active-calls/', [OrphanedActiveCallsController::class, 'index'])->name('web.orphan-active-calls.index');

    Route::get('/phone-book-entries', [WebPhoneBookEntriesController::class, 'index'])->name('web.phone-book-entries.index');
    Route::get('/phone-book-entries/lookup/{phoneBookEntry:phone_number}', [WebPhoneBookEntriesController::class,'show'])->name('web.phone-book-entries.lookup');

    Route::get('/phone-line-schedule/{competitionPhoneNumber}', [WebPhoneLineScheduleController::class, 'index'])->name('web.phone-line-schedule.index');
    Route::get('/phone-line-schedule/schedule/{phoneLineSchedule}', [WebPhoneLineScheduleController::class, 'show'])->name('web.phone-line-schedule.show');


    Route::get('/competitions', [CompetitionController::class, 'index'])->name('web.competition.index');
    Route::get('/competitions/{competition}', [CompetitionController::class, 'show'])->name('web.competition.show');
    Route::get('/competitions/{competition}/clear-stats', CompetitionClearStatsController::class)->name('web.competition.clear-stats-cache');

    Route::get('/entries/non-entries', [FailedEntriesController::class, 'index'])->name('web.entries.failed.index');
    Route::get('/participants', [ParticipantsController::class, 'index'])->name('web.participants.index');

    Route::get('/api-request-logs/', [ApiRequestLogsController::class, 'index'])->name('web.api-request-logs.index');
    Route::get('/api-request-logs/{apiRequestLog}', [ApiRequestLogsController::class, 'show'])->name('web.api-request-logs.show');

    Route::get('/shout-request-logs/', [ShoutRequestLogsController::class, 'index'])->name('web.shout-request-logs.index');
    Route::get('/shout-request-logs/{shoutRequestLog}', [ShoutRequestLogsController::class, 'show'])->name('web.shout-request-logs.show');

    Route::get('/max-capacity-call-logs/', [MaxCapacityCallLogsController::class, 'index'])->name('web.max-capacity-call-logs.index');

    Route::get('/documentation/call-flow', DocsCallFlowController::class)->name('web.docs.call-flow');

//    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
//    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
//    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');
//    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
//    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
//    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    if (!app()->environment('production')) {
        Route::get('/log-clear-down', LogClearDownController::class)->name('web.log-clear-down');
    }

    if (app()->isLocal()) {
        Route::get('/deans-web-test', DeansWebTestController::class)->name('deans-web-test');
    }
});


Route::prefix('web')->middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});
