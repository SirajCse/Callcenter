<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\Task;
use App\Models\CallCenter\AgentDailyStat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $agent = Auth::user();
        $tab   = $request->get('tab', 'pending');

        $tasks = Task::with('patient', 'agent', 'transferredTo')
            ->forAgent($agent->id)
            ->when($tab === 'pending',     fn($q) => $q->pending())
            ->when($tab === 'completed',   fn($q) => $q->completed())
            ->when($tab === 'transferred', fn($q) => $q->transferred())
            ->when($tab === 'pinned',      fn($q) => $q->pinned()->pending())
            ->when($tab === 'priority',    fn($q) => $q->highPriority()->pending())
            ->orderByRaw("FIELD(priority,'high','medium','low')")
            ->paginate(25);

        return view('callcenter.tasks.index', compact('tasks', 'tab', 'agent'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'           => 'required|exists:users,id',
            'title'                => 'required|string|max:255',
            'task_type'            => 'required|in:' . implode(',', array_keys(Task::TYPES)),
            'call_type'            => 'required|in:outgoing,incoming',
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
            'status'      => 'pending',
        ]));

        if ($request->ajax()) {
            return response()->json(['success' => true, 'task' => $task->load('patient')]);
        }

        return back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

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
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();

        return back()->with('success', 'Task deleted.');
    }

    /**
     * Mark task as completed.
     */
    public function complete(Request $request, Task $task)
    {
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
     */
    public function transfer(Request $request, Task $task)
    {
        $request->validate([
            'transferred_to'  => 'required|exists:users,id',
            'transfer_reason' => 'nullable|string|max:500',
        ]);

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
            'assigned_by'          => Auth::id(),
            'title'                => $task->title,
            'task_type'            => $task->task_type,
            'call_type'            => $task->call_type,
            'priority'             => $task->priority,
            'status'               => 'pending',
            'due_date'             => $task->due_date,
            'note'                 => $task->note . "\n[Transferred from: " . Auth::user()->name . "] " . $request->transfer_reason,
            'followup_target_note' => $task->followup_target_note,
            'followup_target_date' => $task->followup_target_date,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Task transferred.']);
        }

        return back()->with('success', 'Task transferred.');
    }

    /**
     * Toggle pin.
     */
    public function pin(Request $request, Task $task)
    {
        $task->update(['is_pinned' => !$task->is_pinned]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'pinned' => $task->is_pinned]);
        }

        return back();
    }
}
