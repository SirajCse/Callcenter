@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'My Call Logs')

@section('page-styles')
@include('callcenter.partials._frest_css')
<style>
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
@media(max-width:768px){.stat-row{grid-template-columns:repeat(2,1fr)}}

.filters-card{background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:12px 14px;margin-bottom:12px;box-shadow:var(--cc-shadow-sm)}
.filters-grid{display:grid;grid-template-columns:repeat(4,1fr) auto;gap:8px;align-items:end}
@media(max-width:768px){.filters-grid{grid-template-columns:repeat(2,1fr)}}
.filters-grid .form-control,.filters-grid select,.filters-grid input{height:34px;font-size:12px;border-radius:var(--cc-r2);border:1px solid var(--cc-border2);padding:6px 10px}
.filters-grid .form-control:focus,.filters-grid select:focus,.filters-grid input:focus{border-color:var(--cc-primary);box-shadow:0 0 0 3px rgba(90,141,238,.12);outline:none}

#calllogsTable{font-size:12px;width:100%!important}
#calllogsTable thead th{background:#fafafa;color:var(--cc-text-muted);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:10px 12px;border-bottom:2px solid var(--cc-border);border-top:none}
#calllogsTable tbody td{padding:10px 12px;vertical-align:middle;color:var(--cc-text);border-top:1px solid var(--cc-border)}
#calllogsTable tbody tr:hover td{background:rgba(90,141,238,.03)}

.btn-icon{width:28px;height:28px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;cursor:pointer;transition:all .2s;font-size:11px;text-decoration:none}
.btn-icon.outline{background:rgba(71,95,123,.08);color:var(--cc-text-muted)}
.btn-icon.outline:hover{background:var(--cc-primary-light);color:var(--cc-primary)}
.btn-icon.primary{background:var(--cc-primary-light);color:var(--cc-primary)}
.btn-icon.primary:hover{background:var(--cc-primary);color:#fff}

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
    <h2><i class="fas fa-phone-alt"></i> My Call Logs</h2>
    <a href="{{ route('callcenter.board') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Board</a>
  </div>

  {{-- Stat Cards --}}
  @php
    $allLogs     = \App\Models\PatientCallLog::where('call_by', auth()->id());
    $totalLogs   = (clone $allLogs)->count();
    $answered    = (clone $allLogs)->where('receive', 1)->count();
    $noAnswer    = (clone $allLogs)->where('receive', 0)->count();
    $todayLogs   = (clone $allLogs)->whereDate('call_date', today())->count();
  @endphp
  <div class="stat-row">
    <div class="cc-stat-card primary">
      <div class="sc-icon"><i class="fas fa-phone-alt"></i></div>
      <div class="sc-num">{{ $totalLogs }}</div>
      <div class="sc-label">Total Calls</div>
    </div>
    <div class="cc-stat-card success">
      <div class="sc-icon"><i class="fas fa-phone-volume"></i></div>
      <div class="sc-num">{{ $answered }}</div>
      <div class="sc-label">Answered</div>
    </div>
    <div class="cc-stat-card danger">
      <div class="sc-icon"><i class="fas fa-phone-slash"></i></div>
      <div class="sc-num">{{ $noAnswer }}</div>
      <div class="sc-label">No Answer</div>
    </div>
    <div class="cc-stat-card warning">
      <div class="sc-icon"><i class="fas fa-calendar-day"></i></div>
      <div class="sc-num">{{ $todayLogs }}</div>
      <div class="sc-label">Today</div>
    </div>
  </div>

  {{-- Filters --}}
  <div class="filters-card">
    <form method="GET">
      <div class="filters-grid">
        <div>
          <label class="filter-label">Call Type</label>
          <select name="type" class="form-control">
            <option value="">All</option>
            <option value="outgoing" {{ request('type') === 'outgoing' ? 'selected' : '' }}>Outgoing</option>
            <option value="incoming" {{ request('type') === 'incoming' ? 'selected' : '' }}>Incoming</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Outcome</label>
          <select name="receive" class="form-control">
            <option value="">All</option>
            <option value="1" {{ request('receive') === '1' ? 'selected' : '' }}>Answered</option>
            <option value="0" {{ request('receive') === '0' ? 'selected' : '' }}>No Answer</option>
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
          <a href="{{ route('callcenter.mycalls') }}" class="btn-frest outline sm"><i class="fas fa-times"></i></a>
        </div>
      </div>
    </form>
  </div>

  {{-- Main Card --}}
  <div class="fcard">
    <div class="fcard-head">
      <h3><i class="fas fa-phone-alt"></i> Call History</h3>
      <span class="fpill fp-primary">{{ $logs->total() }} records</span>
    </div>
    <div class="fcard-body" style="padding:8px 12px">
      <div class="table-responsive">
        <table class="table" id="calllogsTable" width="100%">
          <thead>
            <tr>
              <th width="4%">#</th>
              <th width="18%">Patient</th>
              <th width="10%">Type</th>
              <th width="10%">Outcome</th>
              <th width="8%">Duration</th>
              <th width="13%">Date</th>
              <th width="27%">Note</th>
              <th width="10%">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logs as $log)
            <tr>
              <td style="color:var(--cc-text-muted)">{{ $loop->iteration }}</td>
              <td>
                <div class="td-name" style="cursor:pointer;color:var(--cc-primary)"
                  onclick="window.location='{{ route('callcenter.board') }}?pid={{ $log->patient_id }}'">
                  {{ $log->patient?->name ?? '—' }}
                </div>
                <div class="td-sub">{{ $log->patient?->phone ?? '' }}</div>
              </td>
              <td>
                <span class="fpill {{ $log->method === 'incoming' ? 'fp-success' : 'fp-primary' }}">
                  <i class="fas fa-{{ $log->method === 'incoming' ? 'phone-volume' : 'phone-alt' }}" style="font-size:9px"></i>
                  {{ ucfirst($log->method ?? '—') }}
                </span>
              </td>
              <td>
                <span class="fpill {{ $log->receive ? 'fp-success' : 'fp-danger' }}">
                  <i class="fas fa-{{ $log->receive ? 'check' : 'times' }}" style="font-size:9px"></i>
                  {{ $log->receive ? 'Answered' : 'No Answer' }}
                </span>
                @if($log->die)
                <div style="margin-top:3px"><span class="fpill fp-danger"><i class="fas fa-skull" style="font-size:9px"></i> Deceased</span></div>
                @endif
              </td>
              <td>
                <span style="font-size:12px;color:var(--cc-text-muted);font-family:monospace">{{ gmdate('i:s', $log->duration ?? 0) }}</span>
              </td>
              <td>
                <div style="font-size:12px;font-weight:500">{{ \Carbon\Carbon::parse($log->call_date)->format('d M Y') }}</div>
                <div class="td-sub">{{ \Carbon\Carbon::parse($log->call_date)->format('h:i A') }}</div>
              </td>
              <td>
                <div style="font-size:12px;color:var(--cc-text-muted)">{{ Str::limit($log->call_note ?? $log->caller_opinion ?? '—', 70) }}</div>
                @if($log->followup_target_date)
                <div style="font-size:10px;color:var(--cc-primary);margin-top:3px">
                  <i class="fas fa-calendar-check" style="font-size:9px"></i>
                  FU: {{ \Carbon\Carbon::parse($log->followup_target_date)->format('d M Y') }}
                </div>
                @endif
              </td>
              <td>
                <div style="display:flex;gap:4px">
                  <a href="{{ route('callcenter.board') }}?pid={{ $log->patient_id }}" class="btn-icon primary" title="Open in Board"><i class="fas fa-phone"></i></a>
                  <button class="btn-icon outline" onclick="openCallHistory({{ $log->patient_id }})" title="Full History"><i class="fas fa-history"></i></button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8">
                <div class="cc-empty"><i class="fas fa-phone-slash"></i><span>No call logs found</span></div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="padding:10px 0 4px">{{ $logs->withQueryString()->links() }}</div>
    </div>
  </div>
</div>

@include('callcenter.calllogs.history_modal')
@endsection

@section('page-scripts')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#calllogsTable')) $('#calllogsTable').DataTable().destroy();
    $('#calllogsTable').DataTable({
        paging: false,
        searching: true,
        ordering: true,
        info: false,
        language: {search: '', searchPlaceholder: 'Search call logs...'},
        columnDefs: [{orderable: false, targets: [7]}]
    });
});

function openCallHistory(patientId) {
    $.get('{{ url("callcenter/calllogs/history") }}/' + patientId, function(res) {
        $('#callHistoryBody').html(res.html);
        $('#modalCallHistory').modal('show');
    });
}
</script>
@endsection
