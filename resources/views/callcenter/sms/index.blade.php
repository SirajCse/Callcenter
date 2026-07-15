@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'SMS Log')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="fade-in">

  {{-- ★ Compact Topbar: KPI chips + actions (saves vertical space) --}}
  <div class="cc-topbar">
    <div class="kpi-chip primary"><span class="kn">{{ $stats['total'] ?? 0 }}</span> Total SMS</div>
    <div class="kpi-chip success"><span class="kn">{{ $stats['sent'] ?? 0 }}</span> Sent</div>
    <div class="kpi-chip danger"><span class="kn">{{ $stats['failed'] ?? 0 }}</span> Failed</div>
    <div class="kpi-chip warning"><span class="kn">{{ $stats['pending'] ?? 0 }}</span> Pending</div>
    <div class="cc-actions">
      <button class="btn-frest info sm" data-toggle="modal" data-target="#modalSms"><i class="fas fa-plus"></i> Send SMS</button>
    </div>
  </div>

  {{-- Filters --}}
  <div class="filters-card">
    <form method="GET">
      <div class="filters-grid">
        <div>
          <label class="filter-label">Status</label>
          <select name="status" class="form-control">
            <option value="">All</option>
            <option value="sent"      {{ request('status') === 'sent'      ? 'selected' : '' }}>Sent</option>
            <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
            <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Failed</option>
            <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Callback</label>
          <select name="callback" class="form-control">
            <option value="">All</option>
            <option value="1" {{ request('callback') === '1' ? 'selected' : '' }}>Received</option>
            <option value="0" {{ request('callback') === '0' ? 'selected' : '' }}>Not Received</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Search Patient</label>
          <input type="text" name="search" class="form-control" placeholder="Patient name / phone" value="{{ request('search') }}">
        </div>
        <div style="display:flex;gap:6px">
          <button type="submit" class="btn-frest primary sm"><i class="fas fa-filter"></i> Filter</button>
          <a href="{{ route('callcenter.sms.index') }}" class="btn-frest outline sm"><i class="fas fa-times"></i></a>
        </div>
      </div>
    </form>
  </div>

  {{-- Main Card --}}
  <div class="fcard">
    <div class="fcard-head">
      <h3><i class="fas fa-comment-alt"></i> SMS Messages</h3>
      <span class="fpill fp-primary">{{ $logs->total() }} records</span>
    </div>
    <div class="fcard-body" style="padding:8px 12px">
      <div class="table-responsive">
        <table class="table" id="smsTable" width="100%">
          <thead>
            <tr>
              <th width="4%">#</th>
              <th width="16%">Patient</th>
              <th width="12%">Phone</th>
              <th width="28%">Message</th>
              <th width="9%">Status</th>
              <th width="9%">Callback</th>
              <th width="10%">Resend</th>
              <th width="12%">Sent At</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logs as $log)
            <tr>
              <td style="color:var(--cc-text-muted)">{{ $loop->iteration }}</td>
              <td>
                <div class="td-name">{{ $log->patient?->name ?? '—' }}</div>
                <div class="td-sub">{{ $log->patient?->register_id ?? '' }}</div>
              </td>
              <td style="font-size:12px;font-family:monospace">{{ $log->phone_number }}</td>
              <td>
                <div style="font-size:12px;color:var(--cc-text-muted)">{{ Str::limit($log->message, 90) }}</div>
                @if($log->template_key)
                <div style="margin-top:3px"><span class="fpill fp-secondary" style="font-size:10px">{{ $log->template_key }}</span></div>
                @endif
              </td>
              <td>
                <span class="fpill {{ $statusPillClasses[$log->status] ?? 'fp-secondary' }}">{{ ucfirst($log->status) }}</span>
              </td>
              <td>
                @if($log->is_callback_received)
                  <span class="fpill fp-success"><i class="fas fa-check" style="font-size:9px"></i> Yes</span>
                @else
                  <span class="fpill fp-secondary">No</span>
                @endif
              </td>
              <td>
                <button class="btn-icon info" onclick="resendSms({{ $log->id }}, this)" title="Resend">
                  <i class="fas fa-redo"></i>
                </button>
                @if($log->resend_count > 0)
                <span style="font-size:10px;color:var(--cc-text-muted);margin-left:4px">×{{ $log->resend_count }}</span>
                @endif
              </td>
              <td>
                <div style="font-size:11px;color:var(--cc-text-muted)">
                  {{ $log->sent_at ? $log->sent_at->format('d M Y') : '—' }}
                </div>
                @if($log->sent_at)
                <div class="td-sub">{{ $log->sent_at->format('h:i A') }}</div>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8">
                <div class="cc-empty"><i class="fas fa-comment-slash"></i><span>No SMS logs found</span></div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="padding:10px 0 4px">{{ $logs->links() }}</div>
    </div>
  </div>
</div>

<input type="hidden" id="smsPatientId">
@include('callcenter.sms._modal')
@endsection

@section('page-scripts')
@include('callcenter.partials._frest_js_init')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#smsTable')) $('#smsTable').DataTable().destroy();
    $('#smsTable').DataTable({
        paging: false,
        searching: true,
        ordering: true,
        info: false,
        language: {search: '', searchPlaceholder: 'Search SMS...'},
        columnDefs: [{orderable: false, targets: [6]}]
    });
});

function resendSms(id, btn) {
    $(btn).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $.post('{{ route("callcenter.sms.resend", ["sms" => "__ID__"]) }}'.replace('__ID__', id))
        .done(function(res) {
            if (res.success) { toastr.success('SMS resent.'); location.reload(); }
        }).fail(function() {
            toastr.error('Resend failed.');
            $(btn).prop('disabled', false).html('<i class="fas fa-redo"></i>');
        });
}
</script>
@endsection
