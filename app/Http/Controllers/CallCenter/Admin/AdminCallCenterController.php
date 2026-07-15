<?php

namespace App\Http\Controllers\CallCenter\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\Task;
use App\Models\CallCenter\AgentDailyStat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminCallCenterController extends Controller
{
    /**
     * KPI array for admin views.
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
     * Build $rankedStats with computed properties the blade expects.
     */
    private function rankedStats()
    {
        $todayStats = AgentDailyStat::with('agent')->whereDate('stat_date', today())->get();

        return $todayStats->sortByDesc('success_rate')->values()->map(function ($stat, $index) {
            $rank = $index + 1;

            $stat->rank = $rank;

            // Rank badge class + medal
            if ($rank === 1) {
                $stat->rank_border_class = 'border-warning';
                $stat->rank_badge_class = 'badge-warning';
                $stat->rank_medal = '🥇';
            } elseif ($rank === 2) {
                $stat->rank_border_class = 'border-secondary';
                $stat->rank_badge_class = 'badge-secondary';
                $stat->rank_medal = '🥈';
            } elseif ($rank === 3) {
                $stat->rank_border_class = 'border-info';
                $stat->rank_badge_class = 'badge-info';
                $stat->rank_medal = '🥉';
            } else {
                $stat->rank_border_class = 'border-light';
                $stat->rank_badge_class = 'badge-light';
                $stat->rank_medal = '';
            }

            // Success rate color
            $rate = $stat->success_rate ?? 0;
            if ($rate >= 80) {
                $stat->success_rate_color = 'var(--cc-success, #39da8a)';
            } elseif ($rate >= 60) {
                $stat->success_rate_color = 'var(--cc-warning, #fdac41)';
            } else {
                $stat->success_rate_color = 'var(--cc-danger, #ff5b5b)';
            }

            return $stat;
        });
    }

    public function index()
    {
        $agents       = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->get();
        $kpi          = $this->kpi();
        $rankedStats  = $this->rankedStats();

        return view('callcenter.admin.index', compact('agents', 'kpi', 'rankedStats'));
    }

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
            $query->where(fn($q) => $q->whereDoesntHave('appointments')->orWhereHas('appointments', fn($q2) => $q2->where('date', '<', $date)));
        }
        if ($request->filled('not_called_days')) {
            $date = now()->subDays($request->not_called_days);
            $query->where(fn($q) => $q->whereDoesntHave('callLogs')->orWhereHas('callLogs', fn($q2) => $q2->where('call_date', '<', $date)));
        }
        if ($request->filled('missed_followup') && $request->missed_followup === 'yes') {
            $query->whereHas('tasks', fn($q) => $q->where('task_type', 'followup_call')->where('status', 'pending')->where('due_date', '<', today()));
        }

        $limit    = (int) ($request->count ?? 500);
        $patients = $query->limit($limit)->get(['id','name','phone','gender']);
        $agents   = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->get();

        if ($request->ajax()) {
            return response()->json(['patients' => $patients, 'total' => $patients->count()]);
        }

        return view('callcenter.admin.assign', compact('patients', 'agents'));
    }

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
                'patient_id' => $patientId, 'agent_id' => $agentId, 'assigned_by' => Auth::id(),
                'title' => Task::TYPES[$request->task_type] ?? $request->task_type, 'task_type' => $request->task_type,
                'call_type' => 'outgoing', 'priority' => $request->priority, 'status' => 'pending',
                'due_date' => $request->due_date ?? today(), 'note' => $request->note ?? '',
            ]);
            $count++;
            $agentIndex++;
        }

        return $request->ajax()
            ? response()->json(['success' => true, 'count' => $count, 'message' => "$count tasks assigned."])
            : back()->with('success', "$count tasks assigned.");
    }

    public function monitor()
    {
        $agents = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))
            ->with(['currentTask' => fn($q) => $q->pending()->latest()])
            ->with(['dailyStats' => fn($q) => $q->where('stat_date', today())])
            ->get();

        $kpi = $this->kpi();

        // ★ Compute rank + color properties for each agent (same as index())
        $agents = $agents->sortByDesc(fn($a) => $a->dailyStats->first()?->success_rate ?? 0)->values()->map(function ($ag, $index) {
            $stat = $ag->dailyStats->first();
            $rank = $index + 1;
            $rate = $stat?->success_rate ?? 0;

            $ag->rank = $rank;
            $ag->total_calls = $stat?->total_calls ?? 0;
            $ag->completed_tasks = $stat?->completed_tasks ?? 0;
            $ag->transferred_tasks = $stat?->transferred_tasks ?? 0;
            $ag->pending_tasks = \App\Models\CallCenter\Task::forAgent($ag->id)->pending()->count();
            $ag->success_rate = $rate;

            if ($rate >= 80) {
                $ag->success_rate_color = 'var(--cc-success, #39da8a)';
            } elseif ($rate >= 60) {
                $ag->success_rate_color = 'var(--cc-warning, #fdac41)';
            } else {
                $ag->success_rate_color = 'var(--cc-danger, #ff5b5b)';
            }

            return $ag;
        });

        // ★ Fetch MikoPBX data directly from the package services (Frest-styled, no Livewire needed)
        $pbxAgents = collect();
        $activeCalls = [];
        $activeCallCount = 0;
        $health = ['overall' => 'unknown', 'ami' => 'unknown', 'rest' => 'unknown', 'sip' => 'unknown'];
        $pbxConfig = [
            'url' => config('mikopbx.url', '—'),
            'ami_host' => config('mikopbx.ami.host', '—'),
            'ami_port' => config('mikopbx.ami.port', '—'),
        ];

        // ★ Fetch PBX agents (extensions with live status from AMI)
        try {
            if (class_exists(\BitDreamIT\MikoPBX\Services\AgentService::class)) {
                $pbxAgents = app(\BitDreamIT\MikoPBX\Services\AgentService::class)->all();
            }
        } catch (\Throwable $e) {
            // PBX offline — $pbxAgents stays empty
        }

        // ★ Fetch active calls from REST API
        try {
            if (class_exists(\BitDreamIT\MikoPBX\Services\RestApiService::class)) {
                $api = app(\BitDreamIT\MikoPBX\Services\RestApiService::class);
                $response = $api->getActiveCalls();
                $activeCalls = $response['data'] ?? [];
                if (is_array($activeCalls) && !array_is_list($activeCalls)) {
                    $activeCalls = array_values($activeCalls);
                }
                $activeCallCount = count($activeCalls);
                $health['rest'] = 'ok';
            }
        } catch (\Throwable $e) {
            $health['rest'] = 'fail';
        }

        // ★ Fetch health status from HealthCheckService
        // check() returns: ['amiOk'=>bool, 'ariOk'=>bool, 'sipOk'=>bool, 'calls'=>int, 'online'=>int, 'status'=>'healthy'|'degraded'|'critical']
        try {
            if (class_exists(\BitDreamIT\MikoPBX\Services\HealthCheckService::class)) {
                $healthResult = app(\BitDreamIT\MikoPBX\Services\HealthCheckService::class)->check();
                $health['ami']     = ($healthResult['amiOk'] ?? false) ? 'ok' : 'fail';
                $health['rest']    = ($healthResult['ariOk'] ?? false) ? 'ok' : 'fail';
                $health['sip']     = ($healthResult['sipOk'] ?? false) ? 'ok' : 'fail';
                $health['overall'] = $healthResult['status'] ?? 'unknown';
            }
        } catch (\Throwable $e) {
            // Health check failed — keep defaults
        }

        $kpi['active_calls'] = $activeCallCount;

        return view('callcenter.admin.monitor', compact('agents', 'kpi', 'activeCallCount', 'activeCalls', 'pbxAgents', 'health', 'pbxConfig'));
    }

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
            ->groupBy('agent_id')->orderByDesc('total_calls')->get();

        // ★ Compute rank + color properties (same logic as rankedStats in index())
        $stats = $stats->values()->map(function ($stat, $index) {
            $rank = $index + 1;
            $rate = $stat->avg_success_rate ?? 0;

            $stat->rank = $rank;

            if ($rank === 1) {
                $stat->rank_border_class = 'border-warning';
                $stat->rank_badge_class = 'badge-warning';
                $stat->rank_medal = '🥇';
            } elseif ($rank === 2) {
                $stat->rank_border_class = 'border-secondary';
                $stat->rank_badge_class = 'badge-secondary';
                $stat->rank_medal = '🥈';
            } elseif ($rank === 3) {
                $stat->rank_border_class = 'border-info';
                $stat->rank_badge_class = 'badge-info';
                $stat->rank_medal = '🥉';
            } else {
                $stat->rank_border_class = 'border-light';
                $stat->rank_badge_class = 'badge-light';
                $stat->rank_medal = '';
            }

            if ($rate >= 80) {
                $stat->success_rate_color = 'var(--cc-success, #39da8a)';
            } elseif ($rate >= 60) {
                $stat->success_rate_color = 'var(--cc-warning, #fdac41)';
            } else {
                $stat->success_rate_color = 'var(--cc-danger, #ff5b5b)';
            }

            return $stat;
        });

        return view('callcenter.admin.performance', compact('stats', 'from', 'to'));
    }
}
