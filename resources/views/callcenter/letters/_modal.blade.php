{{-- resources/views/callcenter/letters/_modal.blade.php
    Fixed: AJAX submission via submitLetter(event). --}}
<div class="modal fade" id="modalLetter" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h6 class="modal-title"><i class="fas fa-envelope mr-2"></i>Send Postal Letter</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('callcenter.letters.store') }}" method="POST"
            id="letterForm" onsubmit="return submitLetter(event)">
        @csrf
        <input type="hidden" name="patient_id" id="letterPatientId">
        <input type="hidden" name="task_id"    id="letterTaskId">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Delivery Address</label>
                <input type="text" name="delivery_address" id="letterAddress"
                  class="form-control form-control-sm" required placeholder="Full postal address...">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="small font-weight-bold">Reason</label>
                <select name="reason" class="form-control form-control-sm">
                  @foreach(\App\Models\CallCenter\LetterLog::REASONS as $k => $v)
                  <option value="{{ $k }}">{{ $v }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label class="small font-weight-bold">Letter Content</label>
                <textarea name="content" class="form-control form-control-sm" rows="5"
                  placeholder="Dear Patient, ..."></textarea>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label class="small font-weight-bold">Internal Note</label>
                <input type="text" name="internal_note" class="form-control form-control-sm"
                  placeholder="Internal note (not printed on letter)...">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning text-white">
            <i class="fas fa-print mr-1"></i> Queue for Print
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ── AJAX submission ──────────────────────────────────────
function submitLetter(event) {
    if (event) event.preventDefault();
    var $form = $('#letterForm');
    var $btn  = $form.find('button[type="submit"]');
    var prev  = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Queuing...');

    $.ajax({
        url:  $form.attr('action'),
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json'
    }).done(function (res) {
        if (res && res.success) {
            toastr.success('Letter queued for print.');
            $('#modalLetter').modal('hide');
            $form[0].reset();
            setTimeout(function () { location.reload(); }, 600);
        } else {
            toastr.error((res && res.message) || 'Failed to queue letter.');
        }
    }).fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message)
               || (xhr.responseJSON && xhr.responseJSON.errors
                    && Object.values(xhr.responseJSON.errors).flat().join(', '))
               || 'Server error while queuing letter.';
        toastr.error(msg);
    }).always(function () {
        $btn.prop('disabled', false).html(prev);
    });
    return false;
}
</script>
