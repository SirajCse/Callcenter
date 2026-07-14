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
        $agent  = Auth::user();
        $tab    = $request->get('tab', 'pending');
        $ccData = app(CallCenterData::class);

        // ★ Stats with EXACT keys blade uses: pending, completed, transferred, pinned, priority, overdue
        $stats = $ccData->taskStats($agent->id);

        // ★ $tabs array (icon/label/count) for the tab nav
        $tabs = [
            'pending'     => ['icon' => '📋', 'label' => 'Pending',       'count' => $stats['pending']],
            'completed'   => ['icon' => '✅', 'label' => 'Completed',     'count' => $stats['completed']],
            'transferred' => ['icon' => '🔄', 'label' => 'Transferred',   'count' => $stats['transferred']],
            'pinned'      => ['icon' => '📌', 'label' => 'Pinned',        'count' => $stats['pinned']],
            'priority'    => ['icon' => '⚠️', 'label' => 'High Priority', 'count' => $stats['priority']],
        ];

        // ★ $transferAgents for the transfer dropdown
        $transferAgents = $ccData->agents($agent->id);

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

    public function update(Request $request, $task)
    {
        $model = Task::findOrFail($task);
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
        $model->update($validated);
        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'Task updated.'])
            : back()->with('success', 'Task updated.');
    }

    public function destroy(Request $request, $task)
    {
        $model = Task::findOrFail($task);
        $model->delete();
        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'Task deleted.'])
            : back()->with('success', 'Task deleted.');
    }

    public function complete(Request $request, $task)
    {
        $model = Task::findOrFail($task);
        $model->update(['status' => 'completed', 'completed_at' => now()]);
        AgentDailyStat::recalculate(Auth::id());
        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'Task marked as completed.'])
            : back()->with('success', 'Task completed.');
    }

    public function transfer(Request $request, $task)
    {
        $request->validate([
            'transferred_to'  => 'required|exists:users,id',
            'transfer_reason' => 'nullable|string|max:500',
        ]);
        $agent = Auth::user();
        $model = Task::findOrFail($task);

        $model->update([
            'status'          => 'transferred',
            'transferred_to'  => $request->transferred_to,
            'transfer_reason' => $request->transfer_reason,
            'transferred_at'  => now(),
        ]);

        Task::create([
            'patient_id'           => $model->patient_id,
            'agent_id'             => $request->transferred_to,
            'assigned_by'          => $agent->id,
            'title'                => $model->title,
            'task_type'            => $model->task_type,
            'call_type'            => $model->call_type,
            'priority'             => $model->priority,
            'status'               => 'pending',
            'due_date'             => $model->due_date,
            'note'                 => ($model->note ?? '') . "\n[Transferred from: {$agent->name}] " . ($request->transfer_reason ?? ''),
            'followup_target_note' => $model->followup_target_note,
            'followup_target_date' => $model->followup_target_date,
        ]);

        AgentDailyStat::recalculate($agent->id);
        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'Task transferred successfully.'])
            : back()->with('success', 'Task transferred.');
    }

    public function pin(Request $request, $task)
    {
        $model = Task::findOrFail($task);
        $model->update(['is_pinned' => !$model->is_pinned]);
        return $request->ajax()
            ? response()->json(['success' => true, 'pinned' => $model->is_pinned, 'message' => $model->is_pinned ? 'Task pinned.' : 'Task unpinned.'])
            : back();
    }
}
