<?php

namespace App\Services\CallCenter;

use App\Models\PatientCallLog;
use App\Models\User;
use App\Models\CallCenter\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * DialService — bridges the Call Center to MikoPBX telephony.
 *
 * Uses the HasMikoPBXExtension trait on the User model:
 *   $agent->callNumber($phone)  →  AMI Originate (rings agent first, then bridges)
 *
 * Flow:
 *   1. Agent clicks "Call" on a task/patient
 *   2. dialPatient() fires $agent->callNumber($phone)
 *   3. A PatientCallLog is created with outcome='dialing'
 *   4. The Log Call modal opens pre-filled
 *   5. Agent picks the outcome → updateOutcome() updates the log
 */
class DialService
{
    /**
     * Originate an outbound call from the current agent to a patient.
     *
     * @param User    $patient
     * @param int|null $taskId  Optional task to link
     * @return array  { success, call_log_id, message }
     */
    public function dialPatient(User $patient, ?int $taskId = null): array
    {
        $agent = Auth::user();

        // ── 1. Validate the agent has a PBX extension ──────────
        if (! $agent->hasPbxExtension()) {
            return [
                'success'     => false,
                'message'     => "No PBX extension assigned to agent {$agent->name}. Assign one in admin settings.",
                'call_log_id' => null,
            ];
        }

        // ── 2. Get the phone number to dial ────────────────────
        $phone = $this->cleanPhone($patient->phone);
        if (! $phone) {
            return [
                'success'     => false,
                'message'     => "Patient {$patient->name} has no valid phone number.",
                'call_log_id' => null,
            ];
        }

        // ── 3. Create a PatientCallLog entry (dialing state) ────
        $log = PatientCallLog::create([
            'patient_id'   => $patient->id,
            'call_by'      => $agent->id,
            'method'       => 'outgoing',
            'type'         => $taskId ? (Task::find($taskId)?->task_type ?? 'followup_call') : 'followup_call',
            'contact_info' => $phone,
            'call_date'    => now(),
            'call_note'    => 'Auto-dial initiated from call center board.',
            'duration'     => 0,
            'receive'      => 0,
            'die'          => 0,
            'call_count'   => PatientCallLog::where('patient_id', $patient->id)->count() + 1,
            'task_id'      => $taskId,
            'caller_opinion' => 'dialing',
        ]);

        // ── 4. Fire the AMI Originate via MikoPBX trait ─────────
        try {
            $result = $agent->callNumber($phone);

            if (($result['Response'] ?? '') === 'Success' || ($result['Response'] ?? '') === 'Follows') {
                return [
                    'success'     => true,
                    'call_log_id' => $log->id,
                    'message'     => "Dialing {$phone} from extension {$agent->getPbxExtension()}...",
                ];
            }

            // AMI returned a non-success response
            return [
                'success'     => false,
                'call_log_id' => $log->id,
                'message'     => 'PBX rejected the call: ' . ($result['Message'] ?? 'Unknown error'),
            ];

        } catch (\Throwable $e) {
            Log::error('MikoPBX dial failed', [
                'agent'   => $agent->id,
                'patient' => $patient->id,
                'phone'   => $phone,
                'error'   => $e->getMessage(),
            ]);

            // Update the log to reflect failure
            $log->update(['caller_opinion' => 'dial_failed', 'call_note' => $e->getMessage()]);

            return [
                'success'     => false,
                'call_log_id' => $log->id,
                'message'     => 'Telephony error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update a call log's outcome after the agent finishes the call.
     *
     * @param int   $callLogId
     * @param array $data  caller_opinion, duration, call_note, receive, die, etc.
     * @return PatientCallLog|null
     */
    public function updateOutcome(int $callLogId, array $data): ?PatientCallLog
    {
        $log = PatientCallLog::find($callLogId);
        if (! $log) {
            return null;
        }

        $log->update([
            'caller_opinion'  => $data['caller_opinion']  ?? $log->caller_opinion,
            'duration'        => $data['duration']        ?? $log->duration,
            'call_note'       => $data['call_note']       ?? $log->call_note,
            'receive'         => $data['receive']         ?? $log->receive,
            'die'             => $data['die']             ?? $log->die,
            'transfer_to'     => $data['transfer_to']     ?? $log->transfer_to,
            'transfer_cause'  => $data['transfer_cause']  ?? $log->transfer_cause,
        ]);

        return $log->fresh();
    }

    /**
     * Clean a phone number for dialing (strip spaces, dashes, parentheses;
     * ensure it starts with country code or 0).
     */
    private function cleanPhone(?string $phone): ?string
    {
        if (! $phone || $phone === 'INVALID') {
            return null;
        }
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        if (strlen($cleaned) < 6) {
            return null;
        }
        return $cleaned;
    }
}
