{{-- resources/views/callcenter/board/partials/_modal_transfer.blade.php
    NEW file — the #modalTransfer referenced by transferTask(id) in board.js.
    Bootstrap 4 modal, AJAX submission via submitTransfer(event). --}}
<div class="modal fade" id="modalTransfer" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h6 class="modal-title"><i class="fas fa-exchange-alt mr-2"></i>Transfer Task</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form id="transferForm" onsubmit="return submitTransfer(event)">
        @csrf
        <input type="hidden" id="transferTaskId" name="task_id">
        <div class="modal-body">
          <div class="form-group">
            <label class="small font-weight-bold">Transfer To Agent</label>
            <select id="transferAgent" name="transferred_to" class="form-control form-control-sm" required>
              <option value="">— Select Agent —</option>
              @foreach(\App\Models\User::whereHas('roles', fn($q)=>$q->whereIn('name',['agent','supervisor']))->get() as $ag)
              <option value="{{ $ag->id }}">{{ $ag->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Transfer Reason</label>
            <input type="text" id="transferReason" name="transfer_reason"
                   class="form-control form-control-sm" placeholder="Transfer reason...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">
            <i class="fas fa-exchange-alt mr-1"></i> Transfer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ── AJAX transfer submission ─────────────────────────────
function submitTransfer(event) {
    if (event) event.preventDefault();
    var taskId = $('#transferTaskId').val();
    var agent  = $('#transferAgent').val();
    var reason = $('#transferReason').val();

    if (!taskId) {
        toastr.error('No task selected for transfer.');
        return false;
    }
    if (!agent) {
        toastr.error('Please select an agent to transfer to.');
        return false;
    }

    var $btn = $('#transferForm button[type="submit"]');
    var prev = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Transferring...');

    var url = '{{ route("callcenter.tasks.transfer", ":id") }}'.replace(':id', taskId);

    $.ajax({
        url:  url,
        type: 'POST',
        data: {
            _token:          '{{ csrf_token() }}',
            transferred_to:  agent,
            transfer_reason: reason
        },
        dataType: 'json'
    }).done(function (res) {
        if (res && res.success) {
            toastr.success(res.message || 'Task transferred successfully.');
            $('#modalTransfer').modal('hide');
            $('#transferForm')[0].reset();
            setTimeout(function () { location.reload(); }, 600);
        } else {
            toastr.error((res && res.message) || 'Failed to transfer task.');
        }
    }).fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message)
               || (xhr.responseJSON && xhr.responseJSON.errors
                    && Object.values(xhr.responseJSON.errors).flat().join(', '))
               || 'Server error while transferring task.';
        toastr.error(msg);
    }).always(function () {
        $btn.prop('disabled', false).html(prev);
    });
    return false;
}
</script>
