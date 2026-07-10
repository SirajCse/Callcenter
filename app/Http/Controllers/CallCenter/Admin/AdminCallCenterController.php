<?php

namespace App\Http\Controllers\CallCenter\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\Task;
use App\Models\CallCenter\AgentDailyStat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminCallCenterController extends Controller
{
	/**
	 * Dashboard index with KPI metrics.
	 */
	public function index(): View
	{
		$todayStats = $this->getTodayStats();
		$agents = $this->attachAgentDayMetrics($this->getAgents(), $todayStats);
		$rankedStats = $this->attachRanks($todayStats->sortByDesc('total_calls'));

		return view('callcenter.admin.index', [
			'agents' => $agents,
			'todayStats' => $todayStats,
			'rankedStats' => $rankedStats,
			'kpi' => $this->getKpiMetrics($agents, $todayStats),
		]);
	}

	/**
	 * Filter patients with advanced criteria.
	 */
	public function filterPatients(Request $request): JsonResponse
	{
		$query = User::query();

		$this->applyRegisterIdFilter($query, $request->register_id);

		if ($request->filled('patient_type')) {
			$query->where('patient_type', 'like', '%' . $request->patient_type . '%');
		}

		if ($request->filled('district')) {
			$query->whereAny(['present_district', 'permanent_district'], 'like', '%' . $request->district . '%');
		}

		if ($request->filled('district')) {
			$query->whereAny(['present_district', 'permanent_district'], 'like', '%' . $request->district . '%');
		}
		if ($request->filled('thana')) {
			$query->whereAny(['present_thana', 'permanent_thana'], 'like', '%' . $request->thana . '%');
		}
		if ($request->filled('gender')) {
			$query->where('gender', $request->gender);
		}
		if ($request->filled('age_from')) {
			$query->where(DB::raw('TIMESTAMPDIFF(YEAR, dob, CURDATE())'), '>=', (int)$request->age_from);
		}
		if ($request->filled('age_to')) {
			$query->where(DB::raw('TIMESTAMPDIFF(YEAR, dob, CURDATE())'), '<=', (int)$request->age_to);
		}
		if ($request->filled('last_visit_months')) {
			$date = now()->subMonths((int)$request->last_visit_months);
			$query->where(fn($q) => $q->whereDoesntHave('appointments')->orWhereHas('appointments', fn($q2) => $q2->where('date', '<', $date)));
		}
		if ($request->filled('not_called_days')) {
			$date = now()->subDays((int)$request->not_called_days);
			$query->where(fn($q) => $q->whereDoesntHave('callLogs')->orWhereHas('callLogs', fn($q2) => $q2->where('call_date', '<', $date)));
		}
		if ($request->filled('missed_followup') && $request->missed_followup === 'yes') {
			$query->whereHas('tasks', fn($q) => $q->where('task_type', 'followup_call')->where('status', 'pending')->where('due_date', '<', today()));
		}

		$limit = min((int)($request->count ?? 100), 1000);

		$patients = $query->limit($limit)->get(['id', 'name','register_id', 'phone', 'gender']);

		return response()->json([
			'success' => true,
			'patients' => $patients,
			'total' => $patients->count(),
		]);
	}

	/**
	 * Assign tasks to agents.
	 */
	public function assignTasks(Request $request): JsonResponse
	{
		$request->validate([
			'patient_ids' => 'required|array|min:1|max:1000',
			'patient_ids.*' => 'exists:users,id',
			'agent_id' => 'nullable|exists:users,id',
			'task_type' => 'required|string|in:' . implode(',', array_keys(Task::TYPES)),
			'priority' => 'required|in:high,medium,low',
			'due_date' => 'nullable|date|after_or_equal:today',
			'note' => 'nullable|string|max:1000',
			'distribute' => 'nullable|boolean',
		]);

		$agentIds = $request->distribute
			? $this->getAgents()->pluck('id')->toArray()
			: [$request->agent_id];

		if (empty($agentIds)) {
			return response()->json(['success' => false, 'message' => 'No agents available.'], 422);
		}

		$count = 0;
		$agentIndex = 0;

		foreach ($request->patient_ids as $patientId) {
			Task::create([
				'patient_id' => $patientId,
				'agent_id' => $agentIds[$agentIndex % count($agentIds)],
				'assigned_by' => Auth::id(),
				'title' => Task::TYPES[$request->task_type] ?? $request->task_type,
				'task_type' => $request->task_type,
				'call_type' => 'outgoing',
				'priority' => $request->priority,
				'status' => 'pending',
				'due_date' => $request->due_date ?? today(),
				'note' => $request->note ?? '',
			]);
			$count++;
			$agentIndex++;
		}

		return response()->json([
			'success' => true,
			'message' => "{$count} tasks assigned to " . count($agentIds) . " agent(s).",
			'count' => $count,
		]);
	}

	/**
	 * Show assign tasks view.
	 */
	public function showAssignView(Request $request): View
	{
		$query = User::query()->whereHas('roles', fn($q) => $q->where('name', 'patient'));

		if ($request->filled('district')) {
			$query->whereAny(['present_district', 'permanent_district'], 'like', '%' . $request->district . '%');
		}

		$patients = $query->limit(500)->get(['id', 'name', 'phone', 'gender', 'permanent_district', 'thana']);

		return view('callcenter.admin.assign', [
			'patients' => $patients,
			'agents' => $this->getAgents(),
		]);
	}

	/**
	 * Live monitor dashboard.
	 */
	public function monitor(): View
	{
		$todayStats = $this->getTodayStats();
		$agents = $this->attachAgentDayMetrics($this->getAgents(), $todayStats);

		return view('callcenter.admin.monitor', [
			'agents' => $agents,
			'todayStats' => $todayStats,
			'kpi' => $this->getKpiMetrics($agents, $todayStats),
		]);
	}

	/**
	 * Agent performance ranking.
	 */
	public function performance(Request $request): View
	{
		$from = $request->input('from', now()->startOfMonth()->toDateString());
		$to = $request->input('to', today()->toDateString());

		$stats = AgentDailyStat::with('agent')
			->whereBetween('stat_date', [$from, $to])
			->select(
				'agent_id',
				DB::raw('SUM(total_calls) as total_calls'),
				DB::raw('SUM(completed_tasks) as completed_tasks'),
				DB::raw('SUM(transferred_tasks) as transferred_tasks'),
				DB::raw('AVG(success_rate) as avg_success_rate')
			)
			->groupBy('agent_id')
			->orderByDesc('total_calls')
			->get();

		$stats = $this->attachRanks($stats, 'plain');

		return view('callcenter.admin.performance', compact('stats', 'from', 'to'));
	}

	// ─── Helper Methods ────────────────────────────────────────────

	private function getAgents(): \Illuminate\Database\Eloquent\Collection
	{
		return User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent', 'supervisor']))->get();
	}

	/**
	 * Attach today's stat row, a pending-task count, and a display success-rate to each agent
	 * in ONE batched query — avoids the N+1 query that used to live in the monitor/index views.
	 */
	private function attachAgentDayMetrics(\Illuminate\Database\Eloquent\Collection $agents, \Illuminate\Database\Eloquent\Collection $todayStats): \Illuminate\Database\Eloquent\Collection
	{
		$pendingCounts = Task::whereIn('agent_id', $agents->pluck('id'))
			->pending()
			->selectRaw('agent_id, count(*) as c')
			->groupBy('agent_id')
			->pluck('c', 'agent_id');

		return $agents->map(function ($agent) use ($todayStats, $pendingCounts) {
			$agent->day_stat = $todayStats->firstWhere('agent_id', $agent->id);
			$agent->pending_tasks = $pendingCounts->get($agent->id, 0);
			$agent->success_rate = $agent->day_stat?->success_rate ?? 0;
			$agent->success_rate_color = $this->successRateColor($agent->success_rate);
			return $agent;
		});
	}

	/** Bootstrap-friendly colour band for a success-rate percentage. */
	private function successRateColor(float $rate): string
	{
		return $rate >= 80 ? '#22c55e' : ($rate >= 50 ? '#fbbf24' : '#ef4444');
	}

	/** Rank CSS classes for a leaderboard position (1st/2nd/3rd get special styling). */
	private const RANK_CLASSES = [
		'prefixed' => [ // used by admin/index + admin/monitor markup
			1 => ['border' => 'border-gold',   'badge' => 'bg-gold',   'medal' => '🥇'],
			2 => ['border' => 'border-silver', 'badge' => 'bg-silver', 'medal' => '🥈'],
			3 => ['border' => 'border-bronze', 'badge' => 'bg-bronze', 'medal' => '🥉'],
		],
		'plain' => [ // used by admin/performance markup
			1 => ['border' => 'gold',   'badge' => 'gold',   'medal' => '🥇'],
			2 => ['border' => 'silver', 'badge' => 'silver', 'medal' => '🥈'],
			3 => ['border' => 'bronze', 'badge' => 'bronze', 'medal' => '🥉'],
		],
	];

	/**
	 * Attach 1-based rank + display classes to a collection already sorted best-first.
	 */
	private function attachRanks(\Illuminate\Support\Collection $sorted, string $scheme = 'prefixed'): \Illuminate\Support\Collection
	{
		$map = self::RANK_CLASSES[$scheme];
		$defaultBadge = $scheme === 'prefixed' ? 'bg-secondary' : 'default';

		return $sorted->values()->map(function ($item, $i) use ($map, $defaultBadge) {
			$rank = $i + 1;
			$style = $map[$rank] ?? ['border' => '', 'badge' => $defaultBadge, 'medal' => ''];
			$item->rank = $rank;
			$item->rank_border_class = $style['border'];
			$item->rank_badge_class = $style['badge'];
			$item->rank_medal = $style['medal'];
			$rate = $item->success_rate ?? $item->avg_success_rate ?? 0;
			$item->success_rate_color = $this->successRateColor((float) $rate);
			return $item;
		});
	}

	private function getTodayStats(): \Illuminate\Database\Eloquent\Collection
	{
		return AgentDailyStat::with('agent')->whereDate('stat_date', today())->get();
	}

	private function getKpiMetrics($agents, $todayStats): array
	{
		return [
			'total_agents' => $agents->count(),
			'online_agents' => $agents->where('is_online', true)->count(),
			'tasks_today' => Task::whereDate('created_at', today())->count(),
			'completed_today' => Task::completed()->whereDate('completed_at', today())->count(),
			'pending_tasks' => Task::pending()->count(),
			'overdue_tasks' => Task::pending()->where('due_date', '<', today())->count(),
			'avg_success_rate' => $todayStats->avg('success_rate') ?? 0,
		];
	}

	private function applyRegisterIdFilter($query, $value): void
	{
		if (empty($value)) return;

		$expression = 'CAST(SUBSTRING(register_id, 4, LOCATE("/", register_id) - 4) AS UNSIGNED)';

		if (str_contains($value, '>')) {
			$query->whereRaw("{$expression} > ?", [(int)trim(str_replace('>', '', $value))]);
		} elseif (str_contains($value, '<')) {
			$query->whereRaw("{$expression} < ?", [(int)trim(str_replace('<', '', $value))]);
		} elseif (str_contains($value, '-')) {
			[$min, $max] = array_map('trim', explode('-', $value));
			$query->whereRaw("{$expression} BETWEEN ? AND ?", [(int)$min, (int)$max]);
		} elseif (is_numeric($value)) {
			$query->whereRaw("{$expression} = ?", [(int)$value]);
		}
	}
}