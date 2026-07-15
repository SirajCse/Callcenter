@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'Follow-Up List')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="fade-in">

  {{-- ★ Compact Topbar: KPI chips + actions (saves vertical space) --}}
  <div class="cc-topbar">
    <div class="kpi-chip primary"><span class="kn">{{ $stats['total'] ?? 0 }}</span> Total Patients</div>
    <div class="kpi-chip warning"><span class="kn">{{ $stats['not_called'] ?? 0 }}</span> Not Called</div>
    <div class="kpi-chip success"><span class="kn">{{ $stats['with_phone'] ?? 0 }}</span> Has Phone</div>
    <div class="kpi-chip danger"><span class="kn">{{ $stats['no_phone'] ?? 0 }}</span> No Phone</div>
    <div class="cc-actions">
      <a href="{{ route('callcenter.board') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Board</a>
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

  {{-- Bulk SMS Modal --}}
  <div class="modal fade" id="modalBulkSms" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background:var(--cc-info-light)">
          <h6 class="modal-title" style="color:var(--cc-info)"><i class="fas fa-sms"></i> Send Bulk SMS</h6>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-2">Sending to <strong id="bulkSmsCount">0</strong> selected patient(s). Patients without a valid phone number are skipped automatically.</p>
          <div class="form-group mb-0">
            <label class="filter-label">Message Template</label>
            <select id="bulkSmsTemplate" class="form-control form-control-sm">
              <option value="missed">Missed Call — We tried to reach you</option>
              <option value="appt">Appointment Reminder</option>
              <option value="lab">Lab Results Ready</option>
              <option value="fu">Follow-up Reminder</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-frest outline sm" data-dismiss="modal">Cancel</button>
          <button class="btn-frest info sm" id="btnSendBulkSms" onclick="submitBulkSms()"><i class="fas fa-paper-plane"></i> Send</button>
        </div>
      </div>
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
            @php($lastLog = $patient->latestCallLog)
            <tr>
              <td class="text-center"><input type="checkbox" class="fu-chk" value="{{ $patient->id }}" onchange="updateCount()" style="accent-color:var(--cc-primary);width:14px;height:14px"></td>
              <td>
                <div class="td-name">{{ $patient->name }}</div>
                <div class="td-sub">{{ $patient->register_id ?? 'ID:'.$patient->id }} · {{ Str::limit($patient->address ?? '', 35) }}</div>
              </td>
              <td>
                <span class="fpill {{ $patient->phone_ok ? 'fp-success' : 'fp-danger' }}">
                  <i class="fas fa-phone" style="font-size:9px"></i>
                  {{ $patient->phone ?? 'N/A' }}
                </span>
              </td>
              <td style="font-size:11px;color:var(--cc-text-muted)">
                {{ $lastLog?->call_date ? \Carbon\Carbon::parse($lastLog->call_date)->format('d M Y') : '—' }}
              </td>
              <td>
                <span class="fpill fp-secondary">{{ $patient->call_count ?? 0 }}</span>
              </td>
              <td style="font-size:11px;color:var(--cc-text-muted);max-width:200px">
                {{ Str::limit($lastLog?->call_note ?? $lastLog?->caller_opinion ?? '—', 65) }}
              </td>
              <td>
                @if($patient->other_agent_call)
                <div style="font-size:11px;color:var(--cc-warning);font-weight:500">
                  <i class="fas fa-user" style="font-size:9px"></i> {{ $patient->other_agent_call->caller?->name }}
                </div>
                <div style="font-size:10px;color:var(--cc-text-light)">{{ \Carbon\Carbon::parse($patient->other_agent_call->call_date)->format('d M Y') }}</div>
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
@include('callcenter.partials._frest_js_init')
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
    document.getElementById('bulkSmsCount').textContent = ids.length;
    $('#modalBulkSms').modal('show');
}

function submitBulkSms() {
    var ids = Array.from(document.querySelectorAll('.fu-chk:checked')).map(c => c.value);
    if (!ids.length) { toastr.warning('Please select at least one patient.'); $('#modalBulkSms').modal('hide'); return; }

    var $btn = $('#btnSendBulkSms').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');

    $.post('{{ route("callcenter.sms.bulk") }}', {
        patient_ids: ids,
        template_key: $('#bulkSmsTemplate').val()
    }).done(function(res) {
        if (res.success) {
            toastr.success(res.message);
            $('#modalBulkSms').modal('hide');
            document.querySelectorAll('.fu-chk').forEach(c => c.checked = false);
            updateCount();
        }
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.message ?? 'Bulk SMS failed.');
    }).always(function() {
        $btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send');
    });
}

function openCallHistory(patientId) {
    $.get('{{ route("callcenter.calllogs.history", ["patientId" => "__ID__"]) }}'.replace('__ID__', patientId), function(res) {
        $('#callHistoryBody').html(res.html);
        $('#modalCallHistory').modal('show');
    });
}
</script>
@endsection
