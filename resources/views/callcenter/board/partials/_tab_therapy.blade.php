{{-- _tab_therapy.blade.php (Frest Design) --}}
@forelse($therapies ?? [] as $t)
<div class="tl-item">
    <div class="tl-top" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px;gap:8px">
        <div class="tl-title">Invoice: {{ $t->invoice_id ?? '—' }}</div>
        <span class="fpill fp-primary">Therapy</span>
    </div>
    @foreach($t->services->take(3) as $s)
    <div class="tl-sub">{{ $s->service?->name ?? '—' }}</div>
    @endforeach
    <div class="tl-meta"><i class="far fa-calendar"></i> {{ \Carbon\Carbon::parse($t->date)->format('d M Y') }} &nbsp; Total: {{ number_format($t->total,2) }}</div>
</div>
@empty
<div style="text-align:center;padding:40px 20px;color:var(--cc-text-light)">
    <i class="fas fa-heartbeat" style="font-size:30px;margin-bottom:10px;opacity:.3;display:block"></i>
    <span style="font-size:13px">No therapy records</span>
</div>
@endforelse
