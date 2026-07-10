{{-- resources/views/callcenter/board/partials/_modal_log_call.blade.php
    Fixed: AJAX submission + "Dial & Log" auto-dial button (no full page reload). --}}
<div class="modal fade" id="modalLogCall" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title"><i class="fas fa-phone-alt mr-2"></i>Log Call</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('callcenter.calllogs.store') }}" method="POST"
            id="logCallForm" onsubmit="return submitLogCall(event)">
        @csrf
        <input type="hidden" name="patient_id" id="logCallPatientId">
        <input type="hidden" name="task_id"    id="logCallTaskId">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold">Call Type</label>
                <select name="method" id="logCallMethod" class="form-control form-control-sm">
                  <option value="outgoing">Outgoing</option>
                  <option value="incoming">Incoming</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold">Task Type</label>
                <select name="type" class="form-control form-control-sm">
                  @foreach(\App\Models\CallCenter\Task::TYPES as $k => $v)
                  <option value="{{ $k }}">{{ $v }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold">Outcome</label>
                <select name="caller_opinion" class="form-control form-control-sm" id="callOutcomeSelect">
                  <option value="answered">Answered</option>
                  <option value="no_answer">No Answer</option>
                  <option value="busy">Busy / Engaged</option>
                  <option value="out_of_reach">Out of Reach</option>
                  <option value="wrong_number">Wrong Number</option>
                  <option value="callback">Callback Requested</option>
                  <option value="dead">Deceased Confirmed</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold">Duration (seconds)</label>
                <input type="number" name="duration" class="form-control form-control-sm" placeholder="e.g. 272">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold">Priority</label>
                <select name="priority" class="form-control form-control-sm">
                  <option value="high">High</option>
                  <option value="medium" selected>Medium</option>
                  <option value="low">Low</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="small font-weight-bold">Call Date/Time</label>
                <input type="datetime-local" name="call_date" class="form-control form-control-sm"
                       value="{{ now()->format('Y-m-d\TH:i') }}">
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label class="small font-weight-bold">Call Note</label>
                <textarea name="call_note" class="form-control form-control-sm" rows="3"
                          placeholder="Notes from this call..."></textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Follow-up Target Date</label>
                <input type="date" name="followup_target_date" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Follow-up Note</label>
                <input type="text" name="followup_target_note" class="form-control form-control-sm"
                       placeholder="What to discuss next...">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Transfer To</label>
                <select name="transfer_to" class="form-control form-control-sm">
                  <option value="">— No Transfer —</option>
                  @foreach(\App\Models\User::whereHas('roles', fn($q)=>$q->whereIn('name',['agent','supervisor']))->get() as $ag)
                  <option value="{{ $ag->id }}">{{ $ag->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Transfer Reason</label>
                <input type="text" name="transfer_cause" class="form-control form-control-sm" placeholder="Reason...">
              </div>
            </div>
            <div class="col-12">
              <div class="d-flex flex-wrap" style="gap:14px;padding:10px;background:#f8f8f8;border-radius:6px">
                <label class="d-flex align-items-center mb-0" style="gap:6px;cursor:pointer;font-size:12px">
                  <input type="hidden" name="receive" value="0">
                  <input type="checkbox" name="receive" value="1"> Call Answered
                </label>
                <label class="d-flex align-items-center mb-0" style="gap:6px;cursor:pointer;font-size:12px">
                  <input type="hidden" name="sms_sent" value="0">
                  <input type="checkbox" name="sms_sent" value="1"> Send SMS (No Answer)
                </label>
                <label class="d-flex align-items-center mb-0" style="gap:6px;cursor:pointer;font-size:12px">
                  <input type="hidden" name="letter_sent" value="0">
                  <input type="checkbox" name="letter_sent" value="1"> Queue Letter (Wrong Number)
                </label>
                <label class="d-flex align-items-center mb-0 text-danger" style="gap:6px;cursor:pointer;font-size:12px">
                  <input type="hidden" name="die" value="0">
                  <input type="checkbox" name="die" value="1"> Mark Deceased
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" onclick="dialAndLog()">
            <i class="fas fa-phone-volume mr-1"></i> Dial &amp; Log
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Save &amp; Log
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ── AJAX submit handler ────────────────────────────────────
function submitLogCall(event) {
    if (event) event.preventDefault();
    var $form = $('#logCallForm');
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
            toastr.success('Call logged successfully.');
            $('#modalLogCall').modal('hide');
            $form[0].reset();
            setTimeout(function () { location.reload(); }, 600);
        } else {
            toastr.error((res && res.message) || 'Failed to log call.');
        }
    }).fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message)
               || (xhr.responseJSON && xhr.responseJSON.errors
                    && Object.values(xhr.responseJSON.errors).flat().join(', '))
               || 'Server error while logging call.';
        toastr.error(msg);
    }).always(function () {
        $btn.prop('disabled', false).html(prev);
    });
    return false;
}

// ── Dial & Log: trigger auto-dial, force outgoing, then submit ─
function dialAndLog() {
    var patientId = $('#logCallPatientId').val();
    // Force "outgoing" since we are initiating the call.
    $('#logCallMethod').val('outgoing');

    // Trigger the auto-dial function defined in board.js (MikoPBX).
    // If unavailable, fall back to a simple toast and proceed.
    if (typeof dialPatient === 'function' && patientId) {
        try {
            var promise = dialPatient(patientId);
            if (promise && typeof promise.then === 'function') {
                promise.then(function () { submitLogCall(null); })
                       .catch(function () {
                           toastr.warning('Auto-dial failed — logging call anyway.');
                           submitLogCall(null);
                       });
                return;
            }
        } catch (e) {
            console.error('dialPatient error', e);
        }
    } else {
        toastr.info('Auto-dial not available — logging call.');
    }
    // Default: submit immediately
    submitLogCall(null);
}
</script>
