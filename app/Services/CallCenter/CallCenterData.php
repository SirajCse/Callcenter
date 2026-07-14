<?php

namespace App\Services\CallCenter;

use App\Models\CallCenter\Task;
use App\Models\PatientCallLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Provides common data (stats, agents) that ALL call-center views need.
 *
 * Usage in any controller:
 *   $common = app(CallCenterData::class)->getCommonData();
 *   return view('callcenter.tasks.index', array_merge($common, compact('tasks')));
 */
class CallCenterData
{
    /**
     * Get the common data shared by all call-center views:
     *   - $agent  (current logged-in user)
     *   - $stats  (today's KPI counts: completed, pending, transferred, followup, missed)
     *   - $agents (list of agents/supervisors for dropdowns)
     */
    public function getCommonData(): array
    {
        $agent   = Auth::user();
        $agentId = $agent?->id ?? 0;

        $stats = [
            'completed'   => $agentId ? Task::forAgent($agentId)->completed()->whereDate('completed_at', today())->count() : 0,
            'pending'     => $agentId ? Task::forAgent($agentId)->pending()->count() : 0,
            'transferred' => $agentId ? Task::forAgent($agentId)->transferred()->whereDate('transferred_at', today())->count() : 0,
            'followup'    => $agentId ? Task::forAgent($agentId)->pending()->where('task_type', 'followup_call')->count() : 0,
            'missed'      => $agentId ? PatientCallLog::where('call_by', $agentId)
                                ->whereIn('caller_opinion', ['no_answer','busy','out_of_reach','wrong_number'])->count() : 0,
        ];

        $agents = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent', 'supervisor']))
            ->where('id', '!=', $agentId)
            ->get(['id', 'name']);

        return compact('agent', 'stats', 'agents');
    }

    /**
     * Get patient-related counts for the patient card.
     * Moves these queries OUT of the blade view and INTO the controller.
     */
    public function getPatientStats(int $patientId): array
    {
        $totalCalls = \App\Models\PatientCallLog::where('patient_id', $patientId)->count();
        $totalAppts = \App\Models\Chamber\Appointment::where('patient_id', $patientId)->count();
        $totalLabs  = \App\Models\Lab\Group::where('patient_id', $patientId)->count();
        $lastCall   = \App\Models\PatientCallLog::where('patient_id', $patientId)->latest('call_date')->first();
        $lastVisit  = \App\Models\Chamber\Appointment::where('patient_id', $patientId)->latest('date')->value('date');

        return compact('totalCalls', 'totalAppts', 'totalLabs', 'lastCall', 'lastVisit');
    }
}
