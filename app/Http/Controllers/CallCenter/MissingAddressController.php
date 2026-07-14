<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\MissingAddress;
use App\Services\CallCenter\CallCenterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MissingAddressController extends Controller
{
    public function index()
    {
        $ccData = app(CallCenterData::class);

        $records = MissingAddress::with('patient')->latest()->paginate(30);

        // ★ Stats keys blade expects: total, pending, awaiting, resolved
        $stats = $ccData->missingAddressStats();

        return view('callcenter.missing_address.index', compact('records', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id|unique:missing_addresses,patient_id',
            'note'       => 'nullable|string',
        ]);

        $record = MissingAddress::create([
            'patient_id' => $request->patient_id, 'note' => $request->note, 'status' => 'pending',
        ]);

        return $request->ajax()
            ? response()->json(['success' => true, 'record' => $record->load('patient'), 'message' => 'Added to missing address list.'])
            : back()->with('success', 'Added to missing address list.');
    }

    public function update(Request $request, $missingAddress)
    {
        $model = MissingAddress::findOrFail($missingAddress);
        $request->validate([
            'status'           => 'nullable|in:pending,awaiting,delivered,updated',
            'letter_sent'      => 'boolean',
            'letter_sent_date' => 'nullable|date',
            'note'             => 'nullable|string',
        ]);
        $model->update($request->only(['status', 'letter_sent', 'letter_sent_date', 'note']));
        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'Record updated.'])
            : back()->with('success', 'Record updated.');
    }
}
