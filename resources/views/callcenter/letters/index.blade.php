@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'Letter Log')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="fade-in">

  {{-- Module Header --}}
  <div class="module-head">
    <h2><i class="fas fa-envelope"></i> Letter Log</h2>
    <button class="btn-frest warning sm" data-toggle="modal" data-target="#modalLetter">
      <i class="fas fa-plus"></i> New Letter
    </button>
  </div>

  {{-- Stat Cards --}}
  <div class="stat-row">
    <div class="cc-stat-card primary">
      <div class="sc-icon"><i class="fas fa-envelope"></i></div>
      <div class="sc-num">{{ $stats['total'] }}</div>
      <div class="sc-label">Total Letters</div>
    </div>
    <div class="cc-stat-card success">
      <div class="sc-icon"><i class="fas fa-check-circle"></i></div>
      <div class="sc-num">{{ $stats['sent'] }}</div>
      <div class="sc-label">Sent / Delivered</div>
    </div>
    <div class="cc-stat-card warning">
      <div class="sc-icon"><i class="fas fa-clock"></i></div>
      <div class="sc-num">{{ $stats['queued'] }}</div>
      <div class="sc-label">Queued</div>
    </div>
    <div class="cc-stat-card info">
      <div class="sc-icon"><i class="fas fa-print"></i></div>
      <div class="sc-num">{{ $stats['printed'] }}</div>
      <div class="sc-label">Printed</div>
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
            <option value="queued"    {{ request('status') === 'queued'    ? 'selected' : '' }}>Queued</option>
            <option value="printed"   {{ request('status') === 'printed'   ? 'selected' : '' }}>Printed</option>
            <option value="sent"      {{ request('status') === 'sent'      ? 'selected' : '' }}>Sent</option>
            <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Reason</label>
          <select name="reason" class="form-control">
            <option value="">All</option>
            @foreach(\App\Models\CallCenter\LetterLog::REASONS as $k => $v)
            <option value="{{ $k }}" {{ request('reason') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="filter-label">Search Patient</label>
          <input type="text" name="search" class="form-control" placeholder="Patient name" value="{{ request('search') }}">
        </div>
        <div style="display:flex;gap:6px">
          <button type="submit" class="btn-frest primary sm"><i class="fas fa-filter"></i> Filter</button>
          <a href="{{ route('callcenter.letters.index') }}" class="btn-frest outline sm"><i class="fas fa-times"></i></a>
        </div>
      </div>
    </form>
  </div>

  {{-- Main Card --}}
  <div class="fcard">
    <div class="fcard-head">
      <h3><i class="fas fa-envelope"></i> Letters</h3>
      <span class="fpill fp-primary">{{ $letters->total() }} records</span>
    </div>
    <div class="fcard-body" style="padding:8px 12px">
      <div class="table-responsive">
        <table class="table" id="lettersTable" width="100%">
          <thead>
            <tr>
              <th width="4%">#</th>
              <th width="16%">Patient</th>
              <th width="20%">Delivery Address</th>
              <th width="14%">Reason</th>
              <th width="9%">Status</th>
              <th width="18%">Internal Note</th>
              <th width="12%">Sent Date</th>
              <th width="7%">Agent</th>
            </tr>
          </thead>
          <tbody>
            @forelse($letters as $letter)
            <tr>
              <td style="color:var(--cc-text-muted)">{{ $loop->iteration }}</td>
              <td>
                <div class="td-name">{{ $letter->patient?->name ?? '—' }}</div>
                <div class="td-sub">{{ $letter->patient?->register_id ?? '' }}</div>
              </td>
              <td style="font-size:12px;color:var(--cc-text-muted)">{{ Str::limit($letter->delivery_address, 45) }}</td>
              <td>
                <span class="fpill fp-secondary">{{ \App\Models\CallCenter\LetterLog::REASONS[$letter->reason] ?? $letter->reason }}</span>
              </td>
              <td>
                <span class="fpill {{ $statusPillClasses[$letter->status] ?? 'fp-secondary' }}">{{ ucfirst($letter->status) }}</span>
              </td>
              <td style="font-size:11px;color:var(--cc-text-muted)">{{ Str::limit($letter->internal_note, 55) }}</td>
              <td>
                <div style="font-size:11px;color:var(--cc-text-muted)">
                  {{ $letter->sent_date ? $letter->sent_date->format('d M Y') : '—' }}
                </div>
              </td>
              <td>
                <div style="font-size:11px;color:var(--cc-text-muted)">{{ $letter->agent?->name ?? '—' }}</div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8">
                <div class="cc-empty"><i class="fas fa-envelope-open"></i><span>No letters found</span></div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="padding:10px 0 4px">{{ $letters->links() }}</div>
    </div>
  </div>
</div>

<input type="hidden" id="letterPatientId">
@include('callcenter.letters._modal')
@endsection

@section('page-scripts')
@include('callcenter.partials._frest_js_init')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#lettersTable')) $('#lettersTable').DataTable().destroy();
    $('#lettersTable').DataTable({
        paging: false,
        searching: true,
        ordering: true,
        info: false,
        language: {search: '', searchPlaceholder: 'Search letters...'},
        columnDefs: [{orderable: false, targets: []}]
    });
});
</script>
@endsection
