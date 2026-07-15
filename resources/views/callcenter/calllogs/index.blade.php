@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'My Call Logs')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="fade-in">

  {{-- ★ Compact Topbar: KPI chips + actions (saves vertical space) --}}
  <div class="cc-topbar">
    <div class="kpi-chip primary"><span class="kn">{{ $stats['total'] ?? 0 }}</span> Total Calls</div>
    <div class="kpi-chip success"><span class="kn">{{ $stats['answered'] ?? 0 }}</span> Answered</div>
    <div class="kpi-chip danger"><span class="kn">{{ $stats['no_answer'] ?? 0 }}</span> No Answer</div>
    <div class="kpi-chip warning"><span class="kn">{{ $stats['today'] ?? 0 }}</span> Today</div>
    <div class="cc-actions">
      <a href="{{ route('callcenter.board') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Board</a>
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
@include('callcenter.partials._frest_js_init')
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
    $.get('{{ route("callcenter.calllogs.history", ["patientId" => "__ID__"]) }}'.replace('__ID__', patientId), function(res) {
        $('#callHistoryBody').html(res.html);
        $('#modalCallHistory').modal('show');
    });
}
</script>
@endsection
