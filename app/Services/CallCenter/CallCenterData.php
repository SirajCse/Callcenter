<?php

namespace App\Services\CallCenter;

use App\Models\CallCenter\Task;
use App\Models\CallCenter\SmsLog;
use App\Models\CallCenter\LetterLog;
use App\Models\CallCenter\MissingAddress;
use App\Models\PatientCallLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Provides per-view $stats arrays + shared data for call-center views.
 *
 * Each view needs DIFFERENT $stats keys (e.g. calllogs needs
 * total/answered/no_answer/today, tasks needs pending/completed/...).
 * This service computes the correct keys for each view.
 */
class CallCenterData
{
    /**
     * Stats for the BOARD view: completed, followup, pending, transferred.
     */
    public function boardStats(int $agentId): array
    {
        return [
            'completed'   => Task::forAgent($agentId)->completed()->whereDate('completed_at', today())->count(),
            'pending'     => Task::forAgent($agentId)->pending()->count(),
            'transferred' => Task::forAgent($agentId)->transferred()->whereDate('transferred_at', today())->count(),
            'followup'    => Task::forAgent($agentId)->pending()->where('task_type', 'followup_call')->count(),
        ];
    }

    /**
     * Stats for the TASKS view: pending, completed, transferred, pinned, priority, overdue.
     */
    public function taskStats(int $agentId): array
    {
        return [
            'pending'     => Task::forAgent($agentId)->pending()->count(),
            'completed'   => Task::forAgent($agentId)->completed()->count(),
            'transferred' => Task::forAgent($agentId)->transferred()->count(),
            'pinned'      => Task::forAgent($agentId)->pinned()->pending()->count(),
            'priority'    => Task::forAgent($agentId)->pending()->highPriority()->count(),
            'overdue'     => Task::forAgent($agentId)->pending()->where('due_date', '<', today())->count(),
        ];
    }

    /**
     * Stats for the CALL LOGS view: total, answered, no_answer, today.
     */
    public function callLogStats(int $agentId): array
    {
        $logs = PatientCallLog::where('call_by', $agentId);
        return [
            'total'     => (clone $logs)->count(),
            'answered'  => (clone $logs)->where('receive', 1)->count(),
            'no_answer' => (clone $logs)->whereIn('caller_opinion', ['no_answer','busy','out_of_reach','wrong_number'])->count(),
            'today'     => (clone $logs)->whereDate('call_date', today())->count(),
        ];
    }

    /**
     * Stats for the FOLLOW-UP view: total, not_called, with_phone, no_phone.
     * Computed for the current page of patients.
     */
    public function followUpStats($patients): array
    {
        $total     = $patients->count();
        $notCalled = 0;
        $withPhone = 0;
        $noPhone   = 0;

        foreach ($patients as $p) {
            $hasPhone = !empty($p->phone) && $p->phone !== 'INVALID';
            $hasCalls = $p->call_count > 0 || ($p->last_call_date !== null);

            if (!$hasCalls) $notCalled++;
            if ($hasPhone) $withPhone++;
            else $noPhone++;
        }

        return compact('total', 'notCalled', 'withPhone', 'noPhone');
    }

    /**
     * Stats for the SMS view: total, sent, failed, pending.
     */
    public function smsStats(): array
    {
        return [
            'total'   => SmsLog::count(),
            'sent'    => SmsLog::where('status', 'sent')->count(),
            'failed'  => SmsLog::where('status', 'failed')->count(),
            'pending' => SmsLog::where('status', 'pending')->count(),
        ];
    }

    /**
     * Stats for the LETTERS view: total, sent, queued, printed.
     */
    public function letterStats(): array
    {
        return [
            'total'   => LetterLog::count(),
            'sent'    => LetterLog::whereIn('status', ['sent', 'delivered'])->count(),
            'queued'  => LetterLog::where('status', 'queued')->count(),
            'printed' => LetterLog::where('status', 'printed')->count(),
        ];
    }

    /**
     * Stats for the MISSING ADDRESS view: total, pending, awaiting, resolved.
     */
    public function missingAddressStats(): array
    {
        return [
            'total'     => MissingAddress::count(),
            'pending'   => MissingAddress::where('status', 'pending')->count(),
            'awaiting'  => MissingAddress::where('status', 'awaiting')->count(),
            'resolved'  => MissingAddress::where('status', 'resolved')->orWhere('status', 'delivered')->count(),
        ];
    }

    /**
     * Agents list (for dropdowns). Excludes the current user.
     */
    public function agents(int $excludeId = 0)
    {
        return User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent', 'supervisor']))
            ->where('id', '!=', $excludeId)
            ->get(['id', 'name']);
    }

    /**
     * Patient-related counts for the patient card.
     * Moves these queries OUT of the blade view and INTO the controller.
     */
    public function getPatientStats(int $patientId): array
    {
        $totalCalls = PatientCallLog::where('patient_id', $patientId)->count();
        $totalAppts = \App\Models\Chamber\Appointment::where('patient_id', $patientId)->count();
        $totalLabs  = \App\Models\Lab\Group::where('patient_id', $patientId)->count();
        $lastCall   = PatientCallLog::where('patient_id', $patientId)->latest('call_date')->first();
        $lastVisit  = \App\Models\Chamber\Appointment::where('patient_id', $patientId)->latest('date')->value('date');

        return compact('totalCalls', 'totalAppts', 'totalLabs', 'lastCall', 'lastVisit');
    }
}
