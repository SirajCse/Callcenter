<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\SmsLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsLogController extends Controller
{
    /** Maps a log status to its Blade pill CSS class. Keeps the view free of lookup logic. */
    public const STATUS_PILL_CLASSES = [
        'delivered' => 'fp-success',
        'failed'    => 'fp-danger',
        'sent'      => 'fp-primary',
        'pending'   => 'fp-warning',
    ];

    public function index(Request $request)
    {
        $logs = SmsLog::with('patient', 'agent')
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'total'  => SmsLog::count(),
            'sent'   => SmsLog::where('status', 'sent')->count(),
            'failed' => SmsLog::where('status', 'failed')->count(),
            'pending'=> SmsLog::where('status', 'pending')->count(),
        ];

        return view('callcenter.sms.index', [
            'logs' => $logs,
            'stats' => $stats,
            'statusPillClasses' => self::STATUS_PILL_CLASSES,
        ]);
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
            return response()->json(['success' => true, 'sms' => $sms]);
        }

        return back()->with('success', 'SMS sent successfully.');
    }

    public function resend(Request $request, SmsLog $sms)
    {
        $sms->increment('resend_count');
        $sms->update(['status' => 'sent', 'sent_at' => now()]);

        // TODO: integrate real SMS gateway here

        if ($request->ajax()) {
            return response()->json(['success' => true, 'resend_count' => $sms->resend_count]);
        }

        return back()->with('success', 'SMS resent.');
    }

    /**
     * Send the same templated message to a batch of patients (used by the
     * Follow-up list's "Bulk SMS" action).
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'patient_ids'   => 'required|array|min:1|max:200',
            'patient_ids.*' => 'exists:users,id',
            'template_key'  => 'required|string|in:' . implode(',', array_keys(SmsLog::TEMPLATES)),
        ]);

        $patients = User::whereIn('id', $request->patient_ids)->get(['id', 'phone']);
        $message  = SmsLog::TEMPLATES[$request->template_key];
        $sent     = 0;
        $skipped  = 0;

        foreach ($patients as $patient) {
            if (empty($patient->phone) || $patient->phone === 'INVALID') {
                $skipped++;
                continue;
            }

            SmsLog::create([
                'patient_id'   => $patient->id,
                'agent_id'     => Auth::id(),
                'phone_number' => $patient->phone,
                'message'      => $message,
                'template_key' => $request->template_key,
                'status'       => 'sent', // TODO: integrate real SMS gateway; treat as queued until then
                'sent_at'      => now(),
            ]);
            $sent++;
        }

        return response()->json([
            'success' => true,
            'sent'    => $sent,
            'skipped' => $skipped,
            'message' => "{$sent} SMS sent." . ($skipped ? " {$skipped} skipped (no valid phone)." : ''),
        ]);
    }
}
