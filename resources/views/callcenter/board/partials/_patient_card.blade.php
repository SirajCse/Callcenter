{{-- resources/views/callcenter/board/partials/_patient_card.blade.php (Frest Design)
    Fixed: the "Log Call" button in .pc-actions now triggers auto-dial via
    dialAndOpenLogCall() (defined in board.js), which calls dialPatient()
    first, then opens the Log Call modal. All other action buttons are
    preserved (SMS, All Notes, New Task, Letter, Deceased). --}}
@php
    $isDeceased = $patient->is_active == false || ($patient->died ?? false);
    $phoneValid = !empty($patient->phone) && $patient->phone !== 'INVALID';

    $totalCalls = \App\Models\PatientCallLog::where('patient_id', $patient->id)->count();
    $totalAppts = \App\Models\Chamber\Appointment::where('patient_id', $patient->id)->count();
    $totalLabs  = \App\Models\Lab\Group::where('patient_id', $patient->id)->count();
    $lastCall   = \App\Models\PatientCallLog::where('patient_id', $patient->id)->latest('call_date')->first();
    $lastVisit  = \App\Models\Chamber\Appointment::where('patient_id', $patient->id)->latest('date')->value('date');
@endphp

<div class="pc-hero {{ $isDeceased ? 'deceased' : '' }}">
    <div class="pc-hero-accent {{ $isDeceased ? 'danger' : '' }}"></div>

    <div class="pc-status-badge {{ $isDeceased ? 'badge-deceased' : 'badge-active' }}">
        @if($isDeceased) ⚰ DECEASED @else ✓ ACTIVE @endif
    </div>

    <div class="pc-top-row">
        <div class="pc-avatar">
            {{ strtoupper(substr($patient->name,0,1)) }}{{ strtoupper(substr(strstr($patient->name,' ') ?: '',1,1)) }}
        </div>
        <div>
            <div class="pc-name">{{ $patient->name }}</div>
            <div class="pc-pills">
                <span class="pc-pill">{{ $patient->age ?? '—' }} yrs · {{ $patient->gender ?? '—' }}</span>
                <span class="pc-pill tag">{{ $patient->register_id ?? 'ID:'.$patient->id }}</span>
                <span class="pc-pill {{ $phoneValid ? 'phone-ok' : 'phone-bad' }}">
                    <i class="fas fa-phone" style="font-size:9px"></i>
                    {{ $patient->phone ?? 'N/A' }}
                    @if(!$phoneValid) · INVALID @endif
                </span>
                @if($patient->email)
                <span class="pc-pill">{{ $patient->email }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="pc-address">
        <i class="fas fa-map-marker-alt"></i>
        {{ $patient->address ?? '—' }}
        @if($patient->present_district) · {{ $patient->present_district }} @endif
        @if($patient->present_thana) · {{ $patient->present_thana }} @endif
    </div>
</div>

<div class="pc-metrics">
    <div class="pc-metric"><div class="mn">{{ $totalCalls }}</div><div class="ml">Calls</div></div>
    <div class="pc-metric"><div class="mn">{{ $totalAppts }}</div><div class="ml">Appts</div></div>
    <div class="pc-metric"><div class="mn">{{ $totalLabs }}</div><div class="ml">Labs</div></div>
    <div class="pc-metric"><div class="mn" style="font-size:13px">{{ $lastCall?->call_date ? \Carbon\Carbon::parse($lastCall->call_date)->format('d M') : '—' }}</div><div class="ml">Last Call</div></div>
    <div class="pc-metric"><div class="mn" style="font-size:13px">{{ $lastVisit ? \Carbon\Carbon::parse($lastVisit)->format('d M') : '—' }}</div><div class="ml">Last Visit</div></div>
</div>

<div class="pc-actions">
    <button class="pac success" onclick="dialAndOpenLogCall('{{ $patient->id }}')"><i class="fas fa-phone-alt"></i> Log Call</button>
    <button class="pac primary" onclick="openSmsModal({{ $patient->id }})"><i class="fas fa-comment-alt"></i> SMS</button>
    <button class="pac secondary" onclick="openCallHistory({{ $patient->id }})"><i class="fas fa-history"></i> All Notes</button>
    <button class="pac secondary" onclick="openNewTaskForPatient({{ $patient->id }})"><i class="fas fa-tasks"></i> New Task</button>
    @if(!$phoneValid)
    <button class="pac warning" onclick="openLetterModal({{ $patient->id }})"><i class="fas fa-envelope"></i> Letter</button>
    @endif
    @if(!$isDeceased)
    <button class="pac danger" onclick="confirmDeceased({{ $patient->id }})"><i class="fas fa-skull"></i> Deceased</button>
    @endif
</div>

@if($lastCall)
<div class="pc-last-note">
    <i class="fas fa-clock"></i>
    <span class="lnote-label">Last Call:</span>
    <span class="lnote-text">"{{ Str::limit($lastCall->call_note ?? $lastCall->caller_opinion ?? 'No note', 100) }}"</span>
    <span class="fpill fp-secondary">{{ \Carbon\Carbon::parse($lastCall->call_date)->format('d M, h:i A') }}</span>
</div>
@endif

<script>
function confirmDeceased(id) {
    if (confirm('⚠ Mark this patient as DECEASED? This action is significant.')) {
        toastr.warning('Please update patient status via Edit Profile.');
    }
}
</script>
