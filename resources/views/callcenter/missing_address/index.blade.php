@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'Missing Address')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="fade-in">

  {{-- ★ Compact Topbar: KPI chips + actions (saves vertical space) --}}
  <div class="cc-topbar">
    <div class="kpi-chip primary"><span class="kn">{{ $stats['total'] ?? 0 }}</span> Total Records</div>
    <div class="kpi-chip danger"><span class="kn">{{ $stats['pending'] ?? 0 }}</span> Pending</div>
    <div class="kpi-chip warning"><span class="kn">{{ $stats['awaiting'] ?? 0 }}</span> Awaiting</div>
    <div class="kpi-chip success"><span class="kn">{{ $stats['resolved'] ?? 0 }}</span> Resolved</div>
    <div class="cc-actions">
      <span class="fpill fp-danger" style="font-size:12px;padding:5px 12px">{{ $records->total() }} records</span>
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
            <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
            <option value="awaiting"  {{ request('status') === 'awaiting'  ? 'selected' : '' }}>Awaiting</option>
            <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
            <option value="updated"   {{ request('status') === 'updated'   ? 'selected' : '' }}>Updated</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Letter Sent</label>
          <select name="letter_sent" class="form-control">
            <option value="">All</option>
            <option value="1" {{ request('letter_sent') === '1' ? 'selected' : '' }}>Yes</option>
            <option value="0" {{ request('letter_sent') === '0' ? 'selected' : '' }}>No</option>
          </select>
        </div>
        <div>
          <label class="filter-label">Search Patient</label>
          <input type="text" name="search" class="form-control" placeholder="Patient name / ID" value="{{ request('search') }}">
        </div>
        <div style="display:flex;gap:6px">
          <button type="submit" class="btn-frest primary sm"><i class="fas fa-filter"></i> Filter</button>
          <a href="{{ route('callcenter.missing_address.index') }}" class="btn-frest outline sm"><i class="fas fa-times"></i></a>
        </div>
      </div>
    </form>
  </div>

  {{-- Main Card --}}
  <div class="fcard">
    <div class="fcard-head">
      <h3><i class="fas fa-map-marker-alt"></i> Missing Address Records</h3>
      <span class="fpill fp-danger">{{ $records->total() }} total</span>
    </div>
    <div class="fcard-body" style="padding:8px 12px">
      <div class="table-responsive">
        <table class="table" id="missingTable" width="100%">
          <thead>
            <tr>
              <th width="4%">#</th>
              <th width="20%">Patient</th>
              <th width="10%">Letter Sent</th>
              <th width="12%">Sent Date</th>
              <th width="10%">Status</th>
              <th width="24%">Note</th>
              <th width="20%">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($records as $rec)
            <tr>
              <td style="color:var(--cc-text-muted)">{{ $loop->iteration }}</td>
              <td>
                <div class="td-name">{{ $rec->patient?->name ?? '—' }}</div>
                <div class="td-sub">{{ $rec->patient?->register_id ?? 'ID:'.$rec->patient_id }}</div>
              </td>
              <td>
                <span class="fpill {{ $rec->letter_sent ? 'fp-success' : 'fp-danger' }}">
                  <i class="fas fa-{{ $rec->letter_sent ? 'check' : 'times' }}" style="font-size:9px"></i>
                  {{ $rec->letter_sent ? 'Yes' : 'No' }}
                </span>
              </td>
              <td style="font-size:11px;color:var(--cc-text-muted)">
                {{ $rec->letter_sent_date ? $rec->letter_sent_date->format('d M Y') : '—' }}
              </td>
              <td>
                <span class="fpill {{ $statusPillClasses[$rec->status] ?? 'fp-secondary' }}">{{ ucfirst($rec->status) }}</span>
              </td>
              <td style="font-size:11px;color:var(--cc-text-muted)">{{ Str::limit($rec->note, 60) }}</td>
              <td>
                <div class="action-bar">
                  @if(!$rec->letter_sent)
                  <button class="btn-icon warning" onclick="updateRecord({{ $rec->id }}, 'sent')" title="Mark Letter Sent">
                    <i class="fas fa-envelope"></i>
                  </button>
                  @endif
                  @if($rec->status !== 'updated')
                  <button class="btn-icon success" onclick="updateRecord({{ $rec->id }}, 'updated')" title="Mark Resolved">
                    <i class="fas fa-check"></i>
                  </button>
                  @endif
                  <button class="btn-icon" style="background:var(--cc-primary-light);color:var(--cc-primary)"
                    onclick="openNoteModal({{ $rec->id }}, '{{ addslashes($rec->note ?? '') }}')" title="Edit Note">
                    <i class="fas fa-sticky-note"></i>
                  </button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7">
                <div class="cc-empty"><i class="fas fa-map-marker-alt"></i><span>No missing address records</span></div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div style="padding:10px 0 4px">{{ $records->links() }}</div>
    </div>
  </div>
</div>

{{-- Note Modal --}}
<div class="modal fade" id="noteModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content" style="border:none;border-radius:var(--cc-r3)">
      <div class="modal-header" style="background:var(--cc-primary-light);border-bottom:1px solid var(--cc-border)">
        <h6 class="modal-title" style="color:var(--cc-primary)"><i class="fas fa-sticky-note"></i> Update Note</h6>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="noteRecordId">
        <textarea id="noteText" class="form-control form-control-sm" rows="3" placeholder="Add a note..."></textarea>
      </div>
      <div class="modal-footer" style="border-top:1px solid var(--cc-border)">
        <button type="button" class="btn-frest outline sm" data-dismiss="modal">Cancel</button>
        <button class="btn-frest primary sm" onclick="saveNote()"><i class="fas fa-save"></i> Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-scripts')
@include('callcenter.partials._frest_js_init')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#missingTable')) $('#missingTable').DataTable().destroy();
    $('#missingTable').DataTable({
        paging: false,
        searching: true,
        ordering: true,
        info: false,
        language: {search: '', searchPlaceholder: 'Search records...'},
        columnDefs: [{orderable: false, targets: [6]}]
    });
});

function updateRecord(id, status) {
    var data = {_method: 'PUT', status: status};
    if (status === 'sent') {
        data.letter_sent = 1;
        data.letter_sent_date = new Date().toISOString().split('T')[0];
        data.status = 'awaiting';
    }
    $.post('{{ route("callcenter.missing.update", ["missingAddress" => "__ID__"]) }}'.replace('__ID__', id), data)
        .done(function(res) { if (res.success) { toastr.success('Record updated.'); location.reload(); } })
        .fail(function() { toastr.error('Update failed.'); });
}

function openNoteModal(id, note) {
    $('#noteRecordId').val(id);
    $('#noteText').val(note);
    $('#noteModal').modal('show');
}

function saveNote() {
    var id = $('#noteRecordId').val();
    $.post('{{ route("callcenter.missing.update", ["missingAddress" => "__ID__"]) }}'.replace('__ID__', id), {
        _method: 'PUT',
        note: $('#noteText').val()
    }).done(function(res) {
        if (res.success) {
            toastr.success('Note saved.');
            $('#noteModal').modal('hide');
            location.reload();
        }
    }).fail(function() { toastr.error('Failed to save note.'); });
}
</script>
@endsection
