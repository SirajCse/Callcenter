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
        $ccData = app(CallCenterData::class);

        $logs = SmsLog::with('patient', 'agent')
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->latest()->paginate(30);

        // ★ Stats keys blade expects: total, sent, failed, pending
        $stats = $ccData->smsStats();

        return view('callcenter.sms.index', compact('logs', 'stats'));
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
            'patient_id' => $request->patient_id, 'agent_id' => Auth::id(), 'task_id' => $request->task_id,
            'phone_number' => $request->phone_number, 'message' => $request->message,
            'template_key' => $request->template_key, 'status' => 'pending', 'sent_at' => now(),
        ]);

        // TODO: integrate real SMS gateway here
        $sms->update(['status' => 'sent']);

        return $request->ajax()
            ? response()->json(['success' => true, 'sms' => $sms, 'message' => 'SMS sent successfully.'])
            : back()->with('success', 'SMS sent successfully.');
    }

    public function resend(Request $request, $sms)
    {
        $model = SmsLog::findOrFail($sms);
        $model->increment('resend_count');
        $model->update(['status' => 'sent', 'sent_at' => now()]);

        // TODO: integrate real SMS gateway here

        return $request->ajax()
            ? response()->json(['success' => true, 'resend_count' => $model->resend_count, 'message' => 'SMS resent.'])
            : back()->with('success', 'SMS resent.');
    }

    /**
     * ★ NEW: Bulk SMS — used by followup/index.blade.php
     * POST /callcenter/sms/bulk { patient_ids, message }
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'patient_ids'   => 'required|array|min:1',
            'patient_ids.*' => 'exists:users,id',
            'message'       => 'required|string|max:500',
        ]);

        $count = 0;
        foreach ($request->patient_ids as $patientId) {
            $patient = User::find($patientId);
            if ($patient && $patient->phone) {
                SmsLog::create([
                    'patient_id' => $patientId, 'agent_id' => Auth::id(),
                    'phone_number' => $patient->phone, 'message' => $request->message,
                    'status' => 'sent', 'sent_at' => now(),
                ]);
                $count++;
            }
        }

        return $request->ajax()
            ? response()->json(['success' => true, 'count' => $count, 'message' => "$count SMS sent."])
            : back()->with('success', "$count SMS sent.");
    }
}
