<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\Task;
use App\Models\PatientCallLog;
use App\Models\User;
use App\Services\CallCenter\CallCenterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgentCallController extends Controller
{
    /**
     * Show PBX call logs for the current agent (from mikopbx_call_logs).
     *
     * Matching logic:
     *   - mikopbx_call_logs.extension = users.pbx_extension  → identifies agent
     *   - mikopbx_call_logs.callee (outbound) / caller (inbound) = users.phone/phone2 → identifies patient
     *   - tasks.due_date = call date → links to day's task
     */
    public function index(Request $request)
    {
        $agent  = Auth::user();
        $ccData = app(CallCenterData::class);
        $ext    = $agent->pbx_extension;

        // If agent has no PBX extension, show empty state
        if (!$ext) {
            $stats = ['total' => 0, 'answered' => 0, 'missed' => 0, 'today' => 0, 'outbound' => 0, 'inbound' => 0];
            return view('callcenter.agent_calls.index', compact('agent', 'stats', 'calls'))
                ->with('error', 'No PBX extension assigned to your account. Contact admin.');
        }

        // ── Build query on mikopbx_call_logs (via the package's CallLog model) ──
        $query = $this->callLogModel()::where('extension', $ext);

        // Filters
        if ($request->filled('from')) {
            $query->whereDate('started_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('started_at', '<=', $request->to);
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('phone')) {
            $phone = $request->phone;
            $query->where(function ($q) use ($phone) {
                $q->where('caller', 'like', "%{$phone}%")
                  ->orWhere('callee', 'like', "%{$phone}%");
            });
        }

        $calls = $query->latest('started_at')->paginate(50)->withQueryString();

        // ── Match each call to a patient + task ──
        $patientCache = [];
        $taskCache = [];

        foreach ($calls as $call) {
            // For outbound: the callee is the patient's phone
            // For inbound: the caller is the patient's phone
            $phone = ($call->direction === 'inbound')
                ? $call->caller
                : $call->callee;

            // Clean the phone (remove +, spaces, etc.)
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

            // Try to match patient by phone or phone2
            $call->matched_patient = null;
            $call->linked_task = null;

            if ($cleanPhone && strlen($cleanPhone) >= 6) {
                // Check cache first
                if (isset($patientCache[$cleanPhone])) {
                    $call->matched_patient = $patientCache[$cleanPhone];
                } else {
                    $patient = User::where('phone', $phone)
                        ->orWhere('phone2', $phone)
                        ->orWhere('phone', $cleanPhone)
                        ->orWhere('phone2', $cleanPhone)
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', '') = ?", [$cleanPhone])
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone2, '+', ''), ' ', ''), '-', '') = ?", [$cleanPhone])
                        ->first();

                    $patientCache[$cleanPhone] = $patient;
                    $call->matched_patient = $patient;
                }

                // If patient found, try to match a task for the call date
                if ($call->matched_patient) {
                    $callDate = substr($call->started_at, 0, 10); // YYYY-MM-DD
                    $taskKey = $call->matched_patient->id . '_' . $callDate;

                    if (isset($taskCache[$taskKey])) {
                        $call->linked_task = $taskCache[$taskKey];
                    } else {
                        $task = Task::where('patient_id', $call->matched_patient->id)
                            ->where('agent_id', $agent->id)
                            ->whereDate('due_date', $callDate)
                            ->first();

                        $taskCache[$taskKey] = $task;
                        $call->linked_task = $task;
                    }
                }
            }
        }

        // ── Stats for the topbar ──
        $baseQuery = $this->callLogModel()::where('extension', $ext);
        $stats = [
            'total'    => (clone $baseQuery)->count(),
            'answered' => (clone $baseQuery)->where('status', 'answered')->count(),
            'missed'   => (clone $baseQuery)->whereIn('status', ['missed', 'failed'])->count(),
            'ended'    => (clone $baseQuery)->where('status', 'ended')->count(),
            'today'    => (clone $baseQuery)->whereDate('started_at', today())->count(),
            'outbound' => (clone $baseQuery)->where('direction', 'outbound')->count(),
            'inbound'  => (clone $baseQuery)->where('direction', 'inbound')->count(),
        ];

        return view('callcenter.agent_calls.index', compact('agent', 'calls', 'stats'));
    }

    /**
     * Get the MikoPBX CallLog model class (if the package is installed).
     */
    private function callLogModel()
    {
        if (class_exists(\BitDreamIT\MikoPBX\Models\CallLog::class)) {
            return \BitDreamIT\MikoPBX\Models\CallLog::class;
        }

        // Fallback: use a raw query builder if the package model isn't available
        return new class {
            public static function __callStatic($method, $args)
            {
                $table = config('mikopbx.table_prefix', 'mikopbx_') . 'call_logs';
                $query = DB::table($table);

                // Convert Eloquent-style where to query-builder
                if ($method === 'where') {
                    return $query->where(...$args);
                }
                if ($method === 'whereDate') {
                    return $query->whereDate(...$args);
                }
                if ($method === 'whereIn') {
                    return $query->whereIn(...$args);
                }
                if ($method === 'latest') {
                    return $query->orderBy(...($args ?: ['started_at']));
                }

                return $query->$method(...$args);
            }
        };
    }
}
