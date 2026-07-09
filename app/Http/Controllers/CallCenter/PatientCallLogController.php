<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\PatientCallLog;
use App\Models\CallCenter\Task;
use App\Models\CallCenter\SmsLog;
use App\Models\CallCenter\LetterLog;
use App\Models\CallCenter\AgentDailyStat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientCallLogController extends Controller
{
    /**
     * Show all call logs for current agent.
     */
    public function index(Request $request)
    {
        $agent = Auth::user();

        $logs = PatientCallLog::with('patient', 'caller', 'transfer')
            ->where('call_by', $agent->id)
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->when($request->from, fn($q, $v) => $q->whereDate('call_date', '>=', $v))
            ->when($request->to,   fn($q, $v) => $q->whereDate('call_date', '<=', $v))
            ->latest('call_date')
            ->paginate(25);

        return view('callcenter.calllogs.index', compact('logs', 'agent'));
    }

    /**
     * Store a new call log (and optionally complete/create task).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'           => 'required|exists:users,id',
            'task_id'              => 'nullable|exists:tasks,id',
            'method'               => 'required|in:outgoing,incoming',
            'type'                 => 'required|string',
            'contact_info'         => 'nullable|string',
            'call_date'            => 'required|date',
            'call_note'            => 'nullable|string',
            'duration'             => 'nullable|integer',
            'caller_opinion'       => 'nullable|string',
            'patient_opinion'      => 'nullable|string',
            'receive'              => 'boolean',
            'die'                  => 'boolean',
            'come_back'            => 'nullable|string',
            'transfer_to'          => 'nullable|exists:users,id',
            'transfer_cause'       => 'nullable|string',
            'transfer_opinion'     => 'nullable|string',
            'priority'             => 'in:high,medium,low',
            'followup_target_date' => 'nullable|date',
            'followup_target_note' => 'nullable|string',
            'sms_sent'             => 'boolean',
            'letter_sent'          => 'boolean',
            'is_deceased_call'     => 'boolean',
            'appointment_id'       => 'nullable|exists:appointments,id',
        ]);

        DB::beginTransaction();
        try {
            $log = PatientCallLog::create(array_merge($validated, [
                'call_by'    => Auth::id(),
                'call_count' => PatientCallLog::where('patient_id', $validated['patient_id'])->count() + 1,
            ]));

            // Mark patient deceased if flagged
            if (!empty($validated['die'])) {
                User::where('id', $validated['patient_id'])->update(['is_active' => false]);
            }

            // Complete linked task
            if (!empty($validated['task_id']) && !empty($validated['receive'])) {
                Task::where('id', $validated['task_id'])->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);
            }

            // Auto-create follow-up task if date set
            if (!empty($validated['followup_target_date'])) {
                Task::create([
                    'patient_id'           => $validated['patient_id'],
                    'agent_id'             => Auth::id(),
                    'assigned_by'          => Auth::id(),
                    'title'                => 'Follow-up: ' . (Auth::user()->name ?? ''),
                    'task_type'            => 'followup_call',
                    'call_type'            => 'outgoing',
                    'priority'             => $validated['priority'] ?? 'medium',
                    'status'               => 'pending',
                    'due_date'             => $validated['followup_target_date'],
                    'note'                 => $validated['followup_target_note'] ?? '',
                    'followup_target_note' => $validated['followup_target_note'] ?? '',
                    'followup_target_date' => $validated['followup_target_date'],
                ]);
            }

            // Log SMS if flagged
            if (!empty($validated['sms_sent'])) {
                SmsLog::create([
                    'patient_id'  => $validated['patient_id'],
                    'agent_id'    => Auth::id(),
                    'task_id'     => $validated['task_id'] ?? null,
                    'call_log_id' => $log->id,
                    'phone_number'=> $validated['contact_info'] ?? '',
                    'message'     => SmsLog::TEMPLATES['missed'],
                    'status'      => 'pending',
                ]);
            }

            // Log Letter if flagged
            if (!empty($validated['letter_sent'])) {
                $patient = User::find($validated['patient_id']);
                LetterLog::create([
                    'patient_id'      => $validated['patient_id'],
                    'agent_id'        => Auth::id(),
                    'task_id'         => $validated['task_id'] ?? null,
                    'call_log_id'     => $log->id,
                    'delivery_address'=> $patient->address ?? '',
                    'reason'          => 'invalid_phone',
                    'status'          => 'pending',
                ]);
            }

            AgentDailyStat::recalculate(Auth::id());

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'log' => $log->load('caller')]);
            }

            return back()->with('success', 'Call logged successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Full call history for a patient (modal).
     */
    public function history(Request $request, $patientId)
    {
        $patient = User::withTrashed()->findOrFail($patientId);

        $logs = PatientCallLog::with('caller', 'transfer', 'appointment')
            ->where('patient_id', $patientId)
            ->latest('call_date')
            ->get();

        // Which agents called this patient
        $otherAgentCalls = PatientCallLog::with('caller')
            ->where('patient_id', $patientId)
            ->where('call_by', '!=', Auth::id())
            ->latest('call_date')
            ->take(5)
            ->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('callcenter.calllogs.history_modal', compact('patient', 'logs', 'otherAgentCalls'))->render(),
            ]);
        }

        return view('callcenter.calllogs.history_modal', compact('patient', 'logs', 'otherAgentCalls'));
    }
}
