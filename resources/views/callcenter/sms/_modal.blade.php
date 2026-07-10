{{-- resources/views/callcenter/sms/_modal.blade.php
    Fixed: AJAX submission via submitSms(event); template select populated
    from \App\Models\CallCenter\SmsLog::TEMPLATES; 160-char counter. --}}
<div class="modal fade" id="modalSms" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h6 class="modal-title"><i class="fas fa-comment-alt mr-2"></i>Send SMS</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('callcenter.sms.store') }}" method="POST"
            id="smsForm" onsubmit="return submitSms(event)">
        @csrf
        <input type="hidden" name="patient_id" id="smsPatientId">
        <input type="hidden" name="task_id"    id="smsTaskId">
        <div class="modal-body">
          <div class="form-group">
            <label class="small font-weight-bold">Phone Number</label>
            <input type="text" name="phone_number" class="form-control form-control-sm"
                   id="smsPhone" placeholder="+880...">
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Template</label>
            <select id="smsTemplateSelect" class="form-control form-control-sm"
                    onchange="fillSmsTemplate(this.value)">
              <option value="">— Custom Message —</option>
              @foreach(\App\Models\CallCenter\SmsLog::TEMPLATES as $key => $body)
              <option value="{{ $key }}">{{ ucfirst($key) }} — {{ \Illuminate\Support\Str::limit($body, 40) }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Message</label>
            <textarea name="message" id="smsMessage" class="form-control form-control-sm" rows="4"
                      maxlength="500" placeholder="Enter your message..."
                      oninput="updateSmsCount()"></textarea>
            <small class="text-muted">
              <span id="smsCharCount">0</span>/160 characters
              <span id="smsCharWarn" class="text-warning" style="display:none;margin-left:6px">
                <i class="fas fa-exclamation-triangle"></i> exceeds single SMS segment
              </span>
            </small>
          </div>
          <input type="hidden" name="template_key" id="smsTemplateKey">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white">
            <i class="fas fa-paper-plane mr-1"></i> Send SMS
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Mirrors \App\Models\CallCenter\SmsLog::TEMPLATES
var smsTemplates = {
    @foreach(\App\Models\CallCenter\SmsLog::TEMPLATES as $key => $body)
    '{{ $key }}': @json($body),
    @endforeach
};

function fillSmsTemplate(key) {
    if (smsTemplates[key]) {
        document.getElementById('smsMessage').value = smsTemplates[key];
        document.getElementById('smsTemplateKey').value = key;
    } else {
        document.getElementById('smsTemplateKey').value = '';
    }
    updateSmsCount();
}

function updateSmsCount() {
    var len = document.getElementById('smsMessage').value.length;
    document.getElementById('smsCharCount').textContent = len;
    document.getElementById('smsCharWarn').style.display = (len > 160) ? 'inline' : 'none';
}

// ── AJAX submission ──────────────────────────────────────
function submitSms(event) {
    if (event) event.preventDefault();
    var $form = $('#smsForm');
    var $btn  = $form.find('button[type="submit"]');
    var prev  = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Sending...');

    $.ajax({
        url:  $form.attr('action'),
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json'
    }).done(function (res) {
        if (res && res.success) {
            toastr.success('SMS sent successfully.');
            $('#modalSms').modal('hide');
            $form[0].reset();
            updateSmsCount();
            setTimeout(function () { location.reload(); }, 600);
        } else {
            toastr.error((res && res.message) || 'Failed to send SMS.');
        }
    }).fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message)
               || (xhr.responseJSON && xhr.responseJSON.errors
                    && Object.values(xhr.responseJSON.errors).flat().join(', '))
               || 'Server error while sending SMS.';
        toastr.error(msg);
    }).always(function () {
        $btn.prop('disabled', false).html(prev);
    });
    return false;
}
</script>
