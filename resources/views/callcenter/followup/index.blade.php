@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'Follow-Up List')

@section('page-styles')
@include('callcenter.partials._frest_css')
<style>
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
@media(max-width:768px){.stat-row{grid-template-columns:repeat(2,1fr)}}

.filters-card{background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:12px 14px;margin-bottom:12px;box-shadow:var(--cc-shadow-sm)}
.filters-grid{display:grid;grid-template-columns:repeat(5,1fr) auto;gap:8px;align-items:end}
@media(max-width:992px){.filters-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:576px){.filters-grid{grid-template-columns:repeat(2,1fr)}}
.filters-grid .form-control,.filters-grid select,.filters-grid input{height:34px;font-size:12px;border-radius:var(--cc-r2);border:1px solid var(--cc-border2);padding:6px 10px}
.filters-grid .form-control:focus,.filters-grid select:focus,.filters-grid input:focus{border-color:var(--cc-primary);box-shadow:0 0 0 3px rgba(90,141,238,.12);outline:none}

.bulk-bar{background:var(--cc-primary-light);border:1px solid rgba(90,141,238,.2);border-radius:var(--cc-r2);padding:10px 14px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;font-size:12px}

#followupTable{font-size:12px;width:100%!important}
#followupTable thead th{background:#fafafa;color:var(--cc-text-muted);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:10px 12px;border-bottom:2px solid var(--cc-border);border-top:none}
#followupTable tbody td{padding:10px 12px;vertical-align:middle;color:var(--cc-text);border-top:1px solid var(--cc-border)}
#followupTable tbody tr:hover td{background:rgba(90,141,238,.03)}

.action-bar{display:flex;gap:4px}
.btn-icon{width:28px;height:28px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;cursor:pointer;transition:all .2s;font-size:11px;text-decoration:none}
.btn-icon.primary{background:var(--cc-primary-light);color:var(--cc-primary)}
.btn-icon.primary:hover{background:var(--cc-primary);color:#fff}
.btn-icon.outline{background:rgba(71,95,123,.08);color:var(--cc-text-muted)}
.btn-icon.outline:hover{background:var(--cc-primary-light);color:var(--cc-primary)}

.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input{height:32px;border-radius:var(--cc-r2);border:1px solid var(--cc-border2);padding:4px 10px;font-size:12px}
.dataTables_wrapper .dataTables_paginate .paginate_button{padding:5px 10px;border-radius:var(--cc-r);font-size:12px}
.dataTables_wrapper .dataTables_paginate .paginate_button.current{background:var(--cc-primary)!important;border-color:var(--cc-primary)!important;color:#fff!important}
.dataTables_wrapper .dataTables_info,.dataTables_wrapper label{font-size:11px;color:var(--cc-text-muted)}
</style>
@endsection

@section('content')
<div class="fade-in">

  {{-- Module Header --}}
  <div class="module-head">
    <h2><i class="fas fa-redo-alt"></i> Follow-Up Patient List</h2>
    <a href="{{ route('callcenter.board') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Board</a>
  </div>

  {{-- Stat Cards --}}
  @php
    $totalPatients = $patients->total();
    $notCalled     = $patients->getCollection()->filter(fn($p) => !$p->latestCallLog)->count();
    $withPhone     = $patients->getCollection()->filter(fn($p) => $p->phone && $p->phone !== 'INVALID')->count();
    $noPhone       = $patients->getCollection()->filter(fn($p) => !$p->phone || $p->phone === 'INVALID')->count();
  @endphp
  <div class="stat-row">
    <div class="cc-stat-card primary">
      <div class="sc-icon"><i class="fas fa-users"></i></div>
      <div class="sc-num">{{ $totalPatients }}</div>
      <div class="sc-label">Total Patients</div>
    </div>
    <div class="cc-stat-card warning">
      <div class="sc-icon"><i class="fas fa-phone-slash"></i></div>
      <div class="sc-num">{{ $notCalled }}</div>
      <div class="sc-label">Not Called (page)</div>
    </div>
    <div class="cc-stat-card success">
      <div class="sc-icon"><i class="fas fa-phone"></i></div>
      <div class="sc-num">{{ $withPhone }}</div>
      <div class="sc-label">Has Phone</div>
    </div>
    <div class="cc-stat-card danger">
      <div class="sc-icon"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="sc-num">{{ $noPhone }}</div>
      <div class="sc-label">No Phone</div>
    </div>
  </div>

  {{-- Filters --}}
  <div class="filters-card">
    <form method="GET" id="fuFilterForm">
      <div class="filters-grid">
        <div>
          <label class="filter-label">Agent</label>
          <select name="agent_id" class="form-control">
            <option value="">All Agents</option>
            @foreach($agents as $ag)
            <option value="{{ $ag->id }}" {{ request('agent_id') == $ag->id ? 'selected' : '' }}>{{ $ag->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="filter-label">Status</label>
          <select name="status" class="form-control">
            <option value="">All</option>
            <option value="not_called"      {{ request('status') === 'not_called'      ? 'selected' : '' }}>Not Called</option>
            <option value="callback_needed" {{ request('status') === 'callback_needed' ? 'selected' : '' }}>Callback Needed</option>
            <option value="busy"            {{ request('status') === 'busy'            ? 'selected' : '' }}>Busy</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Priority</label>
          <select name="priority" class="form-control">
            <option value="">All</option>
            <option value="high"   {{ request('priority') === 'high'   ? 'selected' : '' }}>High</option>
            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
            <option value="low"    {{ request('priority') === 'low'    ? 'selected' : '' }}>Low</option>
          </select>
        </div>
        <div>
          <label class="filter-label">From</label>
          <input type="date" name="from" class="form-control" value="{{ request('from') }}">
        </div>
        <div>
          <label class="filter-label">To</label>
          <input type="date" name="to" class="form-control" value="{{ request('to') }}">
        </div>
        <div style="display:flex;gap:6px">
          <button type="submit" class="btn-frest primary sm"><i class="fas fa-filter"></i> Filter</button>
          <a href="{{ route('callcenter.followup.index') }}" class="btn-frest outline sm"><i class="fas fa-times"></i></a>
        </div>
      </div>
    </form>
  </div>

  {{-- Bulk Action Bar --}}
  <div class="bulk-bar">
    <div style="display:flex;align-items:center;gap:8px">
      <input type="checkbox" id="chkSelectAll" onchange="toggleAll(this)" style="accent-color:var(--cc-primary);width:14px;height:14px">
      <span><strong id="selectedCount">0</strong> patients selected</span>
    </div>
    <div style="display:flex;gap:6px">
      <button class="btn-frest success sm" onclick="saveAsToday()"><i class="fas fa-calendar-plus"></i> Add as Today's Tasks</button>
      <button class="btn-frest info sm" onclick="bulkSms()"><i class="fas fa-sms"></i> Bulk SMS</button>
    </div>
  </div>

  {{-- Main Card --}}
  <div class="fcard">
    <div class="fcard-head">
      <h3><i class="fas fa-redo-alt"></i> Follow-Up Patients</h3>
      <span class="fpill fp-primary">{{ $patients->total() }} total</span>
    </div>
    <div class="fcard-body" style="padding:8px 12px">
      <div class="table-responsive">
        <table class="table" id="followupTable" width="100%">
          <thead>
            <tr>
              <th width="3%" class="text-center"><input type="checkbox" id="chkHeader" onchange="toggleAll(this)" style="accent-color:var(--cc-primary)"></th>
              <th width="20%">Patient</th>
              <th width="14%">Phone</th>
              <th width="10%">Last Call</th>
              <th width="7%">Calls</th>
              <th width="22%">Last Note</th>
              <th width="14%">Other Agent</th>
              <th width="10%">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($patients as $patient)
            @php
              $lastLog   = $patient->latestCallLog;
              $otherCall = \App\Models\PatientCallLog::where('patient_id', $patient->id)
                  ->where('call_by', '!=', auth()->id())->latest('call_date')->first();
              $phoneOk   = $patient->phone && $patient->phone !== 'INVALID';
            @endphp
            <tr>
              <td class="text-center"><input type="checkbox" class="fu-chk" value="{{ $patient->id }}" onchange="updateCount()" style="accent-color:var(--cc-primary);width:14px;height:14px"></td>
              <td>
                <div class="td-name">{{ $patient->name }}</div>
                <div class="td-sub">{{ $patient->register_id ?? 'ID:'.$patient->id }} · {{ Str::limit($patient->address ?? '', 35) }}</div>
              </td>
              <td>
                <span class="fpill {{ $phoneOk ? 'fp-success' : 'fp-danger' }}">
                  <i class="fas fa-phone" style="font-size:9px"></i>
                  {{ $patient->phone ?? 'N/A' }}
                </span>
              </td>
              <td style="font-size:11px;color:var(--cc-text-muted)">
                {{ $lastLog ? \Carbon\Carbon::parse($lastLog->call_date)->format('d M Y') : '—' }}
              </td>
              <td>
                <span class="fpill fp-secondary">{{ $patient->call_count ?? 0 }}</span>
              </td>
              <td style="font-size:11px;color:var(--cc-text-muted);max-width:200px">
                {{ Str::limit($lastLog?->call_note ?? $lastLog?->caller_opinion ?? '—', 65) }}
              </td>
              <td>
                @if($otherCall)
                <div style="font-size:11px;color:var(--cc-warning);font-weight:500">
                  <i class="fas fa-user" style="font-size:9px"></i> {{ $otherCall->caller?->name }}
                </div>
                <div style="font-size:10px;color:var(--cc-text-light)">{{ \Carbon\Carbon::parse($otherCall->call_date)->format('d M Y') }}</div>
                @else
                <span style="color:var(--cc-text-light);font-size:11px">—</span>
                @endif
              </td>
              <td>
                <div class="action-bar">
                  <a href="{{ route('callcenter.board') }}?pid={{ $patient->id }}" class="btn-icon primary" title="Open in Board"><i class="fas fa-phone"></i></a>
                  <button class="btn-icon outline" onclick="openCallHistory({{ $patient->id }})" title="Call History"><i class="fas fa-history"></i></button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8">
                <div class="cc-empty"><i class="fas fa-redo-alt"></i><span>No follow-up patients found</span></div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="padding:10px 0 4px">{{ $patients->withQueryString()->links() }}</div>
    </div>
  </div>
</div>

@include('callcenter.calllogs.history_modal')
@endsection

@section('page-scripts')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#followupTable')) $('#followupTable').DataTable().destroy();
    $('#followupTable').DataTable({
        paging: false,
        searching: true,
        ordering: true,
        info: false,
        language: {search: '', searchPlaceholder: 'Search patients...'},
        columnDefs: [{orderable: false, targets: [0, 7]}]
    });
});

function toggleAll(cb) {
    document.querySelectorAll('.fu-chk').forEach(c => c.checked = cb.checked);
    updateCount();
}
function updateCount() {
    const n = document.querySelectorAll('.fu-chk:checked').length;
    document.getElementById('selectedCount').textContent = n;
    document.getElementById('chkSelectAll').checked = n === document.querySelectorAll('.fu-chk').length && n > 0;
}
function saveAsToday() {
    var ids = Array.from(document.querySelectorAll('.fu-chk:checked')).map(c => c.value);
    if (!ids.length) { toastr.warning('Please select at least one patient.'); return; }
    $.post('{{ route("callcenter.followup.savetoday") }}', {_token: '{{ csrf_token() }}', patient_ids: ids},
        function(res) {
            if (res.success) {
                toastr.success(res.message);
                document.querySelectorAll('.fu-chk').forEach(c => c.checked = false);
                updateCount();
            }
        }).fail(function(xhr) { toastr.error(xhr.responseJSON?.message ?? 'Error saving tasks.'); });
}
function bulkSms() {
    var ids = Array.from(document.querySelectorAll('.fu-chk:checked')).map(c => c.value);
    if (!ids.length) { toastr.warning('Please select at least one patient.'); return; }
    toastr.info(ids.length + ' patient(s) selected for bulk SMS.');
}
function openCallHistory(patientId) {
    $.get('{{ url("callcenter/calllogs/history") }}/' + patientId, function(res) {
        $('#callHistoryBody').html(res.html);
        $('#modalCallHistory').modal('show');
    });
}
</script>
@endsection
