<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\LetterLog;
use App\Services\CallCenter\CallCenterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LetterLogController extends Controller
{
    public function index(Request $request)
    {
        $ccData = app(CallCenterData::class);

        $letters = LetterLog::with('patient', 'agent')
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()->paginate(30);

        // ★ Stats keys blade expects: total, sent, queued, printed
        $stats = $ccData->letterStats();

        return view('callcenter.letters.index', compact('letters', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'       => 'required|exists:users,id',
            'delivery_address' => 'required|string',
            'reason'           => 'required|in:' . implode(',', array_keys(LetterLog::REASONS)),
            'content'          => 'nullable|string',
            'internal_note'    => 'nullable|string',
            'task_id'          => 'nullable|exists:tasks,id',
        ]);

        $letter = LetterLog::create(array_merge($request->only([
            'patient_id', 'delivery_address', 'reason', 'content', 'internal_note', 'task_id',
        ]), ['agent_id' => Auth::id(), 'status' => 'queued']));

        return $request->ajax()
            ? response()->json(['success' => true, 'letter' => $letter, 'message' => 'Letter queued for print.'])
            : back()->with('success', 'Letter queued for print.');
    }
}
