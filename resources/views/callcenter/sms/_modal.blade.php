{{-- resources/views/callcenter/sms/_modal.blade.php --}}
<div class="modal fade" id="modalSms" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h6 class="modal-title"><i class="fas fa-comment-alt mr-2"></i>Send SMS</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('callcenter.sms.store') }}" method="POST">
        @csrf
        <input type="hidden" name="patient_id" id="smsPatientId">
        <div class="modal-body">
          <div class="form-group">
            <label class="small font-weight-bold">Phone Number</label>
            <input type="text" name="phone_number" class="form-control form-control-sm" id="smsPhone" placeholder="+880...">
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Template</label>
            <select class="form-control form-control-sm" onchange="fillSmsTemplate(this.value)">
              <option value="">— Custom Message —</option>
              <option value="missed">Missed Call — We tried to reach you</option>
              <option value="appt">Appointment Reminder</option>
              <option value="lab">Lab Results Ready</option>
              <option value="fu">Follow-up Reminder</option>
            </select>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">Message</label>
            <textarea name="message" id="smsMessage" class="form-control form-control-sm" rows="4"
              placeholder="Enter your message..." oninput="updateSmsCount()"></textarea>
            <small class="text-muted"><span id="smsCharCount">0</span>/160 characters</small>
          </div>
          <input type="hidden" name="template_key" id="smsTemplateKey">
          <input type="hidden" name="task_id" id="smsTaskId">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white"><i class="fas fa-paper-plane mr-1"></i> Send SMS</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var smsTemplates = {
    missed : 'We tried to reach you today. Please call us back at your earliest convenience.',
    appt   : 'Reminder: You have an upcoming appointment. Please confirm by calling us.',
    lab    : 'Your lab results are ready. Please contact us to discuss the findings.',
    fu     : 'This is a follow-up from your last visit. We would like to check on your health.'
};

function fillSmsTemplate(key) {
    if (smsTemplates[key]) {
        document.getElementById('smsMessage').value = smsTemplates[key];
        document.getElementById('smsTemplateKey').value = key;
        updateSmsCount();
    }
}

function updateSmsCount() {
    document.getElementById('smsCharCount').textContent =
        document.getElementById('smsMessage').value.length;
}
</script>
