<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\LetterLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LetterLogController extends Controller
{
    public function index(Request $request)
    {
        $letters = LetterLog::with('patient', 'agent')
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()->paginate(30);

        return view('callcenter.letters.index', compact('letters'));
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
        ]), [
            'agent_id' => Auth::id(),
            'status'   => 'queued',
        ]));

        if ($request->ajax()) {
            return response()->json(['success' => true, 'letter' => $letter]);
        }

        return back()->with('success', 'Letter queued for print.');
    }
}
