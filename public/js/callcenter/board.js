/* ═══════════════════════════════════════════════════════════════
   CALL CENTER BOARD JS — Auto-Dial + AJAX Interactions
   Loaded via: <script src="{{ asset('js/callcenter/board.js') }}"></script>
   ═══════════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
    var DIAL_URL   = window.CC_DIAL_URL  || '/callcenter/dial';
    var OUTCOME_URL = window.CC_OUTCOME_URL_TEMPLATE || '/callcenter/calllogs/__ID__/outcome';

    // ─────────────────────────────────────────────────────────────
    // ★ AUTO-DIAL: Originate outbound call via MikoPBX AMI
    // ─────────────────────────────────────────────────────────────

    /**
     * Dial a patient — fires AMI Originate via the backend DialService.
     * Returns a Promise that resolves with { success, call_log_id, message }.
     *
     * @param {number} patientId
     * @param {number|null} taskId
     */
    window.dialPatient = function (patientId, taskId) {
        return new Promise(function (resolve) {
            if (!patientId) {
                toastr.error('No patient selected to dial.');
                resolve({ success: false });
                return;
            }

            toastr.info('Initiating call via PBX...', '', { timeOut: 3000 });

            $.ajax({
                url: DIAL_URL,
                type: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                    patient_id: patientId,
                    task_id: taskId || null,
                },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        toastr.success(res.message, 'Dialing', { timeOut: 5000 });
                        // Store the call_log_id so the Log Call modal can update it
                        window._pendingCallLogId = res.call_log_id;
                    } else {
                        toastr.warning(res.message || 'Dial failed', 'PBX Warning', { timeOut: 6000 });
                        window._pendingCallLogId = res.call_log_id || null;
                    }
                    resolve(res);
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON?.message || 'Telephony connection failed. Check PBX settings.';
                    toastr.error(msg, 'Dial Error', { timeOut: 6000 });
                    resolve({ success: false, message: msg });
                }
            });
        });
    };

    /**
     * Dial a patient, then open the Log Call modal pre-filled.
     * This is what the "Call" button on task cards + patient card calls.
     */
    window.dialAndOpenLogCall = function (patientId, taskId) {
        // Open the modal immediately (don't block on dial)
        openLogCall(patientId, taskId);

        // Fire the dial in parallel
        if (patientId) {
            dialPatient(patientId, taskId);
        }
    };

    // ─────────────────────────────────────────────────────────────
    // AJAX FORM SUBMISSIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Submit the Log Call form via AJAX.
     * If a call_log_id exists from auto-dial, updates the outcome instead.
     */
    window.submitLogCall = function (e) {
        e.preventDefault();
        var $form = $(e.target);
        var $btn  = $form.find('button[type="submit"]:last');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        var data = $form.serialize();

        // If we have a pending call_log_id from auto-dial, update the outcome instead
        if (window._pendingCallLogId) {
            var url = OUTCOME_URL_TEMPLATE.replace('__ID__', window._pendingCallLogId);
            $.ajax({
                url: url,
                type: 'POST',
                data: data + '&_method=POST',
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        toastr.success(res.message || 'Call outcome saved.');
                        $('#modalLogCall').modal('hide');
                        window._pendingCallLogId = null;
                        location.reload();
                    } else {
                        toastr.error(res.message || 'Failed to save call outcome.');
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Error saving call.');
                },
                complete: function () {
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
            return false;
        }

        // Normal store (no auto-dial)
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Call logged successfully.');
                    $('#modalLogCall').modal('hide');
                    location.reload();
                } else {
                    toastr.error(res.message || 'Failed to log call.');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error logging call.');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });

        return false;
    };

    /**
     * "Dial & Log" button handler — forces outgoing, dials, then submits.
     */
    window.dialAndLog = function () {
        var patientId = $('#logCallPatientId').val();
        var taskId    = $('#logCallTaskId').val();

        // Force method to outgoing
        $('#logCallForm select[name="method"]').val('outgoing');

        // Dial first, then submit the form
        dialPatient(patientId, taskId).then(function () {
            $('#logCallForm').submit();
        });
    };

    /**
     * Submit New Task form via AJAX.
     */
    window.submitNewTask = function (e) {
        e.preventDefault();
        var $form = $(e.target);
        var $btn  = $form.find('button[type="submit"]:last');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    toastr.success('Task created successfully.');
                    $('#modalNewTask').modal('hide');
                    location.reload();
                } else {
                    toastr.error(res.message || 'Failed to create task.');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error creating task.');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });

        return false;
    };

    /**
     * Submit Transfer form via AJAX.
     */
    window.submitTransfer = function (e) {
        e.preventDefault();
        var taskId = $('#transferTaskId').val();
        if (!taskId) {
            toastr.error('No task selected.');
            return false;
        }

        var $form = $(e.target);
        var $btn  = $form.find('button[type="submit"]:last');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Transferring...');

        var url = $form.attr('action').replace(':id', taskId).replace('%3Aid', taskId);

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Task transferred.');
                    $('#modalTransfer').modal('hide');
                    location.reload();
                } else {
                    toastr.error(res.message || 'Transfer failed.');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error transferring task.');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });

        return false;
    };

    /**
     * Submit SMS form via AJAX.
     */
    window.submitSms = function (e) {
        e.preventDefault();
        var $form = $(e.target);
        var $btn  = $form.find('button[type="submit"]:last');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    toastr.success('SMS sent successfully.');
                    $('#modalSms').modal('hide');
                    location.reload();
                } else {
                    toastr.error(res.message || 'Failed to send SMS.');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error sending SMS.');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });

        return false;
    };

    /**
     * Submit Letter form via AJAX.
     */
    window.submitLetter = function (e) {
        e.preventDefault();
        var $form = $(e.target);
        var $btn  = $form.find('button[type="submit"]:last');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Queuing...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    toastr.success('Letter queued for print.');
                    $('#modalLetter').modal('hide');
                    location.reload();
                } else {
                    toastr.error(res.message || 'Failed to queue letter.');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error queuing letter.');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });

        return false;
    };

    // ─────────────────────────────────────────────────────────────
    // SMS template filler + char counter
    // ─────────────────────────────────────────────────────────────
    window.fillSmsTemplate = function (key) {
        if (window.CC_SMS_TEMPLATES && window.CC_SMS_TEMPLATES[key]) {
            $('#smsMessage').val(window.CC_SMS_TEMPLATES[key]).trigger('input');
            $('#smsTemplateKey').val(key);
        }
    };

    $(document).ready(function () {
        // SMS char counter
        $('#smsMessage').on('input', function () {
            var len = $(this).val().length;
            $('#smsCharCount').text(len);
            if (len > 160) {
                $('#smsCharWarning').show();
            } else {
                $('#smsCharWarning').hide();
            }
        });
    });

})();
