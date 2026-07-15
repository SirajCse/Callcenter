@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'My Tasks')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="fade-in">

  {{-- ★ Compact Topbar: KPI chips + actions (saves vertical space) --}}
  <div class="cc-topbar">
    <div class="kpi-chip warning" onclick="switchTab('pending')"><span class="kn">{{ $stats['pending'] ?? 0 }}</span> Pending</div>
    <div class="kpi-chip success" onclick="switchTab('completed')"><span class="kn">{{ $stats['completed'] ?? 0 }}</span> Completed</div>
    <div class="kpi-chip info" onclick="switchTab('transferred')"><span class="kn">{{ $stats['transferred'] ?? 0 }}</span> Transferred</div>
    <div class="kpi-chip primary" onclick="switchTab('pinned')"><span class="kn">{{ $stats['pinned'] ?? 0 }}</span> Pinned</div>
    <div class="kpi-chip danger" onclick="switchTab('priority')"><span class="kn">{{ $stats['priority'] ?? 0 }}</span> High Priority</div>
    @if(($stats['overdue'] ?? 0) > 0)
    <div class="kpi-chip danger" onclick="switchTab('pending')"><span class="kn">{{ $stats['overdue'] ?? 0 }}</span> Overdue</div>
    @endif
    <div class="cc-actions">
      <a href="{{ route('callcenter.board') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Board</a>
      <button class="btn-frest primary sm" data-toggle="modal" data-target="#modalNewTask"><i class="fas fa-plus"></i> New Task</button>
    </div>
  </div>

  {{-- Main Card --}}
  <div class="fcard">

    {{-- Tab Nav --}}
    <div class="task-tabs">
      @foreach($tabs as $key => $meta)
      <a href="{{ route('callcenter.tasks.index', ['tab' => $key]) }}"
         class="ttab {{ $tab === $key ? 'active' : '' }}">
        {{ $meta['icon'] }} {{ $meta['label'] }}
        <span style="background:{{ $tab === $key ? 'rgba(255,255,255,.25)' : 'var(--cc-border)' }};padding:1px 7px;border-radius:20px;font-size:10px">{{ $meta['count'] }}</span>
      </a>
      @endforeach
    </div>

    {{-- Table --}}
    <div class="fcard-body" style="padding:8px 12px">
      <div class="table-responsive">
        <table class="table" id="tasksTable" width="100%">
          <thead>
            <tr>
              <th width="4%">#</th>
              <th width="22%">Task</th>
              <th width="18%">Patient</th>
              <th width="12%">Type</th>
              <th width="8%">Priority</th>
              <th width="12%">Due Date</th>
              <th width="10%">Status</th>
              <th width="14%">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tasks as $task)
            @php($isOverdue = $task->due_date && $task->due_date < today() && $task->status === "pending")
            <tr class="{{ $isOverdue ? 'overdue-row' : '' }}">
              <td style="color:var(--cc-text-muted)">{{ $loop->iteration }}</td>
              <td>
                <div style="display:flex;align-items:flex-start;gap:6px">
                  <span class="prio-dot {{ $task->priority }}" style="margin-top:4px"></span>
                  <div>
                    <div class="td-name">
                      @if($task->is_pinned)<i class="fas fa-thumbtack" style="color:var(--cc-warning);font-size:10px;margin-right:3px"></i>@endif
                      {{ $task->title }}
                    </div>
                    @if($task->note)
                    <div class="td-sub">{{ Str::limit($task->note, 55) }}</div>
                    @endif
                    @if($task->followup_target_date)
                    <div style="font-size:10px;color:var(--cc-primary);margin-top:2px">
                      <i class="fas fa-calendar-check" style="font-size:9px"></i>
                      FU: {{ $task->followup_target_date->format('d M Y') }}
                    </div>
                    @endif
                  </div>
                </div>
              </td>
              <td>
                <div class="td-name">{{ $task->patient?->name ?? '—' }}</div>
                <div class="td-sub">
                  @if($task->patient?->phone)<i class="fas fa-phone" style="font-size:9px"></i> {{ $task->patient->phone }}@endif
                </div>
              </td>
              <td><span class="fpill fp-primary">{{ \App\Models\CallCenter\Task::TYPES[$task->task_type] ?? $task->task_type }}</span></td>
              <td>
                <span class="fpill {{ $priorityPillClasses[$task->priority] ?? 'fp-secondary' }}">
                  <span class="prio-dot {{ $task->priority }}"></span>
                  {{ ucfirst($task->priority) }}
                </span>
              </td>
              <td>
                @if($task->due_date)
                  <span style="font-size:12px;color:{{ $isOverdue ? 'var(--cc-danger)' : 'var(--cc-text-muted)' }};font-weight:{{ $isOverdue ? '600' : '400' }}">
                    {{ $task->due_date->format('d M Y') }}
                  </span>
                  @if($isOverdue)
                  <div style="font-size:10px;color:var(--cc-danger)"><i class="fas fa-exclamation-circle"></i> Overdue</div>
                  @endif
                @else
                  <span style="color:var(--cc-text-light)">—</span>
                @endif
              </td>
              <td>
                <span class="fpill {{ $statusPillClasses[$task->status] ?? 'fp-secondary' }}">{{ ucfirst($task->status) }}</span>
                @if($task->completed_at)
                  <div class="td-sub">{{ $task->completed_at->format('d M, h:i A') }}</div>
                @endif
                @if($task->transferredTo && $task->status === 'transferred')
                  <div style="font-size:10px;color:var(--cc-info);margin-top:2px">→ {{ $task->transferredTo->name }}</div>
                @endif
              </td>
              <td>
                @if($task->status === 'pending')
                <div class="action-bar">
                  <button class="btn-icon success" onclick="completeTask({{ $task->id }})" title="Mark Complete"><i class="fas fa-check"></i></button>
                  <a href="{{ route('callcenter.board') }}?pid={{ $task->patient_id }}" class="btn-icon primary" title="Open in Board"><i class="fas fa-phone"></i></a>
                  <button class="btn-icon warning" onclick="openTransfer({{ $task->id }})" title="Transfer"><i class="fas fa-exchange-alt"></i></button>
                  <button class="btn-icon outline" onclick="pinTask({{ $task->id }})" title="{{ $task->is_pinned ? 'Unpin' : 'Pin' }}">
                    <i class="fas fa-thumbtack" style="{{ $task->is_pinned ? 'color:var(--cc-warning)' : '' }}"></i>
                  </button>
                </div>
                @else
                <span style="color:var(--cc-text-light);font-size:11px">—</span>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8">
                <div class="cc-empty"><i class="fas fa-check-double"></i><span>No tasks in this category</span></div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div style="padding:10px 0 4px">{{ $tasks->withQueryString()->links() }}</div>
    </div>
  </div>
</div>

{{-- Transfer Modal --}}
<div class="modal fade" id="modalTransfer" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--cc-warning-light)">
        <h6 class="modal-title" style="color:var(--cc-warning)"><i class="fas fa-exchange-alt"></i> Transfer Task</h6>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="transferTaskId">
        <div class="form-group mb-2">
          <label class="filter-label">Transfer To</label>
          <select id="transferTo" class="form-control form-control-sm">
            @foreach($transferAgents as $ag)
            <option value="{{ $ag->id }}">{{ $ag->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group mb-0">
          <label class="filter-label">Reason</label>
          <textarea id="transferReason" class="form-control form-control-sm" rows="2" placeholder="Why are you transferring?"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-frest outline sm" data-dismiss="modal">Cancel</button>
        <button class="btn-frest warning sm" onclick="submitTransfer()"><i class="fas fa-exchange-alt"></i> Transfer</button>
      </div>
    </div>
  </div>
</div>

@include('callcenter.board.partials._modal_new_task')
@endsection

@section('page-scripts')
@include('callcenter.partials._frest_js_init')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#tasksTable')) $('#tasksTable').DataTable().destroy();
    $('#tasksTable').DataTable({
        paging: false,
        searching: true,
        ordering: true,
        info: false,
        language: {search: '', searchPlaceholder: 'Search tasks...'},
        columnDefs: [{orderable: false, targets: [7]}]
    });
});

function switchTab(tab) {
    window.location = '{{ route("callcenter.tasks.index") }}?tab=' + tab;
}

// Route templates — {id} is replaced per call below.
const ROUTE_TASK_COMPLETE  = '{{ route("callcenter.tasks.complete", ["task" => "__ID__"]) }}';
const ROUTE_TASK_PIN       = '{{ route("callcenter.tasks.pin", ["task" => "__ID__"]) }}';
const ROUTE_TASK_TRANSFER  = '{{ route("callcenter.tasks.transfer", ["task" => "__ID__"]) }}';

function completeTask(id) {
    if (!confirm('Mark this task as completed?')) return;
    $.post(ROUTE_TASK_COMPLETE.replace('__ID__', id))
        .done(function(res) { if (res.success) { toastr.success(res.message); location.reload(); } })
        .fail(function(xhr) { toastr.error(xhr.responseJSON?.message ?? 'Could not complete task.'); });
}

function pinTask(id) {
    $.post(ROUTE_TASK_PIN.replace('__ID__', id))
        .done(function(res) { if (res.success) location.reload(); })
        .fail(function(xhr) { toastr.error(xhr.responseJSON?.message ?? 'Could not update pin.'); });
}

function openTransfer(id) {
    $('#transferTaskId').val(id);
    $('#modalTransfer').modal('show');
}

function submitTransfer() {
    var id = $('#transferTaskId').val();
    var transferTo = $('#transferTo').val();
    if (!transferTo) { toastr.warning('Select an agent to transfer to.'); return; }

    $.post(ROUTE_TASK_TRANSFER.replace('__ID__', id), {
        transferred_to: transferTo,
        transfer_reason: $('#transferReason').val()
    }).done(function(res) {
        if (res.success) {
            toastr.success(res.message);
            $('#modalTransfer').modal('hide');
            location.reload();
        }
    }).fail(function(xhr) { toastr.error(xhr.responseJSON?.message ?? 'Transfer failed.'); });
}
</script>
@endsection
