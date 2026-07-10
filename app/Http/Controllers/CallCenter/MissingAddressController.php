<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\MissingAddress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MissingAddressController extends Controller
{
    /** Maps a record status to its Blade pill CSS class. Keeps the view free of lookup logic. */
    public const STATUS_PILL_CLASSES = [
        'updated'  => 'fp-success',
        'delivered'=> 'fp-info',
        'awaiting' => 'fp-warning',
        'pending'  => 'fp-secondary',
    ];

    public function index()
    {
        $records = MissingAddress::with('patient')->latest()->paginate(30);

        $stats = [
            'total'    => MissingAddress::count(),
            'pending'  => MissingAddress::where('status', 'pending')->count(),
            'awaiting' => MissingAddress::where('status', 'awaiting')->count(),
            'resolved' => MissingAddress::whereIn('status', ['updated', 'delivered'])->count(),
        ];

        return view('callcenter.missing_address.index', [
            'records' => $records,
            'stats' => $stats,
            'statusPillClasses' => self::STATUS_PILL_CLASSES,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id|unique:missing_addresses,patient_id',
            'note'       => 'nullable|string',
        ]);

        $record = MissingAddress::create([
            'patient_id' => $request->patient_id,
            'note'       => $request->note,
            'status'     => 'pending',
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'record' => $record->load('patient')]);
        }

        return back()->with('success', 'Added to missing address list.');
    }

    public function update(Request $request, MissingAddress $missingAddress)
    {
        $request->validate([
            'status'           => 'nullable|in:pending,awaiting,delivered,updated',
            'letter_sent'      => 'boolean',
            'letter_sent_date' => 'nullable|date',
            'note'             => 'nullable|string',
        ]);

        $missingAddress->update($request->only(['status', 'letter_sent', 'letter_sent_date', 'note']));

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Record updated.');
    }
}
