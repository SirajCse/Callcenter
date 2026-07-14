<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\Task;
use App\Models\CallCenter\AgentDailyStat;
use App\Models\User;
use App\Services\CallCenter\CallCenterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $agent = Auth::user();
        $tab   = $request->get('tab', 'pending');
        $data  = app(CallCenterData::class);

        // ★ Stats with EXACT keys the blade view uses
        $stats = $data->taskStats($agent->id);

        // ★ Tab metadata (icon/label/count) the blade @foreach($tabs) expects
        $tabs = [
            'pending'     => ['icon' => '📋', 'label' => 'Pending',     'count' => $stats['pending']],
            'completed'   => ['icon' => '✅', 'label' => 'Completed',   'count' => $stats['completed']],
            'transferred' => ['icon' => '🔄', 'label' => 'Transferred', 'count' => $stats['transferred']],
            'pinned'      => ['icon' => '📌', 'label' => 'Pinned',      'count' => $stats['pinned']],
            'priority'    => ['icon' => '⚠️', 'label' => 'High Priority','count' => $stats['priority']],
        ];

        // ★ Transfer agents for the dropdown
        $transferAgents = $data->agents($agent->id);

        $tasks = Task::with('patient', 'agent', 'transferredTo')
            ->forAgent($agent->id)
            ->when($tab === 'pending',     fn($q) => $q->pending())
            ->when($tab === 'completed',   fn($q) => $q->completed())
            ->when($tab === 'transferred', fn($q) => $q->transferred())
            ->when($tab === 'pinned',      fn($q) => $q->pinned()->pending())
            ->when($tab === 'priority',    fn($q) => $q->highPriority()->pending())
            ->orderByRaw("FIELD(priority,'high','medium','low')")
            ->paginate(25);

        return view('callcenter.tasks.index', compact('tasks', 'tab', 'agent', 'stats', 'tabs', 'transferAgents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'           => 'required|exists:users,id',
            'title'                => 'required|string|max:255',
            'task_type'            => 'required|in:' . implode(',', array_keys(Task::TYPES)),
            'call_type'            => 'nullable|in:outgoing,incoming',
            'priority'             => 'required|in:high,medium,low',
            'due_date'             => 'nullable|date',
            'note'                 => 'nullable|string',
            'followup_target_note' => 'nullable|string',
            'followup_target_date' => 'nullable|date',
            'is_pinned'            => 'boolean',
        ]);

        $task = Task::create(array_merge($validated, [
            'agent_id'    => Auth::id(),
            'assigned_by' => Auth::id(),
            'call_type'   => $validated['call_type'] ?? 'outgoing',
            'status'      => 'pending',
            'due_date'    => $validated['due_date'] ?? today()->toDateString(),
        ]));

        if ($request->ajax()) {
            return response()->json(['success' => true, 'task' => $task->load('patient'), 'message' => 'Task created successfully.']);
        }

        return back()->with('success', 'Task created successfully.');
    }

    /**
     * ★ FIX: Use explicit $taskId + findOrFail (route-model binding was failing).
     */
    public function update(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        $validated = $request->validate([
            'title'                => 'sometimes|string|max:255',
            'task_type'            => 'sometimes|in:' . implode(',', array_keys(Task::TYPES)),
            'priority'             => 'sometimes|in:high,medium,low',
            'due_date'             => 'nullable|date',
            'note'                 => 'nullable|string',
            'followup_target_note' => 'nullable|string',
            'followup_target_date' => 'nullable|date',
            'is_pinned'            => 'boolean',
        ]);

        $task->update($validated);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Task updated.']);
        }

        return back()->with('success', 'Task updated.');
    }

    /**
     * ★ FIX: Use explicit $taskId + findOrFail.
     */
    public function destroy(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);
        $task->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Task deleted.']);
        }

        return back()->with('success', 'Task deleted.');
    }

    /**
     * Mark task as completed.
     * ★ FIX: Use explicit $taskId + findOrFail.
     */
    public function complete(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        $task->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        AgentDailyStat::recalculate(Auth::id());

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Task marked as completed.']);
        }

        return back()->with('success', 'Task completed.');
    }

    /**
     * Transfer task to another agent.
     * ★ FIX: Use explicit $taskId + findOrFail (route-model binding was failing).
     */
    public function transfer(Request $request, $taskId)
    {
        $request->validate([
            'transferred_to'  => 'required|exists:users,id',
            'transfer_reason' => 'nullable|string|max:500',
        ]);

        $agent = Auth::user();

        $task = Task::findOrFail($taskId);

        $task->update([
            'status'          => 'transferred',
            'transferred_to'  => $request->transferred_to,
            'transfer_reason' => $request->transfer_reason,
            'transferred_at'  => now(),
        ]);

        // Create a copy for the new agent
        Task::create([
            'patient_id'           => $task->patient_id,
            'agent_id'             => $request->transferred_to,
            'assigned_by'          => $agent->id,
            'title'                => $task->title,
            'task_type'            => $task->task_type,
            'call_type'            => $task->call_type,
            'priority'             => $task->priority,
            'status'               => 'pending',
            'due_date'             => $task->due_date,
            'note'                 => ($task->note ?? '') . "\n[Transferred from: {$agent->name}] " . ($request->transfer_reason ?? ''),
            'followup_target_note' => $task->followup_target_note,
            'followup_target_date' => $task->followup_target_date,
        ]);

        AgentDailyStat::recalculate($agent->id);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Task transferred successfully.']);
        }

        return back()->with('success', 'Task transferred.');
    }

    /**
     * Toggle pin.
     * ★ FIX: Use explicit $taskId + findOrFail.
     */
    public function pin(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        $task->update(['is_pinned' => !$task->is_pinned]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'pinned' => $task->is_pinned, 'message' => $task->is_pinned ? 'Task pinned.' : 'Task unpinned.']);
        }

        return back();
    }
}
