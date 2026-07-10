# MedCRM Pro — Call Center Fixed Files (Drop-in for Laravel 12)

## Installation — "What goes where"

Copy these files into your existing Laravel project root, preserving the folder structure:

```
your-laravel-project/
├── app/
│   ├── Http/Controllers/CallCenter/
│   │   ├── CallBoardController.php          ← FIXED: +dialPatient() method
│   │   ├── TaskController.php               ← FIXED: null-safe transfer, +recalculate
│   │   ├── PatientCallLogController.php     ← FIXED: die→died, +updateOutcome()
│   │   └── FollowUpController.php           ← FIXED: is_active→died
│   ├── Models/CallCenter/
│   │   └── AgentDailyStat.php               ← FIXED: recalculate() uses method not type
│   └── Services/CallCenter/
│       └── DialService.php                  ← NEW: MikoPBX auto-dial wrapper
├── routes/
│   └── callcenter.php                       ← FIXED: auth middleware + dial/outcome routes
├── resources/views/callcenter/
│   ├── board/
│   │   ├── index.blade.php                  ← FIXED: +transfer modal + board.js + dial buttons
│   │   └── partials/
│   │       ├── _modal_log_call.blade.php    ← FIXED: AJAX submit + "Dial & Log" button
│   │       ├── _modal_new_task.blade.php    ← FIXED: AJAX submit
│   │       ├── _modal_transfer.blade.php    ← NEW: was missing (transferTask broke)
│   │       ├── _patient_card.blade.php      ← FIXED: Call button → auto-dial
│   │       └── _task_card.blade.php         ← FIXED: Call button → auto-dial
│   ├── sms/_modal.blade.php                 ← FIXED: AJAX submit + templates + char counter
│   └── letters/_modal.blade.php             ← FIXED: AJAX submit
└── public/js/callcenter/
    └── board.js                             ← NEW: dialPatient() + all AJAX form handlers
```

## What was fixed

### 1. ★ Auto-Dial via MikoPBX (the big one)
- **Before:** "Call" button just opened a modal to log a call manually.
- **After:** "Call" button fires `POST /callcenter/dial` → `DialService::dialPatient()` → `$agent->callNumber($phone)` (AMI Originate via the `HasMikoPBXExtension` trait) → rings the agent's extension, then bridges to the patient. A `PatientCallLog` is created with `caller_opinion='dialing'`. The Log Call modal opens pre-filled. When the agent submits the outcome, it calls `POST /callcenter/calllogs/{id}/outcome` to update the existing log.

### 2. Fixed `AgentDailyStat::recalculate()`
- **Bug:** Used `$logs->where('type', 'outgoing')` but `type` stores the task type (e.g. 'followup_call'), not 'outgoing'/'incoming'. So `outgoing_calls` and `incoming_calls` were always 0.
- **Fix:** Changed to `$logs->where('method', 'outgoing')` (the `method` field stores outgoing/incoming).

### 3. Fixed `FollowUpController::index()`
- **Bug:** `->where('is_active', true)` — the `users` table has no `is_active` column. Query would throw SQL error.
- **Fix:** Changed to `->where('died', 0)` (alive patients).

### 4. Fixed `PatientCallLogController::store()` — deceased flag
- **Bug:** `die=1` flag set `User::where('id', ...)->update(['is_active' => false])` — non-existent column.
- **Fix:** Now sets `['died' => 1, 'died_date' => today()]`.

### 5. Fixed missing Transfer modal
- **Bug:** `transferTask(id)` JS opened `#modalTransfer` but no such blade file existed → button did nothing.
- **Fix:** Created `_modal_transfer.blade.php` with agent select + reason + AJAX submit.

### 6. Added `auth` middleware to routes
- **Bug:** `//middleware(['auth'])->` was commented out — anyone could access the call center.
- **Fix:** `Route::middleware(['auth'])->prefix('callcenter')...`

### 7. All modal forms now submit via AJAX
- **Before:** Forms did full page reloads.
- **After:** jQuery AJAX with loading spinners, Toastr notifications, no page reload (except intentional `location.reload()` to refresh task lists).

### 8. Fixed `TaskController::transfer()`
- **Bug:** `$task->note . "\n[Transferred...]"` could fail if `note` is null.
- **Fix:** `($task->note ?? '') . "\n[Transferred...]"`. Also added `AgentDailyStat::recalculate()`.

## New routes added

```php
Route::post('/dial',                        [CallBoardController::class, 'dialPatient'])->name('dial');
Route::post('/calllogs/{callLog}/outcome',  [PatientCallLogController::class, 'updateOutcome'])->name('calllogs.outcome');
```

## Prerequisites (already in your project)

- `bitdreamit/laravel-mikopbx` package (in composer.json ✓)
- `HasMikoPBXExtension` trait on User model (already used ✓)
- `pbx_extension` column on users table (migration 000002 ✓)
- MikoPBX `.env` config (`MIKOPBX_AMI_HOST`, `MIKOPBX_AMI_USER`, `MIKOPBX_AMI_SECRET`, etc.)
- Agents must have `pbx_extension` assigned (e.g. `User::find(1)->update(['pbx_extension' => '101'])`)

## Auto-dial flow (how it works end-to-end)

```
1. Agent clicks "Call" on a task/patient card
   ↓ JS: dialAndOpenLogCall(patientId, taskId)
   ↓
2. POST /callcenter/dial { patient_id, task_id }
   ↓ CallBoardController::dialPatient()
   ↓ DialService::dialPatient()
   ↓   $agent = Auth::user()
   ↓   $agent->hasPbxExtension()  ← check
   ↓   $phone = cleanPhone($patient->phone)
   ↓   PatientCallLog::create([outcome='dialing', method='outgoing', ...])
   ↓   $agent->callNumber($phone)  ← AMI Originate (rings agent ext, bridges to patient)
   ↓
3. JSON response { success, call_log_id, message }
   ↓ JS: window._pendingCallLogId = call_log_id
   ↓ JS: toastr.info("Dialing...")
   ↓
4. Log Call modal opens pre-filled (agent picks outcome after the call)
   ↓ JS: submitLogCall() → POST /callcenter/calllogs/{callLogId}/outcome
   ↓ PatientCallLogController::updateOutcome()
   ↓   Updates caller_opinion, duration, call_note, receive, die, transfer_to
   ↓   AgentDailyStat::recalculate()
   ↓
5. If "receive=1" (answered) and task_id set → task auto-completed
   If "die=1" → patient marked deceased (died=1, died_date=today)
   If "sms_sent=1" → SmsLog created
   If "letter_sent=1" → LetterLog created
```

## Testing the auto-dial

1. Ensure your `.env` has MikoPBX AMI credentials:
   ```
   MIKOPBX_AMI_HOST=pbx.htncr.org
   MIKOPBX_AMI_USER=admin
   MIKOPBX_AMI_SECRET=your_secret
   ```
2. Assign a PBX extension to your agent user:
   ```php
   User::where('email', 'agent@htncr.org')->update(['pbx_extension' => '101']);
   ```
3. Navigate to `/callcenter`, click "Call" on any task → the agent's extension (101) rings first, then bridges to the patient's phone.

## Files NOT included (unchanged — keep your existing versions)

These files were already correct in your project, so they're NOT in this zip:
- `app/Http/Controllers/CallCenter/SmsLogController.php`
- `app/Http/Controllers/CallCenter/LetterLogController.php`
- `app/Http/Controllers/CallCenter/MissingAddressController.php`
- `app/Http/Controllers/CallCenter/Admin/AdminCallCenterController.php`
- `app/Models/CallCenter/Task.php`
- `app/Models/CallCenter/SmsLog.php`
- `app/Models/CallCenter/LetterLog.php`
- `app/Models/CallCenter/MissingAddress.php`
- `app/Models/CallCenter/CallNote.php`
- All `resources/views/callcenter/board/partials/_tab_*.blade.php`
- `resources/views/callcenter/calllogs/history_modal.blade.php`
- `resources/views/callcenter/admin/*.blade.php`
- `resources/views/callcenter/followup/index.blade.php`
- `resources/views/callcenter/partials/_frest_css.blade.php`
