@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'SMS Log')

@section('page-styles')
@include('callcenter.partials._frest_css')
<style>
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
@media(max-width:768px){.stat-row{grid-template-columns:repeat(2,1fr)}}

.filters-card{background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:12px 14px;margin-bottom:12px;box-shadow:var(--cc-shadow-sm)}
.filters-grid{display:grid;grid-template-columns:repeat(3,1fr) auto;gap:8px;align-items:end}
@media(max-width:768px){.filters-grid{grid-template-columns:repeat(2,1fr)}}
.filters-grid .form-control,.filters-grid select,.filters-grid input{height:34px;font-size:12px;border-radius:var(--cc-r2);border:1px solid var(--cc-border2);padding:6px 10px}
.filters-grid .form-control:focus,.filters-grid select:focus,.filters-grid input:focus{border-color:var(--cc-primary);box-shadow:0 0 0 3px rgba(90,141,238,.12);outline:none}

#smsTable{font-size:12px;width:100%!important}
#smsTable thead th{background:#fafafa;color:var(--cc-text-muted);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:10px 12px;border-bottom:2px solid var(--cc-border);border-top:none}
#smsTable tbody td{padding:10px 12px;vertical-align:middle;color:var(--cc-text);border-top:1px solid var(--cc-border)}
#smsTable tbody tr:hover td{background:rgba(90,141,238,.03)}

.btn-icon{width:28px;height:28px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;cursor:pointer;transition:all .2s;font-size:11px}
.btn-icon.info{background:var(--cc-info-light);color:var(--cc-info)}
.btn-icon.info:hover{background:var(--cc-info);color:#fff}

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
    <h2><i class="fas fa-comment-alt"></i> SMS Log</h2>
    <button class="btn-frest info sm" data-toggle="modal" data-target="#modalSms">
      <i class="fas fa-plus"></i> Send SMS
    </button>
  </div>

  {{-- Stat Cards --}}
  @php
    $allSms    = \App\Models\CallCenter\SmsLog::query();
    $totalSms  = (clone $allSms)->count();
    $sentSms   = (clone $allSms)->where('status','sent')->count();
    $failedSms = (clone $allSms)->where('status','failed')->count();
    $pendSms   = (clone $allSms)->where('status','pending')->count();
  @endphp
  <div class="stat-row">
    <div class="cc-stat-card primary">
      <div class="sc-icon"><i class="fas fa-comment-alt"></i></div>
      <div class="sc-num">{{ $totalSms }}</div>
      <div class="sc-label">Total SMS</div>
    </div>
    <div class="cc-stat-card success">
      <div class="sc-icon"><i class="fas fa-check-circle"></i></div>
      <div class="sc-num">{{ $sentSms }}</div>
      <div class="sc-label">Sent</div>
    </div>
    <div class="cc-stat-card danger">
      <div class="sc-icon"><i class="fas fa-times-circle"></i></div>
      <div class="sc-num">{{ $failedSms }}</div>
      <div class="sc-label">Failed</div>
    </div>
    <div class="cc-stat-card warning">
      <div class="sc-icon"><i class="fas fa-clock"></i></div>
      <div class="sc-num">{{ $pendSms }}</div>
      <div class="sc-label">Pending</div>
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
                @php $sc = ['delivered'=>'fp-success','failed'=>'fp-danger','sent'=>'fp-primary','pending'=>'fp-warning']; @endphp
                <span class="fpill {{ $sc[$log->status] ?? 'fp-secondary' }}">{{ ucfirst($log->status) }}</span>
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
    $.post('{{ url("callcenter/sms") }}/' + id + '/resend', {_token: '{{ csrf_token() }}'},
        function(res) {
            if (res.success) { toastr.success('SMS resent.'); location.reload(); }
        }).fail(function() {
            toastr.error('Resend failed.');
            $(btn).prop('disabled', false).html('<i class="fas fa-redo"></i>');
        });
}
</script>
@endsection
