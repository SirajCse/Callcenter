<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\Task;
use App\Models\PatientCallLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowUpController extends Controller
{
    /**
     * Follow-up patient list with filters.
     */
    public function index(Request $request)
    {
        $agent = Auth::user();

        // Patients who have a follow-up task pending for this agent
        $query = User::whereHas('roles', fn($q) => $q->where('name', 'patient'))
            ->where('is_active', true)
            ->with(['latestCallLog' => fn($q) => $q->where('call_by', $agent->id)])
            ->withCount(['callLogs as call_count'])
            ->withMax('callLogs as last_call_date', 'call_date');

        // Filters
        if ($request->filled('agent_id')) {
            $query->whereHas('callLogs', fn($q) => $q->where('call_by', $request->agent_id));
        }
        if ($request->filled('priority')) {
            $query->whereHas('tasks', fn($q) => $q->where('priority', $request->priority)->pending());
        }
        if ($request->filled('from')) {
            $query->where(fn($q) => $q->whereHas('callLogs', fn($q2) => $q2->whereDate('call_date', '>=', $request->from))
                ->orDoesntHave('callLogs'));
        }
        if ($request->filled('to')) {
            $query->whereHas('callLogs', fn($q) => $q->whereDate('call_date', '<=', $request->to));
        }
        if ($request->filled('status')) {
            $outcomeMap = [
                'not_called'       => fn($q) => $q->doesntHave('callLogs'),
                'callback_needed'  => fn($q) => $q->whereHas('callLogs', fn($q2) => $q2->where('caller_opinion', 'callback')),
                'busy'             => fn($q) => $q->whereHas('callLogs', fn($q2) => $q2->where('caller_opinion', 'busy')),
            ];
            if (isset($outcomeMap[$request->status])) {
                $outcomeMap[$request->status]($query);
            }
        }

        $patients = $query->paginate(50)->withQueryString();

        $agents = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->get(['id','name']);

        return view('callcenter.followup.index', compact('patients', 'agents', 'agent'));
    }

    /**
     * Save selected patients as today's task list.
     */
    public function saveToday(Request $request)
    {
        $request->validate([
            'patient_ids'   => 'required|array|min:1',
            'patient_ids.*' => 'exists:users,id',
            'task_type'     => 'nullable|string',
            'priority'      => 'nullable|in:high,medium,low',
        ]);

        $agent    = Auth::user();
        $count    = 0;
        $taskType = $request->task_type ?? 'followup_call';
        $priority = $request->priority  ?? 'medium';

        foreach ($request->patient_ids as $patientId) {
            // Skip if already has pending task today
            $exists = Task::where('patient_id', $patientId)
                ->where('agent_id', $agent->id)
                ->pending()
                ->whereDate('due_date', today())
                ->exists();

            if (!$exists) {
                Task::create([
                    'patient_id'  => $patientId,
                    'agent_id'    => $agent->id,
                    'assigned_by' => $agent->id,
                    'title'       => 'Follow-up Call — ' . now()->format('d M Y'),
                    'task_type'   => $taskType,
                    'call_type'   => 'outgoing',
                    'priority'    => $priority,
                    'status'      => 'pending',
                    'due_date'    => today(),
                ]);
                $count++;
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'count' => $count, 'message' => "$count tasks added to today's list."]);
        }

        return back()->with('success', "$count follow-up tasks added to today.");
    }
}
