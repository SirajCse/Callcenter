{{-- resources/views/callcenter/board/partials/_modal_new_task.blade.php --}}
<div class="modal fade" id="modalNewTask" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>New Task</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('callcenter.tasks.store') }}" method="POST">
        @csrf
        <input type="hidden" name="patient_id" id="newTaskPatientId" value="{{ $patient->id ?? '' }}">
        <div class="modal-body">
          <div class="form-group">
            <label class="small font-weight-bold">Task Title</label>
            <input type="text" name="title" class="form-control form-control-sm" required placeholder="Task title...">
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Task Type</label>
                <select name="task_type" class="form-control form-control-sm">
                  @foreach(\App\Models\CallCenter\Task::TYPES as $k => $v)
                  <option value="{{ $k }}">{{ $v }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Priority</label>
                <select name="priority" class="form-control form-control-sm">
                  <option value="high">High</option>
                  <option value="medium" selected>Medium</option>
                  <option value="low">Low</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Call Type</label>
                <select name="call_type" class="form-control form-control-sm">
                  <option value="outgoing">Outgoing</option>
                  <option value="incoming">Incoming</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Due Date</label>
                <input type="date" name="due_date" class="form-control form-control-sm" value="{{ today()->toDateString() }}">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Note</label>
            <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Task notes..."></textarea>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Follow-up Target Note</label>
            <input type="text" name="followup_target_note" class="form-control form-control-sm" placeholder="What to say during follow-up...">
          </div>
          <label class="d-flex align-items-center mb-0" style="gap:8px;cursor:pointer;font-size:12px">
            <input type="checkbox" name="is_pinned" value="1"> Pin this task
          </label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Save Task</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Transfer modal --}}
<div class="modal fade" id="modalTransfer" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h6 class="modal-title"><i class="fas fa-exchange-alt mr-2"></i>Transfer Task</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form id="transferForm">
        @csrf
        <input type="hidden" id="transferTaskId">
        <div class="modal-body">
          <div class="form-group">
            <label class="small font-weight-bold">Transfer To Agent</label>
            <select id="transferAgent" class="form-control form-control-sm">
              @foreach(\App\Models\User::whereHas('roles', fn($q)=>$q->whereIn('name',['agent','supervisor']))->get() as $ag)
              <option value="{{ $ag->id }}">{{ $ag->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Reason</label>
            <input type="text" id="transferReason" class="form-control form-control-sm" placeholder="Transfer reason...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-warning" onclick="submitTransfer()"><i class="fas fa-exchange-alt mr-1"></i> Transfer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function submitTransfer() {
    var id     = $('#transferTaskId').val();
    var agent  = $('#transferAgent').val();
    var reason = $('#transferReason').val();
    $.post('{{ url("callcenter/tasks") }}/' + id + '/transfer', {
        _token: '{{ csrf_token() }}',
        transferred_to: agent,
        transfer_reason: reason
    }, function(res) {
        if (res.success) { toastr.success(res.message); $('#modalTransfer').modal('hide'); location.reload(); }
    });
}
</script>
