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

        // ── Load patient tab data ──────────────────────────────────
        $appointments = collect();
        $callLogs     = collect();
        $labGroups    = collect();
        $therapies    = collect();
        $nebulizes    = collect();
        $vaccinations = collect();

        if ($patient) {
            $appointments = Appointment::where('patient_id', $patient->id)->latest('date')->take(10)->get();
            $callLogs     = PatientCallLog::where('patient_id', $patient->id)->with('caller')->latest('call_date')->take(20)->get();
            $labGroups    = Group::where('patient_id', $patient->id)->with('items')->latest()->take(10)->get();
            $therapies    = Therapy::where('patient_id', $patient->id)->latest('date')->take(10)->get();
            $nebulizes    = Nebulize::where('patient_id', $patient->id)->latest('date')->take(10)->get();
            $vaccinations = Sale::where('patient_id', $patient->id)->latest('date')->take(10)->get();
        }

        // ── Today stats ────────────────────────────────────────────
        $stats = [
            'completed'   => $agentId ? Task::forAgent($agentId)->completed()->whereDate('completed_at', today())->count() : 0,
            'pending'     => $agentId ? Task::forAgent($agentId)->pending()->count() : 0,
            'transferred' => $agentId ? Task::forAgent($agentId)->transferred()->whereDate('transferred_at', today())->count() : 0,
            'followup'    => $agentId ? Task::forAgent($agentId)->pending()->where('task_type', 'followup_call')->count() : 0,
            'missed'      => $agentId ? PatientCallLog::where('call_by', $agentId)->whereIn('caller_opinion', ['no_answer','busy','out_of_reach','wrong_number'])->count() : 0,
        ];

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

        // ── Agents for transfer dropdown ───────────────────────────
        $agents = User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent', 'supervisor']))
            ->where('id', '!=', $agentId)->get(['id', 'name']);

        return view('callcenter.board.index', compact(
            'agent', 'patient', 'stats', 'tasks', 'appointments', 'callLogs',
            'labGroups', 'therapies', 'nebulizes', 'vaccinations', 'agents'
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
        ];

        if ($request->ajax()) {
            return response()->json([
                'card'  => view('callcenter.board.partials._patient_card', $data)->render(),
                'tabs'  => view('callcenter.board.partials._tabs', $data)->render(),
            ]);
        }

        return view('callcenter.board.index', array_merge($data, [
            'agent'  => Auth::user(),
            'agents' => User::whereHas('roles', fn($q) => $q->whereIn('name', ['agent','supervisor']))->get(['id','name']),
        ]));
    }

    /**
     * ★ NEW: Auto-dial a patient via MikoPBX (AMI Originate).
     *
     * POST /callcenter/dial  { patient_id, task_id? }
     */
    public function dialPatient(Request $request, DialService $dialer)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'task_id'    => 'nullable|exists:tasks,id',
        ]);

        $patient = User::findOrFail($request->patient_id);
        $result  = $dialer->dialPatient($patient, $request->task_id);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * My last calls list.
     */
    public function myCalls()
    {
        $agent = Auth::user();
        $logs = PatientCallLog::with('patient')
            ->where('call_by', $agent->id)
            ->latest('call_date')
            ->paginate(30);

        return view('callcenter.calllogs.index', compact('logs', 'agent'));
    }

    /**
     * My profile + stats.
     */
    public function myStats()
    {
        $agent      = Auth::user();
        $todayStat  = AgentDailyStat::where('agent_id', $agent->id)->whereDate('stat_date', today())->first();
        $monthStats = AgentDailyStat::where('agent_id', $agent->id)
            ->whereMonth('stat_date', now()->month)->get();
        $tasks = Task::with('patient')->forAgent($agent->id)->pending()->get();

        return view('callcenter.board.my_stats', compact('agent', 'todayStat', 'monthStats', 'tasks'));
    }
}
