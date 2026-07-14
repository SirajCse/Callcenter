<?php

use App\Http\Controllers\CallCenter\CallBoardController;
use App\Http\Controllers\CallCenter\TaskController;
use App\Http\Controllers\CallCenter\PatientCallLogController;
use App\Http\Controllers\CallCenter\FollowUpController;
use App\Http\Controllers\CallCenter\SmsLogController;
use App\Http\Controllers\CallCenter\LetterLogController;
use App\Http\Controllers\CallCenter\MissingAddressController;
use App\Http\Controllers\CallCenter\Admin\AdminCallCenterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CALL CENTER ROUTES — FINAL FIXED VERSION
| Require in web.php:  require __DIR__.'/callcenter.php';
|
| ★ Route params match the EXACT names used in blade JS:
|     {task}           → ["task" => "__ID__"]
|     {sms}            → ["sms" => "__ID__"]
|     {missingAddress} → ["missingAddress" => "__ID__"]
|     {patientId}      → ["patientId" => "__ID__"]
|     {id}             → ":id" string replacement
|     {callLog}        → ":id" string replacement
|
| ★ Route names match blade route() calls EXACTLY:
|     missing_address.index (not missing.index)
|     sms.bulk (added)
|
| ★ Controllers use explicit findOrFail($param) — no route-model binding.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('callcenter')->name('callcenter.')->group(function () {

    // ── Board ────────────────────────────────────────────────────
    Route::get('/',                             [CallBoardController::class, 'index'])->name('board');
    Route::get('/patient/{id}',                 [CallBoardController::class, 'patient'])->name('patient');
    Route::get('/my-calls',                     [CallBoardController::class, 'myCalls'])->name('mycalls');
    Route::get('/my-stats',                     [CallBoardController::class, 'myStats'])->name('mystats');

    // ── Auto-Dial (MikoPBX) ──────────────────────────────────────
    Route::post('/dial',                        [CallBoardController::class, 'dialPatient'])->name('dial');
    Route::post('/calllogs/{callLog}/outcome',  [PatientCallLogController::class, 'updateOutcome'])->name('calllogs.outcome');

    // ── Tasks (param {task} — matches blade ["task" => "__ID__"]) ──
    Route::get('/tasks',                        [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks',                       [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}',                 [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}',              [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/complete',       [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('/tasks/{task}/transfer',       [TaskController::class, 'transfer'])->name('tasks.transfer');
    Route::post('/tasks/{task}/pin',            [TaskController::class, 'pin'])->name('tasks.pin');

    // ── Call Logs (history param {patientId} — matches blade) ────
    Route::get('/calllogs',                     [PatientCallLogController::class, 'index'])->name('calllogs.index');
    Route::post('/calllogs',                    [PatientCallLogController::class, 'store'])->name('calllogs.store');
    Route::get('/calllogs/history/{patientId}', [PatientCallLogController::class, 'history'])->name('calllogs.history');

    // ── Follow-up ────────────────────────────────────────────────
    Route::get('/followup',                     [FollowUpController::class, 'index'])->name('followup.index');
    Route::post('/followup/save-today',         [FollowUpController::class, 'saveToday'])->name('followup.savetoday');

    // ── SMS (param {sms} — matches blade ["sms" => "__ID__"]) ────
    Route::get('/sms',                          [SmsLogController::class, 'index'])->name('sms.index');
    Route::post('/sms',                         [SmsLogController::class, 'store'])->name('sms.store');
    Route::post('/sms/{sms}/resend',            [SmsLogController::class, 'resend'])->name('sms.resend');
    // ★ Added: bulk SMS route (used by followup/index.blade.php)
    Route::post('/sms/bulk',                    [SmsLogController::class, 'bulk'])->name('sms.bulk');

    // ── Letters ──────────────────────────────────────────────────
    Route::get('/letters',                      [LetterLogController::class, 'index'])->name('letters.index');
    Route::post('/letters',                     [LetterLogController::class, 'store'])->name('letters.store');

    // ── Missing Address (param {missingAddress} — matches blade) ──
    // ★ Route name is missing_address.index (NOT missing.index) — matches blade
    Route::get('/missing-address',              [MissingAddressController::class, 'index'])->name('missing_address.index');
    Route::post('/missing-address',             [MissingAddressController::class, 'store'])->name('missing_address.store');
    Route::put('/missing-address/{missingAddress}', [MissingAddressController::class, 'update'])->name('missing.update');

    // ── Admin (supervisor/admin only) ────────────────────────────
    Route::middleware(['role:ADMINISTRATOR|SUPER-ADMIN|ADMIN'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/',                         [AdminCallCenterController::class, 'index'])->name('index');
        Route::get('/filter-patients',          [AdminCallCenterController::class, 'filterPatients'])->name('filter');
        Route::post('/assign-tasks',            [AdminCallCenterController::class, 'assignTasks'])->name('assign');
        Route::get('/monitor',                  [AdminCallCenterController::class, 'monitor'])->name('monitor');
        Route::get('/performance',              [AdminCallCenterController::class, 'performance'])->name('performance');
    });
});
