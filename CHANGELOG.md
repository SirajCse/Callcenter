# Call Center Module — Cleanup & Fix Changelog

## 1. CSS — removed the duplicated inline `<style>` blocks
- Every one of the 12 top-level views had its own copy-pasted `<style>` block (~54KB total,
  reinventing the same design tokens 13 times) **in addition to** including the shared
  `_frest_css.blade.php` partial.
- All of it has been deduplicated and merged into **one real, cacheable stylesheet**:
  `public/css/callcenter/callcenter.css` (organized into commented sections per module).
- `_frest_css.blade.php` is now just a single `<link>` tag to that file (cache-busted with
  `filemtime()`), included once per page instead of a wall of inline CSS.
- Net effect: Blade view files shrank by ~900 lines, and the browser now caches one CSS file
  across every Call Center page instead of re-downloading/re-parsing it on every request.

## 2. Business logic & queries moved out of Blade into controllers
Every `@php ... @endphp` block that did more than trivial variable aliasing has been removed.
Specifically:

- **calllogs, sms, letters, missing_address, tasks** index pages: the "stat card" counts
  (totals, status breakdowns) were being computed with live queries *inside the view* on every
  page load. These now live in each controller's `index()` and are passed in as `$stats`.
- **Status/priority → CSS pill class** lookup tables (`$sc = [...]`, `$pc = [...]`) were
  redefined inline in every row of every table. Moved to `public const` maps on the relevant
  controllers (`STATUS_PILL_CLASSES`, `PRIORITY_PILL_CLASSES`) and passed to the view once.
- **`_patient_card.blade.php`** was firing **5 raw Eloquent queries directly from the view**
  every time a patient card rendered (call count, appointment count, lab count, last call, last
  visit). These now run once in `CallBoardController::patientCardMeta()` and are passed in.
- **`_task_card.blade.php`** computed its priority pill/card class with a ternary on every card.
  Now attached once per task collection in the controller.
- **`admin/index`, `admin/monitor`, `admin/performance`**: rank badges (🥇/🥈/🥉) and the
  per-agent "pending tasks" count were computed in the view — the pending count via a **fresh
  DB query per table row** (classic N+1). Both are now computed once in
  `AdminCallCenterController` (`attachAgentDayMetrics()` / `attachRanks()`), and the N+1 query
  is gone — replaced with a single grouped `whereIn` count query for the whole page.
- **`followup/index`**: the "called by another agent" column ran **one query per row**
  (`PatientCallLog::where(...)->first()`) — real N+1. Replaced with a single batched query in
  `FollowUpController::index()` before pagination.
- **`board/my_stats.blade.php`**: the "today" stat cards array and the month chart's max-value
  calculation moved into `CallBoardController::myStats()`.
- A few leftover raw queries for populating `<select>` dropdowns (transfer-to agent lists in
  the task modal, transfer modal, and new-task modal) were moved into the controllers that
  render those pages, instead of querying `User::whereHas(...)` directly from Blade.

The only PHP left in views now is either plain `{{ }}` output or a handful of single-line
`@php($x = $alreadyLoaded->relation)` aliases with no query/business logic behind them —
standard, low-risk Blade usage.

## 3. JavaScript fixes
- **Real bug fixed:** several places built AJAX URLs like
  `'{{ route("callcenter.patient", ":id") }}'.replace(':id', id)`. Laravel's `route()` helper
  URL-encodes route parameters, so `:id` was actually rendered as `%3Aid` in the HTML — the
  `.replace(':id', ...)` never matched anything, silently breaking `loadPatient()`,
  `completeTask()`, `pinTask()`, and `openCallHistory()` on the main call board. Replaced with
  a safe `__ID__` placeholder pattern everywhere this occurred.
- Every hardcoded `'/callcenter/...'` / `url('callcenter/...')` string path has been replaced
  with Laravel's `route()` helper, so routes stay correct if the URL prefix ever changes.
- Added one shared `_frest_js_init.blade.php` partial that sets `$.ajaxSetup` with the CSRF
  header once, instead of passing `_token` manually on every single `$.post()` call.
- **Follow-up list "Bulk SMS" button was a stub** — it only showed a toast
  (`toastr.info(ids.length + ' selected')`) and never sent anything. It now opens a real
  template-picker modal and calls a new `SmsLogController::bulkStore()` endpoint
  (`POST /callcenter/sms/bulk`) that sends the templated message to every selected patient
  with a valid phone number and reports how many were sent/skipped.
- Added `.fail()` error handling with real messages (`xhr.responseJSON?.message`) to AJAX calls
  that previously had none, so failures don't fail silently.

## Files changed
- `app/Http/Controllers/CallCenter/*.php` (all 7 controllers + Admin controller)
- `routes/callcenter.php` (added `sms.bulk` route)
- `resources/views/callcenter/**/*.blade.php` (all 20 views/partials)
- `public/css/callcenter/callcenter.css` (new, consolidated)
- `resources/views/callcenter/partials/_frest_js_init.blade.php` (new)

## Not changed (per your instructions)
- Your Frest layout file — not touched.
- Models/migrations — you said these already exist in your app, so none were added here.
