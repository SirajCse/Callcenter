<?php

namespace App\Http\Controllers\CallCenter\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\Task;
use App\Models\CallCenter\AgentDailyStat;
use App\Models\PatientCallLog;
use App\Models\User;
use App\Services\CallCenter\CallCenterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminCallCenterController extends Controller
{
    /**
     * Admin panel index.
     */
    public function index()
    {
        $agents    = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->get();
        $todayStats = AgentDailyStat::with('agent')->whereDate('stat_date', today())->get();

        // ★ FIX: Pass $stats that the blade view expects
        $common = app(CallCenterData::class)->getCommonData();

        return view('callcenter.admin.index', array_merge(
            compact('agents', 'todayStats'), $common
        ));
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

        $common = app(CallCenterData::class)->getCommonData();

        if ($request->ajax()) {
            return response()->json(['patients' => $patients, 'total' => $patients->count()]);
        }

        return view('callcenter.admin.assign', array_merge(
            compact('patients'), $common
        ));
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

        $todayStats = AgentDailyStat::whereDate('stat_date', today())->with('agent')->get();

        $common = app(CallCenterData::class)->getCommonData();

        return view('callcenter.admin.monitor', array_merge(
            compact('agents', 'todayStats'), $common
        ));
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

        $common = app(CallCenterData::class)->getCommonData();

        return view('callcenter.admin.performance', array_merge(
            compact('stats', 'from', 'to'), $common
        ));
    }
}
