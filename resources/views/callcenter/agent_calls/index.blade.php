@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'My PBX Call Log')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="fade-in">

  {{-- ★ Compact Topbar with PBX call stats --}}
  <div class="cc-topbar">
    <div class="kpi-chip primary"><span class="kn">{{ $stats['total'] ?? 0 }}</span> Total Calls</div>
    <div class="kpi-chip success"><span class="kn">{{ $stats['answered'] ?? 0 }}</span> Answered</div>
    <div class="kpi-chip danger"><span class="kn">{{ $stats['missed'] ?? 0 }}</span> Missed</div>
    <div class="kpi-chip warning"><span class="kn">{{ $stats['today'] ?? 0 }}</span> Today</div>
    <div class="kpi-chip info"><span class="kn">{{ $stats['outbound'] ?? 0 }}</span> Outbound</div>
    <div class="kpi-chip info"><span class="kn">{{ $stats['inbound'] ?? 0 }}</span> Inbound</div>
    <div class="cc-actions">
      <a href="{{ route('callcenter.board') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Board</a>
    </div>
  </div>

  @if(isset($error))
  <div class="fcard" style="margin-bottom:12px">
    <div class="cc-empty">
      <i class="fas fa-exclamation-triangle"></i>
      <span>{{ $error }}</span>
    </div>
  </div>
  @endif

  {{-- ★ Filters --}}
  <div class="filters-card">
    <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr)) auto;gap:10px;align-items:end">
      <div>
        <label class="filter-label">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
      </div>
      <div>
        <label class="filter-label">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
      </div>
      <div>
        <label class="filter-label">Direction</label>
        <select name="direction" class="form-control form-control-sm">
          <option value="">All</option>
          <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Outbound</option>
          <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Inbound</option>
        </select>
      </div>
      <div>
        <label class="filter-label">Status</label>
        <select name="status" class="form-control form-control-sm">
          <option value="">All</option>
          <option value="answered" {{ request('status') === 'answered' ? 'selected' : '' }}>Answered</option>
          <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Ended</option>
          <option value="missed" {{ request('status') === 'missed' ? 'selected' : '' }}>Missed</option>
          <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
      </div>
      <div>
        <label class="filter-label">Phone</label>
        <input type="text" name="phone" value="{{ request('phone') }}" class="form-control form-control-sm" placeholder="Search phone...">
      </div>
      <div style="display:flex;gap:6px">
        <button class="btn-frest primary sm"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('callcenter.agent-calls.index') }}" class="btn-frest outline sm"><i class="fas fa-undo"></i></a>
      </div>
    </form>
  </div>

  {{-- ★ Call Log Table --}}
  <div class="fcard">
    <div class="fcard-head">
      <h3><i class="fas fa-phone-alt"></i> PBX Call Log — Extension {{ $agent->pbx_extension ?? 'N/A' }}</h3>
      <span class="fpill fp-secondary">{{ $calls->total() }} calls</span>
    </div>
    <div class="fcard-body" style="padding:8px 12px">
      <div class="table-responsive">
        <table class="dtable" width="100%">
          <thead>
            <tr>
              <th width="12%">Date / Time</th>
              <th width="6%">Dir</th>
              <th width="15%">From → To</th>
              <th width="15%">Patient</th>
              <th width="10%">Status</th>
              <th width="6%">Duration</th>
              <th width="10%">Linked Task</th>
              <th width="8%">Recording</th>
              <th width="8%">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($calls as $call)
              @php
                // Determine the "outside" number (patient's phone)
                $outsideNumber = ($call->direction === 'inbound') ? $call->caller : $call->callee;
                $insideNumber  = ($call->direction === 'inbound') ? $call->callee : $call->caller;

                // Status pill
                $statusPill = match($call->status ?? '') {
                  'answered' => 'fp-success',
                  'ended'    => 'fp-primary',
                  'missed'   => 'fp-danger',
                  'failed'   => 'fp-danger',
                  default    => 'fp-secondary',
                };

                // Format duration
                $dur = $call->billsec ?? $call->duration ?? 0;
                $durFormatted = $dur > 0 ? gmdate('i:s', $dur) : '0:00';

                // Call date for task matching
                $callDate = substr($call->started_at ?? '', 0, 10);
              @endphp
            <tr>
              {{-- Date / Time --}}
              <td>
                <div style="font-size:12px;font-weight:600;color:var(--cc-text-dark)">
                  {{ $call->started_at ? \Carbon\Carbon::parse($call->started_at)->format('d M Y') : '—' }}
                </div>
                <div class="td-sub">
                  {{ $call->started_at ? \Carbon\Carbon::parse($call->started_at)->format('h:i A') : '' }}
                </div>
              </td>

              {{-- Direction --}}
              <td>
                @if($call->direction === 'outbound')
                  <span class="fpill fp-primary"><i class="fas fa-arrow-up" style="font-size:9px"></i> Out</span>
                @elseif($call->direction === 'inbound')
                  <span class="fpill fp-success"><i class="fas fa-arrow-down" style="font-size:9px"></i> In</span>
                @else
                  <span class="fpill fp-secondary">{{ $call->direction ?? '—' }}</span>
                @endif
              </td>

              {{-- From → To --}}
              <td>
                <div style="font-size:12px">
                  <span style="color:var(--cc-text)">{{ $call->caller ?? '—' }}</span>
                  <i class="fas fa-arrow-right" style="font-size:9px;margin:0 4px;color:var(--cc-text-light)"></i>
                  <span style="font-weight:600;color:var(--cc-text-dark)">{{ $call->callee ?? '—' }}</span>
                </div>
                <div class="td-sub">Ext: {{ $call->extension ?? '—' }}</div>
              </td>

              {{-- Patient (matched) --}}
              <td>
                @if($call->matched_patient)
                  <div class="td-name">
                    <a href="{{ route('callcenter.board') }}?pid={{ $call->matched_patient->id }}" style="color:var(--cc-primary)">
                      {{ $call->matched_patient->name }}
                    </a>
                  </div>
                  <div class="td-sub">
                    {{ $call->matched_patient->register_id ?? 'ID:'.$call->matched_patient->id }}
                    @if($call->matched_patient->died) <span class="fpill fp-danger" style="font-size:9px">Died</span> @endif
                  </div>
                @else
                  {{-- Unknown number — show the phone with a "tag" option --}}
                  <div style="font-size:12px;color:var(--cc-text-muted)">
                    <i class="fas fa-user-slash" style="font-size:10px;margin-right:3px"></i>
                    Unknown
                  </div>
                  <div class="td-sub">
                    {{ $outsideNumber ?? '—' }}
                    <a href="{{ route('callcenter.board') }}?phone={{ urlencode($outsideNumber) }}" class="btn-icon outline" style="margin-left:4px;width:20px;height:20px;font-size:9px" title="Search patient">
                      <i class="fas fa-search"></i>
                    </a>
                  </div>
                @endif
              </td>

              {{-- Status --}}
              <td>
                <span class="fpill {{ $statusPill }}">{{ ucfirst($call->status ?? '—') }}</span>
                @if($call->cause)
                <div class="td-sub">{{ \Illuminate\Support\Str::limit($call->cause, 25) }}</div>
                @endif
              </td>

              {{-- Duration --}}
              <td>
                <span style="font-size:12px;font-weight:600;color:{{ $dur > 0 ? 'var(--cc-text-dark)' : 'var(--cc-text-light)' }}">
                  {{ $durFormatted }}
                </span>
                @if($call->billsec && $call->billsec > 0)
                <div class="td-sub">bill: {{ gmdate('i:s', $call->billsec) }}</div>
                @endif
              </td>

              {{-- Linked Task --}}
              <td>
                @if($call->linked_task)
                  <a href="{{ route('callcenter.tasks.index', ['tab' => $call->linked_task->status]) }}" style="color:var(--cc-primary);font-size:12px;font-weight:600">
                    {{ \Illuminate\Support\Str::limit($call->linked_task->title, 20) }}
                  </a>
                  <div class="td-sub">
                    <span class="fpill {{ $call->linked_task->priority === 'high' ? 'fp-danger' : ($call->linked_task->priority === 'medium' ? 'fp-warning' : 'fp-success') }}" style="font-size:9px">
                      {{ $call->linked_task->priority }}
                    </span>
                    <span class="fpill {{ $call->linked_task->status === 'completed' ? 'fp-success' : 'fp-warning' }}" style="font-size:9px">{{ $call->linked_task->status }}</span>
                  </div>
                @else
                  <span style="color:var(--cc-text-light);font-size:11px">No task</span>
                @endif
              </td>

              {{-- Recording --}}
              <td>
                @if($call->recording_url)
                  <a href="{{ config('mikopbx.url', '') . $call->recording_url }}" target="_blank" class="btn-icon success" title="Play recording">
                    <i class="fas fa-play"></i>
                  </a>
                @else
                  <span style="color:var(--cc-text-light);font-size:11px">—</span>
                @endif
              </td>

              {{-- Actions --}}
              <td>
                <div class="action-bar">
                  @if($call->matched_patient)
                  <a href="{{ route('callcenter.board') }}?pid={{ $call->matched_patient->id }}" class="btn-icon primary" title="Open in Board">
                    <i class="fas fa-eye"></i>
                  </a>
                  @endif
                  @if($call->matched_patient && (!$call->linked_task || $call->linked_task->status === 'pending'))
                  <button class="btn-icon success" onclick="openLogCall({{ $call->matched_patient->id }})" title="Log this call">
                    <i class="fas fa-phone"></i>
                  </button>
                  @endif
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="9">
                <div class="cc-empty">
                  <i class="fas fa-phone-slash"></i>
                  <span>No PBX calls found{{ isset($error) ? ' — '.$error : '' }}</span>
                </div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div style="padding:10px 0 4px">{{ $calls->withQueryString()->links() }}</div>
    </div>
  </div>

</div>

{{-- Include the Log Call modal (for the "Log this call" button) --}}
@include('callcenter.board.partials._modal_log_call')
@endsection

@section('page-scripts')
@include('callcenter.partials._frest_js_init')
<script src="{{ asset('js/callcenter/board.js') }}"></script>
<script>
// Reuse the board's openLogCall function
function openLogCall(patientId, taskId) {
    if (typeof window.openLogCall === 'function' && window.openLogCall !== openLogCall) {
        window.openLogCall(patientId, taskId);
        return;
    }
    $('#logCallPatientId').val(patientId || '');
    $('#logCallTaskId').val(taskId || '');
    window._pendingCallLogId = null;
    $('#modalLogCall').modal('show');
}
window.openLogCall = openLogCall;
</script>
@endsection
