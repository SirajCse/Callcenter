@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'Call Board')

@section('page-styles')
<style>
:root {
  --cc-primary:#5a8dee; --cc-primary-light:rgba(90,141,238,0.12);
  --cc-success:#39da8a; --cc-success-light:rgba(57,218,138,0.12);
  --cc-danger:#ff5b5b; --cc-danger-light:rgba(255,91,91,0.12);
  --cc-warning:#fdac41; --cc-warning-light:rgba(253,172,65,0.12);
  --cc-info:#00cfdd; --cc-info-light:rgba(0,207,221,0.12);
  --cc-purple:#7367f0; --cc-purple-light:rgba(115,103,240,0.12);
  --cc-body:#f8f7fa; --cc-card:#fff;
  --cc-border:#ebebeb; --cc-border2:#dde3ec;
  --cc-shadow:0 4px 24px 0 rgba(34,41,47,0.08);
  --cc-shadow-sm:0 2px 8px 0 rgba(34,41,47,0.08);
  --cc-text:#475f7b; --cc-text-dark:#2c3e5d;
  --cc-text-muted:#828d99; --cc-text-light:#adb5bd;
  --cc-r:0.357rem; --cc-r2:0.5rem; --cc-r3:0.75rem;
}
.cc-topbar{display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap}
.cc-search{flex:1;max-width:400px;position:relative}
.cc-search .select2-container{width:100%!important}
.cc-search .select2-container--default .select2-selection--single{background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:4px 14px 4px 38px;height:38px;color:var(--cc-text-dark);font-size:13px;outline:none;transition:border-color .2s,box-shadow .2s}
.cc-search .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:28px;padding-left:0;color:var(--cc-text-dark)}
.cc-search .select2-container--default .select2-selection--single .select2-selection__placeholder{color:var(--cc-text-light)}
.cc-search .select2-container--default.select2-container--open .select2-selection--single{border-color:var(--cc-primary);box-shadow:0 0 0 3px rgba(90,141,238,0.15)}
.cc-search .select2-container--default .select2-selection--single .select2-selection__arrow{top:6px;right:8px}
.cc-search > i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--cc-text-muted);font-size:13px;z-index:2;pointer-events:none}
.kpi-chip{display:flex;align-items:center;gap:7px;background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:6px 12px;font-size:12px;font-weight:500;color:var(--cc-text);cursor:pointer;transition:all .2s;white-space:nowrap}
.kpi-chip:hover{border-color:var(--cc-primary);background:var(--cc-primary-light)}
.kpi-chip .kn{font-weight:700;font-size:14px}
.kpi-chip.success .kn{color:var(--cc-success)}
.kpi-chip.warning .kn{color:var(--cc-warning)}
.kpi-chip.primary .kn{color:var(--cc-primary)}
.kpi-chip.danger .kn{color:var(--cc-danger)}
.cc-actions{display:flex;gap:8px;margin-left:auto}
.cc-board{display:flex;gap:14px;min-height:calc(100vh - 200px)}
.cc-col-left{display:flex;flex-direction:column;gap:12px;width:58%;min-width:0}
.cc-col-right{width:42%;display:flex;flex-direction:column;gap:12px;min-width:0}
@media(max-width:1200px){.cc-board{flex-direction:column}.cc-col-left,.cc-col-right{width:100%}}
.fcard{background:var(--cc-card);border:1px solid var(--cc-border);border-radius:var(--cc-r3);box-shadow:var(--cc-shadow);overflow:hidden}
.pc-hero{padding:18px 18px 14px;border-bottom:1px solid var(--cc-border);position:relative;background:#fff}
.pc-hero.deceased{background:linear-gradient(135deg,rgba(255,91,91,0.04) 0%,#fff 60%)}
.pc-hero-accent{position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--cc-primary),var(--cc-purple))}
.pc-hero-accent.danger{background:linear-gradient(90deg,var(--cc-danger),var(--cc-warning))}
.pc-status-badge{position:absolute;top:16px;right:16px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600}
.badge-deceased{background:var(--cc-danger-light);color:var(--cc-danger);border:1px solid rgba(255,91,91,0.2)}
.badge-critical{background:var(--cc-warning-light);color:var(--cc-warning);border:1px solid rgba(253,172,65,0.2)}
.badge-active{background:var(--cc-success-light);color:var(--cc-success);border:1px solid rgba(57,218,138,0.2)}
.pc-top-row{display:flex;align-items:flex-start;gap:14px;margin-bottom:12px}
.pc-avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--cc-primary),var(--cc-purple));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;color:#fff;flex-shrink:0;box-shadow:0 4px 12px rgba(90,141,238,0.3)}
.pc-name{font-size:19px;font-weight:600;color:var(--cc-text-dark);margin-bottom:6px;line-height:1.2}
.pc-pills{display:flex;gap:6px;flex-wrap:wrap}
.pc-pill{padding:3px 10px;border-radius:20px;font-size:11px;background:var(--cc-body);border:1px solid var(--cc-border);color:var(--cc-text-muted);font-weight:500}
.pc-pill.phone-ok{color:var(--cc-success);border-color:rgba(57,218,138,0.3);background:var(--cc-success-light)}
.pc-pill.phone-bad{color:var(--cc-danger);border-color:rgba(255,91,91,0.3);background:var(--cc-danger-light)}
.pc-pill.tag{background:var(--cc-primary-light);color:var(--cc-primary);border-color:rgba(90,141,238,0.2)}
.pc-address{font-size:12px;color:var(--cc-text-muted);display:flex;align-items:center;gap:6px;padding-left:66px;margin-top:4px}
.pc-metrics{display:grid;grid-template-columns:repeat(5,1fr);border-top:1px solid var(--cc-border);border-bottom:1px solid var(--cc-border)}
.pc-metric{padding:12px 8px;text-align:center;border-right:1px solid var(--cc-border)}
.pc-metric:last-child{border-right:none}
.pc-metric .mn{font-size:18px;font-weight:700;color:var(--cc-primary);line-height:1}
.pc-metric .ml{font-size:10px;color:var(--cc-text-muted);margin-top:4px;font-weight:500;text-transform:uppercase;letter-spacing:.5px}
.pc-actions{padding:12px 16px;display:flex;gap:8px;flex-wrap:wrap;background:#fafafa;border-bottom:1px solid var(--cc-border)}
.pac{padding:7px 14px;border-radius:var(--cc-r2);border:1px solid;cursor:pointer;font-size:12px;font-weight:500;display:flex;align-items:center;gap:6px;transition:all .2s}
.pac.success{background:var(--cc-success-light);color:var(--cc-success);border-color:rgba(57,218,138,0.2)}
.pac.success:hover{background:var(--cc-success);color:#fff}
.pac.primary{background:var(--cc-primary-light);color:var(--cc-primary);border-color:rgba(90,141,238,0.2)}
.pac.primary:hover{background:var(--cc-primary);color:#fff}
.pac.warning{background:var(--cc-warning-light);color:var(--cc-warning);border-color:rgba(253,172,65,0.2)}
.pac.warning:hover{background:var(--cc-warning);color:#fff}
.pac.danger{background:var(--cc-danger-light);color:var(--cc-danger);border-color:rgba(255,91,91,0.2)}
.pac.danger:hover{background:var(--cc-danger);color:#fff}
.pac.secondary{background:var(--cc-body);color:var(--cc-text);border-color:var(--cc-border2)}
.pac.secondary:hover{background:var(--cc-primary-light);color:var(--cc-primary);border-color:rgba(90,141,238,0.2)}
.pac i{font-size:11px}
.pc-last-note{padding:10px 16px;font-size:12px;color:var(--cc-text-muted);display:flex;align-items:center;gap:8px;background:#fff}
.pc-last-note .lnote-label{font-weight:600;color:var(--cc-text);white-space:nowrap;flex-shrink:0}
.pc-last-note .lnote-text{font-style:italic;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1}
.tabs-panel{background:var(--cc-card);border:1px solid var(--cc-border);border-radius:var(--cc-r3);box-shadow:var(--cc-shadow);overflow:hidden;flex:1;display:flex;flex-direction:column;min-height:0}
.tabs-nav{display:flex;border-bottom:1px solid var(--cc-border);background:#fafafa;overflow-x:auto;flex-shrink:0}
.tabs-nav::-webkit-scrollbar{height:0}
.tn-btn{padding:12px 16px;background:none;border:none;cursor:pointer;font-size:12px;font-weight:500;color:var(--cc-text-muted);white-space:nowrap;transition:all .2s;border-bottom:2px solid transparent}
.tn-btn:hover{color:var(--cc-primary);background:var(--cc-primary-light)}
.tn-btn.active{color:var(--cc-primary);border-bottom-color:var(--cc-primary);font-weight:600;background:#fff}
.cc-tab-body{display:none;padding:12px;overflow-y:auto;flex:1;min-height:0;max-height:360px}
.cc-tab-body.active{display:block}
.tl-item{padding:12px 14px;background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);margin-bottom:8px;border-left:3px solid var(--cc-primary);transition:all .2s}
.tl-item:hover{box-shadow:var(--cc-shadow-sm)}
.tl-item.critical{border-left-color:var(--cc-danger);background:rgba(255,91,91,0.02)}
.tl-item.warning{border-left-color:var(--cc-warning)}
.tl-title{font-weight:600;font-size:13px;color:var(--cc-text-dark)}
.tl-sub{font-size:12px;color:var(--cc-text-muted);line-height:1.5;margin-top:4px}
.tl-meta{font-size:11px;color:var(--cc-text-light);margin-top:6px;display:flex;gap:12px;flex-wrap:wrap}
.fpill{padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:3px}
.fp-success{background:var(--cc-success-light);color:var(--cc-success)}
.fp-danger{background:var(--cc-danger-light);color:var(--cc-danger)}
.fp-warning{background:var(--cc-warning-light);color:var(--cc-warning)}
.fp-primary{background:var(--cc-primary-light);color:var(--cc-primary)}
.fp-info{background:var(--cc-info-light);color:var(--cc-info)}
.fp-secondary{background:rgba(71,95,123,0.1);color:#475f7b}
.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;flex-shrink:0}
.stat-card{background:var(--cc-card);border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:14px 16px;cursor:pointer;transition:all .2s;box-shadow:var(--cc-shadow-sm)}
.stat-card:hover{box-shadow:var(--cc-shadow);transform:translateY(-1px)}
.stat-card .sc-row{display:flex;justify-content:space-between;align-items:flex-start}
.stat-card .sc-icon{width:38px;height:38px;border-radius:var(--cc-r2);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.stat-card .sc-num{font-size:24px;font-weight:700;line-height:1;margin-top:8px}
.stat-card .sc-label{font-size:11px;color:var(--cc-text-muted);margin-top:4px;font-weight:500}
.sc-success .sc-icon{background:var(--cc-success-light);color:var(--cc-success)}
.sc-success .sc-num{color:var(--cc-success)}
.sc-warning .sc-icon{background:var(--cc-warning-light);color:var(--cc-warning)}
.sc-warning .sc-num{color:var(--cc-warning)}
.sc-primary .sc-icon{background:var(--cc-primary-light);color:var(--cc-primary)}
.sc-primary .sc-num{color:var(--cc-primary)}
.sc-danger .sc-icon{background:var(--cc-danger-light);color:var(--cc-danger)}
.sc-danger .sc-num{color:var(--cc-danger)}
.task-panel{background:var(--cc-card);border:1px solid var(--cc-border);border-radius:var(--cc-r3);box-shadow:var(--cc-shadow);display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}
.tp-head{padding:14px 18px;border-bottom:1px solid var(--cc-border);display:flex;justify-content:space-between;align-items:center;flex-shrink:0;background:#fafafa}
.tp-head h3{font-size:14px;font-weight:600;color:var(--cc-text-dark);display:flex;align-items:center;gap:8px;margin:0}
.tp-head h3 i{color:var(--cc-primary)}
.tp-tabs{display:flex;border-bottom:1px solid var(--cc-border);background:#fafafa;overflow-x:auto;flex-shrink:0}
.tp-tabs::-webkit-scrollbar{height:0}
.tp-tab{padding:10px 14px;background:none;border:none;cursor:pointer;font-size:11px;font-weight:500;color:var(--cc-text-muted);border-bottom:2px solid transparent;white-space:nowrap;transition:all .2s}
.tp-tab.active{color:var(--cc-primary);border-bottom-color:var(--cc-primary);font-weight:600;background:#fff}
.tp-tab:hover{color:var(--cc-primary);background:var(--cc-primary-light)}
.task-list-scroll{flex:1;overflow-y:auto;padding:10px;max-height:450px}
.tk-card{background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:12px 14px;margin-bottom:8px;cursor:pointer;transition:all .2s;border-left:3px solid;position:relative}
.tk-card:hover{box-shadow:var(--cc-shadow-sm);transform:translateX(2px)}
.tk-card.hp{border-left-color:var(--cc-danger)}
.tk-card.mp{border-left-color:var(--cc-warning)}
.tk-card.lp{border-left-color:var(--cc-success)}
.tc-title{font-weight:600;font-size:13px;color:var(--cc-text-dark);line-height:1.3}
.tc-note{font-size:12px;color:var(--cc-text-muted);margin:5px 0 6px;line-height:1.5}
.tc-meta{font-size:11px;color:var(--cc-text-light);display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:4px}
.tc-actions{display:flex;gap:6px;margin-top:10px;border-top:1px solid var(--cc-border);padding-top:8px}
.tca{padding:5px 12px;border-radius:var(--cc-r);border:1px solid;cursor:pointer;font-size:11px;font-weight:500;transition:all .2s;display:flex;align-items:center;gap:4px;background:none}
.tca.success{background:var(--cc-success-light);color:var(--cc-success);border-color:rgba(57,218,138,0.2)}
.tca.success:hover{background:var(--cc-success);color:#fff}
.tca.primary{background:var(--cc-primary-light);color:var(--cc-primary);border-color:rgba(90,141,238,0.2)}
.tca.primary:hover{background:var(--cc-primary);color:#fff}
.tca.warning{background:var(--cc-warning-light);color:var(--cc-warning);border-color:rgba(253,172,65,0.2)}
.tca.warning:hover{background:var(--cc-warning);color:#fff}
.tca.secondary{background:var(--cc-body);color:var(--cc-text);border-color:var(--cc-border)}
.tca.secondary:hover{background:var(--cc-primary-light);color:var(--cc-primary)}
.btn-frest{padding:8px 16px;border-radius:var(--cc-r2);border:none;cursor:pointer;font-size:13px;font-weight:500;display:flex;align-items:center;gap:6px;transition:all .2s;line-height:1}
.btn-frest.primary{background:var(--cc-primary);color:#fff;box-shadow:0 4px 14px rgba(90,141,238,0.4)}
.btn-frest.primary:hover{background:#4a7fe0}
.btn-frest.outline{background:#fff;border:1px solid var(--cc-border2);color:var(--cc-text)}
.btn-frest.outline:hover{background:var(--cc-primary-light);border-color:var(--cc-primary);color:var(--cc-primary)}
.fade-in{animation:fadeIn .25s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}
</style>
@endsection

@section('content')

{{-- ── Topbar: Search + KPIs + Actions ──────────────────── --}}
<div class="cc-topbar">
  <div class="cc-search">
    <i class="fas fa-search"></i>
    <select class="form-control" id="ccPatientSearch" name="patient_id">
      @if($patient)
        <option value="{{ $patient->id }}" selected>{{ $patient->name }} ({{ $patient->register_id ?? $patient->phone }})</option>
      @endif
    </select>
  </div>
  <div class="kpi-chip success" onclick="switchTaskTab('completed')"><span class="kn">{{ $stats['completed'] }}</span> Done Today</div>
  <div class="kpi-chip warning" onclick="switchTaskTab('pending')"><span class="kn">{{ $stats['pending'] }}</span> Pending</div>
  <div class="kpi-chip primary" onclick="switchTaskTab('pending')"><span class="kn">{{ $stats['followup'] }}</span> Follow-up</div>
  <div class="kpi-chip danger" onclick="switchTaskTab('transferred')"><span class="kn">{{ $stats['transferred'] }}</span> Transferred</div>
  <div class="cc-actions">
    <button class="btn-frest outline" onclick="dialAndOpenLogCall(currentPatientId)"><i class="fas fa-phone-alt"></i> Dial &amp; Log</button>
    <button class="btn-frest primary" data-toggle="modal" data-target="#modalNewTask"><i class="fas fa-plus"></i> New Task</button>
  </div>
</div>

{{-- ── Main Board ───────────────────────────────────────── --}}
<div class="cc-board">

  {{-- LEFT: Patient Card + Tabs --}}
  <div class="cc-col-left">

    @if($patient)
    <div class="fcard fade-in" id="patientCardWrap">
      @include('callcenter.board.partials._patient_card', ['patient' => $patient])
    </div>
    @else
    <div class="fcard fade-in" id="patientCardWrap">
      <div style="padding:60px 20px;text-align:center;color:var(--cc-text-light)">
        <i class="fas fa-search" style="font-size:30px;margin-bottom:10px;opacity:.3;display:block"></i>
        <span style="font-size:13px">Search a patient or click a task to begin.</span>
      </div>
    </div>
    @endif

    <div class="tabs-panel">
      <div class="tabs-nav">
        <button class="tn-btn active" onclick="switchProfileTab(this,'tab-appt')">📅 Appointments</button>
        <button class="tn-btn" onclick="switchProfileTab(this,'tab-calls')">📞 Call History</button>
        <button class="tn-btn" onclick="switchProfileTab(this,'tab-lab')">🔬 Lab Reports</button>
        <button class="tn-btn" onclick="switchProfileTab(this,'tab-therapy')">💊 Therapy</button>
        <button class="tn-btn" onclick="switchProfileTab(this,'tab-neb')">🌫️ Nebulize</button>
        <button class="tn-btn" onclick="switchProfileTab(this,'tab-vac')">💉 Vaccination</button>
      </div>
      <div id="tab-appt" class="cc-tab-body active">@include('callcenter.board.partials._tab_appointments')</div>
      <div id="tab-calls" class="cc-tab-body">@include('callcenter.board.partials._tab_calllogs')</div>
      <div id="tab-lab" class="cc-tab-body">@include('callcenter.board.partials._tab_lab')</div>
      <div id="tab-therapy" class="cc-tab-body">@include('callcenter.board.partials._tab_therapy')</div>
      <div id="tab-neb" class="cc-tab-body">@include('callcenter.board.partials._tab_nebulize')</div>
      <div id="tab-vac" class="cc-tab-body">@include('callcenter.board.partials._tab_vaccination')</div>
    </div>
  </div>

  {{-- RIGHT: Stats + Task Panel --}}
  <div class="cc-col-right">

    <div class="stats-grid">
      <div class="stat-card sc-success" onclick="switchTaskTab('completed')">
        <div class="sc-row">
          <div><div class="sc-num">{{ $stats['completed'] }}</div><div class="sc-label">Completed Today</div></div>
          <div class="sc-icon"><i class="fas fa-check-circle"></i></div>
        </div>
      </div>
      <div class="stat-card sc-warning" onclick="switchTaskTab('pending')">
        <div class="sc-row">
          <div><div class="sc-num">{{ $stats['pending'] }}</div><div class="sc-label">Pending Tasks</div></div>
          <div class="sc-icon"><i class="fas fa-clock"></i></div>
        </div>
      </div>
      <div class="stat-card sc-primary" onclick="switchTaskTab('transferred')">
        <div class="sc-row">
          <div><div class="sc-num">{{ $stats['transferred'] }}</div><div class="sc-label">Transferred</div></div>
          <div class="sc-icon"><i class="fas fa-exchange-alt"></i></div>
        </div>
      </div>
      <div class="stat-card sc-danger" onclick="switchTaskTab('pinned')">
        <div class="sc-row">
          <div><div class="sc-num">{{ $tasks['pinned']->count() }}</div><div class="sc-label">Pinned Priority</div></div>
          <div class="sc-icon"><i class="fas fa-thumbtack"></i></div>
        </div>
      </div>
    </div>

    <div class="task-panel">
      <div class="tp-head">
        <h3><i class="fas fa-tasks"></i> Task Manager</h3>
        <button class="btn-frest primary" style="font-size:11px;padding:6px 12px" data-toggle="modal" data-target="#modalNewTask"><i class="fas fa-plus"></i> Add</button>
      </div>
      <div class="tp-tabs">
        <button class="tp-tab active" data-tab="pending">📋 Pending ({{ $tasks['pending']->count() }})</button>
        <button class="tp-tab" data-tab="completed">✅ Done ({{ $tasks['completed']->count() }})</button>
        <button class="tp-tab" data-tab="transferred">🔄 Xfer ({{ $tasks['transferred']->count() }})</button>
        <button class="tp-tab" data-tab="pinned">📌 Pinned</button>
        <button class="tp-tab" data-tab="priority">⚠️ Priority</button>
      </div>

      @foreach(['pending','completed','transferred','pinned','priority'] as $tabKey)
      <div class="task-list-scroll task-tab-body" id="tklist-{{ $tabKey }}" style="{{ $tabKey !== 'pending' ? 'display:none' : '' }}">
        @forelse($tasks[$tabKey] as $task)
          @include('callcenter.board.partials._task_card', ['task' => $task])
        @empty
          <div style="text-align:center;padding:40px 20px;color:var(--cc-text-light)">
            <i class="fas fa-check-double" style="font-size:30px;margin-bottom:10px;opacity:.3;display:block"></i>
            <span style="font-size:13px">No tasks here</span>
          </div>
        @endforelse
      </div>
      @endforeach
    </div>
  </div>
</div>

{{-- ══════════════ MODALS ══════════════ --}}
@include('callcenter.board.partials._modal_log_call')
@include('callcenter.board.partials._modal_new_task')
@include('callcenter.board.partials._modal_transfer')
@include('callcenter.calllogs.history_modal')
@include('callcenter.sms._modal')
@include('callcenter.letters._modal')

@endsection

@section('page-scripts')
{{-- ★ Auto-dial JS (only dialPatient + dialAndOpenLogCall + modal focus fix) --}}
{{-- All form-submit handlers are defined inline in their respective modal blade files --}}
<script src="{{ asset('js/callcenter/board.js') }}"></script>

<script>
var currentPatientId = {{ $patient?->id ?? 'null' }};

// ── Select2 Patient Search ─────────────────────────────────
$('#ccPatientSearch').select2({
    placeholder: 'Search Patient by Name/PID/NID/Phone/Reg',
    width: '100%',
    allowClear: true,
    ajax: {
        url: '{{ url("ajax/get_patient_by_anything") }}',
        data: function (params) { return { term: params.term || '' }; },
        delay: 300,
        processResults: function (data) {
            return { results: $.map(data, function (item) {
                var phone = '', died = '';
                if (item.phone && item.phone2) { phone = ' ' + item.phone + ' / ' + item.phone2; }
                else if (item.phone) { phone = item.phone; }
                else if (item.phone2) { phone = item.phone2; }
                if (item.died == 1) { died = '(Died) '; }
                return { text: died + item.name + ' ' + (item.email || '') + ' (' + (item.register_id || item.id) + ') ' + phone, id: item.id };
            })};
        }
    }
});

$('#ccPatientSearch').on('select2:select', function (e) {
    var patientId = e.params.data.id;
    if (patientId) { window.location.href = '{{ route("callcenter.board") }}?pid=' + patientId; }
});
$('#ccPatientSearch').on('select2:clear', function () {
    window.location.href = '{{ route("callcenter.board") }}';
});

// ── Profile Tabs ───────────────────────────────────────────
function switchProfileTab(btn, tabId) {
    document.querySelectorAll('.tn-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.cc-tab-body').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    var el = document.getElementById(tabId);
    if (el) el.classList.add('active');
}

// ── Task Tabs ──────────────────────────────────────────────
document.querySelectorAll('.tp-tab').forEach(function(btn) {
    btn.addEventListener('click', function() { switchTaskTab(this.dataset.tab); });
});

function switchTaskTab(tab) {
    document.querySelectorAll('.tp-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.task-tab-body').forEach(b => b.style.display = 'none');
    var activeBtn = document.querySelector('.tp-tab[data-tab="'+tab+'"]');
    if (activeBtn) activeBtn.classList.add('active');
    var activeBody = document.getElementById('tklist-'+tab);
    if (activeBody) activeBody.style.display = 'block';
}

// ── Load Patient (AJAX) ────────────────────────────────────
function loadPatient(id) {
    currentPatientId = id;
    $.ajax({
        url: '{{ route("callcenter.patient", ":id") }}'.replace(':id', id),
        type: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function(res) {
            $('#patientCardWrap').html(res.card);
            $('.tabs-panel .cc-tab-body').remove();
            $('.tabs-nav').after(res.tabs);
            document.querySelectorAll('.tn-btn')[0].click();
        },
        error: function() { toastr.error('Failed to load patient data.'); }
    });
}

// ── Task Actions ───────────────────────────────────────────
function completeTask(id) {
    $.post('{{ route("callcenter.tasks.complete", ":id") }}'.replace(':id', id),
        { _token: '{{ csrf_token() }}' }
    ).done(function(res) {
        if (res.success) { toastr.success(res.message || 'Task completed.'); location.reload(); }
        else { toastr.error(res.message || 'Failed to complete task.'); }
    }).fail(function(xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Server error completing task.');
    });
}
function transferTask(id) {
    $('#transferTaskId').val(id);
    $('#modalTransfer').modal('show');
}
function pinTask(id) {
    $.post('{{ route("callcenter.tasks.pin", ":id") }}'.replace(':id', id),
        { _token: '{{ csrf_token() }}' }
    ).done(function(res) {
        if (res.success) { toastr.success(res.pinned ? 'Task pinned.' : 'Task unpinned.'); location.reload(); }
        else { toastr.error(res.message || 'Failed to toggle pin.'); }
    }).fail(function(xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Server error pinning task.');
    });
}

// ── Modals ─────────────────────────────────────────────────
function openLogCall(patientId, taskId) {
    $('#logCallPatientId').val(patientId || currentPatientId);
    $('#logCallTaskId').val(taskId || '');
    window._pendingCallLogId = null;
    $('#modalLogCall').modal('show');
}
function openSmsModal(patientId) { $('#smsPatientId').val(patientId || currentPatientId); $('#modalSms').modal('show'); }
function openLetterModal(patientId) { $('#letterPatientId').val(patientId || currentPatientId); $('#modalLetter').modal('show'); }
function openCallHistory(patientId) {
    $.get('{{ route("callcenter.calllogs.history", ":id") }}'.replace(':id', patientId || currentPatientId),
        function(res) { $('#callHistoryBody').html(res.html); $('#modalCallHistory').modal('show'); }
    );
}
function openNewTaskForPatient(id) { $('#newTaskPatientId').val(id); $('#modalNewTask').modal('show'); }
</script>
@endsection
