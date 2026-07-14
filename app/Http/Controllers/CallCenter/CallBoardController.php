<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\Task;
use App\Models\CallCenter\AgentDailyStat;
use App\Models\PatientCallLog;
use App\Models\User;
use App\Models\Chamber\Appointment;
use App\Models\Lab\Group;
use App\Models\Lab\Therapy;
use App\Models\Lab\Nebulize;
use App\Models\Lab\Sale;
use App\Services\CallCenter\DialService;
use App\Services\CallCenter\CallCenterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallBoardController extends Controller
{
    /**
     * Main call board – loads current agent's pending tasks + first patient.
     */
    public function index(Request $request)
    {
        $agent   = Auth::user();
        $agentId = $agent?->id ?? 0;

        // ── Patient from URL ?pid= or first pending task ──────────
        $patient = null;
        $pid     = $request->get('pid');

        if ($pid) {
            $patient = User::find($pid);
        } else {
            $firstTask = Task::forAgent($agentId)->pending()
                ->orderByRaw("FIELD(priority,'high','medium','low')")
                ->first();
            if ($firstTask) {
                $patient = $firstTask->patient;
            } else {
                $patient = User::whereHas('roles', fn($q) => $q->where('name', 'patient'))
                    ->where('died', 0)
                    ->first();
            }
        }

        // ── Common data (stats, agents) ────────────────────────────
        $ccData = app(CallCenterData::class);
        $stats  = $ccData->boardStats($agentId);
        $agents = $ccData->agents($agentId);

        // ── Task tabs ──────────────────────────────────────────────
        $tasks = [
            'pending'     => $agentId ? Task::with('patient')->forAgent($agentId)->pending()
                                ->orderByRaw("FIELD(priority,'high','medium','low')")->get() : collect(),
            'completed'   => $agentId ? Task::with('patient')->forAgent($agentId)->completed()
                                ->whereDate('completed_at', today())->latest('completed_at')->get() : collect(),
            'transferred' => $agentId ? Task::with('patient','transferredTo')->forAgent($agentId)->transferred()
                                ->latest('transferred_at')->get() : collect(),
            'pinned'      => $agentId ? Task::with('patient')->forAgent($agentId)->pinned()->pending()->get() : collect(),
            'priority'    => $agentId ? Task::with('patient')->forAgent($agentId)->pending()->highPriority()->get() : collect(),
        ];

        // ── Patient tab data + patient stats (moved from blade) ────
        $appointments = collect();
        $callLogs     = collect();
        $labGroups    = collect();
        $therapies    = collect();
        $nebulizes    = collect();
        $vaccinations = collect();
        $patientStats = ['totalCalls' => 0, 'totalAppts' => 0, 'totalLabs' => 0, 'lastCall' => null, 'lastVisit' => null];

        if ($patient) {
            $appointments = Appointment::where('patient_id', $patient->id)->latest('date')->take(10)->get();
            $callLogs     = PatientCallLog::where('patient_id', $patient->id)->with('caller')->latest('call_date')->take(20)->get();
            $labGroups    = Group::where('patient_id', $patient->id)->with('items')->latest()->take(10)->get();
            $therapies    = Therapy::where('patient_id', $patient->id)->latest('date')->take(10)->get();
            $nebulizes    = Nebulize::where('patient_id', $patient->id)->latest('date')->take(10)->get();
            $vaccinations = Sale::where('patient_id', $patient->id)->latest('date')->take(10)->get();
            $patientStats = app(CallCenterData::class)->getPatientStats($patient->id);
        }

        return view('callcenter.board.index', compact(
            'agent', 'patient', 'stats', 'tasks', 'appointments', 'callLogs',
            'labGroups', 'therapies', 'nebulizes', 'vaccinations', 'agents', 'patientStats'
        ));
    }

    /**
     * Load patient data (AJAX) – called when switching patient in board.
     */
    public function patient(Request $request, $id)
    {
        $patient = User::withTrashed()->findOrFail($id);

        $data = [
            'patient'      => $patient,
            'appointments' => Appointment::where('patient_id', $id)->latest('date')->take(10)->get(),
            'callLogs'     => PatientCallLog::where('patient_id', $id)->with('caller')->latest('call_date')->take(20)->get(),
            'labGroups'    => Group::where('patient_id', $id)->with('items')->latest()->take(10)->get(),
            'therapies'    => Therapy::where('patient_id', $id)->latest('date')->take(10)->get(),
            'nebulizes'    => Nebulize::where('patient_id', $id)->latest('date')->take(10)->get(),
            'vaccinations' => Sale::where('patient_id', $id)->latest('date')->take(10)->get(),
            'patientStats' => app(CallCenterData::class)->getPatientStats($id),
        ];

        if ($request->ajax()) {
            return response()->json([
                'card'  => view('callcenter.board.partials._patient_card', $data)->render(),
                'tabs'  => view('callcenter.board.partials._tabs', $data)->render(),
            ]);
        }

        $ccData = app(CallCenterData::class);
        $stats  = $ccData->boardStats(Auth::id());
        $agents = $ccData->agents(Auth::id());

        $tasks = [
            'pending'     => Task::with('patient')->forAgent(Auth::id())->pending()->orderByRaw("FIELD(priority,'high','medium','low')")->get(),
            'completed'   => Task::with('patient')->forAgent(Auth::id())->completed()->whereDate('completed_at', today())->latest('completed_at')->get(),
            'transferred' => Task::with('patient','transferredTo')->forAgent(Auth::id())->transferred()->latest('transferred_at')->get(),
            'pinned'      => Task::with('patient')->forAgent(Auth::id())->pinned()->pending()->get(),
            'priority'    => Task::with('patient')->forAgent(Auth::id())->pending()->highPriority()->get(),
        ];

        return view('callcenter.board.index', array_merge($data, compact('stats', 'agents', 'tasks')));
    }

    /**
     * ★ NEW: Auto-dial a patient via MikoPBX (AMI Originate).
     */
    public function dialPatient(Request $request, DialService $dialer)
    {
        $patientId = $request->input('patient_id');
        $taskId    = $request->input('task_id');

        if (! $patientId) {
            return response()->json(['success' => false, 'message' => 'No patient ID provided.'], 422);
        }

        $patient = User::withTrashed()->find($patientId);
        if (! $patient) {
            return response()->json(['success' => false, 'message' => "Patient not found (ID: {$patientId})."], 422);
        }

        if ($taskId) {
            $task = Task::find($taskId);
            if (! $task) {
                return response()->json(['success' => false, 'message' => "Task not found (ID: {$taskId})."], 422);
            }
        }

        $result = $dialer->dialPatient($patient, $taskId ?: null);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * My last calls list.
     */
    public function myCalls()
    {
        $agent = Auth::user();
        $data  = app(CallCenterData::class);

        $logs = PatientCallLog::with('patient')
            ->where('call_by', $agent->id)
            ->latest('call_date')
            ->paginate(30);

        // ★ Stats with EXACT keys the blade view uses
        $stats = $data->callLogStats($agent->id);

        return view('callcenter.calllogs.index', compact('logs', 'agent', 'stats'));
    }

    /**
     * My profile + stats.
     */
    public function myStats()
    {
        $agent      = Auth::user();
        $ccData     = app(CallCenterData::class);
        $todayStat  = AgentDailyStat::where('agent_id', $agent->id)->whereDate('stat_date', today())->first();
        $monthStats = AgentDailyStat::where('agent_id', $agent->id)
            ->whereMonth('stat_date', now()->month)->get();
        $tasks = Task::with('patient')->forAgent($agent->id)->pending()->get();

        $stats = $ccData->boardStats($agent->id);

        return view('callcenter.board.my_stats', compact('agent', 'todayStat', 'monthStats', 'tasks', 'stats'));
    }
}
