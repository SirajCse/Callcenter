/* ═══════════════════════════════════════════════════════════════
   CALL CENTER BOARD JS — Auto-Dial ONLY
   ═══════════════════════════════════════════════════════════════
   This file ONLY contains the MikoPBX auto-dial functions.
   All form-submit handlers (submitLogCall, submitNewTask, submitTransfer,
   submitSms, submitLetter, dialAndLog) are defined INLINE in their
   respective blade modal files — do NOT duplicate them here.
   ═══════════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    // ─────────────────────────────────────────────────────────────
    // ★ AUTO-DIAL: Originate outbound call via MikoPBX AMI
    // ─────────────────────────────────────────────────────────────

    /**
     * Dial a patient — fires AMI Originate via the backend DialService.
     * Returns a Promise that resolves with { success, call_log_id, message }.
     *
     * @param {number|string} patientId
     * @param {number|string|null} taskId
     */
    window.dialPatient = function (patientId, taskId) {
        return new Promise(function (resolve) {
            if (!patientId || patientId === 'null' || patientId === 'undefined') {
                toastr.error('No patient selected to dial.');
                resolve({ success: false, message: 'No patient selected.' });
                return;
            }

            // Get CSRF token from meta tag or hidden input
            var csrfToken = $('meta[name="csrf-token"]').attr('content')
                         || $('input[name="_token"]').val()
                         || '';

            toastr.info('Initiating call via PBX...', '', { timeOut: 3000 });

            $.ajax({
                url: '/callcenter/dial',
                type: 'POST',
                data: {
                    _token: csrfToken,
                    patient_id: patientId,
                    task_id: taskId || '',
                },
                dataType: 'json'
            }).done(function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Dialing...', 'Dialing', { timeOut: 5000 });
                    // Store the call_log_id so submitLogCall can update the outcome
                    window._pendingCallLogId = res.call_log_id || null;
                } else {
                    toastr.warning(res.message || 'Dial failed.', 'PBX Warning', { timeOut: 6000 });
                    window._pendingCallLogId = res.call_log_id || null;
                }
                resolve(res);
            }).fail(function (xhr) {
                // ★ Show the ACTUAL validation error from Laravel (422)
                var msg = 'Dial failed.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        // Laravel validation errors: { errors: { field: ["message"] } }
                        msg = Object.values(xhr.responseJSON.errors).flat().join('; ');
                    } else if (xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                } else if (xhr.status === 419) {
                    msg = 'Session expired. Please refresh the page and try again.';
                } else if (xhr.status === 404) {
                    msg = 'Dial endpoint not found. Ensure routes/callcenter.php is updated.';
                } else if (xhr.status === 0) {
                    msg = 'Network error — could not reach the server.';
                } else {
                    msg = 'Server error (' + xhr.status + '). Check PBX settings.';
                }
                toastr.error(msg, 'Dial Error', { timeOut: 8000 });
                window._pendingCallLogId = null;
                resolve({ success: false, message: msg });
            });
        });
    };

    /**
     * Dial a patient, then open the Log Call modal pre-filled.
     * This is what the "Call" button on task cards + patient card calls.
     */
    window.dialAndOpenLogCall = function (patientId, taskId) {
        // Open the modal immediately (don't block on dial)
        if (typeof openLogCall === 'function') {
            openLogCall(patientId, taskId);
        }

        // Fire the dial in parallel
        if (patientId && patientId !== 'null') {
            dialPatient(patientId, taskId);
        }
    };

    // ─────────────────────────────────────────────────────────────
    // ★ FIX: Blur focus before ANY modal hides → prevents
    //   "Blocked aria-hidden on an element because its descendant
    //    retained focus" accessibility warning.
    // ─────────────────────────────────────────────────────────────
    $(document).on('hide.bs.modal', '.modal', function () {
        // Blur whatever element currently has focus inside this modal
        $(this).find('button:focus, a:focus, input:focus, select:focus, textarea:focus').blur();
        // Move focus to body as a safe fallback
        document.activeElement && document.activeElement.blur && document.activeElement.blur();
    });

})();
