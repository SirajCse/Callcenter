{{-- resources/views/callcenter/board/partials/_modal_new_task.blade.php
    Fixed: AJAX submission via submitNewTask(event). Transfer modal was
    extracted into its own _modal_transfer.blade.php file. --}}
<div class="modal fade" id="modalNewTask" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>New Task</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('callcenter.tasks.store') }}" method="POST"
            id="newTaskForm" onsubmit="return submitNewTask(event)">
        @csrf
        <input type="hidden" name="patient_id" id="newTaskPatientId" value="{{ $patient->id ?? '' }}">
        <div class="modal-body">
          <div class="form-group">
            <label class="small font-weight-bold">Task Title</label>
            <input type="text" name="title" class="form-control form-control-sm"
                   required placeholder="Task title...">
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
                <input type="date" name="due_date" class="form-control form-control-sm"
                       value="{{ today()->toDateString() }}">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Note</label>
            <textarea name="note" class="form-control form-control-sm" rows="2"
                      placeholder="Task notes..."></textarea>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Follow-up Target Note</label>
            <input type="text" name="followup_target_note" class="form-control form-control-sm"
                   placeholder="What to say during follow-up...">
          </div>
          <label class="d-flex align-items-center mb-0" style="gap:8px;cursor:pointer;font-size:12px">
            <input type="checkbox" name="is_pinned" value="1"> Pin this task
          </label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save mr-1"></i> Save Task
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ── AJAX submit handler for new task ──────────────────────
function submitNewTask(event) {
    if (event) event.preventDefault();
    var $form = $('#newTaskForm');
    var $btn  = $form.find('button[type="submit"]');
    var prev  = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

    $.ajax({
        url:  $form.attr('action'),
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json'
    }).done(function (res) {
        if (res && res.success) {
            toastr.success('Task created successfully.');
            $('#modalNewTask').modal('hide');
            $form[0].reset();
            setTimeout(function () { location.reload(); }, 600);
        } else {
            toastr.error((res && res.message) || 'Failed to create task.');
        }
    }).fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message)
               || (xhr.responseJSON && xhr.responseJSON.errors
                    && Object.values(xhr.responseJSON.errors).flat().join(', '))
               || 'Server error while creating task.';
        toastr.error(msg);
    }).always(function () {
        $btn.prop('disabled', false).html(prev);
    });
    return false;
}
</script>
