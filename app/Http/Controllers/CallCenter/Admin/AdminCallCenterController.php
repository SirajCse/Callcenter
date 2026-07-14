<?php

namespace App\Http\Controllers\CallCenter\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\Task;
use App\Models\CallCenter\AgentDailyStat;
use App\Models\PatientCallLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminCallCenterController extends Controller
{
    /**
     * Compute the KPI array the admin blade views use.
     * Keys: total_agents, online_agents, tasks_today, pending_tasks, completed_today, overdue_tasks
     */
    private function kpi(): array
    {
        $agents = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->get();

        return [
            'total_agents'    => $agents->count(),
            'online_agents'   => $agents->where('is_online', true)->count(),
            'tasks_today'     => Task::whereDate('created_at', today())->count(),
            'pending_tasks'   => Task::pending()->count(),
            'completed_today' => Task::completed()->whereDate('completed_at', today())->count(),
            'overdue_tasks'   => Task::pending()->where('due_date', '<', today())->count(),
        ];
    }

    /**
     * Admin panel index.
     */
    public function index()
    {
        $agents     = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->get();
        $todayStats = AgentDailyStat::with('agent')->whereDate('stat_date', today())->get();
        $kpi        = $this->kpi();

        // ★ Ranked agents (by success_rate desc) for the performance mini-card
        $ranked = $todayStats->sortByDesc('success_rate')->values();

        return view('callcenter.admin.index', compact('agents', 'todayStats', 'kpi', 'ranked'));
    }

    /**
     * Filter patients by admin criteria.
     */
    public function filterPatients(Request $request)
    {
        $query = User::query();

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('age_from')) {
            $query->where(DB::raw('TIMESTAMPDIFF(YEAR, dob, CURDATE())'), '>=', $request->age_from);
        }
        if ($request->filled('age_to')) {
            $query->where(DB::raw('TIMESTAMPDIFF(YEAR, dob, CURDATE())'), '<=', $request->age_to);
        }

        if ($request->filled('last_visit_months')) {
            $date = now()->subMonths($request->last_visit_months);
            $query->where(function ($q) use ($date) {
                $q->whereDoesntHave('appointments')
                  ->orWhereHas('appointments', fn($q2) => $q2->where('date', '<', $date));
            });
        }

        if ($request->filled('not_called_days')) {
            $date = now()->subDays($request->not_called_days);
            $query->where(function ($q) use ($date) {
                $q->whereDoesntHave('callLogs')
                  ->orWhereHas('callLogs', fn($q2) => $q2->where('call_date', '<', $date));
            });
        }

        if ($request->filled('missed_followup') && $request->missed_followup === 'yes') {
            $query->whereHas('tasks', fn($q) => $q->where('task_type', 'followup_call')
                ->where('status', 'pending')
                ->where('due_date', '<', today()));
        }

        $limit    = (int) ($request->count ?? 500);
        $patients = $query->limit($limit)->get(['id','name','phone','gender']);
        $agents   = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->get();

        if ($request->ajax()) {
            return response()->json(['patients' => $patients, 'total' => $patients->count()]);
        }

        return view('callcenter.admin.assign', compact('patients', 'agents'));
    }

    /**
     * Assign filtered patients as tasks to agent(s).
     */
    public function assignTasks(Request $request)
    {
        $request->validate([
            'patient_ids'   => 'required|array|min:1',
            'patient_ids.*' => 'exists:users,id',
            'agent_id'      => 'nullable|exists:users,id',
            'task_type'     => 'required|string',
            'priority'      => 'required|in:high,medium,low',
            'due_date'      => 'nullable|date',
            'note'          => 'nullable|string',
            'distribute'    => 'boolean',
        ]);

        $agents = $request->distribute
            ? User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->pluck('id')->toArray()
            : [$request->agent_id];

        $count = 0;
        $agentIndex = 0;

        foreach ($request->patient_ids as $patientId) {
            $agentId = $agents[$agentIndex % count($agents)];

            Task::create([
                'patient_id'  => $patientId,
                'agent_id'    => $agentId,
                'assigned_by' => Auth::id(),
                'title'       => Task::TYPES[$request->task_type] ?? $request->task_type,
                'task_type'   => $request->task_type,
                'call_type'   => 'outgoing',
                'priority'    => $request->priority,
                'status'      => 'pending',
                'due_date'    => $request->due_date ?? today(),
                'note'        => $request->note ?? '',
            ]);

            $count++;
            $agentIndex++;
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'count' => $count, 'message' => "$count tasks assigned."]);
        }

        return back()->with('success', "$count tasks assigned.");
    }

    /**
     * Live monitor.
     */
    public function monitor()
    {
        $agents = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))
            ->with(['currentTask' => fn($q) => $q->pending()->latest()])
            ->get();
        $kpi = $this->kpi();

        return view('callcenter.admin.monitor', compact('agents', 'kpi'));
    }

    /**
     * Agent performance ranking.
     */
    public function performance(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? today()->toDateString();

        $stats = AgentDailyStat::with('agent')
            ->whereBetween('stat_date', [$from, $to])
            ->select('agent_id',
                DB::raw('SUM(total_calls) as total_calls'),
                DB::raw('SUM(completed_tasks) as completed_tasks'),
                DB::raw('SUM(transferred_tasks) as transferred_tasks'),
                DB::raw('AVG(success_rate) as avg_success_rate')
            )
            ->groupBy('agent_id')
            ->orderByDesc('total_calls')
            ->get();

        return view('callcenter.admin.performance', compact('stats', 'from', 'to'));
    }
}
