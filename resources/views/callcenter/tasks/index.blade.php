@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'My Tasks')

@section('page-styles')
@include('callcenter.partials._frest_css')
<style>
/* ── Stat row ─────────────────────────────────────────── */
.stat-row{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:14px}
@media(max-width:992px){.stat-row{grid-template-columns:repeat(3,1fr)}}
@media(max-width:576px){.stat-row{grid-template-columns:repeat(2,1fr)}}

/* ── Tab nav ──────────────────────────────────────────── */
.task-tabs{display:flex;gap:4px;padding:10px 14px;border-bottom:1px solid var(--cc-border);background:#fafafa;overflow-x:auto;flex-wrap:nowrap}
.task-tabs::-webkit-scrollbar{height:0}
.ttab{padding:7px 16px;border-radius:var(--cc-r2);border:1px solid var(--cc-border);background:#fff;font-size:12px;font-weight:600;color:var(--cc-text-muted);cursor:pointer;transition:all .2s;text-decoration:none;white-space:nowrap;display:inline-flex;align-items:center;gap:5px}
.ttab:hover{border-color:var(--cc-primary);color:var(--cc-primary);background:var(--cc-primary-light);text-decoration:none}
.ttab.active{background:var(--cc-primary);color:#fff;border-color:var(--cc-primary);box-shadow:0 4px 12px rgba(90,141,238,.35)}

/* ── Priority indicator ───────────────────────────────── */
.prio-bar{width:3px;height:100%;border-radius:2px;position:absolute;left:0;top:0}
.prio-high{background:var(--cc-danger)}
.prio-medium{background:var(--cc-warning)}
.prio-low{background:var(--cc-success)}
.prio-dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0}
.prio-dot.high{background:var(--cc-danger)}
.prio-dot.medium{background:var(--cc-warning)}
.prio-dot.low{background:var(--cc-success)}

/* ── Table ────────────────────────────────────────────── */
#tasksTable{font-size:12px;width:100%!important}
#tasksTable thead th{background:#fafafa;color:var(--cc-text-muted);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:10px 12px;border-bottom:2px solid var(--cc-border);border-top:none}
#tasksTable tbody td{padding:10px 12px;vertical-align:middle;color:var(--cc-text);border-top:1px solid var(--cc-border)}
#tasksTable tbody tr{position:relative}
#tasksTable tbody tr:hover td{background:rgba(90,141,238,.03)}
.overdue-row td{background:rgba(255,91,91,.03)!important}

/* ── Action buttons ───────────────────────────────────── */
.action-bar{display:flex;gap:4px;flex-wrap:wrap}
.btn-icon{width:28px;height:28px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;cursor:pointer;transition:all .2s;font-size:11px;text-decoration:none}
.btn-icon.success{background:var(--cc-success-light);color:var(--cc-success)}
.btn-icon.success:hover{background:var(--cc-success);color:#fff}
.btn-icon.primary{background:var(--cc-primary-light);color:var(--cc-primary)}
.btn-icon.primary:hover{background:var(--cc-primary);color:#fff}
.btn-icon.warning{background:var(--cc-warning-light);color:var(--cc-warning)}
.btn-icon.warning:hover{background:var(--cc-warning);color:#fff}
.btn-icon.outline{background:rgba(71,95,123,.08);color:var(--cc-text-muted)}
.btn-icon.outline:hover{background:var(--cc-primary-light);color:var(--cc-primary)}
.btn-icon.danger{background:var(--cc-danger-light);color:var(--cc-danger)}
.btn-icon.danger:hover{background:var(--cc-danger);color:#fff}

/* ── Modal ────────────────────────────────────────────── */
.modal-content{border:none;border-radius:var(--cc-r3);box-shadow:0 24px 48px rgba(0,0,0,.18)}
.modal-header{border-bottom:1px solid var(--cc-border);padding:14px 18px;border-radius:var(--cc-r3) var(--cc-r3) 0 0}
.modal-footer{padding:12px 18px;border-top:1px solid var(--cc-border);border-radius:0 0 var(--cc-r3) var(--cc-r3)}

/* ── DataTables ───────────────────────────────────────── */
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
    <h2><i class="fas fa-tasks"></i> My Tasks</h2>
    <div style="display:flex;gap:6px">
      <a href="{{ route('callcenter.board') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Board</a>
      <button class="btn-frest primary sm" data-toggle="modal" data-target="#modalNewTask"><i class="fas fa-plus"></i> New Task</button>
    </div>
  </div>

  {{-- Stat Cards --}}
  @php
    $allTasks   = \App\Models\CallCenter\Task::forAgent(auth()->id());
    $statPending     = (clone $allTasks)->pending()->count();
    $statCompleted   = (clone $allTasks)->completed()->count();
    $statTransferred = (clone $allTasks)->transferred()->count();
    $statPinned      = (clone $allTasks)->pinned()->pending()->count();
    $statHigh        = (clone $allTasks)->highPriority()->pending()->count();
    $statOverdue     = (clone $allTasks)->pending()->where('due_date','<',today())->count();
  @endphp
  <div class="stat-row">
    <div class="cc-stat-card warning" onclick="switchTab('pending')">
      <div class="sc-icon"><i class="fas fa-clock"></i></div>
      <div class="sc-num">{{ $statPending }}</div>
      <div class="sc-label">Pending</div>
    </div>
    <div class="cc-stat-card success" onclick="switchTab('completed')">
      <div class="sc-icon"><i class="fas fa-check-circle"></i></div>
      <div class="sc-num">{{ $statCompleted }}</div>
      <div class="sc-label">Completed</div>
    </div>
    <div class="cc-stat-card info" onclick="switchTab('transferred')">
      <div class="sc-icon"><i class="fas fa-exchange-alt"></i></div>
      <div class="sc-num">{{ $statTransferred }}</div>
      <div class="sc-label">Transferred</div>
    </div>
    <div class="cc-stat-card primary" onclick="switchTab('pinned')">
      <div class="sc-icon"><i class="fas fa-thumbtack"></i></div>
      <div class="sc-num">{{ $statPinned }}</div>
      <div class="sc-label">Pinned</div>
    </div>
    <div class="cc-stat-card danger" onclick="switchTab('priority')">
      <div class="sc-icon"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="sc-num">{{ $statHigh }}</div>
      <div class="sc-label">High Priority</div>
    </div>
  </div>

  {{-- Overdue alert --}}
  @if($statOverdue > 0)
  <div style="background:var(--cc-danger-light);border:1px solid rgba(255,91,91,.2);border-radius:var(--cc-r2);padding:10px 14px;margin-bottom:12px;font-size:12px;color:var(--cc-danger);display:flex;align-items:center;gap:8px">
    <i class="fas fa-exclamation-circle"></i>
    <strong>{{ $statOverdue }} overdue task(s)</strong> — past their due date and still pending.
    <a href="{{ route('callcenter.tasks.index', ['tab'=>'pending']) }}" style="color:var(--cc-danger);text-decoration:underline;margin-left:4px">View all pending</a>
  </div>
  @endif

  {{-- Main Card --}}
  <div class="fcard">

    {{-- Tab Nav --}}
    <div class="task-tabs">
      @php
        $tabs = [
          'pending'     => ['📋', 'Pending',     $statPending],
          'completed'   => ['✅', 'Completed',   $statCompleted],
          'transferred' => ['🔄', 'Transferred', $statTransferred],
          'pinned'      => ['📌', 'Pinned',      $statPinned],
          'priority'    => ['⚠️', 'Priority',    $statHigh],
        ];
      @endphp
      @foreach($tabs as $key => [$icon, $label, $count])
      <a href="{{ route('callcenter.tasks.index', ['tab' => $key]) }}"
         class="ttab {{ $tab === $key ? 'active' : '' }}">
        {{ $icon }} {{ $label }}
        <span style="background:{{ $tab === $key ? 'rgba(255,255,255,.25)' : 'var(--cc-border)' }};padding:1px 7px;border-radius:20px;font-size:10px">{{ $count }}</span>
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
            @php
              $isOverdue = $task->status === 'pending' && $task->due_date && $task->due_date->lt(today());
            @endphp
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
                @php $pc = ['high'=>'fp-danger','medium'=>'fp-warning','low'=>'fp-success']; @endphp
                <span class="fpill {{ $pc[$task->priority] ?? 'fp-secondary' }}">
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
                @php $sc = ['pending'=>'fp-warning','completed'=>'fp-success','transferred'=>'fp-info','cancelled'=>'fp-danger']; @endphp
                <span class="fpill {{ $sc[$task->status] ?? 'fp-secondary' }}">{{ ucfirst($task->status) }}</span>
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
            @foreach(\App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->where('id','!=',auth()->id())->get() as $ag)
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

function completeTask(id) {
    if (!confirm('Mark this task as completed?')) return;
    $.post('/callcenter/tasks/' + id + '/complete', {_token: '{{ csrf_token() }}'},
        function(res) {
            if (res.success) { toastr.success(res.message); location.reload(); }
        }).fail(function(xhr) { toastr.error(xhr.responseJSON?.message ?? 'Error'); });
}

function pinTask(id) {
    $.post('/callcenter/tasks/' + id + '/pin', {_token: '{{ csrf_token() }}'},
        function(res) { if (res.success) location.reload(); });
}

function openTransfer(id) {
    $('#transferTaskId').val(id);
    $('#modalTransfer').modal('show');
}

function submitTransfer() {
    var id = $('#transferTaskId').val();
    $.post('/callcenter/tasks/' + id + '/transfer', {
        _token: '{{ csrf_token() }}',
        transferred_to: $('#transferTo').val(),
        transfer_reason: $('#transferReason').val()
    }, function(res) {
        if (res.success) {
            toastr.success(res.message);
            $('#modalTransfer').modal('hide');
            location.reload();
        }
    }).fail(function(xhr) { toastr.error(xhr.responseJSON?.message ?? 'Transfer failed.'); });
}
</script>
@endsection
