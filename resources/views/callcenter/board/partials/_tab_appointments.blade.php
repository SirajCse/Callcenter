{{-- _tab_appointments.blade.php (Frest Design) --}}
@forelse($appointments ?? [] as $appt)
<div class="tl-item {{ ($appt->status ?? '') === 'Scheduled' ? 'warning' : '' }}">
    <div class="tl-top" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px;gap:8px">
        <div class="tl-title">{{ $appt->type ?? $appt->category ?? '—' }}</div>
        <span class="fpill {{ ($appt->status ?? '') === 'Completed' ? 'fp-success' : 'fp-warning' }}">{{ $appt->status ?? '—' }}</span>
    </div>
    <div class="tl-sub"><i class="fas fa-user-md" style="margin-right:4px"></i>{{ $appt->doctor?->name ?? '—' }}</div>
    <div class="tl-meta"><i class="far fa-calendar"></i> {{ \Carbon\Carbon::parse($appt->date)->format('d M Y') }} &nbsp; #{{ $appt->appointment_number ?? '' }}</div>
</div>
@empty
<div style="text-align:center;padding:40px 20px;color:var(--cc-text-light)">
    <i class="far fa-calendar" style="font-size:30px;margin-bottom:10px;opacity:.3;display:block"></i>
    <span style="font-size:13px">No appointments found</span>
</div>
@endforelse
