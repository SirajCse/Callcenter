<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\SmsLog;
use App\Models\User;
use App\Services\CallCenter\CallCenterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = SmsLog::with('patient', 'agent')
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->latest()
            ->paginate(30);

        // ★ FIX: Pass $stats and $agents that the blade view expects
        $common = app(CallCenterData::class)->getCommonData();

        return view('callcenter.sms.index', array_merge(compact('logs'), $common));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'   => 'required|exists:users,id',
            'phone_number' => 'required|string|max:30',
            'message'      => 'required|string|max:500',
            'task_id'      => 'nullable|exists:tasks,id',
            'template_key' => 'nullable|string',
        ]);

        $sms = SmsLog::create([
            'patient_id'   => $request->patient_id,
            'agent_id'     => Auth::id(),
            'task_id'      => $request->task_id,
            'phone_number' => $request->phone_number,
            'message'      => $request->message,
            'template_key' => $request->template_key,
            'status'       => 'pending',
            'sent_at'      => now(),
        ]);

        // TODO: integrate real SMS gateway here
        $sms->update(['status' => 'sent']);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'sms' => $sms, 'message' => 'SMS sent successfully.']);
        }

        return back()->with('success', 'SMS sent successfully.');
    }

    /**
     * ★ FIX: Use explicit $smsId + findOrFail (route-model binding was failing).
     */
    public function resend(Request $request, $smsId)
    {
        $sms = SmsLog::findOrFail($smsId);

        $sms->increment('resend_count');
        $sms->update(['status' => 'sent', 'sent_at' => now()]);

        // TODO: integrate real SMS gateway here

        if ($request->ajax()) {
            return response()->json(['success' => true, 'resend_count' => $sms->resend_count, 'message' => 'SMS resent.']);
        }

        return back()->with('success', 'SMS resent.');
    }
}
