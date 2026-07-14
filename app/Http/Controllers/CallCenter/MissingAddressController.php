<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\MissingAddress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MissingAddressController extends Controller
{
    public function index()
    {
        $records = MissingAddress::with('patient')->latest()->paginate(30);

        return view('callcenter.missing_address.index', compact('records'));
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
            return response()->json(['success' => true, 'record' => $record->load('patient'), 'message' => 'Added to missing address list.']);
        }

        return back()->with('success', 'Added to missing address list.');
    }

    /**
     * ★ FIX: Use explicit $missingAddressId + findOrFail (route-model binding was failing).
     */
    public function update(Request $request, $missingAddressId)
    {
        $missingAddress = MissingAddress::findOrFail($missingAddressId);

        $request->validate([
            'status'           => 'nullable|in:pending,awaiting,delivered,updated',
            'letter_sent'      => 'boolean',
            'letter_sent_date' => 'nullable|date',
            'note'             => 'nullable|string',
        ]);

        $missingAddress->update($request->only(['status', 'letter_sent', 'letter_sent_date', 'note']));

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Record updated.']);
        }

        return back()->with('success', 'Record updated.');
    }
}
