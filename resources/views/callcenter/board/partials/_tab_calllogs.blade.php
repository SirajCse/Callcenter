{{-- _tab_calllogs.blade.php (Frest Design) --}}
@forelse($callLogs ?? [] as $log)
<div class="tl-item {{ in_array($log->caller_opinion,['failed','no_answer','dead']) ? 'critical' : '' }}">
    <div class="tl-top" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px;gap:8px">
        <div class="tl-title">{{ \Carbon\Carbon::parse($log->call_date)->format('d M Y, h:i A') }}</div>
        <div style="display:flex;gap:4px">
            <span class="fpill {{ $log->method === 'incoming' ? 'fp-success' : 'fp-primary' }}">{{ ucfirst($log->method ?? 'outgoing') }}</span>
            @if($log->receive)<span class="fpill fp-success">Answered</span>@else<span class="fpill fp-danger">No Answer</span>@endif
        </div>
    </div>
    <div class="tl-sub">{{ $log->call_note ?? $log->caller_opinion ?? '—' }}</div>
    <div class="tl-meta">
        <span><i class="fas fa-user"></i> {{ $log->caller?->name ?? '—' }}</span>
        <span>⏱ {{ gmdate('i:s', $log->duration ?? 0) }}</span>
        @if($log->transfer_to)<span style="color:var(--cc-warning)">→ {{ $log->transfer?->name }}</span>@endif
    </div>
</div>
@empty
<div style="text-align:center;padding:40px 20px;color:var(--cc-text-light)">
    <i class="fas fa-phone-slash" style="font-size:30px;margin-bottom:10px;opacity:.3;display:block"></i>
    <span style="font-size:13px">No call history</span>
</div>
@endforelse
@if(!empty($callLogs) && count($callLogs))
<button class="btn btn-sm btn-outline-primary w-100 mt-2" onclick="openCallHistory(currentPatientId)">
    <i class="fas fa-history mr-1"></i> View Full Call History
</button>
@endif
